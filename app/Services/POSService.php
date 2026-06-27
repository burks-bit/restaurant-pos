<?php

namespace App\Services;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Menu;
use App\Models\Category;
use App\Models\TableSession;
use App\Models\InventoryItem;
use App\Models\Configuration;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class POSService
{
    public function index()
    {
        $tableSessions = TableSession::with(['table', 'headCounts.headRule', 'addons', 'order', 'reservation'])
            ->where('status', 'open')
            ->whereNull('cashier_id')
            ->where(function ($q) {
                $q->whereNull('order_id')->orWhere('total_amount', 0);
            })
            ->orderBy('ref_no')
            ->get();
        $payment_methods = PaymentMethod::where('is_active', 1)->get();

        $isDiscountAllowed = (int) Configuration::where('name', 'Discounts (SC/PWD)')
            ->value('status') ? 1 : 0;

        return Inertia::render('POS/POS', [
            'payment_methods' => $payment_methods,
            'table_sessions' => $tableSessions,
            'categories' => Category::all(),
            'managers' => User::where('role', 1)->get(),
            'is_discount_allowed' => $isDiscountAllowed,
            'orderable_items' => InventoryItem::where('orderable', 1)->where('current_quantity', '>', 0)->get(),
        ]);
    }

    public function indexPosItems()
    {
        $orderable_items = InventoryItem::where('orderable', 1)->where('current_quantity', '>', 0)->get();
        $isDiscountAllowed = (int) Configuration::where('name', 'Discounts (SC/PWD)')
            ->value('status') ? 1 : 0;

        return Inertia::render('POS/POSItems', [
            'orderable_items' => $orderable_items,
            'is_discount_allowed' => $isDiscountAllowed,
        ]);
    }

    public function index_orig()
    {
        return Inertia::render('POS/Index', [
            'table_sessions' => TableSession::with('table')
                ->where('status', 'open')
                ->whereNull('cashier_id')
                ->where(function ($query) {
                    $query->whereNull('total_amount')
                        ->orWhere('total_amount', 0);
                })
                ->whereNull('order_id')
                ->orderBy('ref_no')
                ->get(),
            'categories' => Category::all(),
            'menus' => Menu::where('is_available', true)->get(),
            'managers' => User::where('role', '1')->get(),
        ]);
    }

    public function verifyManagerPassword(Request $request)
    {
        $manager = User::where('id', $request->manager_id)
            ->where('role', '1')
            ->first();

        if (!$manager) {
            return response()->json(['success' => false]);
        }

        if (!Hash::check($request->password, $manager->password)) {
            return response()->json(['success' => false]);
        }

        return response()->json(['success' => true]);
    }

    public function verifyVoucher($voucher_code)
    {
        $voucher = Voucher::where('control_no', $voucher_code)->first();

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Voucher does not exist.'
            ]);
        }

        if ($voucher->status !== 'available') {
            return response()->json([
                'success' => false,
                'message' => 'Voucher is already used or expired.'
            ]);
        }

        if (Carbon::parse($voucher->validity)->lt(Carbon::today())) {
            $voucher->update(['status' => 'expired']);

            return response()->json([
                'success' => false,
                'message' => 'Voucher already expired.'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Voucher is valid and available.',
            'voucher' => $voucher
        ]);
    }
}