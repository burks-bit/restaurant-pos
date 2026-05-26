<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InventoryItem;
use App\Services\InventoryService;
use Illuminate\Support\Facades\Log;

class InventoryController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index(Request $request)
    {
        try {
            return $this->inventoryService->index($request);
        } catch (\Throwable $e) {
            Log::error('InventoryController@index failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load inventory page.');
        }
    }

    public function items(Request $request)
    {
        try {
            return $this->inventoryService->items($request);
        } catch (\Throwable $e) {
            Log::error('InventoryController@items failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load inventory items.');
        }
    }

    public function store(Request $request)
    {
        try {
            return $this->inventoryService->store($request);
        } catch (\Throwable $e) {
            Log::error('InventoryController@store failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to create inventory item.');
        }
    }

    public function stockIn(Request $request, InventoryItem $item)
    {
        try {
            return $this->inventoryService->stockIn($request, $item);
        } catch (\Throwable $e) {
            Log::error('InventoryController@stockIn failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to stock in item.');
        }
    }

    public function stockOut(Request $request, InventoryItem $item)
    {
        try {
            return $this->inventoryService->stockOut($request, $item);
        } catch (\Throwable $e) {
            Log::error('InventoryController@stockOut failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to stock out item.');
        }
    }

    public function inventoryReportIndex()
    {
        try {
            return $this->inventoryService->inventoryReportIndex();
        } catch (\Throwable $e) {
            Log::error('InventoryController@inventoryReportIndex failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load inventory report page.');
        }
    }

    public function printGeneralReport(Request $request)
    {
        try {
            return $this->inventoryService->printGeneralReport($request);
        } catch (\Throwable $e) {
            Log::error('InventoryController@printGeneralReport failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to print inventory report.');
        }
    }

    public function getCategories()
    {
        try {
            return $this->inventoryService->getCategories();
        } catch (\Throwable $e) {
            Log::error('InventoryController@getCategories failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load inventory categories.');
        }
    }

    public function storeCategory(Request $request)
    {
        try {
            return $this->inventoryService->storeCategory($request);
        } catch (\Throwable $e) {
            Log::error('InventoryController@storeCategory failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to create category.');
        }
    }
}