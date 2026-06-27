<?php

namespace App\Services;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Table;
use App\Models\TableSession;
use App\Models\HeadPricingRule;
use App\Models\PricingScheme;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class TableService
{
    public function index()
    {
        $tables = Table::orderBy('id')->get();

        return Inertia::render('Tables/Index', [
            'tables' => $tables
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'capacity' => 'nullable|integer|min:1',
        ]);

        Table::create($validated);

        return back()->with('success', 'Table created successfully.');
    }

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

    public function destroy(Table $table)
    {
        $table->delete();

        return back()->with('success', 'Table deleted successfully.');
    }

    public function get_tables()
    {
        $tables = Table::orderBy('id')->get();
        $rules = HeadPricingRule::orderBy('id', 'asc')->get();

        $tableSessions = TableSession::with([
            'table',
            'frontdoor',
            'cashier',
            'order',
            'headCounts.headRule'
        ])->whereDate('created_at', Carbon::today())->orderBy('id')->get();

        return Inertia::render('Frontdoor/TableOccupancy', [
            'tables' => $tables,
            'table_sessions' => $tableSessions,
            'head_prices' => $rules,
            'managers' => User::where('role', 1)->get(),
        ]);
    }

    public function assignTable(Request $request, $tableId)
    {
        $request->validate([
            'guests_count' => 'required|integer|min:1',
            'customer_name' => 'required|string|max:255',
            'remarks' => 'nullable|string|max:1000',
            'is_shared' => 'nullable|boolean',
            'head_rule_counts' => 'nullable|array',
            'head_rule_counts.*' => 'integer|min:0',
        ]);

        $customerName = trim($request->customer_name);
        $isShared = (bool) $request->is_shared;

        $openSessions = TableSession::where('table_id', $tableId)
            ->where('status', 'open')
            ->get();

        if ($openSessions->isNotEmpty()) {
            $sameCustomerExists = $openSessions->contains(function ($session) use ($customerName) {
                return strtolower(trim($session->customer_name)) === strtolower($customerName);
            });

            if ($sameCustomerExists) {
                return response()->json([
                    'message' => 'This customer already has an open session on this table.'
                ], 422);
            }

            $hasSharedOpenSession = $openSessions->contains(function ($session) {
                return (int) $session->is_shared === 1;
            });

            if (!$hasSharedOpenSession && !$isShared) {
                return response()->json([
                    'message' => 'Table is already occupied.'
                ], 422);
            }

            if (!$hasSharedOpenSession && $isShared) {
                return response()->json([
                    'message' => 'Table already has a non-shared open session.'
                ], 422);
            }
        }

        $session = DB::transaction(function () use ($request, $tableId, $customerName, $isShared) {
            $today = now()->format('Ymd');

            $lastSession = TableSession::whereDate('created_at', today())
                ->orderBy('id', 'desc')
                ->first();

            $increment = $lastSession
                ? str_pad(((int) substr($lastSession->ref_no, -3)) + 1, 3, '0', STR_PAD_LEFT)
                : '001';

            $table_ref_no = "TBL-{$today}-{$increment}";

            $session = TableSession::create([
                'pricing_scheme_id' => $request->pricing_scheme_id,
                'ref_no' => $table_ref_no,
                'table_id' => $tableId,
                'pax' => $request->guests_count,
                'status' => 'open',
                'frontdoor_id' => auth()->id(),
                'cashier_id' => null,
                'order_id' => null,
                'total_amount' => 0,
                'customer_name' => $customerName,
                'remarks' => $request->remarks,
                // 'is_shared' => $isShared ? 1 : 0,
                'is_shared' => 1,
            ]);

            if ($request->filled('head_rule_counts')) {
                foreach ($request->head_rule_counts as $headId => $count) {
                    if ((int) $count <= 0) {
                        continue;
                    }

                    $rule = HeadPricingRule::find($headId);

                    if (!$rule) {
                        continue;
                    }

                    $session->headCounts()->create([
                        'head_pricing_rule_id' => $headId,
                        'qty' => $count,
                        'price_snapshot' => $rule->price,
                        'subtotal' => $rule->price * $count,
                    ]);
                }
            }

            // ← add here, before return
            if ($request->filled('reservation_id')) {
                Reservation::where('id', $request->reservation_id)
                    ->update([
                        'status'           => 'seated',
                        'table_session_id' => $session->id,
                ]);
            }
        

            return $session;
        });

        return response()->json([
            'success' => true,
            'table_session' => $session->load(['table', 'cashier', 'order', 'headCounts.headRule'])
        ]);
    }

    public function get_tables_for_admission(Request $request)
    {   
        $pricing_schemes = PricingScheme::with([
            'headRules' => function ($query) {
                $query->where('is_active', true)->orderBy('id');
            }
        ])
        ->where('is_active', true)
        ->orderBy('id')
        ->get();


        $tables = Table::with('children')
            ->whereNull('parent_id')
            ->get();

        $rules = HeadPricingRule::orderBy('id', 'asc')->get();

        $table_sessions = TableSession::with([
                'table',
                'cashier',
                'order',
                'headCounts.headRule',
                'reservation',
            ])
            ->where('status', 'open')
            ->get();

        $sessionByTableId = $table_sessions->keyBy('table_id');

        $tables->each(function ($parent) use ($sessionByTableId) {
            $parent->children->transform(function ($child) use ($sessionByTableId) {
                $openSession = $sessionByTableId->get($child->id);

                $child->status = $openSession ? 'occupied' : 'vacant';
                $child->guest_count = $openSession?->pax ?? 0;
                $child->customer_name = $openSession?->customer_name ?? null;
                $child->ref_no = $openSession?->ref_no ?? null;
                $child->table_session_id = $openSession?->id ?? null;

                return $child;
            });

            return $parent;
        });

        $incomingReservation = null;

        if ($request->filled('reservation_id')) {
            $incomingReservation = Reservation::with('reservationPax.headPricingRule', 'pricingScheme')
                ->find($request->reservation_id);
        }

        return Inertia::render('Frontdoor/TableAdmission', [
            'tables' => $tables,
            'head_prices' => $rules,
            'table_sessions' => $table_sessions,
            'pricing_schemes' => $pricing_schemes,
            'incoming_reservation' => $incomingReservation,
        ]);
    }

    public function admitTable(Request $request, $tableId = null)
    {

        $isSingleAdmission = $request->filled('table_id');
        $isGroupAdmission = $request->filled('parent_table_id');

        if (!$isSingleAdmission && !$isGroupAdmission) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid admission payload.'
            ], 422);
        }

        $request->validate([
            // single
            'table_id' => 'nullable|integer|exists:tables,id',
            'rule_id' => 'nullable|integer|exists:head_pricing_rules,id',

            // group
            'parent_table_id' => 'nullable|integer|exists:tables,id',
            'rule_counts' => 'nullable|array',
            'rule_counts.*' => 'nullable|integer|min:0',
            'pricing_breakdown' => 'nullable|array',
            'pricing_breakdown.*.rule_id' => 'required_with:pricing_breakdown|integer|exists:head_pricing_rules,id',
            'pricing_breakdown.*.qty' => 'required_with:pricing_breakdown|integer|min:1',
            'pricing_breakdown.*.price' => 'nullable|numeric|min:0',
            'pricing_breakdown.*.line_total' => 'nullable|numeric|min:0',

            // common
            'customer_name' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:1000',
            'pax' => 'required|integer|min:1',
        ]);

        $resolvedTableId = $isSingleAdmission
            ? (int) $request->table_id
            : (int) $request->parent_table_id;

        if ($tableId !== null && (int) $tableId !== $resolvedTableId) {
            return response()->json([
                'success' => false,
                'message' => 'Route table ID does not match request table ID.'
            ], 422);
        }

        if ($isSingleAdmission) {
            $existingOpenSession = TableSession::where('table_id', $resolvedTableId)
                ->where('status', 'open')
                ->exists();

            if ($existingOpenSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'Table is already occupied.'
                ], 422);
            }
        }

        $result = DB::transaction(function () use ($request, $resolvedTableId, $isSingleAdmission, $isGroupAdmission) {
            $today = now()->format('Ymd');

            $nextRefNo = function () use ($today) {
                $lastSession = TableSession::whereDate('created_at', today())
                    ->orderBy('id', 'desc')
                    ->first();

                $increment = $lastSession
                    ? str_pad(((int) substr($lastSession->ref_no, -3)) + 1, 3, '0', STR_PAD_LEFT)
                    : '001';

                return "TBL-{$today}-{$increment}";
            };

            /*
            |----------------------------------------------------------------------
            | SINGLE ADMISSION
            |----------------------------------------------------------------------
            */
            if ($isSingleAdmission) {
                $totalAmount = (float) ($request->price ?? 0);

                $session = TableSession::create([
                    'ref_no' => $nextRefNo(),
                    'table_id' => $resolvedTableId,
                    'pax' => (int) $request->pax,
                    'status' => 'open',
                    'frontdoor_id' => auth()->id(),
                    'cashier_id' => null,
                    'order_id' => null,
                    'total_amount' => $totalAmount,
                    'customer_name' => $request->customer_name,
                ]);

                if ($request->filled('rule_id')) {
                    $rule = HeadPricingRule::findOrFail($request->rule_id);

                    $qty = max((int) $request->pax, 1);
                    $priceSnapshot = (float) $rule->price;
                    $subtotal = $priceSnapshot * $qty;

                    $session->headCounts()->create([
                        'head_pricing_rule_id' => $rule->id,
                        'qty' => $qty,
                        'price_snapshot' => $priceSnapshot,
                        'subtotal' => $subtotal,
                    ]);

                    if ((float) $session->total_amount !== (float) $subtotal) {
                        $session->update([
                            'total_amount' => $subtotal,
                        ]);
                    }
                }

                return [
                    'mode' => 'single',
                    'table_session' => $session->load([
                        'table',
                        'cashier',
                        'order',
                        'headCounts.headRule'
                    ]),
                ];
            }

            /*
            |----------------------------------------------------------------------
            | GROUP ADMISSION
            |----------------------------------------------------------------------
            | Create sessions on AVAILABLE CHILD TABLES under selected parent
            */
            if ($isGroupAdmission) {
                $parentTable = Table::with('children')->findOrFail($resolvedTableId);

                $children = $parentTable->children ?? collect();

                if ($children->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected parent table has no child slots.'
                    ], 422);
                }

                $childIds = $children->pluck('id')->all();

                $openSessions = TableSession::whereIn('table_id', $childIds)
                    ->where('status', 'open')
                    ->get()
                    ->keyBy('table_id');

                $availableChildren = $children
                    ->filter(function ($child) use ($openSessions) {
                        return !$openSessions->has($child->id);
                    })
                    ->values();

                if ($availableChildren->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No available child tables for the selected parent table.'
                    ], 422);
                }

                $remainingPax = (int) $request->pax;

                $totalAvailableCapacity = $availableChildren->sum(function ($child) {
                    return (int) ($child->capacity ?? 1);
                });

                if ($remainingPax > $totalAvailableCapacity) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Entered pax exceeds available capacity for the selected parent table.'
                    ], 422);
                }

                $estimatedTotal = (float) ($request->estimated_total ?? 0);
                $createdSessions = [];
                $allocatedPaxTotal = 0;

                foreach ($availableChildren as $index => $child) {
                    if ($remainingPax <= 0) {
                        break;
                    }

                    $childCapacity = max((int) ($child->capacity ?? 1), 1);
                    $allocatedPax = min($remainingPax, $childCapacity);

                    $allocatedPaxTotal += $allocatedPax;
                    $remainingPax -= $allocatedPax;

                    $sessionTotal = 0.0;
                    if ((int) $request->pax > 0) {
                        $sessionTotal = round(($estimatedTotal / (int) $request->pax) * $allocatedPax, 2);
                    }

                    $session = TableSession::create([
                        'ref_no' => $nextRefNo(),
                        'table_id' => $child->id,
                        'pax' => $allocatedPax,
                        'status' => 'open',
                        'frontdoor_id' => auth()->id(),
                        'cashier_id' => null,
                        'order_id' => null,
                        'total_amount' => $sessionTotal,
                        'customer_name' => $request->customer_name,
                    ]);

                    $createdSessions[] = $session;
                }

                /*
                |--------------------------------------------------------------
                | Put pricing breakdown on FIRST created session
                |--------------------------------------------------------------
                */
                if (!empty($createdSessions)) {
                    $primarySession = $createdSessions[0];
                    $breakdown = collect($request->pricing_breakdown ?? []);

                    if ($breakdown->isNotEmpty()) {
                        foreach ($breakdown as $item) {
                            $ruleId = (int) ($item['rule_id'] ?? 0);
                            $qty = (int) ($item['qty'] ?? 0);

                            if ($ruleId <= 0 || $qty <= 0) {
                                continue;
                            }

                            $rule = HeadPricingRule::findOrFail($ruleId);
                            $priceSnapshot = (float) $rule->price;
                            $subtotal = $priceSnapshot * $qty;

                            $primarySession->headCounts()->create([
                                'head_pricing_rule_id' => $rule->id,
                                'qty' => $qty,
                                'price_snapshot' => $priceSnapshot,
                                'subtotal' => $subtotal,
                            ]);
                        }

                        $computedTotal = (float) $primarySession->headCounts()->sum('subtotal');

                        /*
                        | Keep total breakdown amount on first session only if you want
                        | OR comment this out if you prefer split totals only.
                        */
                        $primarySession->update([
                            'total_amount' => $computedTotal,
                        ]);
                    } elseif ($request->filled('rule_counts')) {
                        foreach ($request->rule_counts as $ruleId => $qty) {
                            $ruleId = (int) $ruleId;
                            $qty = (int) $qty;

                            if ($ruleId <= 0 || $qty <= 0) {
                                continue;
                            }

                            $rule = HeadPricingRule::findOrFail($ruleId);
                            $priceSnapshot = (float) $rule->price;
                            $subtotal = $priceSnapshot * $qty;

                            $primarySession->headCounts()->create([
                                'head_pricing_rule_id' => $rule->id,
                                'qty' => $qty,
                                'price_snapshot' => $priceSnapshot,
                                'subtotal' => $subtotal,
                            ]);
                        }

                        $computedTotal = (float) $primarySession->headCounts()->sum('subtotal');

                        $primarySession->update([
                            'total_amount' => $computedTotal,
                        ]);
                    }
                }

                $loadedSessions = TableSession::with([
                        'table',
                        'cashier',
                        'order',
                        'headCounts.headRule'
                    ])
                    ->whereIn('id', collect($createdSessions)->pluck('id')->all())
                    ->get()
                    ->values();

                return [
                    'mode' => 'group',
                    'table_sessions' => $loadedSessions,
                ];
            }

            return null;
        });

        if (isset($result['mode']) && $result['mode'] === 'group') {
            return response()->json([
                'success' => true,
                'table_sessions' => $result['table_sessions'],
            ]);
        }

        return response()->json([
            'success' => true,
            'table_session' => $result['table_session'],
        ]);
    }

    public function vacantTable(Request $request, Table $table)
    {
        $request->validate([
            'status' => 'required|in:vacant,occupied',
            'pax'    => 'nullable|integer|min:1',
        ]);

        try {
            $result = DB::transaction(function () use ($request, $table) {
                $pax = $request->pax ?? 1;

                $session = $table->sessions()
                    ->with(['order', 'table', 'cashier'])
                    ->where('status', 'open')
                    ->lockForUpdate()
                    ->first();

                // OCCUPIED
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
                            'pax' => $pax,
                        ]);
                        $session->refresh();
                    }

                    return [
                        'success' => true,
                        'message' => 'Table marked as occupied.',
                        'session' => $session,
                    ];
                }

                // VACANT
                if (!$session) {
                    return [
                        'success' => false,
                        'message' => 'No open table session found.',
                        'session' => null,
                    ];
                }

                // block vacancy if not yet settled
                if (is_null($session->order_id) || is_null($session->cashier_id)) {
                    return [
                        'success' => false,
                        'message' => 'Cannot vacant table. Customer is still dining or payment is not yet settled.',
                        'session' => $session,
                    ];
                }

                if ($session->order && isset($session->order->payment_status) && $session->order->payment_status !== 'paid') {
                    return [
                        'success' => false,
                        'message' => 'Cannot vacant table. Order is not yet fully paid.',
                        'session' => $session,
                    ];
                }

                $updated = $session->update([
                    'status'    => 'closed',
                    'closed_at' => now(),
                ]);

                $session->refresh();

                return [
                    'success' => true,
                    'message' => 'Table marked as vacant.',
                    'session' => $session,
                ];
            });

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'table_session' => $result['session']?->load(['table', 'cashier', 'order']),
            ], $result['success'] ? 200 : 422);

        } catch (\Throwable $e) {
            Log::error('vacantTable failed', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update table status.',
            ], 500);
        }
    }

    public function cancelQueuedTable(Request $request, Table $table)
    {
        $request->validate([
            'remarks' => 'required|string|max:1000',
            'manager_id' => 'required|exists:users,id',
            'manager_password' => 'required|string',
        ]);

        $manager = User::where('id', $request->manager_id)
            ->where('role', 1)
            ->first();

        if (!$manager) {
            return response()->json([
                'success' => false,
                'message' => 'Manager account not found or not authorized.'
            ], 422);
        }

        if (!Hash::check($request->manager_password, $manager->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid manager credential.'
            ], 422);
        }

        $session = DB::transaction(function () use ($request, $table, $manager) {
            $session = $table->sessions()
                ->where('status', 'open')
                ->first();

            if (!$session) {
                abort(404, 'No open table session found.');
            }

            $session->update([
                'status' => 'cancelled',
                'remarks' => $request->remarks,
                'closed_at' => now(),
                // 'approved_by_manager_id' => $manager->id, // add if you have this column
            ]);

            return $session;
        });

        return response()->json([
            'success' => true,
            'message' => 'Table session cancelled successfully.',
            'table_session' => $session->load(['table', 'cashier', 'order'])
        ]);
    }

    public function getOpenSessions()
    {
        $table_sessions = TableSession::with(['table', 'headCounts.headRule', 'addons', 'order','reservation'])
            ->where('status', 'open')
            ->whereNull('cashier_id')
            ->where(function ($q) {
                $q->whereNull('order_id')->orWhere('total_amount', 0);
            })
            ->orderBy('ref_no')
            ->get();

        return response()->json(['table_sessions' => $table_sessions]);
    }
}