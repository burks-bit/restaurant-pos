<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PettyCash;
use App\Services\PettyCashService;
use Illuminate\Support\Facades\Log;

class PettyCashController extends Controller
{
    protected $pettyCashService;

    public function __construct(PettyCashService $pettyCashService)
    {
        $this->pettyCashService = $pettyCashService;
    }

    public function index()
    {
        try {
            return $this->pettyCashService->index();
        } catch (\Throwable $e) {
            Log::error('PettyCashController@index failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load petty cash records.');
        }
    }

    public function store(Request $request)
    {
        try {
            return $this->pettyCashService->store($request);
        } catch (\Throwable $e) {
            Log::error('PettyCashController@store failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to save petty cash.'
            ], 500);
        }
    }

    public function storeDetail(Request $request, PettyCash $pettyCash)
    {
        try {
            return $this->pettyCashService->storeDetail($request, $pettyCash);
        } catch (\Throwable $e) {
            Log::error('PettyCashController@storeDetail failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to save petty cash detail.'
            ], 500);
        }
    }
}