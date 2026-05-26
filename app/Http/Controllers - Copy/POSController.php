<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Inertia\Inertia;
use App\Models\Menu;
use App\Models\Category;
use App\Models\TableSession;
use App\Models\InventoryItem;
use App\Models\Configuration;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class POSController extends Controller
{
    public function index()
    {
        // return Inertia::render('POS/POS', [
        //     'table_sessions' => TableSession::with(['table', 'headCounts.headRule'])
        //         ->where('status', 'open')          // only open sessions
        //         ->whereNull('cashier_id')          // not yet assigned to a cashier
        //         ->where(function ($query) {        // total_amount null or 0
        //             $query->whereNull('total_amount')
        //                 ->orWhere('total_amount', 0);
        //         })
        //         ->whereNull('order_id')            // no order yet
        //         ->orderBy('ref_no')                // sort by ref_no
        //         ->get(),
        //     'categories' => Category::all(),
        //     'managers' => User::where('role', '1')->get(),
        //     'is_discount_allowed' => Configuration::where('name', 'Discounts (SC/PWD)')->value('status') ? 1 : 0,
        // ]);
        $tableSessions = TableSession::with([
            'table',
            'headCounts.headRule'
        ])
            ->where('status', 'open')
            ->whereNull('cashier_id')
            ->where(function ($query) {
                $query->whereNull('total_amount')
                    ->orWhere('total_amount', 0);
            })
            ->whereNull('order_id')
            ->orderBy('ref_no')
            ->get();

        $isDiscountAllowed = (int) Configuration::where('name', 'Discounts (SC/PWD)')
            ->value('status') ? 1 : 0;

        return Inertia::render('POS/POS', [
            'table_sessions'       => $tableSessions,
            'categories'           => Category::all(),
            'managers'             => User::where('role', 1)->get(),
            'is_discount_allowed'  => $isDiscountAllowed,
        ]);
    }

    public function indexPosItems()
    {
        // fetched only paid and while dining tables
        $table_sessions = TableSession::with(['table', 'headCounts.headRule'])
            ->where('status', 'open')
            ->whereNotNull('cashier_id')
            ->whereNotNull('order_id')
            ->whereNotNull('total_amount')
            ->whereNull('closed_at')
            ->orderBy('ref_no')
            ->get();
        
            Log::info('table_sessions');
            Log::info($table_sessions);

        $isDiscountAllowed = (int) Configuration::where('name', 'Discounts (SC/PWD)')
            ->value('status') ? 1 : 0;

        return Inertia::render('POS/POSItems', [
            'table_sessions' => $table_sessions,
            'categories' => Category::all(),
            'menus' => Menu::where('is_available', true)->get(),
            'managers' => User::where('role', '1')->get(),
            'orderable_items' => InventoryItem::where('orderable', '1')->get(),
            'is_discount_allowed'  => $isDiscountAllowed,
        ]);
    }

    public function index_orig()
    {
        // $dsa = TableSession::with('table')->get();
        // Log::info($dsa);
        return Inertia::render('POS/Index', [
            'table_sessions' => TableSession::with('table')
                ->where('status', 'open')          // only open sessions
                ->whereNull('cashier_id')          // not yet assigned to a cashier
                ->where(function ($query) {        // total_amount null or 0
                    $query->whereNull('total_amount')
                        ->orWhere('total_amount', 0);
                })
                ->whereNull('order_id')            // no order yet
                ->orderBy('ref_no')                // sort by ref_no
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
        $voucher = \App\Models\Voucher::where('control_no', $voucher_code)->first();

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

        // Check if expired (date only comparison)
        if (Carbon::parse($voucher->validity)->lt(Carbon::today())) {

            // Optional: auto-update status
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
