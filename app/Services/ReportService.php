<?php

namespace App\Services;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalesExport;
use Mpdf\Mpdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Exports\SalesReportExport;

class ReportService
{
    public function index(Request $request)
    {
        $type = $request->type ?? 'daily';

        $start = $request->start_date
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::today()->startOfDay();

        $end = $request->end_date
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::today()->endOfDay();

        $query = Order::whereBetween('created_at', [$start, $end]);

        if ($type === 'monthly') {
            $orders = $query
                ->selectRaw('DATE(created_at) as date, SUM(total) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        } else {
            $orders = $query
                ->orderBy('created_at')
                ->get();
        }

        Log::info('ReportController@index - Orders fetched', [
            'type' => $type,
            'start' => $start->toDateTimeString(),
            'end' => $end->toDateTimeString(),
            'orders_count' => $orders->count(),
        ]);

        $grandTotal = $orders->sum('total');

        return Inertia::render('Reports/SalesReport', [
            'sales' => $orders,
            'grandTotal' => $grandTotal,
            'filters' => [
                'type' => $type,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ],
        ]);
    }

    public function exportPdf(Request $request)
    {
        $type = $request->type ?? 'daily';
        $start = $request->start_date;
        $end   = $request->end_date;

        $query = Order::where('status', 'paid');

        if ($start && $end) {
            $query->whereBetween('created_at', [
                $start . ' 00:00:00',
                $end . ' 23:59:59',
            ]);
        } else {
            $query->whereDate('created_at', now()->toDateString());
            $start = $end = now()->toDateString();
        }

        if ($type === 'daily') {
            $sales = $query->with('items')->orderBy('created_at')->get();
        } else {
            $orders = $query->get();
            $sales = $orders
                ->groupBy(fn($o) => $o->created_at->format('Y-m-d'))
                ->map(fn($dayOrders, $date) => [
                    'date' => $date,
                    'total' => $dayOrders->sum(fn($o) => (float) $o->total),
                ])
                ->values()
                ->toArray();
        }

        $grandTotal = $type === 'daily'
            ? $sales->sum('total')
            : collect($sales)->sum('total');

        $html = view('reports.sales-pdf', compact(
            'sales',
            'grandTotal',
            'start',
            'end',
            'type'
        ))->render();

        $mpdf = new \Mpdf\Mpdf([
            'format' => 'A4',
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);

        $mpdf->WriteHTML($html);

        return response($mpdf->Output('sales-report.pdf', 'S'))
            ->header('Content-Type', 'application/pdf');
    }

    public function exportExcel()
    {
        return Excel::download(new SalesExport, 'sales-report.xlsx');
    }

    public function fetchInventoryReport(Request $request)
    {
        \Log::info('Inventory General Report (JSON)');
        \Log::info($request->all());

        $reportType = $request->query('type', 'all');
        $startDate  = $request->query('start_date');
        $endDate    = $request->query('end_date');
        $categoryId = $request->query('category_id');
        $itemType   = $request->query('item_type');

        $today = now()->toDateString();

        if (empty($startDate) || empty($endDate) || $startDate === $endDate) {
            $reportDate = $startDate ?? $today;
            $start = $reportDate;
            $end   = $reportDate;
        } else {
            $start = $startDate;
            $end   = $endDate;
        }

        $itemsQuery = \App\Models\InventoryItem::with('category');

        if ($categoryId) {
            $itemsQuery->where('category_id', $categoryId);
        }

        if ($itemType !== null && $itemType !== '' && $itemType !== 'all') {
            if ($itemType === '0') {
                $itemsQuery->where(function ($query) {
                    $query->where('is_dry', false)
                        ->orWhereNull('is_dry');
                });
            } else {
                $itemsQuery->where('is_dry', true);
            }
        }

        $items = $itemsQuery->get();

        $movementsQuery = \App\Models\InventoryMovement::with('item')
            ->whereBetween('created_at', [
                $start . ' 00:00:00',
                $end . ' 23:59:59'
            ]);

        if ($reportType === 'stockin') {
            $movementsQuery->where('type', 'stockin');
        }

        if ($reportType === 'stockout') {
            $movementsQuery->where('type', 'stockout');
        }

        $movements = $movementsQuery->get();

        $reportData = collect();

        foreach ($items as $item) {
            $itemMovements = $movements
                ->where('inventory_item_id', $item->id);

            $stockInQty  = $itemMovements
                ->where('type', 'stockin')
                ->sum('quantity');

            $stockOutQty = $itemMovements
                ->where('type', 'stockout')
                ->sum('quantity');

            if ($reportType === 'stockin' && $stockInQty <= 0) continue;
            if ($reportType === 'stockout' && $stockOutQty <= 0) continue;

            $reportData->push([
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category->name ?? '',
                'unit' => $item->unit,
                'current_quantity' => $item->current_quantity,
                'stockInQty' => $stockInQty,
                'stockOutQty' => $stockOutQty,
                'unit_price' => $item->unit_price ?? 0,
                'total_cost' => $stockOutQty * ($item->unit_price ?? 0),
            ]);
        }

        Log::info('fetched report');
        Log::info($reportData);

        return response()->json([
            'success' => true,
            'data' => $reportData
        ]);
    }

    public function exportInventoryReportExcel(Request $request)
    {
        $reportType = $request->query('type', 'all');
        $startDate  = $request->query('start_date');
        $endDate    = $request->query('end_date');
        $categoryId = $request->query('category_id');
        $itemType   = $request->query('item_type');

        $today = now()->toDateString();

        if (empty($startDate) || empty($endDate) || $startDate === $endDate) {
            $reportDate = $startDate ?? $today;
            $start = $end = $reportDate;
        } else {
            $start = $startDate;
            $end   = $endDate;
        }

        $itemsQuery = \App\Models\InventoryItem::with('category');

        if ($categoryId) {
            $itemsQuery->where('category_id', $categoryId);
        }

        if ($itemType !== null && $itemType !== '' && $itemType !== 'all') {
            if ($itemType === '0') {
                $itemsQuery->where(function ($q) {
                    $q->where('is_dry', false)->orWhereNull('is_dry');
                });
            } else {
                $itemsQuery->where('is_dry', true);
            }
        }

        $items = $itemsQuery->get();

        $movementsQuery = \App\Models\InventoryMovement::with('item')
            ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59']);

        if ($reportType === 'stockin')  $movementsQuery->where('type', 'stockin');
        if ($reportType === 'stockout') $movementsQuery->where('type', 'stockout');

        $movements = $movementsQuery->get();

        $reportData = collect();

        foreach ($items as $item) {
            $itemMovements = $movements->where('inventory_item_id', $item->id);
            $stockInQty    = $itemMovements->where('type', 'stockin')->sum('quantity');
            $stockOutQty   = $itemMovements->where('type', 'stockout')->sum('quantity');

            if ($reportType === 'stockin'  && $stockInQty  <= 0) continue;
            if ($reportType === 'stockout' && $stockOutQty <= 0) continue;

            $reportData->push([
                'id'               => $item->id,
                'name'             => $item->name,
                'category'         => $item->category->name ?? '',
                'unit'             => $item->unit,
                'current_quantity' => $item->current_quantity,
                'stockInQty'       => $stockInQty,
                'stockOutQty'      => $stockOutQty,
                'unit_price'       => $item->unit_price ?? 0,
                'total_cost'       => $stockOutQty * ($item->unit_price ?? 0),
                'remarks'          => $item->remarks ?? '',
            ]);
        }

        // $filename = 'inventory_report_' . now()->format('Ymd_His') . '.xlsx';
        $filename = 'HSB_Inventory_Meat_' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            // new \App\Exports\InventoryReportExport($reportData, $itemType ?? 'all'),
            // $filename
            new \App\Exports\InventoryReportExport(
                $reportData,
                $itemType ?? 'all',
                auth()->user()->name  // 👈 add this
            ),
            $filename
        );
    }

    public function expenseReportIndex()
    {
        $categories = ExpenseCategory::where('status', 1)->get();

        return Inertia::render('Reports/ExpenseReport', [
            'categories' => $categories,
        ]);
    }

    public function fetchExpenseReport(Request $request)
    {
        $categoryId = $request->query('category_id');
        $startDate  = $request->query('start_date');
        $endDate    = $request->query('end_date');

        $query = Expense::with(['category', 'creator']);

        if ($startDate && $endDate) {
            $query->whereBetween('expense_date', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59',
            ]);
        }

        if ($categoryId) {
            $query->where('expense_category_id', $categoryId);
        }

        $expenses = $query
            ->orderBy('expense_date', 'desc')
            ->get();

        $totalExpenses = $expenses->sum('amount');

        Log::info('totalExpenses');
        Log::info($totalExpenses);
        Log::info($expenses);

        return response()->json([
            'data' => $expenses,
            'total' => $totalExpenses
        ]);
    }

