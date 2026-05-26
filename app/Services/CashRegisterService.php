<?php

namespace App\Services;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Shift;
use App\Models\CashRegister;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Expense;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CashRegisterService
{
    public function index()
    {
        $currentDateTime = Carbon::now();
        $currentTime     = $currentDateTime->format('H:i:s');

        $user      = Auth::user();
        $isCashier = $user->role == 2;

        $shifts = Shift::all();

        // ── Determine current shift ───────────────────────────────────────────
        $currentShift = $shifts->first(function ($shift) use ($currentTime) {
            $start = $shift->start_time;
            $end   = $shift->end_time;

            if ($start < $end) {
                return $currentTime >= $start && $currentTime < $end;
            }

            // Overnight shift
            return $currentTime >= $start || $currentTime < $end;
        });

        $currentShiftId = $currentShift?->id;

        // ── Determine shift window ────────────────────────────────────────────
        if ($currentShift) {
            $startTime = $currentShift->start_time;
            $endTime   = $currentShift->end_time;

            $isOvernightShift = $startTime >= $endTime;

            if (!$isOvernightShift) {
                $shiftStart = Carbon::today()->setTimeFromTimeString($startTime);
                $shiftEnd   = Carbon::today()->setTimeFromTimeString($endTime);
            } else {
                if ($currentTime >= $startTime) {
                    $shiftStart = Carbon::today()->setTimeFromTimeString($startTime);
                    $shiftEnd   = Carbon::tomorrow()->setTimeFromTimeString($endTime);
                } else {
                    $shiftStart = Carbon::yesterday()->setTimeFromTimeString($startTime);
                    $shiftEnd   = Carbon::today()->setTimeFromTimeString($endTime);
                }
            }
        } else {
            $shiftStart = Carbon::today()->startOfDay();
            $shiftEnd   = Carbon::today()->endOfDay();
        }

        $shiftStartStr = $shiftStart->toDateTimeString();
        $shiftEndStr   = $shiftEnd->toDateTimeString();

        // ── Expenses ──────────────────────────────────────────────────────────
        $cashier_expenses = Expense::query()
            ->whereDate('expense_date', $shiftStart->toDateString())
            ->whereBetween('created_at', [$shiftStart, $shiftEnd])
            ->when($currentShiftId, fn($q) => $q->where('shift_id', $currentShiftId))
            ->when($isCashier, fn($q) => $q->where('created_by', $user->id))
            ->sum('amount');

        // ── Dynamic payment summary (all payment methods) ─────────────────────
        $paymentSummary = DB::table('orders as ord')
            ->join('order_payments as ordp', 'ordp.order_id', '=', 'ord.id')
            ->join('payment_methods as pm', 'pm.id', '=', 'ordp.payment_method_id')
            ->whereBetween('ord.created_at', [$shiftStartStr, $shiftEndStr])
            ->where('ordp.is_void', 0)
            ->where('ord.status', 'paid')
            ->when($currentShiftId, fn($q) => $q->where('ord.shift_id', $currentShiftId))
            ->when($isCashier, fn($q) => $q->where('ord.user_id', $user->id))
            ->groupBy('pm.id', 'pm.name')
            ->select('pm.id', 'pm.name', DB::raw('SUM(ordp.amount) as total'))
            ->get()
            ->map(fn($row) => [
                'id'    => $row->id,
                'name'  => $row->name,
                'total' => (float) $row->total,
            ])
            ->values()
            ->toArray();

        // ── Reservation fee (pm id=13) merged into GCash (pm id=2) ───────────
        $reservationFee = (float) DB::table('orders as ord')
            ->join('order_payments as ordp', 'ordp.order_id', '=', 'ord.id')
            ->join('payment_methods as pm', 'pm.id', '=', 'ordp.payment_method_id')
            ->whereBetween('ord.created_at', [$shiftStartStr, $shiftEndStr])
            ->where('pm.id', 13)
            ->where('ordp.is_void', 0)
            ->where('ord.status', 'paid')
            ->when($currentShiftId, fn($q) => $q->where('ord.shift_id', $currentShiftId))
            ->when($isCashier, fn($q) => $q->where('ord.user_id', $user->id))
            ->sum('ordp.amount');

        if ($reservationFee > 0) {
            $gcashIndex = array_search(2, array_column($paymentSummary, 'id'));
            if ($gcashIndex !== false) {
                $paymentSummary[$gcashIndex]['total'] += $reservationFee;
            }
            // If GCash (id=2) isn't in the summary yet but reservation fee exists,
            // it is already included via the groupBy query above as pm.id=13.
            // No further action needed — it shows as its own row.
        }

        // ── Backward-compat totals (used by expectedCashOnHand in composable) ─
        $totalCashSales  = collect($paymentSummary)->firstWhere('id', 1)['total'] ?? 0;
        $totalGcashSales = collect($paymentSummary)->firstWhere('id', 2)['total'] ?? 0;

        // ── Cash registers ────────────────────────────────────────────────────
        $registers = CashRegister::with(['cashier', 'shift'])
            ->whereBetween('created_at', [$shiftStartStr, $shiftEndStr])
            ->when($currentShiftId, fn($q) => $q->where('shift_id', $currentShiftId))
            ->when($isCashier, fn($q) => $q->where('cashier_id', $user->id))
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($cr) {
                $cr->denomination = $cr->denomination
                    ? json_decode($cr->denomination, true)
                    : [];
                $cr->over_short = $cr->cash_on_hand - $cr->net_sales;
                return $cr;
            });

        // ── Orders (for count) ────────────────────────────────────────────────
        $ordersCount = Order::whereBetween('created_at', [$shiftStartStr, $shiftEndStr])
            ->where('status', 'paid')
            ->when($currentShiftId, fn($q) => $q->where('shift_id', $currentShiftId))
            ->when($isCashier, fn($q) => $q->where('user_id', $user->id))
            ->count();

        return Inertia::render('CashRegister/Index', [
            'cashRegisters'  => $registers,
            'shifts'         => $shifts,
            'currentShift'   => $currentShift,
            'shiftStart'     => $shiftStartStr,
            'shiftEnd'       => $shiftEndStr,
            'paymentSummary' => $paymentSummary,   // ← dynamic, all payment methods
            'totalCashSales' => $totalCashSales,   // ← kept for any legacy use
            'totalGcashSales'=> $totalGcashSales,  // ← kept for any legacy use
            'cashierExpenses'=> $cashier_expenses,
            'ordersCount'    => $ordersCount,
        ]);
    }

    public function store(Request $request)
    {

        $request->validate([
            'shift_id'             => 'required|exists:shifts,id',
            'cash_on_hand'         => 'required|numeric|min:0',
            'denomination'         => 'nullable|array',
            'denomination.*.denom' => 'required|numeric|min:1',
            'denomination.*.qty'   => 'required|integer|min:1',
            'notes'                => 'nullable|string|max:500',
        ]);

        $cashierId = Auth::id();
        $date      = now()->toDateString();
        $shiftId   = $request->shift_id;

        // ── Pull sales figures from order_payments ────────────────────────────
        $cashSales = OrderPayment::whereHas('order', function ($q) use ($shiftId, $date) {
                $q->where('shift_id', $shiftId)
                  ->whereDate('created_at', $date)
                  ->where('status', 'paid');
            })
            ->whereHas('paymentMethod', fn($q) => $q->where('id', 1))
            ->where('is_void', false)
            ->sum('amount');

        $gcashSales = OrderPayment::whereHas('order', function ($q) use ($shiftId, $date) {
                $q->where('shift_id', $shiftId)
                  ->whereDate('created_at', $date)
                  ->where('status', 'paid');
            })
            ->whereHas('paymentMethod', fn($q) => $q->where('id', 2))
            ->where('is_void', false)
            ->sum('amount');

        $totalExpenses = Expense::where('shift_id', $shiftId)
            ->whereDate('created_at', $date)
            ->sum('amount');

        $expectedCashOnHand = max(($cashSales + $gcashSales) - $totalExpenses, 0);
        $cashOnHand         = (float) $request->cash_on_hand;
        $overShort          = $cashOnHand - $expectedCashOnHand;

        $register = CashRegister::create([
            'cashier_id'            => $cashierId,
            'shift_id'              => $shiftId,
            'date'                  => $date,
            'cash_sales'            => $cashSales,
            'gcash_sales'           => $gcashSales,
            'other_sales'           => null,
            'total_expenses'        => $totalExpenses,
            'expected_cash_on_hand' => $expectedCashOnHand,
            'cash_on_hand'          => $cashOnHand,
            'over_short'            => $overShort,
            'denomination'          => $request->denomination
                                        ? json_encode($request->denomination)
                                        : null,
            'notes'                 => $request->notes,
        ]);

        return redirect()->back()->with('success', 'Cash register posted successfully.');
    }

    public function update(Request $request, $id)
    {

        $request->validate([
            'shift_id'             => 'required|exists:shifts,id',
            'cash_on_hand'         => 'required|numeric|min:0',
            'notes'                => 'nullable|string',
            'denomination'         => 'nullable|array',
            'denomination.*.denom' => 'required|numeric|min:1',
            'denomination.*.qty'   => 'required|integer|min:1',
        ]);

        $register = CashRegister::findOrFail($id);

        // Recompute over_short against the saved expected_cash_on_hand
        $cashOnHand = (float) $request->cash_on_hand;
        $overShort  = $cashOnHand - (float) $register->expected_cash_on_hand;

        $register->update([
            'shift_id'     => $request->shift_id,
            'cash_on_hand' => $cashOnHand,
            'over_short'   => $overShort,
            'notes'        => $request->notes,
            'denomination' => $request->denomination
                ? json_encode($request->denomination)
                : null,
        ]);

        return redirect()->back()->with('success', 'Cash register updated successfully.');
    }

    public function fetchCashRegistered(Request $request)
    {
        $date = $request->input('date', now()->toDateString());

        $registers = CashRegister::with(['cashier', 'shift'])
            ->where('cashier_id', Auth::id())
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($cr) {
                $cr->denomination = $cr->denomination
                    ? json_decode($cr->denomination, true)
                    : [];
                $cr->over_short = $cr->cash_on_hand - $cr->net_sales;
                return $cr;
            });

        return response()->json([
            'cashRegisters' => $registers,
        ]);
    }
}