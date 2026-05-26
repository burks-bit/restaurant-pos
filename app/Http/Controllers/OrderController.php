<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderService;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function index()
    {
        try {
            return $this->orderService->index();
        } catch (\Throwable $e) {
            Log::error('OrderController@index failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load orders.');
        }
    }

    public function getFilteredOrders(Request $request)
    {
        try {
            return $this->orderService->getFilteredOrders($request);
        } catch (\Throwable $e) {
            Log::error('OrderController@getFilteredOrders failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to filter orders.'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            return $this->orderService->store($request);
        } catch (\Throwable $e) {
            Log::error('OrderController@store failed: ' . $e->getMessage());
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function storeOrderedItems(Request $request)
    {
        try {
            return $this->orderService->storeOrderedItems($request);
        } catch (\Throwable $e) {
            Log::error('OrderController@storeOrderedItems failed: ' . $e->getMessage());
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store_orig(Request $request)
    {
        try {
            return $this->orderService->store_orig($request);
        } catch (\Throwable $e) {
            Log::error('OrderController@store_orig failed: ' . $e->getMessage());
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Order $order)
    {
        try {
            return $this->orderService->show($order);
        } catch (\Throwable $e) {
            Log::error('OrderController@show failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load order.');
        }
    }

    public function destroy(Order $order)
    {
        try {
            return $this->orderService->destroy($order);
        } catch (\Throwable $e) {
            Log::error('OrderController@destroy failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to cancel order.');
        }
    }

    public function cancelItem(Order $order, $itemId)
    {
        try {
            return $this->orderService->cancelItem($order, $itemId);
        } catch (\Throwable $e) {
            Log::error('OrderController@cancelItem failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to cancel item.'
            ], 500);
        }
    }

    public function cancel(Order $order)
    {
        try {
            return $this->orderService->cancel($order);
        } catch (\Throwable $e) {
            Log::error('OrderController@cancel failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to cancel order.');
        }
    }

    public function cancel_item(OrderItem $orderItem)
    {
        try {
            return $this->orderService->cancel_item($orderItem);
        } catch (\Throwable $e) {
            Log::error('OrderController@cancel_item failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to cancel order item.');
        }
    }

    public function pdfReceipt($order)
    {
        try {
            return $this->orderService->pdfReceipt($order);
        } catch (\Throwable $e) {
            Log::error('OrderController@pdfReceipt failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to generate receipt.');
        }
    }

    public function verifyManagerPassword(Request $request)
    {
        try {
            return $this->orderService->verifyManagerPassword($request);
        } catch (\Throwable $e) {
            Log::error('OrderController@verifyManagerPassword failed: ' . $e->getMessage());
            return response()->json([
                'success' => false
            ], 500);
        }
    }

    public function refreshComputation()
    {
        try {
            return $this->orderService->refreshComputation();
        } catch (\Throwable $e) {
            Log::error('OrderController@refreshComputation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to refresh computation.'
            ], 500);
        }
    }

    public function storeLeftover(Request $request)
    {
        try {
            return $this->orderService->storeLeftover($request);
        } catch (\Throwable $e) {
            Log::error('OrderController@storeLeftover failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to save leftover charge.'
            ], 500);
        }
    }

    public function storeTableSessionAddons(Request $request)
    {
        try {
            return $this->orderService->storeTableSessionAddons($request);
        } catch (\Throwable $e) {
            Log::error('OrderController@storeTableSessionAddons failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to post table add-ons.'
            ], 500);
        }
    }

    public function voidTableSessionAddon(Request $request, $addon)
    {
        try {
            return $this->orderService->voidTableSessionAddon($request, $addon);
        } catch (\Throwable $e) {
            Log::error('OrderController@voidTableSessionAddon failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to void table add-ons.'
            ], 500);
        }
    }
}