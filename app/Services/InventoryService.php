<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Models\InventoryItem;
use App\Models\InventoryCategory;
use App\Models\InventoryMovement;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Mpdf\Mpdf;

class InventoryService
{
    public function index(Request $request)
    {
        $search = $request->search;

        $today = Carbon::today();

        $items = InventoryItem::with('category')
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('current_quantity', 'asc')
            ->get();

        $inventoryMovements = InventoryMovement::with(['item.category', 'creator', 'updater'])
            ->whereDate('created_at', $today)
            ->orderBy('id', 'desc')
            ->get();

        $categories = InventoryCategory::where('status', true)->get();

        return Inertia::render('Inventory/Index', [
            'categories' => $categories,
            'items' => $items,
            'inventoryMovements' => $inventoryMovements,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function items(Request $request)
    {
        $search = $request->search;

        $items = InventoryItem::with([
            'category',
            'movements' => function ($query) {
                $query->whereDate('created_at', now());
            },
            'creator',
            'updater'
        ])
        ->orderBy('current_quantity', 'asc')
        ->get();

        $categories = InventoryCategory::where('status', true)->get();

        return Inertia::render('Inventory/Items', [
            'items' => $items,
            'categories' => $categories,
            'filters' => [
                'search' => $search
            ]
        ]);
    }

    public function store(Request $request)
    {
        Log::info('Storing new inventory item');
        Log::info($request->all());
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:inventory_categories,id',
            'unit' => 'required|string|max:50',
            'current_quantity' => 'required|numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
        ]);

        $value_type = null;
        if($request->is_dry == 1) {
            $value_type = 1;
        } elseif($request->is_dry == 0) {
            $value_type = 0; // wet ingredient
        } else {
            $value_type = null; // not specified
        }

        $created_item = InventoryItem::create([
            'name' => $request->name,
            'category_id' => $request->category_id,
            'unit' => $request->unit,
            'current_quantity' => $request->current_quantity,
            'unit_price' => $request->unit_price,
            'created_by' => auth()->id(),
            'updated_by' => null,
            'orderable' => $request->orderable,
            'is_dry' => $value_type,
            'remarks' => $request->remarks ?? null,
        ]);

        if ($created_item) {
            InventoryMovement::create([
                'inventory_item_id' => $created_item->id,
                'type' => 'stockin',
                'quantity' => $request->current_quantity,
                'unit_price' => $request->unit_price ?? $created_item->unit_price,
                'note' => $request->note,
                'created_by' => auth()->id(),
                'updated_by' => null,
            ]);
        }

        return back()->with('success', 'Inventory item added successfully');
    }

    public function stockIn(Request $request, InventoryItem $item)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:1',
            'unit_price' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:255',
        ]);

        $item->increment('current_quantity', $request->quantity);

        InventoryMovement::create([
            'inventory_item_id' => $item->id,
            'type' => 'stockin',
            'quantity' => $request->quantity,
            'unit_price' => $request->unit_price ?? $item->unit_price,
            'note' => $request->note,
            'created_by' => auth()->id(),
            'updated_by' => null,
        ]);

        return back()->with('success', 'Stock updated successfully');
    }

    public function stockOut(Request $request, InventoryItem $item)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:1',
            'note' => 'nullable|string|max:255',
        ]);

        if ($request->quantity > $item->current_quantity) {
            return back()->withErrors(['quantity' => 'Not enough stock']);
        }

        $item->decrement('current_quantity', $request->quantity);

        InventoryMovement::create([
            'inventory_item_id' => $item->id,
            'type' => 'stockout',
            'quantity' => $request->quantity,
            'unit_price' => $item->unit_price,
            'note' => $request->note,
            'created_by' => auth()->id(),
            'updated_by' => null,
        ]);

        return back()->with('success', 'Stock updated successfully');
    }

    public function inventoryReportIndex()
    {
        $categories = InventoryCategory::where('status', true)->get();

        return Inertia::render('Reports/InventoryReport', [
            'categories' => $categories
        ]);
    }

    public function printGeneralReport(Request $request)
    {
        Log::info('Inventory General Report');
        Log::info($request->all());

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

        $itemsQuery = InventoryItem::with('category');

        if ($categoryId) {
            $itemsQuery->where('category_id', $categoryId);
        }

        $items = $itemsQuery->get();

        $movements = InventoryMovement::with('item')
            ->whereBetween('created_at', [
                $start . ' 00:00:00',
                $end . ' 23:59:59'
            ]);

        if ($reportType === 'stockin') {
            $movements->where('type', 'stockin');
        }

        if ($reportType === 'stockout') {
            $movements->where('type', 'stockout');
        }

        $movements = $movements->get();

        $reportData = collect();

        foreach ($items as $item) {
            $itemMovements = $movements->where('inventory_item_id', $item->id);

            $stockInQty = $itemMovements
                ->where('type', 'stockin')
                ->sum('quantity');

            $stockOutQty = $itemMovements
                ->where('type', 'stockout')
                ->sum('quantity');

            if ($reportType === 'stockin' && $stockInQty <= 0) continue;
            if ($reportType === 'stockout' && $stockOutQty <= 0) continue;

            $reportData->push([
                'name' => $item->name,
                'category' => $item->category->name ?? '',
                'unit' => $item->unit,
                'stockInQty' => $stockInQty,
                'stockOutQty' => $stockOutQty,
                'unit_price' => $item->unit_price ?? 0,
                'total_cost' => $stockOutQty * ($item->unit_price ?? 0),
            ]);
        }

        $mpdf = new \Mpdf\Mpdf([
            'format' => 'A4',
            'margin_top' => 15,
            'margin_bottom' => 15
        ]);

        $html = view('inventory.general_report_pdf', [
            'reportData' => $reportData,
            'reportType' => $reportType,
            'user' => auth()->user(),
            'isDaily' => $isDaily,
            'start' => $start,
            'end' => $end,
        ])->render();

        $filename = $isDaily
            ? "daily_inventory_report_{$start}.pdf"
            : "inventory_report_{$start}_to_{$end}.pdf";

        $mpdf->WriteHTML($html);

        return $mpdf->Output($filename, 'I');
    }

    public function getCategories()
    {
        $categories = InventoryCategory::orderBy('id', 'desc')->get();

        return Inertia::render('Inventory/Category', [
            'categories' => $categories
        ]);
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:inventory_categories,name',
            'description' => 'nullable|string',
            'status' => 'required|boolean',
        ]);

        InventoryCategory::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
        ]);

        return redirect()->back()->with('success', 'Category created successfully.');
    }

    public function physicalCount(Request $request, InventoryItem $item)
    {
        Log::info('Performing physical count for item ID: ' . $item->id);
        Log::info($request->all());
        $request->validate([
            'quantity'        => 'required|numeric|min:0',
            'adjustment_type' => 'required|string',
            'note'            => 'nullable|string',
        ]);

        $item->movements()->create([
            'type'            => 'adjustment',
            'adjustment_type' => $request->adjustment_type, // 'physical_count'
            'quantity'        => $request->quantity,
            'note'            => $request->note,
            'created_by'      => auth()->id(),
        ]);

        return back();
    }
}