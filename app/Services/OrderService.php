<?php

namespace App\Services;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Order;
use App\Models\Shift;
use App\Models\OrderItem;
use App\Models\OrderAddon;
use App\Models\OrderLeftover;
use App\Models\PaymentMethod;
use App\Models\OrderPayment;
use App\Models\Voucher;
use App\Models\TableSession;
use App\Models\TableSessionAddon;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Branch;
use App\Models\OrderHead;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class OrderService
{
    public function index()
    {
        $today = now()->toDateString();

        $isCashier = auth()->id() && auth()->user()->role === 2;

        $query = Order::with([
                'user',
                'items.menu',
                'tableSession.table',
                'orderHeads.headPricingRule',
                'tableSession.headCounts.headRule',
                'addons',
                'leftover',
                'payments.paymentMethod',
            ])
            ->whereDate('created_at', $today)
            ->orderBy('created_at', 'desc');
        if ($isCashier) {
            $query->where('user_id', auth()->id());
        }
        $orders = $query->get();

        $total_sales_per_cashier = Order::query()
            ->whereDate('created_at', $today)
            ->where('status', 'paid')
            ->where('user_id', auth()->id())
            ->sum('total');
        
        $total_sales_all_cashier = Order::query()
            ->whereDate('created_at', $today)
            ->where('status', 'paid')
            ->whereHas('user', function ($query) {
                $query->where('role', 2);
            })
            ->sum('total');

        $unbilled_sessions = TableSession::with([
                'headCounts.headRule'
            ])
            ->whereDate('created_at', $today)
            ->where('status', 'open')
            ->whereNull('order_id')
            ->get();

        $total_unbilled = $unbilled_sessions->sum(function ($session) {
            return collect($session->headCounts ?? [])->sum(function ($head) {
                return ($head->subtotal ?? 0);
            });
        });

        $payment_methods = PaymentMethod::where('is_active', 1)->get();

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'payment_methods' => $payment_methods,
            'selectedDate' => $today,
            'managers' => User::where('role', 1)->get(),
            'total_sales_per_cashier' => (float)$total_sales_per_cashier,
            'total_sales_all_cashier' => (float)$total_sales_all_cashier,
            'total_unbilled' => (float)$total_unbilled,
            'isCashier' => $isCashier
        ]);
    }

    public function getFilteredOrders(Request $request)
    {
        $date = $request->date;
        $status = $request->status;
        $search = $request->search;

        $today = now()->toDateString();

        $query = Order::with([
            'user',
            'items.menu',
            'tableSession.table',
            'orderHeads.headPricingRule',
            'tableSession.headCounts.headRule'
        ]);

        if (!$date && !$status && !$search) {
            $query->whereDate('created_at', $today);
        } else {
            if ($date) {
                $query->whereDate('created_at', $date);
            }

            if ($status) {
                $query->where('status', $status);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('order_no', 'like', "%{$search}%")
                        ->orWhere('total', 'like', "%{$search}%");
                });
            }
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'orders' => $orders
        ]);
    }

    public function store(Request $request)
    {

        $request->validate([
            'table_session_id' => 'required|exists:table_sessions,id',
            'heads' => 'required|array|min:1',

            'payments' => 'required|array|min:1',
            'payments.*.payment_method_id' => 'required|exists:payment_methods,id',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.reference_no' => 'nullable|string|max:255',
            'payments.*.remarks' => 'nullable|string|max:255',

            'leftovers' => 'nullable|array',
            'leftovers.*.weight' => 'nullable',
            'leftovers.*.price' => 'nullable|numeric|min:0',
            'leftovers.*.remarks' => 'nullable|string|max:255',

            'total_discount' => 'nullable|numeric|min:0',
            'voucher_code' => 'nullable|string|max:255',
            'voucher_discount' => 'nullable',
            'approving_manager' => 'nullable',
            'reservation_fee_used' => 'nullable|numeric|min:0',
        ]);

        $createdOrder = DB::transaction(function () use ($request) {
            $tableSession = TableSession::with([
                    'headCounts.headRule',
                    'addons' => function ($query) {
                        $query->where('is_billed', false)
                            ->where('is_void', 0);
                    },
                ])
                ->lockForUpdate()
                ->findOrFail($request->table_session_id);

            if ($tableSession->status !== 'open') {
                throw new \Exception('Table session already closed.');
            }

            $baseSubtotal = (float) $tableSession->headCounts()->sum('subtotal');

            $addonsTotal = (float) $tableSession->addons()
                ->where('is_billed', false)
                ->where('is_void', 0)
                ->sum('subtotal');

            $leftoversTotal = (float) ($request->leftovers_total ?? 0);

            $subtotal = $baseSubtotal + $addonsTotal + $leftoversTotal;

            // $totalDiscount = (float) ($request->total_discount ?? 0);
            // $total = max($subtotal - $totalDiscount, 0);
            $totalDiscount    = (float) ($request->total_discount ?? 0);
            $reservationFee   = (float) ($request->reservation_fee_used ?? 0);
            $total            = max($subtotal - $totalDiscount - $reservationFee, 0);

            $payments = collect($request->payments ?? []);

            if ($payments->isEmpty()) {
                throw new \Exception('At least one payment method is required.');
            }

            $paymentMethodIds = $payments->pluck('payment_method_id')->filter()->unique()->values();

            $paymentMethods = PaymentMethod::whereIn('id', $paymentMethodIds)
                ->where('is_active', true)
                ->get()
                ->keyBy('id');

            $totalPaid = 0;
            $totalCashPaid = 0;
            $totalCashTendered = 0;

            foreach ($payments as $payment) {
                $methodId = (int) ($payment['payment_method_id'] ?? 0);
                $amountTendered = (float) ($payment['amount'] ?? 0); // raw input from frontend
                $referenceNo = trim((string) ($payment['reference_no'] ?? ''));

                $method = $paymentMethods->get($methodId);

                if (!$method) {
                    throw new \Exception('Invalid or inactive payment method selected.');
                }

                if ($amountTendered <= 0) {
                    throw new \Exception("Payment amount for {$method->name} must be greater than 0.");
                }

                if ((bool) $method->requires_reference && $referenceNo === '') {
                    throw new \Exception("Reference number is required for {$method->name}.");
                }

                $totalPaid += $amountTendered;

                if ((bool) $method->is_cash) {
                    $totalCashTendered += $amountTendered;
                }
            }

            if ($totalPaid < $total) {
                throw new \Exception('Total payment is insufficient.');
            }

            $excess = $totalPaid - $total;
            $changeAmount = $excess > 0 ? min($excess, $totalCashTendered) : 0;

            $discountType = null;

            if (!empty($request->voucher_code) && !empty($request->voucher_discount) && $totalDiscount > 0) {
                $discountType = 'Voucher';
            } elseif ($totalDiscount > 0 && empty($request->voucher_code) && empty($request->voucher_discount)) {
                $discountType = 'SC/PWD';
            }

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

            $order = Order::create([
                'user_id' => auth()->id(),
                'table_session_id' => $tableSession->id,
                'subtotal' => $subtotal,
                'total_discount' => $totalDiscount,
                'total' => $total,
                'reservation_fee_used' => $request->reservation_fee_used ?? 0,

                'shift_id' => $current_shift ? $current_shift->id : null,

                // legacy columns - optional to keep temporarily
                'payment_method' => null,
                'cash_amount' => $totalCashPaid > 0 ? $totalCashPaid : null,

                'change_amount' => $changeAmount,
                'discount_type' => $discountType,
                'status' => 'paid',
                'table_number' => $request->table_session_id,
                'voucher_no_used' => $request->voucher_code,
                'voucher_discount_used' => $request->voucher_discount !== null
                    ? (float) $request->voucher_discount
                    : 0,
                'discount_approving_manager_id' => !empty($request->approving_manager)
                    ? (is_array($request->approving_manager)
                        ? ($request->approving_manager['id'] ?? null)
                        : $request->approving_manager)
                    : null,
                'reservation_id' => $request->reservation_id ?? null,
            ]);

            $order->update([
                'order_no' => now()->format('Ymd') . '-' . str_pad($order->id, 7, '0', STR_PAD_LEFT),
            ]);

            $remainingTotal = $total;

            foreach ($payments as $payment) {
                $methodId = (int) $payment['payment_method_id'];
                $method = $paymentMethods->get($methodId);
                $amountTendered = (float) $payment['amount'];

                // The amount actually applied to the bill for this payment method
                // For a single payment, this is simply min(tendered, remaining)
                $amountApplied = min($amountTendered, $remainingTotal);
                $remainingTotal -= $amountApplied;

                OrderPayment::create([
                    'order_id' => $order->id,
                    'payment_method_id' => $methodId,
                    'amount'            => $amountApplied,          // ✅ actual amount credited (e.g. 5535)
                    'amount_tendered'   => (bool) $method->is_cash  // ✅ cash handed over (e.g. 6000)
                                                ? $amountTendered
                                                : null,
                    'reference_no' => $method && $method->requires_reference
                        ? (trim((string) ($payment['reference_no'] ?? '')) ?: null)
                        : null,
                    'remarks' => trim((string) ($payment['remarks'] ?? '')) ?: null,
                    'received_by' => auth()->id(),
                    'is_void' => false,
                ]);
            }

            if (($request->reservation_fee_used ?? 0) > 0) {
                $reservationFeeMethod = PaymentMethod::where('code', 'reservation_fee')->first();

                if ($reservationFeeMethod) {
                    OrderPayment::create([
                        'order_id'          => $order->id,
                        'payment_method_id' => $reservationFeeMethod->id,
                        'amount'            => $request->reservation_fee_used,
                        'amount_tendered'   => $request->reservation_fee_used,
                        'remarks'           => 'Reservation fee applied',
                        'received_by'       => auth()->id(),
                    ]);
                }
            }

            $voucher = null;
            $voucherCode = $request->voucher_code;

            if ($voucherCode) {
                $voucher = Voucher::where('control_no', $voucherCode)
                    ->lockForUpdate()
                    ->first();

                if (!$voucher) {
                    throw new \Exception('Voucher not found.');
                }

                if ($voucher->status !== 'available') {
                    throw new \Exception('Voucher is not available.');
                }
            }

            if ($voucher) {
                $voucher->update([
                    'used_by_order_id' => $order->id,
                    'used_at' => now(),
                    'status' => 'used',
                ]);
            }

            foreach ($tableSession->headCounts as $head) {
                OrderHead::create([
                    'order_id' => $order->id,
                    'head_pricing_rule_id' => $head->head_pricing_rule_id,
                    'quantity' => $head->qty,
                    'price_snapshot' => $head->price_snapshot,
                    'subtotal' => $head->subtotal,
                ]);
            }

            $sessionAddons = $tableSession->addons()
                ->where('is_billed', false)
                ->where('is_void', 0)
                ->get();

            foreach ($sessionAddons as $addon) {
                OrderAddon::create([
                    'order_id' => $order->id,
                    'inventory_item_id' => $addon->inventory_item_id,
                    'item_name' => $addon->item_name,
                    'unit' => $addon->unit,
                    'quantity' => $addon->quantity,
                    'unit_price' => $addon->unit_price,
                    'subtotal' => $addon->subtotal,
                ]);
            }

            $tableSession->addons()
                ->where('is_billed', false)
                ->where('is_void', 0)
                ->update([
                    'is_billed' => true,
                ]);

            $tableSession->update([
                'cashier_id' => auth()->id(),
                'order_id' => $order->id,
                'total_amount' => $total,
            ]);

            return $order;
        });

        return response()->json([
            'success' => true,
            'order_no' => $createdOrder->order_no,
        ]);
    }

    public function store_orig_03222026(Request $request)
    {
        $request->validate([
            'table_session_id' => 'required|exists:table_sessions,id',
            'heads' => 'required|array|min:1',
            'payment_method' => 'required|in:Cash,GCash,Card',
            'cash_amount' => 'nullable|numeric|min:0',
        ]);

        $order = DB::transaction(function () use ($request) {
            $tableSession = TableSession::with([
                    'headCounts.headRule',
                    'addons' => function ($query) {
                        $query->where('is_billed', false);
                        $query->where('is_void', 0);
                    },
                ])
                ->lockForUpdate()
                ->findOrFail($request->table_session_id);

            if ($tableSession->status !== 'open') {
                throw new \Exception('Table session already closed.');
            }

            $baseSubtotal = (float) $tableSession->headCounts()->sum('subtotal');
            $addonsTotal  = (float) $tableSession->addons()->where('is_billed', false)->sum('subtotal');
            $leftoversTotal = (float) ($request->leftovers_total ?? 0);

            $subtotal = $baseSubtotal + $addonsTotal + $leftoversTotal;

            $totalDiscount = (float) ($request->total_discount ?? 0);
            $total = max($subtotal - $totalDiscount, 0);

            $changeAmount = null;

            if ($request->payment_method === 'Cash') {
                if ((float) $request->cash_amount < $total) {
                    throw new \Exception('Insufficient cash.');
                }

                $changeAmount = (float) $request->cash_amount - $total;
            }

            $discountType = null;

            if (!empty($request->voucher_code) && !empty($request->voucher_discount) && !empty($request->total_discount)) {
                $discountType = 'Voucher';
            } elseif (!empty($request->total_discount) && empty($request->voucher_code) && empty($request->voucher_discount)) {
                $discountType = 'SC/PWD';
            }

            $order = Order::create([
                'user_id' => auth()->id(),
                'table_session_id' => $tableSession->id,
                'subtotal' => $subtotal,
                'total_discount' => $totalDiscount,
                'total' => $total,
                'payment_method' => $request->payment_method,
                'cash_amount' => $request->payment_method === 'Cash'
                    ? $request->cash_amount
                    : null,
                'change_amount' => $changeAmount,
                'discount_type' => $discountType,
                'table_number' => $request->table_session_id,
                'voucher_no_used' => $request->voucher_code,
                'voucher_discount_used' => $request->voucher_discount !== null
                    ? (float) $request->voucher_discount
                    : 0,
                'discount_approving_manager_id' => !empty($request->approving_manager)
                    ? (is_array($request->approving_manager)
                        ? ($request->approving_manager['id'] ?? null)
                        : $request->approving_manager)
                    : null,
            ]);

            $voucher = null;
            $voucherCode = $request->voucher_code;

            if ($voucherCode) {
                $voucher = Voucher::where('control_no', $voucherCode)
                    ->lockForUpdate()
                    ->first();

                if (!$voucher) {
                    throw new \Exception('Voucher not found.');
                }

                if ($voucher->status !== 'available') {
                    throw new \Exception('Voucher is not available.');
                }
            }

            if ($voucher) {
                $voucher->update([
                    'used_by_order_id' => $order->id,
                    'used_at' => now(),
                    'status' => 'used',
                ]);
            }

            $order->update([
                'order_no' => now()->format('Ymd') . '-' . str_pad($order->id, 7, '0', STR_PAD_LEFT),
            ]);

            foreach ($tableSession->headCounts as $head) {
                OrderHead::create([
                    'order_id' => $order->id,
                    'head_pricing_rule_id' => $head->head_pricing_rule_id,
                    'quantity' => $head->qty,
                    'price_snapshot' => $head->price_snapshot,
                    'subtotal' => $head->subtotal,
                ]);
            }

            // SAVE SESSION ADDONS INTO ORDER_ADDONS
            $sessionAddons = $tableSession->addons()
                ->where('is_billed', false)
                ->where('is_void', 0)
                ->get();

            foreach ($sessionAddons as $addon) {
                OrderAddon::create([
                    'order_id' => $order->id,
                    'inventory_item_id' => $addon->inventory_item_id,
                    'item_name' => $addon->item_name,
                    'unit' => $addon->unit,
                    'quantity' => $addon->quantity,
                    'unit_price' => $addon->unit_price,
                    'subtotal' => $addon->subtotal,
                ]);
            }

            // MARK SESSION ADDONS AS BILLED
            $tableSession->addons()
                ->where('is_billed', false)
                ->update([
                    'is_billed' => true,
                ]);

            $tableSession->update([
                'cashier_id' => auth()->id(),
                'order_id' => $order->id,
                'total_amount' => $total,
                // optional if you want to close immediately after billing:
                // 'status' => 'closed',
                // 'closed_at' => now(),
            ]);

            return $order;
        });

        return response()->json([
            'success' => true,
            'order_no' => $order->order_no,
        ]);
    }

    public function store_orig_03132026(Request $request)
    {
        $request->validate([
            'table_session_id' => 'required|exists:table_sessions,id',
            'heads' => 'required|array|min:1',
            'payment_method' => 'required|in:Cash,GCash,Card',
            'cash_amount' => 'nullable|numeric|min:0',
        ]);

        $order = DB::transaction(function () use ($request) {
            $tableSession = TableSession::with('headCounts.headRule')
                ->lockForUpdate()
                ->findOrFail($request->table_session_id);

            if ($tableSession->status !== 'open') {
                throw new \Exception('Table session already closed.');
            }

            $subtotal = $tableSession->headCounts()->sum('subtotal');

            $totalDiscount = $request->total_discount ?? 0;
            $total = max($subtotal - $totalDiscount, 0);

            $changeAmount = null;

            if ($request->payment_method === 'Cash') {
                if ($request->cash_amount < $total) {
                    throw new \Exception('Insufficient cash.');
                }

                $changeAmount = $request->cash_amount - $total;
            }

            $discountType = null;

            if (!empty($request->voucher_code) && !empty($request->voucher_discount) && !empty($request->total_discount)) {
                $discountType = 'Voucher';
            } elseif (!empty($request->total_discount) && empty($request->voucher_code) && empty($request->voucher_discount)) {
                $discountType = 'SC/PWD';
            } else {
                $discountType = null;
            }

            $order = Order::create([
                'user_id' => auth()->id(),
                'table_session_id' => $tableSession->id,
                'subtotal' => $subtotal,
                'total_discount' => $totalDiscount,
                'total' => $total,
                'payment_method' => $request->payment_method,
                'cash_amount' => $request->payment_method === 'Cash'
                    ? $request->cash_amount
                    : null,
                'change_amount' => $changeAmount,
                'discount_type' => $discountType,
                'table_number' => $request->table_session_id,
                'voucher_no_used' => $request->voucher_code,
                'voucher_discount_used' => $request->voucher_discount !== null
                    ? floatval($request->voucher_discount)
                    : 0,
                'discount_approving_manager_id' => !empty($request->approving_manager) ? $request->approving_manager : null,
            ]);

            $voucher = null;
            $voucherCode = $request->voucher_code;

            if ($voucherCode) {
                $voucher = Voucher::where('control_no', $voucherCode)
                    ->lockForUpdate()
                    ->first();

                if (!$voucher) {
                    throw new \Exception('Voucher not found.');
                }

                if ($voucher->status !== 'available') {
                    throw new \Exception('Voucher is not available.');
                }
            }

            if ($voucher) {
                $voucher->update([
                    'used_by_order_id' => $order->id,
                    'used_at' => now(),
                    'status' => 'used',
                ]);
            }

            $order->update([
                'order_no' => now()->format('Ymd') . '-' .
                    str_pad($order->id, 7, '0', STR_PAD_LEFT)
            ]);

            foreach ($tableSession->headCounts as $head) {
                OrderHead::create([
                    'order_id' => $order->id,
                    'head_pricing_rule_id' => $head->head_pricing_rule_id,
                    'quantity' => $head->qty,
                    'price_snapshot' => $head->price_snapshot,
                    'subtotal' => $head->subtotal,
                ]);
            }

            $tableSession->update([
                'cashier_id' => auth()->id(),
                'order_id' => $order->id,
                'total_amount' => $total,
            ]);

            return $order;
        });

        return response()->json([
            'success' => true,
            'order_no' => $order->order_no
        ]);
    }

    private function generateSalesOrderNumber(): string
    {
        $datePart = now()->format('ymdHi'); // example: 2603220830
        $prefix = 'SO-' . $datePart . '-';

        $latestOrder = Order::where('order_no', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first();

        $nextSequence = 1;

        if ($latestOrder && preg_match('/(\d{4})$/', $latestOrder->order_no, $matches)) {
            $nextSequence = ((int) $matches[1]) + 1;
        }

        return $prefix . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
    }

    public function storeOrderedItems(Request $request)
    {
        $request->validate([
            'cart' => 'required|array|min:1',
            'cart.*.id' => 'required|exists:inventory_items,id',
            'cart.*.qty' => 'required|numeric|min:1',
            'payment_method' => 'nullable|in:Cash,GCash,Card',
            'cash_amount' => 'nullable|numeric|min:0',
            'senior_pwd_discount_approved' => 'nullable|boolean',
            'total_discount' => 'nullable|numeric|min:0',
        ]);

        $order = DB::transaction(function () use ($request) {
            $cart = collect($request->cart);
            $itemIds = $cart->pluck('id')->unique()->values();

            $items = InventoryItem::whereIn('id', $itemIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($items->count() !== $itemIds->count()) {
                throw new \Exception('Some items were not found.');
            }

            $subtotal = 0;

            foreach ($cart as $cartItem) {
                $dbItem = $items->get($cartItem['id']);

                if (!$dbItem) {
                    throw new \Exception("Item not found: {$cartItem['id']}");
                }

                $qty = (float) ($cartItem['qty'] ?? 0);

                if ($qty <= 0) {
                    throw new \Exception("Invalid quantity for item {$dbItem->name}");
                }

                $currentQty = (float) $dbItem->current_quantity;

                if ($qty > $currentQty) {
                    throw new \Exception("Insufficient stock for item {$dbItem->name}. Available: {$currentQty}");
                }

                $subtotal += ((float) $dbItem->unit_price * $qty);
            }

            $totalDiscount = (float) ($request->total_discount ?? 0);

            if ($totalDiscount < 0) {
                $totalDiscount = 0;
            }

            if ($totalDiscount > $subtotal) {
                throw new \Exception('Total discount cannot be greater than subtotal.');
            }

            $total = $subtotal - $totalDiscount;

            $paymentMethod = $request->payment_method ?? 'Cash';
            $cashAmount = $paymentMethod === 'Cash'
                ? (float) ($request->cash_amount ?? 0)
                : null;

            $changeAmount = 0;

            if ($paymentMethod === 'Cash') {
                if ($cashAmount < $total) {
                    throw new \Exception('Insufficient cash amount.');
                }

                $changeAmount = $cashAmount - $total;
            }

            $orderNo = $this->generateSalesOrderNumber();

            $now = Carbon::now();
            $time = $now->format('H:i:s');

            $current_shift = Shift::where(function ($q) use ($time) {
                $q->where(function ($q2) use ($time) {
                    $q2->whereRaw('start_time <= end_time')
                        ->where('start_time', '<=', $time)
                        ->where('end_time', '>=', $time);
                })
                ->orWhere(function ($q2) use ($time) {
                    $q2->whereRaw('start_time > end_time')
                        ->where(function ($q3) use ($time) {
                            $q3->where('start_time', '<=', $time)
                                ->orWhere('end_time', '>=', $time);
                        });
                });
            })->first();

            $order = Order::create([
                'user_id' => auth()->id(),
                'subtotal' => $subtotal,
                'total_discount' => $totalDiscount,
                'order_no' => $orderNo,
                'total' => $total,
                'discount_type' => !empty($request->senior_pwd_discount_approved) ? 'senior_pwd' : null,
                'status' => 'paid',
                'cancelled' => 0,
                'cancelled_by' => null,
                'cancellation_remarks' => null,
                'voucher_no_used' => null,
                'voucher_discount_used' => null,
                'payment_method' => $paymentMethod,
                'cash_amount' => $cashAmount,
                'change_amount' => $changeAmount,
                'table_number' => null,
                'discount_approving_manager_id' => null,
                'cancel_approving_manager_id' => null,
                'shift_id' => $current_shift ? $current_shift->id : null,
            ]);

            // ─── Save Order Payment ───────────────────────────────────────────
            $paymentMethodRecord = \App\Models\PaymentMethod::where('name', $paymentMethod)->first();

            $isCash = $paymentMethodRecord && (bool) $paymentMethodRecord->is_cash;
            $requiresReference = $paymentMethodRecord && (bool) $paymentMethodRecord->requires_reference;

            OrderPayment::create([
                'order_id'          => $order->id,
                'payment_method_id' => $paymentMethodRecord?->id,
                'amount'            => $total,                          // actual amount credited
                'amount_tendered'   => $isCash ? $cashAmount : null,    // cash handed over
                'reference_no'      => $requiresReference
                                        ? (trim((string) ($request->reference_no ?? '')) ?: null)
                                        : null,
                'remarks'           => trim((string) ($request->remarks ?? '')) ?: null,
                'received_by'       => auth()->id(),
                'is_void'           => false,
            ]);
            // ─────────────────────────────────────────────────────────────────

            foreach ($cart as $cartItem) {
                $dbItem = $items->get($cartItem['id']);

                $qty = (float) ($cartItem['qty'] ?? 0);
                $unitPrice = (float) $dbItem->unit_price;
                $lineSubtotal = $unitPrice * $qty;

                OrderAddon::create([
                    'order_id' => $order->id,
                    'inventory_item_id' => $dbItem->id,
                    'item_name' => $dbItem->name,
                    'unit' => $dbItem->unit,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $lineSubtotal,
                ]);

                $dbItem->update([
                    'current_quantity' => (float) $dbItem->current_quantity - $qty,
                ]);

                InventoryMovement::create([
                    'inventory_item_id' => $dbItem->id,
                    'type' => 'stockout',
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'note' => 'Single order item sale - Order No: ' . $order->order_no,
                    'created_by' => auth()->id(),
                    'updated_by' => null,
                ]);
            }

            return $order->fresh(['addons']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Order successfully created.',
            'order_id' => $order->id,
            'order_no' => $order->order_no,
        ]);
    }

    public function storeOrderedItems_orig05152026(Request $request)
    {
        $request->validate([
            'cart' => 'required|array|min:1',
            'cart.*.id' => 'required|exists:inventory_items,id',
            'cart.*.qty' => 'required|numeric|min:1',
            'payment_method' => 'nullable|in:Cash,GCash,Card',
            'cash_amount' => 'nullable|numeric|min:0',
            'senior_pwd_discount_approved' => 'nullable|boolean',
            'total_discount' => 'nullable|numeric|min:0',
        ]);

        $order = DB::transaction(function () use ($request) {
            $cart = collect($request->cart);
            $itemIds = $cart->pluck('id')->unique()->values();

            $items = InventoryItem::whereIn('id', $itemIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($items->count() !== $itemIds->count()) {
                throw new \Exception('Some items were not found.');
            }

            $subtotal = 0;

            foreach ($cart as $cartItem) {
                $dbItem = $items->get($cartItem['id']);

                if (!$dbItem) {
                    throw new \Exception("Item not found: {$cartItem['id']}");
                }

                $qty = (float) ($cartItem['qty'] ?? 0);

                if ($qty <= 0) {
                    throw new \Exception("Invalid quantity for item {$dbItem->name}");
                }

                $currentQty = (float) $dbItem->current_quantity;

                if ($qty > $currentQty) {
                    throw new \Exception("Insufficient stock for item {$dbItem->name}. Available: {$currentQty}");
                }

                $subtotal += ((float) $dbItem->unit_price * $qty);
            }

            $totalDiscount = (float) ($request->total_discount ?? 0);

            if ($totalDiscount < 0) {
                $totalDiscount = 0;
            }

            if ($totalDiscount > $subtotal) {
                throw new \Exception('Total discount cannot be greater than subtotal.');
            }

            $total = $subtotal - $totalDiscount;

            $paymentMethod = $request->payment_method ?? 'Cash';
            $cashAmount = $paymentMethod === 'Cash'
                ? (float) ($request->cash_amount ?? 0)
                : null;

            $changeAmount = 0;

            if ($paymentMethod === 'Cash') {
                if ($cashAmount < $total) {
                    throw new \Exception('Insufficient cash amount.');
                }

                $changeAmount = $cashAmount - $total;
            }

            $orderNo = $this->generateSalesOrderNumber();

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

            $order = Order::create([
                'user_id' => auth()->id(),
                'subtotal' => $subtotal,
                'total_discount' => $totalDiscount,
                'order_no' => $orderNo,
                'total' => $total,
                'discount_type' => !empty($request->senior_pwd_discount_approved) ? 'senior_pwd' : null,
                'status' => 'paid',
                'cancelled' => 0,
                'cancelled_by' => null,
                'cancellation_remarks' => null,
                'voucher_no_used' => null,
                'voucher_discount_used' => null,
                'payment_method' => $paymentMethod,
                'cash_amount' => $cashAmount,
                'change_amount' => $changeAmount,
                'table_number' => null,
                'discount_approving_manager_id' => null,
                'cancel_approving_manager_id' => null,
                'shift_id' => $current_shift ? $current_shift->id : null,
            ]);

            foreach ($cart as $cartItem) {
                $dbItem = $items->get($cartItem['id']);

                $qty = (float) ($cartItem['qty'] ?? 0);
                $unitPrice = (float) $dbItem->unit_price;
                $lineSubtotal = $unitPrice * $qty;

                OrderAddon::create([
                    'order_id' => $order->id,
                    'inventory_item_id' => $dbItem->id,
                    'item_name' => $dbItem->name,
                    'unit' => $dbItem->unit,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $lineSubtotal,
                ]);

                $dbItem->update([
                    'current_quantity' => (float) $dbItem->current_quantity - $qty,
                ]);

                InventoryMovement::create([
                    'inventory_item_id' => $dbItem->id,
                    'type' => 'stockout',
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'note' => 'Single order item sale - Order No: ' . $order->order_no,
                    'created_by' => auth()->id(),
                    'updated_by' => null,
                ]);
            }

            return $order->fresh(['addons']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Order successfully created.',
            'order_id' => $order->id,
            'order_no' => $order->order_no,
        ]);
    }

    public function storeOrderedItems_Orig(Request $request)
    {
        $request->validate([
            'table_session_id' => 'required|exists:table_sessions,id',
            'cart' => 'required|array|min:1',
            'cart.*.id' => 'required|exists:inventory_items,id',
            'cart.*.qty' => 'required|numeric|min:1',
            'payment_method' => 'nullable|in:Cash,GCash,Card',
            'cash_amount' => 'nullable|numeric|min:0',
            'senior_pwd_discount_approved' => 'nullable|boolean',
            'total_discount' => 'nullable|numeric|min:0',
            'approving_manager_id' => 'nullable|exists:users,id',
        ]);

        $order = DB::transaction(function () use ($request) {
            $tableSession = TableSession::lockForUpdate()->findOrFail($request->table_session_id);

            if (!$tableSession->order_id) {
                throw new \Exception('Selected table session has no existing order.');
            }

            $order = Order::lockForUpdate()->findOrFail($tableSession->order_id);

            $cart = collect($request->cart);

            $itemIds = $cart->pluck('id')->unique()->values();

            $items = InventoryItem::whereIn('id', $itemIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($items->count() !== $itemIds->count()) {
                throw new \Exception('Some items were not found.');
            }

            foreach ($cart as $cartItem) {
                $dbItem = $items->get($cartItem['id']);

                if (!$dbItem) {
                    throw new \Exception("Item not found: {$cartItem['id']}");
                }

                $qty = (float) ($cartItem['qty'] ?? 0);

                if ($qty <= 0) {
                    throw new \Exception("Invalid quantity for item {$dbItem->name}");
                }

                $currentQty = (float) $dbItem->current_quantity;

                if ($qty > $currentQty) {
                    throw new \Exception("Insufficient stock for item {$dbItem->name}. Available: {$currentQty}");
                }
            }

            foreach ($cart as $cartItem) {
                $dbItem = $items->get($cartItem['id']);

                $qty = (float) ($cartItem['qty'] ?? 0);
                $unitPrice = (float) $dbItem->unit_price;
                $lineSubtotal = $unitPrice * $qty;

                OrderAddon::create([
                    'order_id' => $order->id,
                    'inventory_item_id' => $dbItem->id,
                    'item_name' => $dbItem->name,
                    'unit' => $dbItem->unit,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $lineSubtotal,
                ]);

                $dbItem->update([
                    'current_quantity' => (float) $dbItem->current_quantity - $qty,
                ]);

                InventoryMovement::create([
                    'inventory_item_id' => $dbItem->id,
                    'type' => 'stockout',
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'note' => 'Order add-on - Order No: ' . $order->order_no,
                    'created_by' => auth()->id(),
                    'updated_by' => null,
                ]);
            }

            return $order->fresh(['addons']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Add-ons successfully added to the table order.',
            'order_id' => $order->id,
            'order_no' => $order->order_no,
        ]);
    }

    public function store_orig(Request $request)
    {
        $request->validate([
            'cart' => 'required|array|min:1',
            'payment_method' => 'required|string|in:Cash,GCash,Card',
            'cash_amount' => 'nullable|numeric|min:0',
            'table_session_id' => 'required|exists:table_sessions,id',
            'voucher_code' => 'nullable|string',
        ]);

        $cart = $request->cart;
        $voucherCode = $request->voucher_code;

        $order = null;

        DB::transaction(function () use ($cart, $request, &$order, $voucherCode) {
            $subtotal = collect($cart)->reduce(function ($sum, $item) {
                $itemPrice = (float) $item['price'];
                $itemQty = (int) $item['qty'];
                $itemDiscount = isset($item['discount'])
                    ? (float) $item['discount']
                    : 0;

                return $sum + (($itemPrice - $itemDiscount) * $itemQty);
            }, 0);

            $voucher = null;

            if ($voucherCode) {
                $voucher = Voucher::where('control_no', $voucherCode)
                    ->lockForUpdate()
                    ->first();

                if (!$voucher) {
                    throw new \Exception('Voucher not found.');
                }

                if ($voucher->status !== 'available') {
                    throw new \Exception('Voucher is not available.');
                }
            }

            $totalDiscount = 0;

            if ($voucher) {
                if (str_contains($voucher->type, '%')) {
                    $percent = (float) str_replace('%', '', $voucher->type);
                    $totalDiscount = $subtotal * ($percent / 100);
                } elseif ($voucher->type === 'Free Meal') {
                    $totalDiscount = $subtotal;
                }
            }

            $total = $subtotal - $totalDiscount;

            if ($total < 0) {
                $total = 0;
            }

            $paymentMethod = $request->payment_method;
            $cashAmount = $request->cash_amount;
            $changeAmount = null;

            if ($paymentMethod === 'Cash') {
                if ($cashAmount === null || $cashAmount < $total) {
                    throw new \Exception('Insufficient cash amount.');
                }

                $changeAmount = $cashAmount - $total;
            }

            $order = Order::create([
                'user_id' => auth()->id(),
                'subtotal' => $subtotal,
                'total_discount' => $totalDiscount,
                'total' => $total,
                'payment_method' => $paymentMethod,
                'cash_amount' => $paymentMethod === 'Cash' ? $cashAmount : null,
                'change_amount' => $changeAmount,
                'voucher_no_used' => $voucherCode,
                'voucher_discount_used' => $totalDiscount,
                'table_number' => $request->table_session_id,
                'discount_approving_manager_id' => $request->approving_manager,
            ]);

            $tableSession = TableSession::findOrFail($request->table_session_id);

            $tableSession->update([
                'cashier_id' => auth()->id(),
                'order_id' => $order->id,
                'total_amount' => $total,
            ]);

            $datePrefix = now()->format('Ymd');
            $orderNo = $datePrefix . '-' . str_pad($order->id, 7, '0', STR_PAD_LEFT);

            $order->update([
                'order_no' => $orderNo
            ]);

            foreach ($cart as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_id' => $item['id'],
                    'quantity' => (int) $item['qty'],
                    'price' => (float) $item['price'],
                    'discount' => $item['discount'] ?? 0,
                    'cancelled' => false,
                ]);
            }

            if ($voucher) {
                $voucher->update([
                    'used_by_order_id' => $order->id,
                    'used_at' => now(),
                    'status' => 'used',
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Order placed successfully!',
            'order_id' => $order->id,
            'order_no' => $order->order_no
        ]);
    }

    public function show(Order $order)
    {
        return Inertia::render('Orders/Show', [
            'order' => $order->load('items.menu')
        ]);
    }

    public function destroy(Order $order)
    {
        $order->update(['status' => 'cancelled']);
        $order->items()->update(['cancelled' => true]);

        return redirect()->back();
    }

    public function cancelItem(Order $order, $itemId)
    {
        $managerId = request('manager_id');
        $password = request('manager_password');

        $manager = User::where('id', $managerId)
            ->where('role', 1)
            ->first();

        if (!$manager || !Hash::check($password, $manager->password)) {
            return response()->json([
                'message' => 'Invalid manager credentials.'
            ], 403);
        }

        $item = $order->items()->findOrFail($itemId);

        if ($item->cancelled) {
            return response()->json([
                'message' => 'Item already cancelled.'
            ], 400);
        }

        $item->update([
            'cancelled' => 1,
            'cancelled_by' => auth()->id(),
            'manager_approved_by' => $manager->id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Item cancelled successfully.'
        ]);
    }

    public function cancel(Order $order): RedirectResponse
    {
        $managerId = request()->input('manager');
        $managerPassword = request()->input('manager_password');

        $managerId = request()->input('manager');
        $manager = User::where('id', $managerId)
            ->where('role', 1)
            ->first();

        if (!$manager || !Hash::check($managerPassword, $manager->password)) {
            return back()->with('error', 'Invalid manager credentials.');
        }

        $reason = request()->input('reason');

        if ($order->status === 'cancelled') {
            return back()->with('error', 'Order already cancelled.');
        }

        $order->update([
            'status' => 'cancelled',
            'cancelled' => 1,
            'cancelled_by' => auth()->id(),
            'cancellation_remarks' => $reason,
            'manager_approved_by' => $manager->id,
        ]);

        return back()->with('success', 'Order cancelled successfully.');
    }

    public function cancel_item(OrderItem $orderItem)
    {
        $order = $orderItem->order;

        if ($order->status === 'cancelled') {
            return back()->with('error', 'Cannot cancel item from a cancelled order.');
        }

        if ($orderItem->cancelled) {
            return back()->with('error', 'Item already cancelled.');
        }

        $managerIds = request()->input('manager_id');
        $managerPassword = request()->input('manager_password');

        if (!$managerIds || !$managerPassword) {
            return back()->with('error', 'Manager approval required.');
        }

        $manager = User::whereIn('id', (array) $managerIds)
            ->where('role', '1')
            ->first();

        if (!$manager || !Hash::check($managerPassword, $manager->password)) {
            return back()->with('error', 'Invalid manager credentials.');
        }

        $orderItem->update([
            'cancelled' => 1,
            'cancelled_by' => auth()->id(),
            'manager_id' => $manager->id,
            'cancellation_remarks' => request()->input('reason') ?? 'Cancelled by manager'
        ]);

        $order->load('items');
        $activeItems = $order->items->where('cancelled', 0);

        $subtotal = $activeItems->sum(fn ($item) => $item->price * $item->quantity);

        $total_discount = 0;
        if ($order->discount_type && $order->discount_type !== 'None') {
            if ($activeItems->first()?->discount !== null) {
                $total_discount = $activeItems->sum(fn ($item) => $item->discount * $item->quantity);
            } else {
                $discountRate = match ($order->discount_type) {
                    'PWD/Senior' => 0.20,
                    'Employee' => 0.10,
                    default => 0,
                };
                $total_discount = $subtotal * $discountRate;
            }
        }

        $total = max($subtotal - $total_discount, 0);

        $updateData = [
            'subtotal' => $subtotal,
            'total_discount' => $total_discount,
            'total' => $total,
        ];

        if ($activeItems->isEmpty()) {
            $updateData['status'] = 'cancelled';
            $updateData['cancelled'] = 1;
            $updateData['cancelled_by'] = auth()->id();
            $updateData['cancellation_remarks'] = 'All items cancelled';
        }

        $order->update($updateData);        

        return back()->with('success', 'Item cancelled successfully.');
    }

    public function pdfReceipt($order)
    {
        $order = Order::with([
                'user',
                'tableSession.table',
                'orderHeads.headPricingRule',
                'addons',
                'leftover',
                'payments.paymentMethod',
            ])
            ->where('order_no', $order)
            ->firstOrFail();

        $branch = Branch::where('main', 1)->first();

        $headsSubtotal = $order->orderHeads->sum(function ($head) {
            return (float) $head->subtotal;
        });

        $addonsSubtotal = $order->addons->sum(function ($addon) {
            return (float) $addon->subtotal;
        });

        $leftoverAmount = (float) optional($order->leftover)->amount;
        $discount = (float) ($order->total_discount ?? 0);

        $computedSubtotal = $headsSubtotal + $addonsSubtotal + $leftoverAmount;
        $computedTotal = max($computedSubtotal - $discount, 0);

        $html = view('receipts.receipt', compact(
            'order',
            'branch',
            'headsSubtotal',
            'addonsSubtotal',
            'leftoverAmount',
            'computedSubtotal',
            'computedTotal',
            'discount'
        ))->render();

        $mpdf = new \Mpdf\Mpdf([
            'format' => [58, 3976],
            'margin_left' => 0,
            'margin_right' => 1,
            'margin_top' => 0,
            'margin_bottom' => 0,
        ]);

        $mpdf->WriteHTML($html);

        return $mpdf->Output("Receipt-{$order->order_no}.pdf", 'I');
    }

    public function verifyManagerPassword(Request $request)
    {
        $manager = User::where('id', $request->manager_id)
            ->where('role', '1')
            ->first();

        if (!$manager) {
            return response()->json(['success' => false]);
        }

        if (!Hash::check($request->password, $manager->password)) {
            return response()->json(['success' => false]);
        }

        return response()->json(['success' => true]);
    }

    public function refreshComputation()
    {
        $today = now()->toDateString();

        $orders = Order::with([
            'user',
            'tableSession.table',
            'orderHeads.headPricingRule',
            'tableSession.headCounts.headRule',
            'addons',
            'leftover'
        ])
        ->whereDate('created_at', $today)
        ->orderBy('created_at', 'desc')
        ->get();

        $total_sales_per_cashier = Order::query()
            ->whereDate('created_at', $today)
            ->where('status', 'paid')
            ->where('user_id', auth()->id())
            ->sum('total');
        
        $total_sales_all_cashier = Order::query()
            ->whereDate('created_at', $today)
            ->where('status', 'paid')
            ->whereHas('user', function ($query) {
                $query->where('role', 2);
            })
            ->sum('total');

        $unbilled_sessions = TableSession::with([
                'headCounts.headRule'
            ])
            ->whereDate('created_at', $today)
            ->where('status', 'open')
            ->whereNull('order_id')
            ->get();

        $total_unbilled = $unbilled_sessions->sum(function ($session) {
            return collect($session->headCounts ?? [])->sum(function ($head) {
                return (float) ($head->subtotal ?? 0);
            });
        });

        return response()->json([
            'orders' => $orders,
            'total_sales_per_cashier' => (float)$total_sales_per_cashier,
            'total_sales_all_cashier' => (float)$total_sales_all_cashier,
            'total_unbilled' => $total_unbilled,
        ]);
    }

    public function storeLeftover(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'weight' => 'required|numeric|min:0.01',
            'amount' => 'required|numeric|min:0',
        ]);

        $order = Order::findOrFail($request->order_id);

        if ($order->status === 'cancelled') {
            return response()->json([
                'message' => 'Cancelled orders cannot receive leftover charges.'
            ], 422);
        }

        OrderLeftover::updateOrCreate(
            ['order_id' => $order->id],
            [
                'weight' => $request->weight,
                'amount' => $request->amount,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Leftover charge saved successfully.',
        ]);
    }

    public function storeTableSessionAddons_orig(Request $request)
    {
        $request->validate([
            'table_session_id'        => 'required|exists:table_sessions,id',
            'add_ons'                 => 'required|array|min:1',
            'add_ons.*.item_id'       => 'required|exists:inventory_items,id',
            'add_ons.*.name'          => 'required|string|max:255',
            'add_ons.*.qty'           => 'required|numeric|min:1',
            'add_ons.*.price'         => 'required|numeric|min:0',
            'add_ons.*.unit'          => 'nullable|string|max:50',
        ]);

        try {
            $tableSession = TableSession::where('id', $request->table_session_id)
                ->where('status', 'open')
                ->first();

            if (!$tableSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'Open table session not found.',
                ], 404);
            }

            DB::transaction(function () use ($request, $tableSession) {
                foreach ($request->add_ons as $addon) {
                    $inventoryItem = InventoryItem::find($addon['item_id']);

                    $quantity  = (float) $addon['qty'];
                    $unitPrice = (float) ($inventoryItem?->unit_price ?? $addon['price']);
                    $subtotal  = $quantity * $unitPrice;

                    TableSessionAddon::create([
                        'table_session_id'  => $tableSession->id,
                        'inventory_item_id' => $addon['item_id'],
                        'item_name'         => $addon['name'],
                        'unit'              => $addon['unit'] ?? $inventoryItem?->unit,
                        'quantity'          => $quantity,
                        'unit_price'        => $unitPrice,
                        'subtotal'          => $subtotal,
                        'is_billed'         => false,
                    ]);
                }
            });

            $addons = TableSessionAddon::where('table_session_id', $tableSession->id)
                ->where('is_billed', false)
                ->orderBy('id')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Add-ons posted successfully.',
                'addons'  => $addons,
            ]);
        } catch (\Throwable $e) {
            \Log::error('OrderService@storeTableSessionAddons failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to post add-ons.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function storeTableSessionAddons(Request $request)
    {
        $request->validate([
            'table_session_id'        => 'required|exists:table_sessions,id',
            'add_ons'                 => 'required|array|min:1',
            'add_ons.*.item_id'       => 'required|exists:inventory_items,id',
            'add_ons.*.name'          => 'required|string|max:255',
            'add_ons.*.qty'           => 'required|numeric|min:1',
            'add_ons.*.price'         => 'required|numeric|min:0',
            'add_ons.*.unit'          => 'nullable|string|max:50',
        ]);

        try {
            $tableSession = TableSession::where('id', $request->table_session_id)
                ->where('status', 'open')
                ->first();

            if (!$tableSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'Open table session not found.',
                ], 404);
            }

            DB::transaction(function () use ($request, $tableSession) {
                foreach ($request->add_ons as $addon) {
                    $qty = (float) $addon['qty'];

                    $dbItem = InventoryItem::lockForUpdate()->find($addon['item_id']);

                    if (!$dbItem) {
                        throw new \Exception('Inventory item not found.');
                    }

                    if ((float) $dbItem->current_quantity < $qty) {
                        throw new \Exception("Insufficient stock for {$dbItem->name}. Available: {$dbItem->current_quantity}");
                    }

                    $unitPrice = (float) ($dbItem->unit_price ?? $addon['price']);
                    $subtotal  = $qty * $unitPrice;

                    $tableAddon = TableSessionAddon::create([
                        'table_session_id'  => $tableSession->id,
                        'inventory_item_id' => $dbItem->id,
                        'item_name'         => $dbItem->name ?? $addon['name'],
                        'unit'              => $addon['unit'] ?? $dbItem->unit,
                        'quantity'          => $qty,
                        'unit_price'        => $unitPrice,
                        'subtotal'          => $subtotal,
                        'is_billed'         => false,
                    ]);

                    $newQty = (float) $dbItem->current_quantity - $qty;

                    $dbItem->update([
                        'current_quantity' => $newQty,
                        'updated_by'       => auth()->id(),
                    ]);

                    InventoryMovement::create([
                        'inventory_item_id' => $dbItem->id,
                        'type'              => 'stockout',
                        'quantity'          => $qty,
                        'unit_price'        => $unitPrice,
                        'note'              => 'Table session add-on - Session ID: ' . $tableSession->id . ' - Addon ID: ' . $tableAddon->id,
                        'created_by'        => auth()->id(),
                        'updated_by'        => null,
                    ]);
                }
            });

            $addons = TableSessionAddon::where('table_session_id', $tableSession->id)
                ->where('is_billed', false)
                ->orderBy('id')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Add-ons posted successfully.',
                'addons'  => $addons,
            ]);
        } catch (\Throwable $e) {
            Log::error('OrderService@storeTableSessionAddons failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to post add-ons.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function voidTableSessionAddon(Request $request, $addonId)
    {
        $request->validate([
            'table_session_id' => 'required|exists:table_sessions,id',
            'void_qty'         => 'required|numeric|min:1',
            'remarks'          => 'required|string|max:255',
        ]);

        try {
            $tableSession = TableSession::where('id', $request->table_session_id)
                ->where('status', 'open')
                ->first();

            if (!$tableSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'Open table session not found.',
                ], 404);
            }

            $addon = TableSessionAddon::where('id', $addonId)
                ->where('table_session_id', $tableSession->id)
                ->where('is_billed', false)
                ->first();

            if (!$addon) {
                return response()->json([
                    'success' => false,
                    'message' => 'Add-on not found or already billed.',
                ], 404);
            }

            $voidQty = (float) $request->void_qty;
            $currentQty = (float) $addon->quantity;

            if ($voidQty > $currentQty) {
                return response()->json([
                    'success' => false,
                    'message' => 'Void quantity exceeds current add-on quantity.',
                ], 422);
            }

            DB::transaction(function () use ($request, $tableSession, $addon, $voidQty) {
                $inventoryItem = InventoryItem::lockForUpdate()->find($addon->inventory_item_id);

                if (!$inventoryItem) {
                    throw new \Exception('Inventory item not found.');
                }

                $remainingQty = (float) $addon->quantity - $voidQty;
                $unitPrice = (float) $addon->unit_price;
                $restockedQty = (float) $inventoryItem->current_quantity + $voidQty;

                if ($remainingQty <= 0) {
                    $addon->update([
                        'quantity'       => 0,
                        'subtotal'       => 0,
                        'is_void'        => true,
                        'void_remarks'   => $request->remarks,
                        'updated_at'     => now(),
                    ]);
                } else {
                    $addon->update([
                        'quantity'       => $remainingQty,
                        'subtotal'       => $remainingQty * $unitPrice,
                        'updated_at'     => now(),
                    ]);

                    TableSessionAddon::create([
                        'table_session_id'  => $tableSession->id,
                        'inventory_item_id' => $inventoryItem->id,
                        'item_name'         => $addon->item_name,
                        'unit'              => $addon->unit,
                        'quantity'          => $voidQty,
                        'unit_price'        => $unitPrice,
                        'subtotal'          => 0,
                        'is_billed'         => false,
                        'is_void'           => true,
                        'void_remarks'      => $request->remarks,
                        'voided_by'         => auth()->id()
                    ]);
                }

                $inventoryItem->update([
                    'current_quantity' => $restockedQty,
                    'updated_by'       => auth()->id(),
                ]);

                InventoryMovement::create([
                    'inventory_item_id' => $inventoryItem->id,
                    'type'              => 'stockin',
                    'quantity'          => $voidQty,
                    'unit_price'        => $unitPrice,
                    'note'              => 'Voided table session add-on - Session ID: ' . $tableSession->id .
                                        ' - Addon ID: ' . $addon->id .
                                        ' - Remarks: ' . $request->remarks,
                    'created_by'        => auth()->id(),
                    'updated_by'        => null,
                ]);
            });

            $addons = TableSessionAddon::where('table_session_id', $tableSession->id)
                ->where('is_billed', false)
                ->orderBy('id')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Add-on voided successfully.',
                'addons'  => $addons,
            ]);
        } catch (\Throwable $e) {
            Log::error('OrderService@voidTableSessionAddon failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to void add-on.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

}