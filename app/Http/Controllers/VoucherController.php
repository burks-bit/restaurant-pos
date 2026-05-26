<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VoucherController extends Controller
{
    protected $voucherService;

    public function __construct(VoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
    }

    public function index()
    {
        try {
            return $this->voucherService->index();
        } catch (\Throwable $e) {
            Log::error('VoucherController@index failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load vouchers.');
        }
    }

    public function store(Request $request)
    {
        try {
            return $this->voucherService->store($request);
        } catch (\Throwable $e) {
            Log::error('VoucherController@store failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to create voucher.');
        }
    }

    public function update(Request $request, Voucher $voucher)
    {
        try {
            return $this->voucherService->update($request, $voucher);
        } catch (\Throwable $e) {
            Log::error('VoucherController@update failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to update voucher.');
        }
    }

    public function destroy(Voucher $voucher)
    {
        try {
            return $this->voucherService->destroy($voucher);
        } catch (\Throwable $e) {
            Log::error('VoucherController@destroy failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete voucher.');
        }
    }

    public function print(Voucher $voucher)
    {
        try {
            return $this->voucherService->print($voucher);
        } catch (\Throwable $e) {
            Log::error('VoucherController@print failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to print voucher.');
        }
    }

    public function printAll()
    {
        try {
            return $this->voucherService->printAll();
        } catch (\Throwable $e) {
            Log::error('VoucherController@printAll failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to print vouchers.');
        }
    }
}