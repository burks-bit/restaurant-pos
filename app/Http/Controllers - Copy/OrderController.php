<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Inertia\Inertia;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderAddon;
use App\Models\OrderLeftover;
use App\Models\Voucher;
use App\Models\TableSession;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Branch;
use App\Models\OrderHead;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Mpdf\Mpdf;
use Illuminate\Support\Facades\Hash;

class OrderController extends Controller
{
    // List all orders (today)
    // public function index(Request $request)
    // {
    //     $date = $request->input('date', now()->toDateString());

    //     $orders = Order::with([
    //         'user',
    //         'items.menu',
    //         'tableSession.table',
    //         'orderHeads.headPricingRule', // optional: if you want pricing labels
    //         'tableSession.headCounts.headRule'
    //     ])
    //         ->whereDate('created_at', $date)
    //         ->orderBy('created_at', 'desc')
    //         ->get();

    //     // Log::info('orders with table sessions');
    //     // Log::info($orders->toArray());

    //     return Inertia::render('Orders/Index', [
    //         'orders' => $orders,
    //         'selectedDate' => $date,
    //         'managers' => User::where('role', '1')->get(),
    //     ]);
    // }

    public function index()
    {
        $today = now()->toDateString();

        $orders = Order::with([
                'user',
                'items.menu',
                'tableSession.table',
                'orderHeads.headPricingRule',
                'tableSession.headCounts.headRule',
                'addons',
                'leftover'
            ])
            ->whereDate('created_at', $today)
            ->orderBy('created_at', 'desc')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 1. Total Sales = paid orders only
        |--------------------------------------------------------------------------
        */
        $total_sales = Order::whereDate('created_at', $today)
            ->where('status', 'paid')
            ->sum('total');

        /*
        |--------------------------------------------------------------------------
        | 2. Running Bill = open table sessions with existing order
        |    base order total + addons total
        |--------------------------------------------------------------------------
        */
        $running_bill = DB::table('table_session_heads')
            ->whereIn('table_session_id', function ($query) {
                $query->select('id')
                    ->from('table_sessions')
                    ->where('status', 'open');
            })
            ->sum('subtotal');


        /*
        |--------------------------------------------------------------------------
        | 3. Total Unbilled = open table sessions without order yet
        |    based on head_counts subtotal
        |--------------------------------------------------------------------------
        */
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

        Log::info('orders with addons');
        Log::info($orders);

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'selectedDate' => $today,
            'managers' => User::where('role', 1)->get(),
            'total_sales' => $total_sales,
            'running_bill' => $running_bill,
            'total_unbilled' => $total_unbilled,
        ]);
    }

    public function getFilteredOrders(Request $request)
    {
        $date   = $request->date;
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

        /*
        |--------------------------------------------------------------------------
        | If ALL filters empty → default to today
        |--------------------------------------------------------------------------
        */
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
        // Log::info('store order');
        // Log::info($request->all());
        $request->validate([
            'table_session_id' => 'required|exists:table_sessions,id',
            'heads'            => 'required|array|min:1',
            'payment_method'   => 'required|in:Cash,GCash,Card',
            'cash_amount'      => 'nullable|numeric|min:0',
        ]);

        $order = DB::transaction(function () use ($request) {

            $tableSession = TableSession::with('headCounts.headRule')
                ->lockForUpdate()
                ->findOrFail($request->table_session_id);

            if ($tableSession->status !== 'open') {
                throw new \Exception('Table session already closed.');
            }

            /*
            |--------------------------------------------------------------------------
            | 1. Compute Subtotal from Session Heads
            |--------------------------------------------------------------------------
            */

            // $subtotal = $tableSession->headCounts->sum('subtotal');
            $subtotal = $tableSession->headCounts()->sum('subtotal');

            $totalDiscount = $request->total_discount ?? 0; // <-- apply frontend discount
            $total = max($subtotal - $totalDiscount, 0);     // <-- total after discount

            /*
            |--------------------------------------------------------------------------
            | 2. Cash Validation
            |--------------------------------------------------------------------------
            */

            $changeAmount = null;

            if ($request->payment_method === 'Cash') {

                if ($request->cash_amount < $total) {
                    throw new \Exception('Insufficient cash.');
                }

                $changeAmount = $request->cash_amount - $total;
            }

            /*
            |--------------------------------------------------------------------------
            | 3. Create Order
            |--------------------------------------------------------------------------
            */

            $discountType = null;

            if (!empty($request->voucher_code) && !empty($request->voucher_discount) && !empty($request->total_discount)) {
                $discountType = 'Voucher';
            } elseif (!empty($request->total_discount) && empty($request->voucher_code) && empty($request->voucher_discount)) {
                $discountType = 'SC/PWD';
            } else {
                $discountType = null; // no discount
            }

            $order = Order::create([
                'user_id'          => auth()->id(),
                'table_session_id' => $tableSession->id,
                'subtotal'         => $subtotal,
                'total_discount'   => $totalDiscount,
                'total'            => $total,
                'payment_method'   => $request->payment_method,
                'cash_amount'      => $request->payment_method === 'Cash'
                                        ? $request->cash_amount
                                        : null,
                'change_amount'    => $changeAmount,
                'discount_type'    => $discountType,
                'table_number'     => $request->table_session_id,
                'voucher_no_used'     => $request->voucher_code,
                'voucher_discount_used' => $request->voucher_discount !== null 
                           ? floatval($request->voucher_discount)
                           : 0,
                'discount_approving_manager_id'  => !empty($request->approving_manager) ? $request->approving_manager : null,
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
                    'used_at'          => now(),
                    'status'           => 'used',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 4. Generate Order Number
            |--------------------------------------------------------------------------
            */

            $order->update([
                'order_no' => now()->format('Ymd') . '-' .
                            str_pad($order->id, 7, '0', STR_PAD_LEFT)
            ]);

            /*
            |--------------------------------------------------------------------------
            | 5. Copy Session Heads → Order Heads
            |--------------------------------------------------------------------------
            */

            foreach ($tableSession->headCounts as $head) {

                OrderHead::create([
                    'order_id'            => $order->id,
                    'head_pricing_rule_id'=> $head->head_pricing_rule_id,
                    'quantity'            => $head->qty,
                    'price_snapshot'      => $head->price_snapshot,
                    'subtotal'            => $head->subtotal,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 6. Close Table Session
            |--------------------------------------------------------------------------
            */

            $tableSession->update([
                'cashier_id'   => auth()->id(),
                'order_id'     => $order->id,
                'total_amount' => $total,
            ]);

            return $order;
        });

        return response()->json([
            'success'  => true,
            'order_no' => $order->order_no
        ]);
    }

    // public function storeOrderedItems(Request $request) //03102026
    // {
    //     Log::info('store ordered items');
    //     Log::info($request->all());

    //     $request->validate([
    //         'cart'                         => 'required|array|min:1',
    //         'cart.*.id'                    => 'required|exists:inventory_items,id',
    //         'cart.*.qty'                   => 'required|numeric|min:1',
    //         'payment_method'               => 'required|in:Cash,GCash,Card',
    //         'cash_amount'                  => 'nullable|numeric|min:0',
    //         'senior_pwd_discount_approved' => 'nullable|boolean',
    //         'total_discount'               => 'nullable|numeric|min:0',
    //         'approving_manager_id'         => 'nullable|exists:users,id',
    //     ]);

    //     $order = DB::transaction(function () use ($request) {

    //         $cart = collect($request->cart);

    //         /*
    //         |--------------------------------------------------------------------------
    //         | 1. Load items from DB and lock for update
    //         |--------------------------------------------------------------------------
    //         */
    //         $itemIds = $cart->pluck('id')->unique()->values();

    //         $items = InventoryItem::whereIn('id', $itemIds)
    //             ->lockForUpdate()
    //             ->get()
    //             ->keyBy('id');

    //         if ($items->count() !== $itemIds->count()) {
    //             throw new \Exception('Some items were not found.');
    //         }

    //         /*
    //         |--------------------------------------------------------------------------
    //         | 2. Compute subtotal from cart and validate stock
    //         |--------------------------------------------------------------------------
    //         */
    //         $subtotal = 0;

    //         foreach ($cart as $cartItem) {
    //             $dbItem = $items->get($cartItem['id']);

    //             if (!$dbItem) {
    //                 throw new \Exception("Item not found: {$cartItem['id']}");
    //             }

    //             $qty = (float) ($cartItem['qty'] ?? 0);

    //             if ($qty <= 0) {
    //                 throw new \Exception("Invalid quantity for item {$dbItem->name}");
    //             }

    //             $currentQty = (float) $dbItem->current_quantity;

    //             if ($qty > $currentQty) {
    //                 throw new \Exception("Insufficient stock for item {$dbItem->name}. Available: {$currentQty}");
    //             }

    //             $unitPrice = (float) $dbItem->unit_price;
    //             $lineSubtotal = $unitPrice * $qty;

    //             $subtotal += $lineSubtotal;
    //         }

    //         $totalDiscount = (float) ($request->total_discount ?? 0);
    //         $total = max($subtotal - $totalDiscount, 0);

    //         /*
    //         |--------------------------------------------------------------------------
    //         | 3. Cash validation
    //         |--------------------------------------------------------------------------
    //         */
    //         $cashAmount = $request->filled('cash_amount')
    //             ? (float) $request->cash_amount
    //             : null;

    //         $changeAmount = null;

    //         if ($request->payment_method === 'Cash') {
    //             $cashAmount = $cashAmount ?? 0;

    //             if ($cashAmount < $total) {
    //                 throw new \Exception('Insufficient cash.');
    //             }

    //             $changeAmount = $cashAmount - $total;
    //         }

    //         /*
    //         |--------------------------------------------------------------------------
    //         | 4. Create order
    //         |--------------------------------------------------------------------------
    //         */
    //         $order = Order::create([
    //             'user_id'                        => auth()->id(),
    //             'table_session_id'               => null,
    //             'subtotal'                       => $subtotal,
    //             'total_discount'                 => $totalDiscount,
    //             'total'                          => $total,
    //             'payment_method'                 => $request->payment_method,
    //             'cash_amount'                    => $request->payment_method === 'Cash' ? $cashAmount : null,
    //             'change_amount'                  => $changeAmount,
    //             'discount_type'                  => $request->boolean('senior_pwd_discount_approved') ? 'SC/PWD' : null,
    //             'table_number'                   => null,
    //             'voucher_no_used'                => null,
    //             'voucher_discount_used'          => 0,
    //             'discount_approving_manager_id'  => $request->approving_manager_id ?? null,
    //         ]);

    //         /*
    //         |--------------------------------------------------------------------------
    //         | 5. Generate order number
    //         |--------------------------------------------------------------------------
    //         */
    //         $order->update([
    //             'order_no' => now()->format('Ymd') . '-' . str_pad($order->id, 7, '0', STR_PAD_LEFT)
    //         ]);

    //         /*
    //         |--------------------------------------------------------------------------
    //         | 6. Save ordered items, deduct stock, and record inventory movement
    //         |--------------------------------------------------------------------------
    //         */
    //         foreach ($cart as $cartItem) {
    //             $dbItem = $items->get($cartItem['id']);

    //             $qty = (float) ($cartItem['qty'] ?? 0);
    //             $unitPrice = (float) $dbItem->unit_price;
    //             $lineSubtotal = $unitPrice * $qty;

    //             OrderItem::create([
    //                 'order_id'          => $order->id,
    //                 'inventory_item_id' => $dbItem->id,
    //                 'item_name'         => $dbItem->name,
    //                 'unit'              => $dbItem->unit,
    //                 'quantity'          => $qty,
    //                 'price'             => $unitPrice,
    //                 'subtotal'          => $lineSubtotal,
    //             ]);

    //             $newQuantity = (float) $dbItem->current_quantity - $qty;

    //             $dbItem->update([
    //                 'current_quantity' => $newQuantity,
    //             ]);

    //             InventoryMovement::create([
    //                 'inventory_item_id' => $dbItem->id,
    //                 'type'              => 'stockout',
    //                 'quantity'          => $qty,
    //                 'unit_price'        => $unitPrice,
    //                 'note'              => 'Ordered item - Order No: ' . $order->order_no,
    //                 'created_by'        => auth()->id(),
    //                 'updated_by'        => null,
    //             ]);
    //         }

    //         return $order->fresh();
    //     });

    //     return response()->json([
    //         'success'  => true,
    //         'message'  => 'Order successfully placed.',
    //         'order_id' => $order->id,
    //         'order_no' => $order->order_no,
    //     ]);
    // }

    // public function storeOrderedItems(Request $request) // updating the orders table which is not needed
    // {
    //     Log::info('store ordered items');
    //     Log::info($request->all());

    //     $request->validate([
    //         'table_session_id'              => 'required|exists:table_sessions,id',
    //         'cart'                          => 'required|array|min:1',
    //         'cart.*.id'                     => 'required|exists:inventory_items,id',
    //         'cart.*.qty'                    => 'required|numeric|min:1',
    //         'payment_method'                => 'required|in:Cash,GCash,Card',
    //         'cash_amount'                   => 'nullable|numeric|min:0',
    //         'senior_pwd_discount_approved'  => 'nullable|boolean',
    //         'total_discount'                => 'nullable|numeric|min:0',
    //         'approving_manager_id'          => 'nullable|exists:users,id',
    //     ]);

    //     $order = DB::transaction(function () use ($request) {

    //         $tableSession = TableSession::lockForUpdate()->findOrFail($request->table_session_id);

    //         if (!$tableSession->order_id) {
    //             throw new \Exception('Selected table session has no existing order.');
    //         }

    //         $order = Order::lockForUpdate()->findOrFail($tableSession->order_id);

    //         $cart = collect($request->cart);

    //         /*
    //         |--------------------------------------------------------------------------
    //         | 1. Load inventory items with lock
    //         |--------------------------------------------------------------------------
    //         */
    //         $itemIds = $cart->pluck('id')->unique()->values();

    //         $items = InventoryItem::whereIn('id', $itemIds)
    //             ->lockForUpdate()
    //             ->get()
    //             ->keyBy('id');

    //         if ($items->count() !== $itemIds->count()) {
    //             throw new \Exception('Some items were not found.');
    //         }

    //         /*
    //         |--------------------------------------------------------------------------
    //         | 2. Compute add-on subtotal and validate stock
    //         |--------------------------------------------------------------------------
    //         */
    //         $addonSubtotal = 0;

    //         foreach ($cart as $cartItem) {
    //             $dbItem = $items->get($cartItem['id']);

    //             if (!$dbItem) {
    //                 throw new \Exception("Item not found: {$cartItem['id']}");
    //             }

    //             $qty = (float) ($cartItem['qty'] ?? 0);

    //             if ($qty <= 0) {
    //                 throw new \Exception("Invalid quantity for item {$dbItem->name}");
    //             }

    //             $currentQty = (float) $dbItem->current_quantity;

    //             if ($qty > $currentQty) {
    //                 throw new \Exception("Insufficient stock for item {$dbItem->name}. Available: {$currentQty}");
    //             }

    //             $unitPrice = (float) $dbItem->unit_price;
    //             $lineSubtotal = $unitPrice * $qty;

    //             $addonSubtotal += $lineSubtotal;
    //         }

    //         /*
    //         |--------------------------------------------------------------------------
    //         | 3. Add-on discount and totals
    //         |--------------------------------------------------------------------------
    //         */
    //         $addonDiscount = (float) ($request->total_discount ?? 0);
    //         $addonTotal = max($addonSubtotal - $addonDiscount, 0);

    //         /*
    //         |--------------------------------------------------------------------------
    //         | 4. Optional cash validation for cash method
    //         |--------------------------------------------------------------------------
    //         | Since this is attached to an existing order, decide if you want:
    //         | - to validate only add-on payment, or
    //         | - to just record payment method without cash validation
    //         |--------------------------------------------------------------------------
    //         */
    //         $cashAmount = $request->filled('cash_amount')
    //             ? (float) $request->cash_amount
    //             : null;

    //         $changeAmount = null;

    //         if ($request->payment_method === 'Cash') {
    //             $cashAmount = $cashAmount ?? 0;

    //             if ($cashAmount < $addonTotal) {
    //                 throw new \Exception('Insufficient cash.');
    //             }

    //             $changeAmount = $cashAmount - $addonTotal;
    //         }

    //         /*
    //         |--------------------------------------------------------------------------
    //         | 5. Save add-ons, deduct stock, inventory movement
    //         |--------------------------------------------------------------------------
    //         */
    //         foreach ($cart as $cartItem) {
    //             $dbItem = $items->get($cartItem['id']);

    //             $qty = (float) ($cartItem['qty'] ?? 0);
    //             $unitPrice = (float) $dbItem->unit_price;
    //             $lineSubtotal = $unitPrice * $qty;

    //             OrderAddon::create([
    //                 'order_id'          => $order->id,
    //                 'inventory_item_id' => $dbItem->id,
    //                 'item_name'         => $dbItem->name,
    //                 'unit'              => $dbItem->unit,
    //                 'quantity'          => $qty,
    //                 'unit_price'        => $unitPrice,
    //                 'subtotal'          => $lineSubtotal,
    //             ]);

    //             $newQuantity = (float) $dbItem->current_quantity - $qty;

    //             $dbItem->update([
    //                 'current_quantity' => $newQuantity,
    //             ]);

    //             InventoryMovement::create([
    //                 'inventory_item_id' => $dbItem->id,
    //                 'type'              => 'stockout',
    //                 'quantity'          => $qty,
    //                 'unit_price'        => $unitPrice,
    //                 'note'              => 'Order add-on - Order No: ' . $order->order_no,
    //                 'created_by'        => auth()->id(),
    //                 'updated_by'        => null,
    //             ]);
    //         }

    //         /*
    //         |--------------------------------------------------------------------------
    //         | 6. Update existing order totals
    //         |--------------------------------------------------------------------------
    //         */
    //         $order->update([
    //             'subtotal'                      => (float) $order->subtotal + $addonSubtotal,
    //             'total_discount'                => (float) $order->total_discount + $addonDiscount,
    //             'total'                         => (float) $order->total + $addonTotal,
    //             'payment_method'                => $request->payment_method,
    //             'cash_amount'                   => $request->payment_method === 'Cash' ? $cashAmount : $order->cash_amount,
    //             'change_amount'                 => $request->payment_method === 'Cash' ? $changeAmount : $order->change_amount,
    //             'discount_type'                 => $request->boolean('senior_pwd_discount_approved') ? 'SC/PWD' : $order->discount_type,
    //             'discount_approving_manager_id' => $request->approving_manager_id ?? $order->discount_approving_manager_id,
    //         ]);

    //         /*
    //         |--------------------------------------------------------------------------
    //         | 7. Optional sync table session total amount
    //         |--------------------------------------------------------------------------
    //         */
    //         $tableSession->update([
    //             'total_amount' => $order->total,
    //         ]);

    //         return $order->fresh(['addons']);
    //     });

    //     return response()->json([
    //         'success'  => true,
    //         'message'  => 'Add-ons successfully added to the table order.',
    //         'order_id' => $order->id,
    //         'order_no' => $order->order_no,
    //     ]);
    // }

    public function storeOrderedItems(Request $request)
    {
        Log::info('store ordered items');
        Log::info($request->all());

        $request->validate([
            'table_session_id'              => 'required|exists:table_sessions,id',
            'cart'                          => 'required|array|min:1',
            'cart.*.id'                     => 'required|exists:inventory_items,id',
            'cart.*.qty'                    => 'required|numeric|min:1',
            'payment_method'                => 'nullable|in:Cash,GCash,Card',
            'cash_amount'                   => 'nullable|numeric|min:0',
            'senior_pwd_discount_approved'  => 'nullable|boolean',
            'total_discount'                => 'nullable|numeric|min:0',
            'approving_manager_id'          => 'nullable|exists:users,id',
        ]);

        $order = DB::transaction(function () use ($request) {

            $tableSession = TableSession::lockForUpdate()->findOrFail($request->table_session_id);

            if (!$tableSession->order_id) {
                throw new \Exception('Selected table session has no existing order.');
            }

            $order = Order::lockForUpdate()->findOrFail($tableSession->order_id);

            $cart = collect($request->cart);

            /*
            |--------------------------------------------------------------------------
            | 1. Load inventory items with lock
            |--------------------------------------------------------------------------
            */
            $itemIds = $cart->pluck('id')->unique()->values();

            $items = InventoryItem::whereIn('id', $itemIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($items->count() !== $itemIds->count()) {
                throw new \Exception('Some items were not found.');
            }

            /*
            |--------------------------------------------------------------------------
            | 2. Validate stock only
            |--------------------------------------------------------------------------
            */
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

            /*
            |--------------------------------------------------------------------------
            | 3. Save add-ons, deduct stock, inventory movement
            |--------------------------------------------------------------------------
            */
            foreach ($cart as $cartItem) {
                $dbItem = $items->get($cartItem['id']);

                $qty = (float) ($cartItem['qty'] ?? 0);
                $unitPrice = (float) $dbItem->unit_price;
                $lineSubtotal = $unitPrice * $qty;

                OrderAddon::create([
                    'order_id'          => $order->id,
                    'inventory_item_id' => $dbItem->id,
                    'item_name'         => $dbItem->name,
                    'unit'              => $dbItem->unit,
                    'quantity'          => $qty,
                    'unit_price'        => $unitPrice,
                    'subtotal'          => $lineSubtotal,
                ]);

                $dbItem->update([
                    'current_quantity' => (float) $dbItem->current_quantity - $qty,
                ]);

                InventoryMovement::create([
                    'inventory_item_id' => $dbItem->id,
                    'type'              => 'stockout',
                    'quantity'          => $qty,
                    'unit_price'        => $unitPrice,
                    'note'              => 'Order add-on - Order No: ' . $order->order_no,
                    'created_by'        => auth()->id(),
                    'updated_by'        => null,
                ]);
            }

            return $order->fresh(['addons']);
        });

        return response()->json([
            'success'  => true,
            'message'  => 'Add-ons successfully added to the table order.',
            'order_id' => $order->id,
            'order_no' => $order->order_no,
        ]);
    }

    public function store_orig(Request $request)
    {
        // Log::info('Store request received', [
        //     'user_id' => auth()->id(),
        //     'payload' => $request->all(),
        // ]);

        $request->validate([
            'cart'             => 'required|array|min:1',
            'payment_method'   => 'required|string|in:Cash,GCash,Card',
            'cash_amount'      => 'nullable|numeric|min:0',
            'table_session_id' => 'required|exists:table_sessions,id',
            'voucher_code'     => 'nullable|string',
        ]);

        $cart        = $request->cart;
        $voucherCode = $request->voucher_code;

        $order = null;

        DB::transaction(function () use ($cart, $request, &$order, $voucherCode) {

            /*
            |--------------------------------------------------------------------------
            | 1. Compute Subtotal
            |--------------------------------------------------------------------------
            */
            $subtotal = collect($cart)->reduce(function ($sum, $item) {

                $itemPrice    = (float) $item['price'];
                $itemQty      = (int) $item['qty'];
                $itemDiscount = isset($item['discount'])
                    ? (float) $item['discount']
                    : 0;

                return $sum + (($itemPrice - $itemDiscount) * $itemQty);

            }, 0);


            /*
            |--------------------------------------------------------------------------
            | 2. Validate & Lock Voucher (if provided)
            |--------------------------------------------------------------------------
            */
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


            /*
            |--------------------------------------------------------------------------
            | 3. Compute Voucher Discount (BACKEND SAFE)
            |--------------------------------------------------------------------------
            */
            $totalDiscount = 0;

            if ($voucher) {

                // Percentage voucher (20%, 30%, 40%, etc.)
                if (str_contains($voucher->type, '%')) {

                    $percent = (float) str_replace('%', '', $voucher->type);
                    $totalDiscount = $subtotal * ($percent / 100);
                }

                // Free Meal voucher
                elseif ($voucher->type === 'Free Meal') {
                    $totalDiscount = $subtotal;
                }
            }

            $total = $subtotal - $totalDiscount;

            if ($total < 0) {
                $total = 0;
            }


            /*
            |--------------------------------------------------------------------------
            | 4. Payment Handling
            |--------------------------------------------------------------------------
            */
            $paymentMethod = $request->payment_method;
            $cashAmount    = $request->cash_amount;
            $changeAmount  = null;

            if ($paymentMethod === 'Cash') {

                if ($cashAmount === null || $cashAmount < $total) {
                    throw new \Exception('Insufficient cash amount.');
                }

                $changeAmount = $cashAmount - $total;
            }


            /*
            |--------------------------------------------------------------------------
            | 5. Create Order
            |--------------------------------------------------------------------------
            */
            $order = Order::create([
                'user_id'               => auth()->id(),
                'subtotal'              => $subtotal,
                'total_discount'        => $totalDiscount,
                'total'                 => $total,
                'payment_method'        => $paymentMethod,
                'cash_amount'           => $paymentMethod === 'Cash' ? $cashAmount : null,
                'change_amount'         => $changeAmount,
                'voucher_no_used'       => $voucherCode,
                'voucher_discount_used' => $totalDiscount,
                'table_number'          => $request->table_session_id,
                'discount_approving_manager_id'          => $request->approving_manager,
            ]);


            /*
            |--------------------------------------------------------------------------
            | 6. Update Table Session
            |--------------------------------------------------------------------------
            */
            $tableSession = TableSession::findOrFail($request->table_session_id);

            $tableSession->update([
                'cashier_id'   => auth()->id(),
                'order_id'     => $order->id,
                'total_amount' => $total,
            ]);


            /*
            |--------------------------------------------------------------------------
            | 7. Generate Order Number
            |--------------------------------------------------------------------------
            */
            $datePrefix = now()->format('Ymd');
            $orderNo    = $datePrefix . '-' . str_pad($order->id, 7, '0', STR_PAD_LEFT);

            $order->update([
                'order_no' => $orderNo
            ]);


            /*
            |--------------------------------------------------------------------------
            | 8. Create Order Items
            |--------------------------------------------------------------------------
            */
            foreach ($cart as $item) {

                OrderItem::create([
                    'order_id'  => $order->id,
                    'menu_id'   => $item['id'],
                    'quantity'  => (int) $item['qty'],
                    'price'     => (float) $item['price'],
                    'discount'  => $item['discount'] ?? 0,
                    'cancelled' => false,
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | 9. Mark Voucher as Used
            |--------------------------------------------------------------------------
            */
            if ($voucher) {

                $voucher->update([
                    'used_by_order_id' => $order->id,
                    'used_at'          => now(),
                    'status'           => 'used',
                ]);
            }
        });

        return response()->json([
            'success'  => true,
            'message'  => 'Order placed successfully!',
            'order_id' => $order->id,
            'order_no' => $order->order_no
        ]);
    }





    // Show single order
    public function show(Order $order)
    {
        return Inertia::render('Orders/Show', [
            'order' => $order->load('items.menu')
        ]);
    }

    // Cancel whole order
    public function destroy(Order $order)
    {
        $order->update(['status' => 'cancelled']);
        $order->items()->update(['cancelled' => true]);

        return redirect()->back();
    }

    // Cancel a specific item
    // public function cancelItem(Order $order, $itemId)
    // {
    //     $item = $order->items()->findOrFail($itemId);
    //     $item->update(['cancelled' => true]);

    //     return redirect()->back();
    // }

    public function cancelItem(Order $order, $itemId)
    {
        $managerId = request('manager_id');
        $password  = request('manager_password');

        $manager = \App\Models\User::where('id', $managerId)
            ->where('role', 1) // change if role is string
            ->first();

        if (!$manager || !\Hash::check($password, $manager->password)) {
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


    // public function cancel(Order $order): RedirectResponse
    // {
    //     Log::info('OrderController@cancel triggered');
    //     Log::info('Cancelling order ID: ' . $order);
    //     $managerPassword = request()->input('manager_password');

    //     // Validate manager password
    //     $manager = User::where('role', '1')->first();

    //     if (!$manager || !\Hash::check($managerPassword, $manager->password)) {
    //         return back()->with('error', 'Invalid manager password.');
    //     }

    //     $reason = request()->input('reason');

    //     if ($order->status === 'cancelled') {
    //         return back()->with('error', 'Order already cancelled.');
    //     }

    //     $order->load('items.menu');

    //     $order->update([
    //         'status' => 'cancelled',
    //         'cancelled' => 1,
    //         'cancelled_by' => auth()->id(),
    //         'cancellation_remarks' => $reason,
    //     ]);

    //     return back()->with('success', 'Order cancelled successfully.');
    // }

    public function cancel(Order $order): RedirectResponse
    {
        // Log::info('OrderController@cancel triggered');
        // Log::info('Cancelling order ID: ' . $order->id);
        // Log::info('Request data', request()->all());

        $managerId = request()->input('manager');
        $managerPassword = request()->input('manager_password');

        $manager = User::where('id', $managerId)
            ->where('role', 1) // manager role
            ->first();

        if (!$manager || !\Hash::check($managerPassword, $manager->password)) {
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
        // Log::info('OrderController@cancelItem triggered');
        // Log::info('Cancelling order item ID: ' . $orderItem->id);
        // Log::info('Request data', request()->all());

        $order = $orderItem->order;

        // Safety: block if parent order already cancelled
        if ($order->status === 'cancelled') {
            return back()->with('error', 'Cannot cancel item from a cancelled order.');
        }

        // Check if item already cancelled
        if ($orderItem->cancelled) {
            return back()->with('error', 'Item already cancelled.');
        }

        // --- Manager Approval ---
        $managerIds = request()->input('manager_id'); // can be single or array
        $managerPassword = request()->input('manager_password');

        if (!$managerIds || !$managerPassword) {
            return back()->with('error', 'Manager approval required.');
        }

        // Only check first manager for simplicity
        $manager = User::whereIn('id', (array)$managerIds)
                        ->where('role', '1') // role 1 = manager
                        ->first();

        if (!$manager || !\Hash::check($managerPassword, $manager->password)) {
            return back()->with('error', 'Invalid manager credentials.');
        }

        // --- Cancel the item ---
        $orderItem->update([
            'cancelled' => 1,
            'cancelled_by' => auth()->id(),
            'manager_id' => $manager->id,
            'cancellation_remarks' => request()->input('reason') ?? 'Cancelled by manager'
        ]);

        // Reload items to check remaining active items
        $order->load('items');
        $activeItems = $order->items->where('cancelled', 0);

        // Compute subtotal and discount for remaining items
        $subtotal = $activeItems->sum(fn($item) => $item->price * $item->quantity);

        $total_discount = 0;
        if ($order->discount_type && $order->discount_type !== 'None') {
            if ($activeItems->first()?->discount !== null) {
                $total_discount = $activeItems->sum(fn($item) => $item->discount * $item->quantity);
            } else {
                $discountRate = match($order->discount_type) {
                    'PWD/Senior' => 0.20,
                    'Employee'   => 0.10,
                    default      => 0,
                };
                $total_discount = $subtotal * $discountRate;
            }
        }

        $total = max($subtotal - $total_discount, 0);

        // Update order totals
        $updateData = [
            'subtotal'       => $subtotal,
            'total_discount' => $total_discount,
            'total'          => $total,
        ];

        // If no active items left, mark the order as cancelled
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
            'format' => [80, 180],
            'margin_left' => 4,
            'margin_right' => 4,
            'margin_top' => 4,
            'margin_bottom' => 4,
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

        $total_sales = Order::whereDate('created_at', $today)
            ->where('status', 'paid')
            ->sum('total');
        $running_bill = DB::table('table_session_heads')
            ->whereIn('table_session_id', function ($query) {
                $query->select('id')
                    ->from('table_sessions')
                    ->where('status', 'open');
            })
            ->sum('subtotal');
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
            'total_sales' => $total_sales,
            'running_bill' => $running_bill,
            'total_unbilled' => $total_unbilled,
        ]);
    }

    public function storeLeftover(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'weight'   => 'required|numeric|min:0.01',
            'amount'   => 'required|numeric|min:0',
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

}
