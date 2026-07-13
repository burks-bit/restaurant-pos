<?php

namespace App\Services;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Reservation;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\PaymentMethod;
use App\Models\PricingScheme;
use App\Models\HeadPricingRule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Shift;
use App\Models\User;
use App\Models\Employee;
use App\Models\EmployeeSchedule;

class ReservationService
{
    public function index(?string $startDate = null, ?string $endDate = null)
    {
        try {
            $cashierEmployeeIds = Employee::where('access', 2)->pluck('id')->toArray();

            // cashiers on duty still default to "today" if no range is set
            $cashierDate = $startDate ?: now()->format('Y-m-d');

            $cashiersOnDuty = Employee::whereIn('id', $cashierEmployeeIds)
                ->whereHas('schedules', function ($query) use ($cashierDate) {
                    $query->whereDate('schedule_date', $cashierDate);
                })
                ->with(['schedules' => function ($query) use ($cashierDate) {
                    $query->whereDate('schedule_date', $cashierDate);
                }])
                ->get();

            $mapped = $cashiersOnDuty->map(function ($employee) {
                $schedule = $employee->schedules->first();

                $shiftLabel = $schedule
                    ? Carbon::parse($schedule->time_in)->format('g:iA') . '-' . Carbon::parse($schedule->time_out)->format('g:iA')
                    : 'No Shift';

                return [
                    'employee_id' => $employee->id,
                    'name'        => $employee->first_name . ' ' . $employee->last_name,
                ];
            });

            $reservationsQuery = Reservation::with([
                    'reservationPax.headPricingRule',
                    'pricingScheme',
                ])
                ->orderBy('created_at', 'desc');

            // apply range filter only if at least one bound was actually provided
            if (!empty($startDate) && !empty($endDate)) {
                $reservationsQuery->whereDate('created_at', '>=', $startDate)
                                ->whereDate('created_at', '<=', $endDate);
            } elseif (!empty($startDate)) {
                $reservationsQuery->whereDate('created_at', '>=', $startDate);
            } elseif (!empty($endDate)) {
                $reservationsQuery->whereDate('created_at', '<=', $endDate);
            }
            // both empty → no filter, show all

            $reservations = $reservationsQuery->get();

            return Inertia::render('Reservations/Index', [
                'reservations'    => $reservations,
                'pricing_schemes' => PricingScheme::where('is_active', true)->get(),
                'pricing_rules'   => HeadPricingRule::where('is_active', true)->get(),
                'shifts'          => Shift::all(),
                'cashiersOnDuty'  => $mapped,
                'filters'         => ['start_date' => $startDate, 'end_date' => $endDate],
            ]);
        } catch (\Throwable $e) {
            Log::error('ReservationService@index failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to load reservations.');
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                   => 'required|string|max:191',
            'pricing_scheme_id'      => 'required|exists:pricing_schemes,id',
            'reservation_datetime'   => 'required|date',
            'contact_number'         => 'nullable|string|max:191',
            'remarks'                => 'nullable|string',
            'status'                 => 'in:pending,confirmed,cancelled',
            'reservation_fee'        => 'nullable|numeric|min:0',
            'fee_payment_method'     => 'nullable|string|max:191',
            'fee_reference_no'       => 'nullable|string|max:191',
            'pax_breakdown'          => 'required|array|min:1',
            'pax_breakdown.*.head_pricing_rule_id' => 'required|exists:head_pricing_rules,id',
            'pax_breakdown.*.qty'            => 'required|integer|min:0',
            'pax_breakdown.*.price_snapshot' => 'required|numeric|min:0',
            'pax_breakdown.*.subtotal'       => 'required|numeric|min:0',
            'pax'                    => 'required|integer|min:1',
            'shift_id'               => 'required|exists:shifts,id',
            'cashier_employee_id'    => 'nullable|exists:employees,id',
        ]);

