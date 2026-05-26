<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\User;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalesExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Mpdf\Mpdf;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->type ?? 'daily';

        // DEFAULT: today
        $start = $request->start_date
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::today()->startOfDay();

        $end = $request->end_date
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::today()->endOfDay();

        // AUTO MONTH RANGE
        // if ($type === 'monthly') {
        //     $start = Carbon::now()->startOfMonth();
        //     $end   = Carbon::now()->endOfMonth();
        // }

        // $orders = Order::whereBetween('created_at', [$start, $end])
        //     ->selectRaw('DATE(created_at) as date, SUM(total) as total')
        //     ->groupBy('date')
        //     ->orderBy('date')
        //     ->get();

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
            'sales'       => $orders,
            'grandTotal'  => $grandTotal,
            'filters'     => [
                'type'       => $type,
                'start_date' => $start->toDateString(),
                'end_date'   => $end->toDateString(),
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
                $end   . ' 23:59:59',
            ]);
        } else {
            // default today
            $query->whereDate('created_at', now()->toDateString());
            $start = $end = now()->toDateString();
        }

        if ($type === 'daily') {
            // Fetch individual orders
            $sales = $query->with('items')->orderBy('created_at')->get();
        } else {
            // Monthly / grouped by date
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

        $today = now()->toDateString();

        if (empty($startDate) || empty($endDate) || $startDate === $endDate) {
            $reportDate = $startDate ?? $today;
            $start = $reportDate;
            $end   = $reportDate;
        } else {
            $start = $startDate;
            $end   = $endDate;
        }

        // =====================================
        // GET ITEMS (OPTIONAL CATEGORY FILTER)
        // =====================================
        $itemsQuery = \App\Models\InventoryItem::with('category');
        if ($categoryId) {
            $itemsQuery->where('category_id', $categoryId);
        }
        $items = $itemsQuery->get();

        // =====================================
        // GET MOVEMENTS
        // =====================================
        $movementsQuery = \App\Models\InventoryMovement::with('item')
            ->whereBetween('created_at', [
                $start . ' 00:00:00',
                $end   . ' 23:59:59'
            ]);

        if ($reportType === 'stockin') {
            $movementsQuery->where('type', 'stockin');
        }

        if ($reportType === 'stockout') {
            $movementsQuery->where('type', 'stockout');
        }

        $movements = $movementsQuery->get();

        // =====================================
        // PREPARE JSON RESPONSE
        // =====================================
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

            // skip if no relevant data for selected report type
            if ($reportType === 'stockin' && $stockInQty <= 0) continue;
            if ($reportType === 'stockout' && $stockOutQty <= 0) continue;

            $reportData->push([
                'id'            => $item->id,
                'name'          => $item->name,
                'category'      => $item->category->name ?? '',
                'unit'          => $item->unit,
                'current_quantity' => $item->current_quantity,
                'stockInQty'    => $stockInQty,
                'stockOutQty'   => $stockOutQty,
                'unit_price'    => $item->unit_price ?? 0,
                'total_cost'    => $stockOutQty * ($item->unit_price ?? 0),
            ]);
        }

        Log::info('fetched report');
        Log::info($reportData);

        return response()->json([
            'success' => true,
            'data' => $reportData
        ]);
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

        // Apply date range filter
        if ($startDate && $endDate) {
            $query->whereBetween('expense_date', [
                $startDate . ' 00:00:00',
                $endDate   . ' 23:59:59',
            ]);
        }

        // Apply category filter
        if ($categoryId) {
            $query->where('expense_category_id', $categoryId);
        }

        $expenses = $query
            ->orderBy('expense_date', 'desc')
            ->get();

        $categories = ExpenseCategory::where('status', 1)->get();

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

        // Determine date range
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

        // =====================================
        // GET ITEMS (OPTIONAL CATEGORY FILTER)
        // =====================================
        $itemsQuery = \App\Models\InventoryItem::with('category');
        if ($categoryId) {
            $itemsQuery->where('category_id', $categoryId);
        }
        $items = $itemsQuery->get();

        // =====================================
        // GET MOVEMENTS
        // =====================================
        $movementsQuery = \App\Models\InventoryMovement::with(['item', 'creator'])
            ->whereBetween('created_at', [
                $start . ' 00:00:00',
                $end   . ' 23:59:59'
            ]);

        if ($reportType === 'stockin') {
            $movementsQuery->where('type', 'stockin');
        }

        if ($reportType === 'stockout') {
            $movementsQuery->where('type', 'stockout');
        }

        $movements = $movementsQuery->get();

        // =====================================
        // PREPARE DATA FOR VIEW
        // =====================================
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

            // skip if no relevant data for selected report type
            if ($reportType === 'stockin' && $stockInQty <= 0) continue;
            if ($reportType === 'stockout' && $stockOutQty <= 0) continue;

            $reportData->push([
                'id'            => $item->id,
                'name'          => $item->name,
                'category'      => $item->category->name ?? '',
                'unit'          => $item->unit,
                'current_quantity' => $item->current_quantity,
                'stockInQty'    => $stockInQty,
                'stockOutQty'   => $stockOutQty,
                'unit_price'    => $item->unit_price ?? 0,
                'total_cost'    => $stockOutQty * ($item->unit_price ?? 0),
                'creator_name'  => $itemMovements->first()?->creator?->name ?? '-',
            ]);
        }

        \Log::info('Prepared report data', ['count' => $reportData->count()]);

        // =====================================
        // GENERATE PDF USING MPDF
        // =====================================
        $mpdf = new \Mpdf\Mpdf([
            'format' => 'A4',
            'margin_top' => 15,
            'margin_bottom' => 15,
        ]);

        $html = view('reports.InventoryReport', [
            'reportData' => $reportData,
            'reportType' => $reportType,
            'start'      => $start,
            'end'        => $end,
            'isDaily'    => $isDaily,
            'user'       => auth()->user(),
        ])->render();

        $mpdf->WriteHTML($html);

        $filename = $isDaily
            ? "daily_inventory_report_{$start}.pdf"
            : "inventory_report_{$start}_to_{$end}.pdf";

        // Output PDF inline in browser
        return $mpdf->Output($filename, 'I');
    }

    public function printExpensesReport(Request $request)
    {
        $categoryId = $request->query('category_id');
        $startDate  = $request->query('start_date');
        $endDate    = $request->query('end_date');

        $query = Expense::with(['category', 'creator']);

        // Apply date range filter
        if ($startDate && $endDate) {
            $query->whereBetween('expense_date', [
                $startDate . ' 00:00:00',
                $endDate   . ' 23:59:59',
            ]);
        }

        // Apply category filter
        if ($categoryId) {
            $query->where('expense_category_id', $categoryId);
        }

        $expenses = $query
            ->orderBy('expense_date', 'desc')
            ->get();

        $totalExpenses = $expenses->sum('amount');

        // Determine report type
        $reportType = 'custom';
        $isDaily = false;

        if ($startDate && $endDate && $startDate === $endDate) {
            $reportType = 'daily';
            $isDaily = true;
        }

        // Load Blade view
        $html = view('reports.ExpensesReport', [
            'reportData' => $expenses,
            'reportType' => $reportType,
            'start' => $startDate,
            'end' => $endDate,
            'isDaily' => $isDaily,
            'user' => auth()->user(),
            'totalExpenses' => $totalExpenses
        ])->render();

        // Initialize mPDF
        $mpdf = new Mpdf([
            'format' => 'A4',
            'orientation' => 'P', // P = Portrait, L = Landscape
        ]);

        $mpdf->SetTitle('Expenses Report');
        $mpdf->WriteHTML($html);

        // Output to browser
        return response($mpdf->Output('expenses-report.pdf', 'I'))
            ->header('Content-Type', 'application/pdf');
    }

    public function salesReportIndex()
    {
        $today = now()->toDateString();

        $orders = Order::with([
                'user',
                'tableSession.table',
                'orderHeads.headPricingRule',
            ])
            ->whereDate('created_at', $today)
            ->where('status', 'paid') // default only paid
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Reports/SalesReport', [
            'orders' => $orders,
            'startDate' => $today,
            'endDate' => $today,
            'selectedStatus' => 'paid',
        ]);
    }

    public function fetchSalesReport(Request $request)
    {
        $startDate = $request->start_date;
        $endDate   = $request->end_date;
        $status    = $request->status;

        $query = Order::with([
                'user',
                'tableSession.table',
                'orderHeads.headPricingRule',
            ]);

        // Date filter
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59'
            ]);
        }

        // Status filter
        if ($status) {
            $query->where('status', $status);
        }

        $orders = $query
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'orders' => $orders
        ]);
    }

    public function printSalesReport(Request $request)
    {
        $startDate = $request->start_date;
        $endDate   = $request->end_date;
        $status    = $request->status;

        $query = Order::with([
            'user',
            'tableSession.table',
        ]);

        // Date filter
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59'
            ]);
        }

        // Status filter (optional)
        if ($status) {
            $query->where('status', $status);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        // ===== COMPUTATIONS =====
        $grossSales = $orders->sum('subtotal');
        $totalDiscount = $orders->sum('total_discount');
        $netSales = $orders->sum('total');
        $voidedSales = $orders->where('status', 'cancelled')->sum('total');

        $mpdf = new Mpdf([
            'format' => 'A4',
            // 'orientation' => 'P'
            'orientation' => 'L'
        ]);

        $html = view('reports.SalesReport', [
            'orders' => $orders,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'grossSales' => $grossSales,
            'totalDiscount' => $totalDiscount,
            'netSales' => $netSales,
            'voidedSales' => $voidedSales,
        ])->render();

        $mpdf->WriteHTML($html);

        return $mpdf->Output('Sales_Report.pdf', 'I');
    }
}