    public function printInventoryReport(Request $request)
    {
        \Log::info('Inventory General Report (PDF)');
        \Log::info($request->all());

        $reportType = $request->query('type', 'all');
        $startDate  = $request->query('start_date');
        $endDate    = $request->query('end_date');
        $categoryId = $request->query('category_id');

        $today = now()->toDateString();

        if (empty($startDate) || empty($endDate) || $startDate === $endDate) {
            $reportDate = $startDate ?? $today;
            $start = $reportDate;
            $end   = $reportDate;
            $isDaily = true;
        } else {
            $start = $startDate;
            $end   = $endDate;
            $isDaily = false;
        }

        $itemsQuery = \App\Models\InventoryItem::with('category');

        if ($categoryId) {
            $itemsQuery->where('category_id', $categoryId);
        }

        $items = $itemsQuery->get();

        $movementsQuery = \App\Models\InventoryMovement::with(['item', 'creator'])
            ->whereBetween('created_at', [
                $start . ' 00:00:00',
                $end . ' 23:59:59'
            ]);

        if ($reportType === 'stockin') {
            $movementsQuery->where('type', 'stockin');
        }

        if ($reportType === 'stockout') {
            $movementsQuery->where('type', 'stockout');
        }

        $movements = $movementsQuery->get();

        $reportData = collect();

        foreach ($items as $item) {
            $itemMovements = $movements
                ->where('inventory_item_id', $item->id);

            $stockInQty  = $itemMovements
                ->where('type', 'stockin')
                ->sum('quantity');

            $stockOutQty = $itemMovements
                ->where('type', 'stockout')
                ->sum('quantity');

            if ($reportType === 'stockin' && $stockInQty <= 0) continue;
            if ($reportType === 'stockout' && $stockOutQty <= 0) continue;

            $reportData->push([
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category->name ?? '',
                'unit' => $item->unit,
                'current_quantity' => $item->current_quantity,
                'stockInQty' => $stockInQty,
                'stockOutQty' => $stockOutQty,
                'unit_price' => $item->unit_price ?? 0,
                'total_cost' => $stockOutQty * ($item->unit_price ?? 0),
                'creator_name' => $itemMovements->first()?->creator?->name ?? '-',
            ]);
        }

        \Log::info('Prepared report data', ['count' => $reportData->count()]);

        $mpdf = new \Mpdf\Mpdf([
            'format' => 'A4',
            'margin_top' => 15,
            'margin_bottom' => 15,
        ]);

        $html = view('reports.InventoryReport', [
            'reportData' => $reportData,
            'reportType' => $reportType,
            'start' => $start,
            'end' => $end,
            'isDaily' => $isDaily,
            'user' => auth()->user(),
        ])->render();

        $mpdf->WriteHTML($html);

        $filename = $isDaily
            ? "daily_inventory_report_{$start}.pdf"
            : "inventory_report_{$start}_to_{$end}.pdf";

        return $mpdf->Output($filename, 'I');
    }