        DB::transaction(function () use ($data) {
            // --- 1. Save Reservation ---
            $reservation = Reservation::create([
                'name'                 => $data['name'],
                'pricing_scheme_id'    => $data['pricing_scheme_id'],
                'pax'                  => $data['pax'],
                'reservation_datetime' => $data['reservation_datetime'],
                'contact_number'       => $data['contact_number'],
                'remarks'              => $data['remarks'] ?? null,
                'status'               => $data['status'] ?? 'pending',
                'reservation_fee'      => $data['reservation_fee'] ?? 0,
                'fee_payment_method'   => $data['fee_payment_method'] ?? null,
                'fee_reference_no'     => $data['fee_reference_no'] ?? null,
                'shift_id'             => $data['shift_id'] ?? null,
            ]);

            foreach ($data['pax_breakdown'] as $row) {
                if ($row['qty'] > 0) {
                    $reservation->reservationPax()->create([
                        'head_pricing_rule_id' => $row['head_pricing_rule_id'],
                        'qty'                  => $row['qty'],
                        'price_snapshot'       => $row['price_snapshot'],
                        'subtotal'             => $row['subtotal'],
                    ]);
                }
            }

            // --- 2. Build Order No ---
            // Format: RSVP-YYYYMMDD-{SluggedName}-{4-digit sequence}
            // e.g.  RSVP-20250512-JuanDeLaCruz-0002
            $today      = now()->format('Ymd');
            $nameSlug   = strtoupper(preg_replace('/\s+/', '', $data['name'])); // strip spaces
            $prefix     = "RSVP-{$today}-{$nameSlug}-";

            $lastOrder  = Order::where('order_no', 'like', $prefix . '%')
                            ->orderByDesc('order_no')
                            ->lockForUpdate()          // prevent race condition inside the transaction
                            ->first();

            $nextSeq    = $lastOrder
                            ? ((int) substr($lastOrder->order_no, -4)) + 1
                            : 1;

            $orderNo    = $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

            // --- 3. Compute totals from pax_breakdown ---
            $subtotal = collect($data['pax_breakdown'])->sum('subtotal');

            $now = Carbon::now();
            $time = $now->format('H:i:s');

            $current_shift = Shift::where(function ($q) use ($time) {

                // 🔥 Normal shift (08:00 - 17:00)
                $q->where(function ($q2) use ($time) {
                    $q2->whereRaw('start_time <= end_time')
                        ->where('start_time', '<=', $time)
                        ->where('end_time', '>=', $time);
                })

                // 🔥 Overnight shift (22:00 - 06:00)
                ->orWhere(function ($q2) use ($time) {
                    $q2->whereRaw('start_time > end_time')
                        ->where(function ($q3) use ($time) {
                            $q3->where('start_time', '<=', $time)
                                ->orWhere('end_time', '>=', $time);
                        });
                });

            })->first();

            // --- 4. Create Order ---
            $order = Order::create([
                'user_id'          => $data['cashier_employee_id'],
                'order_no'         => $orderNo,
                'subtotal'         => $data['reservation_fee'],
                'total_discount'   => 0,
                'total'            => $data['reservation_fee'],
                'discount_type'    => null,
                'status'           => 'paid', // mark as paid since the reservation fee is collected upfront
                'cancelled'        => false,
                'cancelled_by'     => null,
                'cancellation_remarks'           => null,
                'voucher_no_used'                => null,
                'voucher_discount_used'          => 0,
                'payment_method'                 => $data['fee_payment_method'] ?? null,
                'cash_amount'                    => $data['reservation_fee'] ?? 0,
                'change_amount'                  => 0,
                'table_number'                   => null,
                'discount_approving_manager_id'  => null,
                'cancel_approving_manager_id'    => null,
                'shift_id'                       => $data['shift_id'] ?? null,
                'reservation_fee_used'           => $data['reservation_fee'] ?? 0,
                // tie back to the reservation if your orders table has this column:
                'reservation_id'              => $reservation->id,
            ]);

            // --- 5. Create Order Payment and sales ledger entry (only when a reservation fee was collected) ---
            $reservationFee = (float) ($data['reservation_fee'] ?? 0);
            $paymentMethod = PaymentMethod::where('code', 'reservation_fee')->first();

            if ($reservationFee > 0) {
                $createdOP = OrderPayment::create([
                    'order_id'         => $order->id,
                    'payment_method_id' => $paymentMethod?->id,
                    'amount'           => $reservationFee,
                    'amount_tendered'  => $reservationFee,
                    'reference_no'     => ($paymentMethod && $paymentMethod->requires_reference)
                                            ? (trim((string) ($data['fee_reference_no'] ?? '')) ?: null)
                                            : null,
                    'remarks'          => null,
                    'received_by'      => auth()->id(),
                    'is_void'          => false,
                ]);
            }
        });

