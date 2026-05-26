<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\POSService;
use Illuminate\Support\Facades\Log;

class POSController extends Controller
{
    protected $posService;

    public function __construct(POSService $posService)
    {
        $this->posService = $posService;
    }

    public function index()
    {
        try {
            return $this->posService->index();
        } catch (\Throwable $e) {
            Log::error('POSController@index failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load POS.');
        }
    }

    public function indexPosItems()
    {
        try {
            return $this->posService->indexPosItems();
        } catch (\Throwable $e) {
            Log::error('POSController@indexPosItems failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load POS items.');
        }
    }

    public function index_orig()
    {
        try {
            return $this->posService->index_orig();
        } catch (\Throwable $e) {
            Log::error('POSController@index_orig failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load POS index.');
        }
    }

    public function verifyManagerPassword(Request $request)
    {
        try {
            return $this->posService->verifyManagerPassword($request);
        } catch (\Throwable $e) {
            Log::error('POSController@verifyManagerPassword failed: ' . $e->getMessage());
            return response()->json([
                'success' => false
            ], 500);
        }
    }

    public function verifyVoucher($voucher_code)
    {
        try {
            return $this->posService->verifyVoucher($voucher_code);
        } catch (\Throwable $e) {
            Log::error('POSController@verifyVoucher failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify voucher.'
            ], 500);
        }
    }
}