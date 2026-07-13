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
use App\Exports\SalesSummaryExport;

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
            $movementsQuery->whereIn('type', ['stockin', 'adjustment']);
        }
        if ($reportType === 'stockout') {
            $movementsQuery->whereIn('type', ['stockout', 'adjustment']);
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

            // ✅ Add this
            $actualCount = $itemMovements
                ->where('type', 'adjustment')
                ->where('adjustment_type', 'physical_count')
                ->sum('quantity');

            if ($reportType === 'stockin' && $stockInQty <= 0) continue;
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
                'actualCount'      => $actualCount,                                         // ✅
                'finalCount'       => $actualCount + $stockInQty - $stockOutQty,            // ✅
                'remarks'          => $item->remarks ?? '',                                 // ✅
            ]);
        }

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

        // Also fix movements query to include adjustments (same fix as before)
        if ($reportType === 'stockin')  $movementsQuery->whereIn('type', ['stockin', 'adjustment']);
        if ($reportType === 'stockout') $movementsQuery->whereIn('type', ['stockout', 'adjustment']);


        $movements = $movementsQuery->get();

        $reportData = collect();

        foreach ($items as $item) {
            $itemMovements = $movements->where('inventory_item_id', $item->id);
            
            $stockInQty    = $itemMovements->where('type', 'stockin')->sum('quantity');
            $stockOutQty   = $itemMovements->where('type', 'stockout')->sum('quantity');

            $actualCount = $itemMovements
                ->where('type', 'adjustment')
                ->where('adjustment_type', 'physical_count')
                ->sum('quantity');

            $finalCount = $actualCount + $stockInQty - $stockOutQty;

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
                'actualCount'      => $actualCount,   // ✅ new
                'finalCount'       => $finalCount,    // ✅ new
                'remarks'          => $item->remarks ?? '',
            ]);
        }

        // $filename = 'inventory_report_' . now()->format('Ymd_His') . '.xlsx';
        if($itemType == '0')
            $filename = 'HSB_Inventory_Wet_' . now()->format('Y-m-d') . '.xlsx';
        
        else if($itemType == '1')
            $filename = 'HSB_Inventory_Dry_' . now()->format('Y-m-d') . '.xlsx';
        
        else
            $filename = 'HSB_Inventory_All_' . now()->format('Y-m-d') . '.xlsx';{
        }
        // $filename = 'HSB_Inventory_Meat_' . now()->format('Y-m-d') . '.xlsx';

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

        return response()->json([
            'data' => $expenses,
            'total' => $totalExpenses
        ]);
    }

    public function printInventoryReport(Request $request)
    {
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

        $cashiers = User::where('role', 2)->get();
        $shifts   = \App\Models\Shift::all();

        $data = $this->getSalesReportData(
            $today,   // startDate
            $today,   // endDate
            'paid',   // status
            null,     // cashierId (no filter on initial load)
            'All',    // shiftId (no filter on initial load)
        );

        return Inertia::render('Reports/SalesReport', [
            'shifts'               => $shifts,
            'orders'               => $data['orders'],
            'startDate'            => $today,
            'endDate'              => $today,
            'cashiers'             => $cashiers,
            'selectedStatus'       => 'paid',
            'selectedCashier'      => '',
            'selectedShift'        => 'All',
            'totalCashierExpenses' => $data['totalExpenses'],
            'totalCashSales'       => $data['totalCashSales'],
            'totalGcashSales'      => $data['totalGcashSales'],
            'totalMayaSales'       => $data['totalMayaSales'],
            'totalReservationFees' => $data['totalReservationFees'],
            'consumedAddons'       => array_values($data['consumedAddons']),
            'payments'             => $data['payments'],
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════════
    // SHARED HELPER — called by both fetchSalesReport and exportSalesReportExcel
    // ════════════════════════════════════════════════════════════════════════════

    /**
     * Resolve the unified list of user IDs to scope queries against.
     * Always includes all frontdoor (role=3) users.
     * If a cashier filter is active, that cashier is merged in too.
     */
    private function resolveTargetUserIds(?string $cashierId): array
    {
        $frontdoorIds = User::where('role', 3)->pluck('id')->toArray();

        return collect($frontdoorIds)
            ->when(
                !is_null($cashierId) && $cashierId !== '',
                fn($c) => $c->push((int) $cashierId)
            )
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Build the aggregated payments breakdown (dine-in + RSVP fees merged).
     * Returns: [ 'Cash' => 1500.00, 'GCash' => 3000.00, 'Reservation Fee' => 4000.00, ... ]
     */
    private function buildPaymentsBreakdown(
        ?string $startDate,
        ?string $endDate,
        ?string $shiftId,
        bool    $hasCashierFilter,
        array   $targetUserIds
    ): array {
        $baseQuery = function () use ($startDate, $endDate, $shiftId) {
            return DB::table('orders as ord')
                ->join('order_payments as ordp', 'ordp.order_id', '=', 'ord.id')
                ->join('payment_methods as pm', 'pm.id', '=', 'ordp.payment_method_id')
                ->where('ordp.is_void', 0)
                ->where('ord.status', 'paid')
                ->when($startDate && $endDate, fn($q) => $q->whereBetween('ord.created_at', [
                    $startDate . ' 00:00:00',
                    $endDate   . ' 23:59:59',
                ]))
                ->when(
                    !is_null($shiftId) && $shiftId !== 'All',
                    fn($q) => $q->where('ord.shift_id', $shiftId)
                );
        };

        // Dine-in: orders with a table, excluding the reservation-fee-redemption row itself
        $dineIn = $baseQuery()
            ->whereNotNull('ord.table_number')
            ->where(function ($q) {
                $q->whereNull('ordp.remarks')
                ->orWhere('ordp.remarks', '!=', 'Reservation fee applied');
            })
            ->when($hasCashierFilter, fn($q) => $q->whereIn('ord.user_id', $targetUserIds))
            ->select('pm.name as payment_method_name', DB::raw('SUM(ordp.amount) as total'))
            ->groupBy('pm.id', 'pm.name')
            ->get();

        // RSVP: reservation fee collection orders (no table yet, tied to a reservation)
        $rsvp = $baseQuery()
            ->whereNotNull('ord.reservation_id')
            ->whereNull('ord.table_number')
            ->whereNull('ordp.remarks')
            ->when($hasCashierFilter, fn($q) => $q->whereIn('ord.user_id', $targetUserIds))
            ->select('pm.name as payment_method_name', DB::raw('SUM(ordp.amount) as total'))
            ->groupBy('pm.id', 'pm.name')
            ->get();

        // ── NEW: Single Orders — no table, no reservation (e.g. SO- prefixed orders) ──
        $singleOrders = $baseQuery()
            ->whereNull('ord.table_number')
            ->whereNull('ord.reservation_id')
            ->when($hasCashierFilter, fn($q) => $q->whereIn('ord.user_id', $targetUserIds))
            ->select('pm.name as payment_method_name', DB::raw('SUM(ordp.amount) as total'))
            ->groupBy('pm.id', 'pm.name')
            ->get();

        // Merge all three buckets
        return $dineIn
            ->concat($rsvp)
            ->concat($singleOrders)
            ->groupBy('payment_method_name')
            ->map(fn($group) => (float) $group->sum('total'))
            ->toArray();
    }

    /**
     * Build the cashier sales summary (Total Sales / Plus DP / Less DP / Remaining Cash).
     * Used by exportSalesSummaryReport(). Handles both per-cashier and consolidated ('All') modes.
     */
    private function buildSalesSummaryData(
        ?string $startDate,
        ?string $endDate,
        ?string $shiftId,
        ?string $cashierId
    ): array {
        $hasCashierFilter = !is_null($cashierId) && $cashierId !== '' && $cashierId !== 'All';
        $targetUserIds    = $this->resolveTargetUserIds($hasCashierFilter ? $cashierId : null);

        $baseOrderQuery = function () use ($startDate, $endDate, $shiftId) {
            return DB::table('orders as ord')
                ->where('ord.status', 'paid')
                ->when($startDate && $endDate, fn($q) => $q->whereBetween('ord.created_at', [
                    $startDate . ' 00:00:00',
                    $endDate   . ' 23:59:59',
                ]))
                ->when(
                    !is_null($shiftId) && $shiftId !== 'All',
                    fn($q) => $q->where('ord.shift_id', $shiftId)
                );
        };

        // ── Total Sales: full billed subtotal of dine-in orders, BEFORE any DP credit is netted out ──
        // $totalSales = (float) $baseOrderQuery()
        //     ->whereNotNull('ord.table_number')
        //     ->when($hasCashierFilter, fn($q) => $q->whereIn('ord.user_id', $targetUserIds))
        //     ->sum('ord.subtotal');

        $totalSales = (float) $baseOrderQuery()
            ->where(function ($q) {
                $q->whereNotNull('ord.table_number')
                ->orWhere(function ($q2) {
                    $q2->whereNull('ord.table_number')->whereNull('ord.reservation_id');
                });
            })
            ->when($hasCashierFilter, fn($q) => $q->whereIn('ord.user_id', $targetUserIds))
            ->sum('ord.subtotal');

        // ── Plus DP: new reservation deposits collected today (fresh cash in, not yet redeemed) ──
        $plusDP = (float) $baseOrderQuery()
            ->join('order_payments as ordp', 'ordp.order_id', '=', 'ord.id')
            ->whereNotNull('ord.reservation_id')
            ->whereNull('ord.table_number')
            ->whereNull('ordp.remarks')
            ->where('ordp.payment_method_id', 13)
            ->where('ordp.is_void', 0)
            ->when($hasCashierFilter, fn($q) => $q->whereIn('ord.user_id', $targetUserIds))
            ->sum('ordp.amount');

        // ── Less DP: deposit credit redeemed against a dine-in bill today (already collected earlier) ──
        $lessDP = (float) $baseOrderQuery()
            ->join('order_payments as ordp', 'ordp.order_id', '=', 'ord.id')
            ->whereNotNull('ord.table_number')
            ->where('ordp.remarks', 'Reservation fee applied')
            ->where('ordp.is_void', 0)
            ->when($hasCashierFilter, fn($q) => $q->whereIn('ord.user_id', $targetUserIds))
            ->sum('ordp.amount');

        // Total Sales + Plus DP - Less DP == actual new cash collected today
        // (identical to grossSales from buildPaymentsBreakdown, just derived via subtotal instead of payment rows)
        $totalAmountOfSales = $totalSales + $plusDP - $lessDP;

        // ── Expenses ──
        $lessExpenses = (float) Expense::when(
                !is_null($shiftId) && $shiftId !== 'All',
                fn($q) => $q->where('shift_id', $shiftId)
            )
            ->when($hasCashierFilter, fn($q) => $q->whereIn('created_by', $targetUserIds))
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('expense_date', [
                $startDate . ' 00:00:00',
                $endDate   . ' 23:59:59',
            ]))
            ->sum('amount');

        $remainingCash = $totalAmountOfSales - $lessExpenses;

        // ── Payment breakdown (mode of payment totals, incl. Reservation Fee) — reuse existing logic ──
        $paymentBreakdown = $this->buildPaymentsBreakdown(
            $startDate, $endDate, $shiftId, $hasCashierFilter, $targetUserIds
        );

        $cashierName = 'All Cashiers';
        if ($hasCashierFilter) {
            $cashier     = User::find($cashierId);
            $cashierName = $cashier?->name ?? 'Unknown Cashier';
        }

        return [
            'isConsolidated'     => !$hasCashierFilter,
            'cashierName'        => $cashierName,
            'totalSales'         => $totalSales,
            'plusDP'             => $plusDP,
            'lessDP'             => $lessDP,
            'totalAmountOfSales' => $totalAmountOfSales,
            'lessExpenses'       => $lessExpenses,
            'remainingCash'      => $remainingCash,
            'paymentBreakdown'   => $paymentBreakdown,
        ];
    }

    /**
     * Fetch all sales report data in one place.
     * Used by both the JSON endpoint and the Excel export.
     */
    private function getSalesReportData(
        ?string $startDate,
        ?string $endDate,
        ?string $status,
        ?string $cashierId,
        ?string $shiftId,
    ): array {
        $hasCashierFilter = !is_null($cashierId) && $cashierId !== '';
        $targetUserIds    = $this->resolveTargetUserIds($cashierId);

        // ── Expenses ──────────────────────────────────────────────────────────────
        $totalExpenses = (float) Expense::when(
                !is_null($shiftId) && $shiftId !== 'All',
                fn($q) => $q->where('shift_id', $shiftId)
            )
            ->when($hasCashierFilter, fn($q) => $q->whereIn('created_by', $targetUserIds))
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('expense_date', [
                $startDate . ' 00:00:00',
                $endDate   . ' 23:59:59',
            ]))
            ->sum('amount');

        // ── Payments breakdown (dine-in + RSVP merged) ────────────────────────────
        $payments = $this->buildPaymentsBreakdown(
            $startDate, $endDate, $shiftId, $hasCashierFilter, $targetUserIds
        );

        // ── Per-method totals (Cash=1, GCash=2, Maya=3) ───────────────────────────
        $buildMethodTotal = function (int $methodId) use (
            $startDate, $endDate, $shiftId, $hasCashierFilter, $targetUserIds
        ) {
            return (float) DB::table('orders as ord')
                ->join('order_payments as ordp', 'ordp.order_id', '=', 'ord.id')
                ->where('ordp.payment_method_id', $methodId)
                ->where('ordp.is_void', 0)
                ->where('ord.status', 'paid')
                ->when($startDate && $endDate, fn($q) => $q->whereBetween('ord.created_at', [
                    $startDate . ' 00:00:00',
                    $endDate   . ' 23:59:59',
                ]))
                ->when($hasCashierFilter, fn($q) => $q->whereIn('ord.user_id', $targetUserIds))
                ->when(
                    !is_null($shiftId) && $shiftId !== 'All',
                    fn($q) => $q->where('ord.shift_id', $shiftId)
                )
                ->sum('ordp.amount');
        };

        $totalCashSales  = $buildMethodTotal(1);
        $totalGcashSales = $buildMethodTotal(2);
        $totalMayaSales  = $buildMethodTotal(3);

        // ── Reservation fees (pm.id=13, RSVP orders only, no remarks) ────────────
        $totalReservationFees = (float) DB::table('orders as ord')
            ->join('order_payments as ordp', 'ordp.order_id', '=', 'ord.id')
            ->where('ordp.payment_method_id', 13)
            ->where('ordp.is_void', 0)
            ->where('ord.status', 'paid')
            ->whereNotNull('ord.reservation_id')
            ->whereNull('ord.table_number')
            ->whereNull('ordp.remarks')
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('ord.created_at', [
                $startDate . ' 00:00:00',
                $endDate   . ' 23:59:59',
            ]))
            ->when(
                !is_null($shiftId) && $shiftId !== 'All',
                fn($q) => $q->where('ord.shift_id', $shiftId)
            )
            ->sum('ordp.amount');

        // ── Orders (with relationships) ───────────────────────────────────────────
        $orders = Order::with([
                'user',
                'tableSession.table',
                'orderHeads.headPricingRule',
                'payments.paymentMethod',
                'addons',
            ])
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [
                $startDate . ' 00:00:00',
                $endDate   . ' 23:59:59',
            ]))
            ->when(!is_null($status) && $status !== '', fn($q) => $q->where('status', $status))
            ->when($hasCashierFilter, fn($q) => $q->whereIn('user_id', $targetUserIds))
            ->when(
                !is_null($shiftId) && $shiftId !== 'All',
                fn($q) => $q->where('shift_id', $shiftId)
            )
            ->orderBy('created_at', 'desc')
            ->get();

        // ── Consumed add-ons ──────────────────────────────────────────────────────
        $consumedAddons = [];
        foreach ($orders as $order) {
            $order->total_addons = $order->addons->sum('subtotal');

            foreach ($order->addons as $addon) {
                $key = $addon->item_name;
                if (!isset($consumedAddons[$key])) {
                    $consumedAddons[$key] = [
                        'item_name'  => $key,
                        'quantity'   => 0,
                        'unit_price' => $addon->unit_price,
                        'total'      => 0,
                    ];
                }
                $consumedAddons[$key]['quantity'] += $addon->quantity;
                $consumedAddons[$key]['total']    += $addon->unit_price * $addon->quantity;
            }
        }

        $grossSales = collect($payments)->sum();
        $netSales   = $grossSales - $totalExpenses;

        return compact(
            'orders',
            'payments',
            'totalExpenses',
            'totalCashSales',
            'totalGcashSales',
            'totalMayaSales',
            'totalReservationFees',
            'consumedAddons',
            'grossSales',
            'netSales',
        );
    }


    // ════════════════════════════════════════════════════════════════════════════
    // PUBLIC ENDPOINTS — now thin wrappers around getSalesReportData()
    // ════════════════════════════════════════════════════════════════════════════

    public function fetchSalesReport(Request $request)
    {
        Log::info('Fetching sales report', [
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'status'     => $request->status,
            'cashier_id' => $request->cashier_id,
            'shift_id'   => $request->shift_id,
        ]);
        
        $data = $this->getSalesReportData(
            $request->start_date,
            $request->end_date,
            $request->status,
            $request->cashier_id,
            $request->shift_id,
        );

        return response()->json([
            'orders'               => $data['orders'],
            'totalCashierExpenses' => $data['totalExpenses'],
            'totalCashSales'       => $data['totalCashSales'],
            'totalGcashSales'      => $data['totalGcashSales'],
            'totalMayaSales'       => $data['totalMayaSales'],
            'totalReservationFees' => $data['totalReservationFees'],
            'consumedAddons'       => array_values($data['consumedAddons']),
            'payments'             => $data['payments'],
        ]);
    }

    public function exportSalesReportExcel(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        $data = $this->getSalesReportData(
            $startDate,
            $endDate,
            $request->input('status', 'paid'),
            $request->input('cashier_id'),
            $request->input('shift_id'),
        );

        $filename = 'sales_report_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new SalesReportExport(
                $data['orders'],
                $data['grossSales'],
                $data['totalExpenses'],
                $data['netSales'],
                $data['payments'],
                $startDate ?? 'N/A',
                $endDate   ?? 'N/A',
            ),
            $filename
        );
    }

    public function exportSalesSummaryReport(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $cashierId = $request->input('cashier_id'); // null / '' / 'All' => consolidated view
        $shiftId   = $request->input('shift_id');

        $summary = $this->buildSalesSummaryData($startDate, $endDate, $shiftId, $cashierId);

        $filename = 'sales_summary_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new SalesSummaryExport(
                $startDate ?? 'N/A',
                $endDate   ?? 'N/A',
                $summary['isConsolidated'],
                $summary['cashierName'],
                $summary['totalSales'],
                $summary['plusDP'],
                $summary['lessDP'],
                $summary['totalAmountOfSales'],
                $summary['lessExpenses'],
                $summary['remainingCash'],
                $summary['paymentBreakdown'],
            ),
            $filename
        );
    }

    public function printSalesReport(Request $request)
    {
        $data = $this->getSalesReportData(
            $request->start_date,
            $request->end_date,
            $request->status,
            $request->cashier_id,
            $request->shift_id,
        );

        // ── Cashier display name ──────────────────────────────────────────────────
        $cashierName = 'All Cashiers';
        if (!empty($request->cashier_id)) {
            $cashier     = User::find($request->cashier_id);
            $cashierName = $cashier?->name ?? 'Unknown Cashier';
        }

        // ── Order-level aggregates (computed from the already-fetched collection) ─
        $grossSales    = $data['orders']->where('status', 'paid')->sum('subtotal');
        $totalDiscount = $data['orders']->where('status', 'paid')->sum('total_discount');
        $voidedSales   = $data['orders']->where('status', 'cancelled')->sum('total');

        // ── Render PDF ────────────────────────────────────────────────────────────
        $mpdf = new Mpdf([
            'format'      => 'A4',
            'orientation' => 'L',
        ]);

        $html = view('reports.SalesReport', [
            'orders'              => $data['orders'],
            'startDate'           => $request->start_date,
            'endDate'             => $request->end_date,
            'status'              => $request->status,
            'cashierId'           => $request->cashier_id,
            'cashierName'         => $cashierName,
            'grossSales'          => $grossSales,
            'totalDiscount'       => $totalDiscount,
            'totalExpenses'       => $data['totalExpenses'],
            'netSales'            => $data['netSales'],
            'voidedSales'         => $voidedSales,
            'totalCashSales'      => $data['totalCashSales'],
            'totalGcashSales'     => $data['totalGcashSales'],
            'totalReservationFee' => $data['totalReservationFees'],
            'consumedAddons'      => array_values($data['consumedAddons']),
            'payments'            => $data['payments'],
        ])->render();

        $mpdf->WriteHTML($html);

        return $mpdf->Output('Sales_Report.pdf', 'I');
    }
}