        return redirect()->back()->with('success', 'Reservation created.');
    }
 
    public function update(Request $request, Reservation $reservation)
    {
        $data = $request->validate([
            'name'                   => 'required|string|max:191',
            'pricing_scheme_id'      => 'required|exists:pricing_schemes,id',
            'reservation_datetime'   => 'required|date',
            'contact_number'         => 'nullable|string|max:191',
            'remarks'                => 'nullable|string',
            'status'                 => 'in:pending,confirmed,cancelled',
            'reservation_fee'        => 'nullable|numeric|min:0',
            'fee_payment_method'     => 'nullable|string|max:191',
            'fee_reference_no'       => 'nullable|string|max:191',
            'pax_breakdown'          => 'required|array|min:1',
            'pax_breakdown.*.head_pricing_rule_id' => 'required|exists:head_pricing_rules,id',
            'pax_breakdown.*.qty'            => 'required|integer|min:0',
            'pax_breakdown.*.price_snapshot' => 'required|numeric|min:0',
            'pax_breakdown.*.subtotal'       => 'required|numeric|min:0',
            'pax'                    => 'required|integer|min:1',
        ]);
 
        DB::transaction(function () use ($data, $reservation) {
            $reservation->update([
                'name'                 => $data['name'],
                'pricing_scheme_id'    => $data['pricing_scheme_id'],
                'pax'                  => $data['pax'],
                'reservation_datetime' => $data['reservation_datetime'],
                'contact_number'       => $data['contact_number'],
                'remarks'              => $data['remarks'] ?? null,
                'status'               => $data['status'],
                'reservation_fee'      => $data['reservation_fee'] ?? 0,
                'fee_payment_method'   => $data['fee_payment_method'] ?? null,
                'fee_reference_no'     => $data['fee_reference_no'] ?? null,
            ]);
 
            $reservation->reservationPax()->delete();
 
            foreach ($data['pax_breakdown'] as $row) {
                if ($row['qty'] > 0) {
                    $reservation->reservationPax()->create([
                        'head_pricing_rule_id' => $row['head_pricing_rule_id'],
                        'qty'                  => $row['qty'],
                        'price_snapshot'       => $row['price_snapshot'],
                        'subtotal'             => $row['subtotal'],
                    ]);
                }
            }
        });
 
        return redirect()->back()->with('success', 'Reservation updated.');
    }

    public function destroy(Reservation $reservation)
    {
        try {
            DB::transaction(function () use ($reservation) {
                // ── Log the reservation details found ────────────────────────────
                Log::info('ReservationController@destroy: Reservation found', [
                    'reservation_id'   => $reservation->id,
                    'reservation_no'   => $reservation->reservation_no ?? null,
                    'customer_name'    => $reservation->customer_name ?? null,
                    'pax'              => $reservation->pax ?? null,
                    'status'           => $reservation->status ?? null,
                    'reservation_date' => $reservation->reservation_date ?? null,
                ]);

                // Find all orders tied to this reservation (deposit order + any dine-in
                // settlement orders created when the table was assigned)
                $orders = Order::where('reservation_id', $reservation->id)->get();

                Log::info('ReservationController@destroy: Orders found for reservation', [
                    'reservation_id' => $reservation->id,
                    'order_count'    => $orders->count(),
                    'order_ids'      => $orders->pluck('id')->toArray(),
                    'order_numbers'  => $orders->pluck('order_no')->toArray(),
                ]);

                if ($orders->isNotEmpty()) {
                    $orderIds = $orders->pluck('id');

                    // ── Log the order_payments found before deleting ─────────────
                    $payments = DB::table('order_payments')
                        ->whereIn('order_id', $orderIds)
                        ->get();

                    Log::info('ReservationController@destroy: Order payments found', [
                        'reservation_id' => $reservation->id,
                        'payment_count'  => $payments->count(),
                        'payment_ids'    => $payments->pluck('id')->toArray(),
                        'payments_detail' => $payments->map(fn($p) => [
                            'id'               => $p->id,
                            'order_id'         => $p->order_id,
                            'payment_method_id' => $p->payment_method_id,
                            'amount'           => $p->amount,
                            'remarks'          => $p->remarks,
                        ])->toArray(),
                    ]);

                    // Delete child payment rows first (FK constraint safety)
                    $deletedPaymentsCount = DB::table('order_payments')
                        ->whereIn('order_id', $orderIds)
                        ->delete();

                    Log::info('ReservationController@destroy: Order payments deleted', [
                        'reservation_id'        => $reservation->id,
                        'deleted_payments_count' => $deletedPaymentsCount,
                    ]);

                    // Delete the orders themselves
                    $deletedOrdersCount = Order::whereIn('id', $orderIds)->delete();

                    Log::info('ReservationController@destroy: Orders deleted', [
                        'reservation_id'      => $reservation->id,
                        'deleted_orders_count' => $deletedOrdersCount,
                        'deleted_order_ids'   => $orderIds->toArray(),
                    ]);
                } else {
                    Log::info('ReservationController@destroy: No orders found for reservation, skipping payment/order deletion', [
                        'reservation_id' => $reservation->id,
                    ]);
                }

                $reservation->delete();

                Log::info('ReservationController@destroy: Reservation deleted', [
                    'reservation_id' => $reservation->id,
                ]);
            });

            return redirect()->back()->with('success', 'Reservation and related orders deleted.');
        } catch (\Throwable $e) {
            Log::error('ReservationController@destroy failed: ' . $e->getMessage(), [
                'reservation_id' => $reservation->id ?? null,
                'trace'          => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Failed to delete reservation.');
        }
    }

    // public function destroy(Reservation $reservation)
    // {
    //     $reservation->delete();
    //     return redirect()->back()->with('success', 'Reservation deleted.');
    // }

    
}