    public function printExpensesReport(Request $request)
    {
        $categoryId = $request->query('category_id');
        $startDate  = $request->query('start_date');
        $endDate    = $request->query('end_date');

        $query = Expense::with(['category', 'creator']);

        if ($startDate && $endDate) {
            $query->whereBetween('expense_date', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59',
            ]);
        }

        if ($categoryId) {
            $query->where('expense_category_id', $categoryId);
        }

        $expenses = $query
            ->orderBy('expense_date', 'desc')
            ->get();

        $totalExpenses = $expenses->sum('amount');

        $reportType = 'custom';
        $isDaily = false;

        if ($startDate && $endDate && $startDate === $endDate) {
            $reportType = 'daily';
            $isDaily = true;
        }

        $html = view('reports.ExpensesReport', [
            'reportData' => $expenses,
            'reportType' => $reportType,
            'start' => $startDate,
            'end' => $endDate,
            'isDaily' => $isDaily,
            'user' => auth()->user(),
            'totalExpenses' => $totalExpenses
        ])->render();

        $mpdf = new Mpdf([
            'format' => 'A4',
            'orientation' => 'P',
        ]);

        $mpdf->SetTitle('Expenses Report');
        $mpdf->WriteHTML($html);

        return response($mpdf->Output('expenses-report.pdf', 'I'))
            ->header('Content-Type', 'application/pdf');
    }

    public function salesReportIndex()
    {
        $today = now()->toDateString();
        Log::info($today);
        
        $cashiers = User::where('role', 2)->get();
        Log::info('cashiers: ' . $cashiers->count());
        Log::info($cashiers);
        $shifts = \App\Models\Shift::all();

        $orders = Order::with([
                'user',
                'tableSession.table',
                'orderHeads.headPricingRule',
                'payments.paymentMethod'
            ])
            ->whereDate('created_at', $today)
            ->where('status', 'paid')
            ->orderBy('created_at', 'desc')
            ->get();
        
        Log::info('orders: ' . $orders);
        $consumedAddons = [];
        foreach ($orders as $order) {
            $order->total_addons = $order->addons->sum('subtotal');

            foreach ($order->addons as $addon) {

                $itemName = $addon->item_name;

                // Initialize item if not existing
                if (!isset($consumedAddons[$itemName])) {
                    $consumedAddons[$itemName] = [
                        'item_name' => $itemName,
                        'quantity'  => 0,
                        'unit_price'=> $addon->unit_price,
                        'total'     => 0,
                    ];
                }

                // Accumulate quantity
                $consumedAddons[$itemName]['quantity'] += $addon->quantity;

                // Accumulate total
                $consumedAddons[$itemName]['total'] += (
                    $addon->unit_price * $addon->quantity
                );
            }
        }
        $consumedAddons = array_values($consumedAddons);

        $cashier_expenses = Expense::whereNotNull('shift_id')
            ->whereDate('expense_date', now()->toDateString())
            ->sum('amount');

        $userId = Auth::id(); // capture once, reuse below

        $totalCashSales = DB::table('orders as ord')
            ->join('order_payments as ordp', 'ordp.order_id', '=', 'ord.id')
            ->join('payment_methods as pm', 'pm.id', '=', 'ordp.payment_method_id')
            ->where('pm.id', 1) // Cash
            ->where('ordp.is_void', 0)
            ->where('ord.status', 'paid')
            ->whereIn('ord.shift_id', [1, 2]) 
            ->whereDate('ord.created_at', $today)
            ->sum('ordp.amount');

        $totalGcashSales = DB::table('orders as ord')
            ->join('order_payments as ordp', 'ordp.order_id', '=', 'ord.id')
            ->join('payment_methods as pm', 'pm.id', '=', 'ordp.payment_method_id')
            ->where('pm.id', 2) // GCash
            ->where('ordp.is_void', 0)
            ->where('ord.status', 'paid')
            ->whereIn('ord.shift_id', [1, 2]) 
            ->whereDate('ord.created_at', $today)
            ->sum('ordp.amount');
        $totalMayaSales = DB::table('orders as ord')
            ->join('order_payments as ordp', 'ordp.order_id', '=', 'ord.id')
            ->join('payment_methods as pm', 'pm.id', '=', 'ordp.payment_method_id')
            ->where('pm.id', 3) // Maya
            ->where('ordp.is_void', 0)
            ->where('ord.status', 'paid')
            ->whereIn('ord.shift_id', [1, 2]) 
            ->whereDate('ord.created_at', $today)
            ->sum('ordp.amount');

        $totalReservationFee = DB::table('orders as ord')
            ->join('order_payments as ordp', 'ordp.order_id', '=', 'ord.id')
            ->join('payment_methods as pm', 'pm.id', '=', 'ordp.payment_method_id')
            ->where('pm.id', 13) // Reservation Fee
            ->where('ordp.is_void', 0)
            ->where('ord.status', 'paid')
            ->whereIn('ord.shift_id', [1, 2]) 
            ->whereDate('ord.created_at', $today)
            ->sum('ordp.amount');

        // ── Payment totals by method (today, all shifts) ──────────────────
        $allPayments = DB::table('orders as ord')
            ->join('order_payments as ordp', 'ordp.order_id', '=', 'ord.id')
            ->join('payment_methods as pm', 'pm.id', '=', 'ordp.payment_method_id')
            ->where('ordp.is_void', 0)
            ->where('ord.status', 'paid')
            ->whereIn('ord.shift_id', [1, 2])
            ->whereDate('ord.created_at', $today)
            ->select('pm.name as payment_method_name', DB::raw('SUM(ordp.amount) as total'))
            ->groupBy('pm.id', 'pm.name')
            ->get();

        $payments = $allPayments->pluck('total', 'payment_method_name')->toArray();
        Log::info('payments', $payments);

        
        // $totalGcashSales += $totalReservationFee;
        Log::info('Reservation Fee: ---'. $totalReservationFee);

        Log::info('totalCashSales: ' . $totalCashSales);
        Log::info('totalGcashSales: ' . $totalGcashSales);

        return Inertia::render('Reports/SalesReport', [
            'shifts' => $shifts,
            'orders' => $orders,
            'startDate' => $today,
            'endDate' => $today,
            'cashiers' => $cashiers,
            'selectedStatus' => 'paid',
            'totalCashierExpenses' => (float)$cashier_expenses,
            'totalCashSales' => (float)$totalCashSales,
            'totalGcashSales' => (float)$totalGcashSales,
            'totalMayaSales' => (float)$totalMayaSales,
            'totalReservationFees' => (float)$totalReservationFee,
            'consumedAddons' => $consumedAddons,
            'payments' => $payments,
        ]);
    }

    public function fetchSalesReport(Request $request)
    {
        Log::info($request->only([
            'start_date',
            'end_date',
            'status',
            'cashier_id',
            'shift_id',
        ]));

        $cashier_expenses = Expense::when($request->shift_id !== null && $request->shift_id !== 'All', function ($query) use ($request) {
                $query->where('shift_id', $request->shift_id);
            })
            ->when(!is_null($request->cashier_id) && $request->cashier_id !== '', function ($query) use ($request) {
                $query->where('created_by', $request->cashier_id);
            })
            ->when($request->start_date && $request->end_date, function ($query) use ($request) {
                $query->whereBetween('expense_date', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59',
                ]);
            })
            ->sum('amount');
        
        $allPayments = DB::table('orders as ord')
            ->join('order_payments as ordp', 'ordp.order_id', '=', 'ord.id')
            ->join('payment_methods as pm', 'pm.id', '=', 'ordp.payment_method_id')
            ->where('ordp.is_void', 0)
            ->where('ord.status', 'paid')
            ->when($request->start_date && $request->end_date, function ($query) use ($request) {
                $query->whereBetween('ord.created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59',
                ]);
            })
            ->when(!is_null($request->cashier_id) && $request->cashier_id !== '', function ($query) use ($request) {
                $query->where('ord.user_id', $request->cashier_id);
            })
            ->when(
                $request->filled('shift_id') && $request->shift_id !== 'All',
                function ($query) use ($request) {
                    $query->where('ord.shift_id', $request->shift_id);
                }
            )
            ->select('pm.name as payment_method_name', DB::raw('SUM(ordp.amount) as total'))
            ->groupBy('pm.id', 'pm.name')
            ->get();

        // Result: [ 'gcash' => 500, 'maya' => 6000, 'cash' => 900 ]
        $payments = $allPayments->pluck('total', 'payment_method_name')->toArray();
        Log::info('payments');
        Log::info($payments);
        
        $allFrontdoorIds = User::where('role', 3)->pluck('id')->toArray();
        $targetUserIds = collect($allFrontdoorIds);
        $totalReservationFee = DB::table('orders as ord')
            ->join('order_payments as ordp', 'ordp.order_id', '=', 'ord.id')
            ->join('payment_methods as pm', 'pm.id', '=', 'ordp.payment_method_id')
            ->leftJoin('reservations as res', 'res.id', '=', 'ord.reservation_id') // ← also remove this if res.* isn't used anywhere else in the query
            ->where('pm.id', 13)
            ->where('ordp.is_void', 0)
            ->where('ord.status', 'paid')
            ->whereNull('ordp.remarks')
            ->when($request->start_date && $request->end_date, function ($query) use ($request) {
                $query->whereBetween('ord.created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59',
                ]);
            })
            ->when(!empty($allFrontdoorIds), function ($query) use ($allFrontdoorIds) {
                $query->whereIn('ord.user_id', $allFrontdoorIds);
            })
            ->when(!is_null($request->shift_id) && $request->shift_id !== 'All', function ($query) use ($request) {
                $query->where('ord.shift_id', $request->shift_id);
            })
            ->sum('ordp.amount');
        
        $totalCashSales = DB::table('orders as ord')
            ->join('order_payments as ordp', 'ordp.order_id', '=', 'ord.id')
            ->join('payment_methods as pm', 'pm.id', '=', 'ordp.payment_method_id')
            ->where('pm.id', 1) // Cash
            ->where('ordp.is_void', 0)
            ->where('ord.status', 'paid')
            ->when($request->start_date && $request->end_date, function ($query) use ($request) {
                $query->whereBetween('ord.created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59',
                ]);
            })
            ->when(!is_null($request->cashier_id) && $request->cashier_id !== '', function ($query) use ($request) {
                $query->where('ord.user_id', $request->cashier_id);
            })
            ->when(
            $request->filled('shift_id') && $request->shift_id !== 'All',
                function ($query) use ($request) {
                    $query->where('ord.shift_id', $request->shift_id);
                }
            )
            ->sum('ordp.amount');

        $totalGcashSales = DB::table('orders as ord')
            ->join('order_payments as ordp', 'ordp.order_id', '=', 'ord.id')
            ->join('payment_methods as pm', 'pm.id', '=', 'ordp.payment_method_id')
            ->where('pm.id', 2) // GCash
            ->where('ordp.is_void', 0)
            ->where('ord.status', 'paid')
            ->when($request->start_date && $request->end_date, function ($query) use ($request) {
                $query->whereBetween('ord.created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59',
                ]);
            })
            ->when(!is_null($request->cashier_id) && $request->cashier_id !== '', function ($query) use ($request) {
                $query->where('ord.user_id', $request->cashier_id);
            })
            ->when(
            $request->filled('shift_id') && $request->shift_id !== 'All',
                function ($query) use ($request) {
                    $query->where('ord.shift_id', $request->shift_id);
                }
            )
            ->sum('ordp.amount');

        $totalMayaSales = DB::table('orders as ord')
            ->join('order_payments as ordp', 'ordp.order_id', '=', 'ord.id')
            ->join('payment_methods as pm', 'pm.id', '=', 'ordp.payment_method_id')
            ->where('pm.id', 3) // GCash
            ->where('ordp.is_void', 0)
            ->where('ord.status', 'paid')
            ->when($request->start_date && $request->end_date, function ($query) use ($request) {
                $query->whereBetween('ord.created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59',
                ]);
            })
            ->when(!is_null($request->cashier_id) && $request->cashier_id !== '', function ($query) use ($request) {
                $query->where('ord.user_id', $request->cashier_id);
            })
            ->when(
            $request->filled('shift_id') && $request->shift_id !== 'All',
                function ($query) use ($request) {
                    $query->where('ord.shift_id', $request->shift_id);
                }
            )
            ->sum('ordp.amount');

        $allFrontdoorIds = User::where('role', 3)->pluck('id')->toArray();

        $hasCashierFilter = !is_null($request->cashier_id) && $request->cashier_id !== '';

        if ($hasCashierFilter) {
            $targetUserIds = collect($allFrontdoorIds)
                ->push((int) $request->cashier_id)
                ->unique()
                ->values()
                ->toArray();
        }

        $orders = Order::with([
                'user',
                'tableSession.table',
                'orderHeads.headPricingRule',
                'payments.paymentMethod',
                'addons',
            ])
            ->when($request->start_date && $request->end_date, function ($query) use ($request) {
                $query->whereBetween('created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59',
                ]);
            })
            ->when(!is_null($request->status) && $request->status !== '', function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($hasCashierFilter, function ($query) use ($targetUserIds) {
                $query->whereIn('user_id', $targetUserIds);  // frontdoor IDs + selected cashier
            })
            ->when($request->shift_id !== null && $request->shift_id !== 'All', function ($query) use ($request) {
                $query->where('shift_id', $request->shift_id);
            })

            ->orderBy('created_at', 'desc')
            ->get();
        
        $consumedAddons = [];
        foreach ($orders as $order) {
            $order->total_addons = $order->addons->sum('subtotal');

            foreach ($order->addons as $addon) {

                $itemName = $addon->item_name;

                // Initialize item if not existing
                if (!isset($consumedAddons[$itemName])) {
                    $consumedAddons[$itemName] = [
                        'item_name' => $itemName,
                        'quantity'  => 0,
                        'unit_price'=> $addon->unit_price,
                        'total'     => 0,
                    ];
                }

                // Accumulate quantity
                $consumedAddons[$itemName]['quantity'] += $addon->quantity;

                // Accumulate total
                $consumedAddons[$itemName]['total'] += (
                    $addon->unit_price * $addon->quantity
                );
            }
        }
        $consumedAddons = array_values($consumedAddons);
        // Log::info('Orders with addons calculated');
        // Log::info($consumedAddons);

        // Log::info('Consumed Addons: ' . json_encode($consumedAddons));
        // Log::info('cashier_expenses: ' . $cashier_expenses);
        // Log::info('totalCashSales: ' . $totalCashSales);
        // Log::info('totalGcashSales: ' . $totalGcashSales);
        // Log::info('Orders fetched: ' . $orders->count());
        // Log::info($orders);

        return response()->json([
            'orders' => $orders,
            'totalCashierExpenses' => (float)$cashier_expenses,
            'totalCashSales' => (float)$totalCashSales,
            'totalGcashSales' => (float)$totalGcashSales,
            'totalMayaSales'=> (float)$totalMayaSales,
            'totalReservationFees' => (float)$totalReservationFee,
            'consumedAddons' => $consumedAddons,
            'payments' => $payments,
        ]);
    }

    public function fetchSalesReport04202026(Request $request)
    {
        Log::info($request->only([
            'start_date',
            'end_date',
            'status',
            'cashier_id',
        ]));

        $orders = Order::with([
                'user',
                'tableSession.table',
                'orderHeads.headPricingRule',
                'payments.paymentMethod'
            ])
            ->when($request->start_date && $request->end_date, function ($query) use ($request) {
                $query->whereBetween('created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59',
                ]);
            })
            ->when(!is_null($request->status) && $request->status !== '', function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when(!is_null($request->cashier_id) && $request->cashier_id !== '', function ($query) use ($request) {
                $query->where('user_id', $request->cashier_id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'orders' => $orders,
        ]);
    }

    public function printSalesReport(Request $request)
    {
        $allFrontdoorIds = User::where('role', 3)->pluck('id')->toArray();
        $targetUserIds = collect($allFrontdoorIds);
        $totalReservationFee = DB::table('orders as ord')
            ->join('order_payments as ordp', 'ordp.order_id', '=', 'ord.id')
            ->join('payment_methods as pm', 'pm.id', '=', 'ordp.payment_method_id')
            ->leftJoin('reservations as res', 'res.id', '=', 'ord.reservation_id') // ← also remove this if res.* isn't used anywhere else in the query
            ->where('pm.id', 13)
            ->where('ordp.is_void', 0)
            ->where('ord.status', 'paid')
            ->whereNull('ordp.remarks')
            ->when($request->start_date && $request->end_date, function ($query) use ($request) {
                $query->whereBetween('ord.created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59',
                ]);
            })
            ->when(!empty($allFrontdoorIds), function ($query) use ($allFrontdoorIds) {
                $query->whereIn('ord.user_id', $allFrontdoorIds);
            })
            ->when(!is_null($request->shift_id) && $request->shift_id !== 'All', function ($query) use ($request) {
                $query->where('ord.shift_id', $request->shift_id);
            })
            ->sum('ordp.amount');

        $cashierName = 'All Cashiers';

        if (!empty($request->cashier_id)) {
            $cashier = \App\Models\User::find($request->cashier_id);
            $cashierName = $cashier?->name ?? 'Unknown Cashier';
        }

        $orders = Order::with([
                'user',
                'tableSession.table',
                'orderHeads.headPricingRule',
                'payments.paymentMethod'
            ])
            ->when($request->start_date && $request->end_date, function ($query) use ($request) {
                $query->whereBetween('created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59',
                ]);
            })
            ->when(!is_null($request->status) && $request->status !== '', function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when(!is_null($request->cashier_id) && $request->cashier_id !== '', function ($query) use ($request) {
                $query->where('user_id', $request->cashier_id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $grossSales = $orders->where('status', 'paid')->sum('subtotal');
        $totalDiscount = $orders->where('status', 'paid')->sum('total_discount');
        // $netSales = $orders->where('status', 'paid')->sum('total');
        $voidedSales = $orders->where('status', 'cancelled')->sum('total');

        $mpdf = new Mpdf([
            'format' => 'A4',
            'orientation' => 'L',
        ]);

        $totalCashSales = DB::table('orders as ord')
            ->join('order_payments as ordp', 'ordp.order_id', '=', 'ord.id')
            ->join('payment_methods as pm', 'pm.id', '=', 'ordp.payment_method_id')
            ->where('pm.id', 1) // Cash
            ->where('ordp.is_void', 0)
            ->where('ord.status', 'paid')
            ->when($request->start_date && $request->end_date, function ($query) use ($request) {
                $query->whereBetween('ord.created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59',
                ]);
            })
            // ->when($request->cashier_id, function ($query) use ($request) {
            //     $query->where('ord.user_id', $request->cashier_id);
            // })
            ->when(!is_null($request->cashier_id) && $request->cashier_id !== '', function ($query) use ($request) {
                $query->where('ordp.received_by', $request->cashier_id);
            })
            ->when($request->shift_id !== null && $request->shift_id !== 'All', function ($query) use ($request) {
                $query->where('ord.shift_id', $request->shift_id);
            })
            ->sum('ordp.amount'); // ← was ord.total

        $expensesQuery = Expense::whereNotNull('shift_id')
            ->when($request->start_date && $request->end_date, function ($query) use ($request) {
                $query->whereBetween('expense_date', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59',
                ]);
            })
            ->when($request->cashier_id, function ($query) use ($request) {
                $query->where('created_by', $request->cashier_id);
            })
            ->when($request->shift_id, function ($query) use ($request) {
                $query->where('shift_id', $request->shift_id);
            });

        $totalExpenses = $expensesQuery->sum('amount');
        $netSales = $orders->where('status', 'paid')->sum('total') - $totalExpenses;

        // $totalReservationFee = DB::table('orders as ord')
        //     ->join('order_payments as ordp', 'ordp.order_id', '=', 'ord.id')
        //     ->join('payment_methods as pm', 'pm.id', '=', 'ordp.payment_method_id')
        //     ->where('pm.id', 13) // Reservation Fee
        //     ->where('ordp.is_void', 0)
        //     ->where('ord.status', 'paid')
        //     ->when($request->start_date && $request->end_date, function ($query) use ($request) {
        //         $query->whereBetween('ord.created_at', [
        //             $request->start_date . ' 00:00:00',
        //             $request->end_date . ' 23:59:59',
        //         ]);
        //     })
        //     ->when($request->shift_id !== null && $request->shift_id !== 'All', function ($query) use ($request) {
        //         $query->where('ord.shift_id', $request->shift_id);
        //     })
        //     ->sum('ordp.amount');
        
            Log::info('totalReservationFee: --------'. $totalReservationFee); //bert

        $totalGcashSales = DB::table('orders as ord')
            ->join('order_payments as ordp', 'ordp.order_id', '=', 'ord.id')
            ->join('payment_methods as pm', 'pm.id', '=', 'ordp.payment_method_id')
            ->where('pm.id', 2) // Gcash
            ->where('ordp.is_void', 0)
            ->where('ord.status', 'paid')
            ->when($request->start_date && $request->end_date, function ($query) use ($request) {
                $query->whereBetween('ord.created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59',
                ]);
            })
            ->when(!is_null($request->cashier_id) && $request->cashier_id !== '', function ($query) use ($request) {
                $query->where('ordp.received_by', $request->cashier_id);
            })
            ->when($request->shift_id !== null && $request->shift_id !== 'All', function ($query) use ($request) {
                $query->where('ord.shift_id', $request->shift_id);
            })
            ->sum('ordp.amount');
        
        $consumedAddons = [];
        foreach ($orders as $order) {
            $order->total_addons = $order->addons->sum('subtotal');

            foreach ($order->addons as $addon) {

                $itemName = $addon->item_name;

                // Initialize item if not existing
                if (!isset($consumedAddons[$itemName])) {
                    $consumedAddons[$itemName] = [
                        'item_name' => $itemName,
                        'quantity'  => 0,
                        'unit_price'=> $addon->unit_price,
                        'total'     => 0,
                    ];
                }

                // Accumulate quantity
                $consumedAddons[$itemName]['quantity'] += $addon->quantity;

                // Accumulate total
                $consumedAddons[$itemName]['total'] += (
                    $addon->unit_price * $addon->quantity
                );
            }
        }
        $consumedAddons = array_values($consumedAddons);
        Log::info('Consumed Addons for PDF: ' . json_encode($consumedAddons));
        $html = view('reports.SalesReport', [
            'orders' => $orders,
            'startDate' => $request->start_date,
            'endDate' => $request->end_date,
            'status' => $request->status,
            'cashierId' => $request->cashier_id,
            'grossSales' => $grossSales,
            'totalDiscount' => $totalDiscount,
            'totalExpenses' => $totalExpenses,
            'netSales' => $netSales,
            'voidedSales' => $voidedSales,
            'cashierName' => $cashierName,
            'totalCashSales' => $totalCashSales,
            'totalGcashSales' => $totalGcashSales,
            'consumedAddons' => $consumedAddons,
            'totalReservationFee' => $totalReservationFee,
        ])->render();

        $mpdf->WriteHTML($html);

        return $mpdf->Output('Sales_Report.pdf', 'I');
    }

    public function printSalesSummaryReport(Request $request)
    {

        $posted_cashes = \App\Models\CashRegister::with(['cashier', 'shift'])
            ->when($request->shift_id !== null && $request->shift_id !== 'All', function ($query) use ($request) {
                $query->where('shift_id', $request->shift_id);
            })
            ->when($request->cashier_id, function ($query) use ($request) {
                $query->where('cashier_id', $request->cashier_id);
            })
            ->when($request->start_date && $request->end_date, function ($query) use ($request) {
                $query->whereBetween('created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59',
                ]);
            })
            ->get();


        $cashierName = 'All Cashiers';

        if (!empty($request->cashier_id)) {
            $cashier = \App\Models\User::find($request->cashier_id);
            $cashierName = $cashier?->name ?? 'Unknown Cashier';
        }

        $orders = Order::with([
                'user',
                'tableSession.table',
                'orderHeads.headPricingRule',
                'payments.paymentMethod'
            ])
            ->when($request->start_date && $request->end_date, function ($query) use ($request) {
                $query->whereBetween('created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59',
                ]);
            })
            ->when(!is_null($request->status) && $request->status !== '', function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when(!is_null($request->cashier_id) && $request->cashier_id !== '', function ($query) use ($request) {
                $query->where('user_id', $request->cashier_id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $grossSales = $orders->where('status', 'paid')->sum('subtotal');
        $totalDiscount = $orders->where('status', 'paid')->sum('total_discount');
        // $netSales = $orders->where('status', 'paid')->sum('total');
        $voidedSales = $orders->where('status', 'cancelled')->sum('total');

        $mpdf = new Mpdf([
            'format' => 'A4',
            'orientation' => 'L',
        ]);

        $totalCashSales = DB::table('orders as ord')
            ->join('order_payments as ordp', 'ordp.order_id', '=', 'ord.id')
            ->join('payment_methods as pm', 'pm.id', '=', 'ordp.payment_method_id')
            ->where('pm.id', 1) // Cash
            ->where('ordp.is_void', 0)
            ->where('ord.status', 'paid')
            ->when($request->start_date && $request->end_date, function ($query) use ($request) {
                $query->whereBetween('ord.created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59',
                ]);
            })
            ->when($request->cashier_id, function ($query) use ($request) {
                $query->where('ord.user_id', $request->cashier_id);
            })
            ->sum('ord.total');

        $expensesQuery = Expense::with(['creator', 'shift'])  // <-- this was missing
            ->when($request->start_date && $request->end_date, function ($query) use ($request) {
                $query->whereBetween('expense_date', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59',
                ]);
            })
            ->when($request->cashier_id, function ($query) use ($request) {
                $query->where('created_by', $request->cashier_id);
            })
            ->when($request->shift_id !== null && $request->shift_id !== 'All', function ($query) use ($request) {
                $query->where('shift_id', $request->shift_id);
            });

        $totalExpenses = $expensesQuery->sum('amount');
        $expenses      = $expensesQuery->get();

        $netSales = $orders->where('status', 'paid')->sum('total') - $totalExpenses;

        $totalGcashSales = DB::table('orders as ord')
            ->join('order_payments as ordp', 'ordp.order_id', '=', 'ord.id')
            ->join('payment_methods as pm', 'pm.id', '=', 'ordp.payment_method_id')
            ->where('pm.id', 2) // Cash
            ->where('ordp.is_void', 0)
            ->where('ord.status', 'paid')
            ->when($request->start_date && $request->end_date, function ($query) use ($request) {
                $query->whereBetween('ord.created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59',
                ]);
            })
            ->when($request->cashier_id, function ($query) use ($request) {
                $query->where('ord.user_id', $request->cashier_id);
            })
            ->sum('ord.total');

        try {
            $html = view('reports.SalesSummaryReport', [
                'orders'          => $orders,
                'startDate'       => $request->start_date,
                'endDate'         => $request->end_date,
                'status'          => $request->status,
                'cashierId'       => $request->cashier_id,
                'grossSales'      => $grossSales,
                'totalDiscount'   => $totalDiscount,
                'totalExpenses'   => $totalExpenses,
                'netSales'        => $netSales,
                'voidedSales'     => $voidedSales,
                'cashierName'     => $cashierName,
                'totalCashSales'  => $totalCashSales,
                'totalGcashSales' => $totalGcashSales,
                'posted_cashes'   => $posted_cashes,
                'expenses'        => $expenses,
            ])->render();

            $mpdf->WriteHTML($html);

            return $mpdf->Output('Sales_Report.pdf', 'I');

        } catch (\Throwable $e) {
            Log::error('PDF render failed', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error'   => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ], 500);
        }
    }

    public function exportSalesReportExcel(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $status    = $request->input('status', 'paid');
        $cashierId = $request->input('cashier_id');
        $shiftId   = $request->input('shift_id');

        // ── Mirror fetchSalesReport's cashier/frontdoor logic ────────────────
        $allFrontdoorIds  = User::where('role', 3)->pluck('id')->toArray();
        $hasCashierFilter = !is_null($cashierId) && $cashierId !== '';

        if ($hasCashierFilter) {
            $targetUserIds = collect($allFrontdoorIds)
                ->push((int) $cashierId)
                ->unique()
                ->values()
                ->toArray();
        }

        $orders = Order::with([
                'payments.paymentMethod',
                'user',
                'tableSession.table',
                'addons',
            ])
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [
                $startDate . ' 00:00:00',
                $endDate   . ' 23:59:59',
            ]))
            ->when($status !== '', fn($q) => $q->where('status', $status))
            ->when($hasCashierFilter, fn($q) => $q->whereIn('user_id', $targetUserIds))
            ->when($shiftId && $shiftId !== 'All', fn($q) => $q->where('shift_id', $shiftId))
            ->orderBy('created_at', 'asc')
            ->get();

        // ── Expenses ──────────────────────────────────────────────────────────
        $cashier_expenses = Expense::when($shiftId && $shiftId !== 'All',
                fn($q) => $q->where('shift_id', $shiftId))
            ->when($hasCashierFilter,
                fn($q) => $q->where('created_by', $cashierId))
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('expense_date', [
                $startDate . ' 00:00:00',
                $endDate   . ' 23:59:59',
            ]))
            ->sum('amount');

        // ── All payment methods grouped (same as fetchSalesReport) ───────────
        $allPayments = DB::table('orders as ord')
            ->join('order_payments as ordp', 'ordp.order_id', '=', 'ord.id')
            ->join('payment_methods as pm', 'pm.id', '=', 'ordp.payment_method_id')
            ->where('ordp.is_void', 0)
            ->where('ord.status', 'paid')
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('ord.created_at', [
                $startDate . ' 00:00:00',
                $endDate   . ' 23:59:59',
            ]))
            ->when($hasCashierFilter, fn($q) => $q->where('ord.user_id', $cashierId))
            ->when($shiftId && $shiftId !== 'All', fn($q) => $q->where('ord.shift_id', $shiftId))
            ->select('pm.name as payment_method_name', DB::raw('SUM(ordp.amount) as total'))
            ->groupBy('pm.id', 'pm.name')
            ->get();

        $paymentTotals = $allPayments->pluck('total', 'payment_method_name')->toArray();

        // ── Reservation fee — frontdoor only, no remarks ─────────────────────
        $totalReservation = (float) DB::table('orders as ord')
            ->join('order_payments as ordp', 'ordp.order_id', '=', 'ord.id')
            ->join('payment_methods as pm', 'pm.id', '=', 'ordp.payment_method_id')
            ->where('pm.id', 13)
            ->where('ordp.is_void', 0)
            ->where('ord.status', 'paid')
            ->whereNull('ordp.remarks')
            ->when(!empty($allFrontdoorIds), fn($q) => $q->whereIn('ord.user_id', $allFrontdoorIds))
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('ord.created_at', [
                $startDate . ' 00:00:00',
                $endDate   . ' 23:59:59',
            ]))
            ->when($shiftId && $shiftId !== 'All', fn($q) => $q->where('ord.shift_id', $shiftId))
            ->sum('ordp.amount');

        // Override reservation fee in paymentTotals with the corrected value
        $paymentTotals['Reservation Fee'] = $totalReservation;

        // ── Gross = sum of ALL payment methods ────────────────────────────────
        $grossSales    = collect($paymentTotals)->sum(fn($v) => (float) $v);
        $totalExpenses = (float) $cashier_expenses;
        $netSales      = $grossSales - $totalExpenses;

        $filename = 'sales_report_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new SalesReportExport(
                $orders,
                $grossSales,
                $totalExpenses,
                $netSales,
                $paymentTotals,
                $startDate ?? 'N/A',
                $endDate   ?? 'N/A',
            ),
            $filename
        );
    }
}