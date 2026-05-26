<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PettyCash;
use App\Services\CashRegisterService;
use Illuminate\Support\Facades\Log;

class CashRegisterController extends Controller
{
    protected $cashRegisterService;

    public function __construct(CashRegisterService $cashRegisterService)
    {
        $this->cashRegisterService = $cashRegisterService;
    }

    public function index()
    {
        try {
            return $this->cashRegisterService->index();
        } catch (\Throwable $e) {
            Log::error('CashRegisterController@index failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load cash records.');
        }
    }

    public function fetchCashRegistered(Request $request)
    {
        try {
            return $this->cashRegisterService->fetchCashRegistered($request);
        } catch (\Throwable $e) {
            Log::error('CashRegisterController@fetchCashRegistered failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load cash records.'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            return $this->cashRegisterService->store($request);
        } catch (\Throwable $e) {
            Log::error('CashRegisterController@store failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to save cash.'
            ], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            return $this->cashRegisterService->update($request);
        } catch (\Throwable $e) {
            Log::error('CashRegisterController@update failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to save cash.'
            ], 500);
        }
    }

}