<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Table;
use App\Models\TableSession;
use App\Models\HeadPricingRule;
use App\Models\TableSessionHead;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TableController extends Controller
{
    /**
     * Show all tables (Backoffice / Frontdesk)
     */
    public function index()
    {
        $tables = Table::orderBy('id')->get();

        return Inertia::render('Tables/Index', [
            'tables' => $tables
        ]);
    }

    /**
     * Create a new table
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'capacity' => 'nullable|integer|min:1',
        ]);

        Table::create($validated);

        return back()->with('success', 'Table created successfully.');
    }

    /**
     * Update table info / status
     */
    public function update(Request $request, Table $table)
    {
        $validated = $request->validate([
            'status' => 'required|in:vacant,occupied',
            'guests_count' => 'nullable|integer|min:0',
        ]);

        $table->update([
            'status' => $validated['status'],
            'guests_count' => $validated['guests_count'] ?? $table->guests_count,
        ]);

        return back()->with('success', 'Table updated successfully.');
    }

    /**
     * Delete a table
     */
    public function destroy(Table $table)
    {
        $table->delete();

        return back()->with('success', 'Table deleted successfully.');
    }

    /**
     * FRONTDOOR: Get tables for tablet
     */
    public function get_tables()
    {
        $tables = Table::orderBy('id')->get();
        $rules = HeadPricingRule::orderBy('id', 'asc')->get();

        $tableSessions = TableSession::with([
            'table',
            'frontdoor',
            'cashier',
            'order', // <-- include the order relation
            'headCounts.headRule'
        ])->whereDate('created_at', Carbon::today())->orderBy('id')->get();

        return Inertia::render('Tables/Table_Occupancy', [
            'tables' => $tables,
            'table_sessions' => $tableSessions,
            'head_prices' => $rules,
        ]);
    }

    public function vacantTable(Request $request, \App\Models\Table $table)
    {
        // Log::info('vacantTable');
        // Log::info($request);
        // Log::info($table);

        $request->validate([
            'status' => 'required|in:vacant,occupied',
            'pax'    => 'nullable|integer|min:1'
        ]);

        $session = DB::transaction(function () use ($request, $table) {

            $pax = $request->pax ?? 1;

            // Get current open session
            $session = $table->sessions()
                ->where('status', 'open')
                ->first();

            if ($request->status === 'occupied') {

                if (!$session) {
                    $session = $table->sessions()->create([
                        'pax'          => $pax,
                        'status'       => 'open',
                        'frontdoor_id' => auth()->id(),
                        'opened_at'    => now(),
                        'cashier_id'   => null,
                        'total_amount' => 0,
                    ]);
                } else {
                    $session->update([
                        'pax' => $pax
                    ]);
                }

            } else {
                if ($session) {
                    $session->update([
                        'status'    => 'closed',
                        'closed_at' => now(),
                    ]);
                }
            }

            return $session;
        });

        return response()->json([
            'success' => true,
            'table_session' => $session?->load(['table','cashier','order'])
        ]);
    }

    public function cancelQueuedTable(Request $request, \App\Models\Table $table)
    {
        Log::info('cancelQueuedTable');
        Log::info($request);
        Log::info($table);
        $request->validate([
            'remarks' => 'required|string|max:1000',
        ]);

        $session = DB::transaction(function () use ($request, $table) {

            $session = $table->sessions()
                ->where('status', 'open')
                ->first();

            if (!$session) {
                abort(404, 'No open table session found.');
            }

            $session->update([
                'status'     => 'cancelled',
                'remarks'    => $request->remarks,
                'closed_at'  => now(),
            ]);

            return $session;
        });

        return response()->json([
            'success' => true,
            'table_session' => $session->load(['table', 'cashier', 'order'])
        ]);
    }

    /**
     * FRONTDOOR: Occupy / Vacate table via tablet
     */
    // public function vacantTable(Request $request, \App\Models\Table $table)
    // {
    //     $request->validate([
    //         'status' => 'required|in:vacant,occupied',
    //         'pax'    => 'nullable|integer|min:1'
    //     ]);

    //     $pax = $request->pax ?? 1;

    //     // Get current open session for this table
    //     $session = $table->sessions()->where('status', 'open')->first();

    //     if ($request->status === 'occupied') {
    //         if (!$session) {
    //             // Create new open session
    //             $table->sessions()->create([
    //                 'pax'          => $pax,
    //                 'status'       => 'open',
    //                 'frontdoor_id' => auth()->id(),
    //                 'opened_at'    => now(),
    //                 'cashier_id'   => null,
    //                 'total_amount' => 0,
    //             ]);
    //         } else {
    //             // Update pax in existing open session
    //             $session->update([
    //                 'pax' => $pax
    //             ]);
    //         }
    //     } else {
    //         // Vacate table: close current session
    //         if ($session) {
    //             $session->update([
    //                 'status'    => 'closed',
    //                 'closed_at' => now(),
    //             ]);
    //         }
    //     }
    //     return back()->with('success', 'Table status updated successfully.');
    // }

    public function assignTable(Request $request, $tableId)
    {
        Log::info('assign table');
        Log::info($request->all());
        $request->validate([
            'guests_count' => 'required|integer|min:1',
            'head_rule_counts' => 'nullable|array',
            'head_rule_counts.*' => 'integer|min:0',
        ]);

        // Check if table already has open session
        $existingOpenSession = TableSession::where('table_id', $tableId)
            ->where('status', 'open')
            ->exists();

        if ($existingOpenSession) {
            return response()->json([
                'message' => 'Table is already occupied.'
            ], 422);
        }

        $session = DB::transaction(function () use ($request, $tableId) {

            $today = now()->format('Ymd');

            $lastSession = TableSession::whereDate('created_at', today())
                ->orderBy('id', 'desc')
                ->first();

            $increment = $lastSession
                ? str_pad(((int) substr($lastSession->ref_no, -3)) + 1, 3, '0', STR_PAD_LEFT)
                : '001';

            $table_ref_no = "TBL-{$today}-{$increment}";

            // Create the table session
            $session = TableSession::create([
                'ref_no'       => $table_ref_no,
                'table_id'     => $tableId,
                'pax'          => $request->guests_count,
                'status'       => 'open',
                'frontdoor_id' => auth()->id(),
                'cashier_id'   => null,
                'order_id'     => null,
                'total_amount' => 0,
                'customer_name' => $request->customer_name,
            ]);
            
            // Save head counts if provided
            if ($request->filled('head_rule_counts')) {
                foreach ($request->head_rule_counts as $headId => $count) {
                    $rule = HeadPricingRule::find($headId);
                    $session->headCounts()->create([
                        'head_pricing_rule_id' => $headId,
                        'qty' => $count,
                        'price_snapshot' => $rule->price,
                        'subtotal' => $rule->price * $count
                    ]);
                }
            }

            return $session;
        });

        // Load relations including headCounts
        return response()->json([
            'success' => true,
            'table_session' => $session->load(['table','cashier','order','headCounts.headRule'])
        ]);
    }

    public function getOpenSessions()
    {
        $table_sessions = TableSession::with(['table', 'headCounts.headRule'])
            ->where('status', 'open')
            ->whereNull('cashier_id')
            ->where(function($q){
                $q->whereNull('order_id')->orWhere('total_amount', 0);
            })
            ->orderBy('ref_no') 
            ->get();

        return response()->json(['table_sessions' => $table_sessions]);
    }


}
