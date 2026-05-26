<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\PettyCash;
use App\Models\PettyCashDetail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PettyCashController extends Controller
{
    public function index()
    {
        
        // Load petty cash entries with their details
        $pettyCashes = PettyCash::with(['details', 'postedByUser', 'updatedByUser', 'details.postedByUser'])->orderBy('date', 'desc')->get()->map(function ($pc) {
            $pc->total_used = $pc->details->sum('amount');
            $pc->remaining = $pc->total_amount - $pc->total_used;
            return $pc;
        });

        // Log::info('PettyCashController');
        // Log::info($pettyCashes);

        return Inertia::render('PettyCashes/Index', [
            'pettyCashes' => $pettyCashes,
        ]);
    }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'date' => 'required|date',
    //         'total_amount' => 'required|numeric|min:0',
    //         'amount_used' => 'nullable|numeric|min:0',
    //         'remaining' => 'nullable|numeric|min:0',
    //         'notes' => 'nullable|string',
    //     ]);

    //     $petty_cash = PettyCash::create([
    //         'date' => $request->date,
    //         'total_amount' => $request->total_amount,
    //         'amount_used' => NULL,
    //         'remaining' => NULL,
    //         'denominations' => $request->denominations,
    //         'notes' => $request->notes,
    //         'posted_by' => auth()->id(),
    //         'updated_by' => NULL,
    //     ]);

    //     // Add computed fields
    //     $petty_cash->total_used = 0;
    //     $petty_cash->remaining = $petty_cash->total_amount;

    //     return response()->json([
    //         'pettyCash' => $petty_cash,
    //         'message' => 'Petty Cash posted successfully.'
    //     ]);
    // }
    

    // public function storeDetail(Request $request, PettyCash $pettyCash)
    // {
    //     // Log::info('storeDetail');
    //     // Log::info($request->all());
    //     $request->validate([
    //         'purpose' => 'required|string',
    //         'amount' => 'required|numeric|min:0',
    //         'denominations' => 'nullable|string', // JSON string if needed
    //         'notes' => 'nullable|string',
    //         'posted_by' => 'nullable|string',
    //     ]);

    //     // Create the petty cash usage detail
    //     $detail = $pettyCash->details()->create([
    //         'purpose' => $request->purpose,
    //         'amount' => $request->amount,
    //         'denominations' => $request->denominations,
    //         'notes' => $request->notes,
    //         'posted_by' => auth()->id(),
    //     ]);

    //     // Recalculate totals
    //     $totalUsed = $pettyCash->details()->sum('amount');
    //     $pettyCash->update([
    //         'amount_used' => $totalUsed,
    //         'remaining' => $pettyCash->total_amount - $totalUsed,
    //         'updated_by' => auth()->id(),
    //     ]);

    //     // Reload the relationship to include latest details
    //     $pettyCash->load('details');

    //     return response()->json([
    //         'pettyCash' => $pettyCash,
    //         'detail' => $detail,
    //         'message' => 'Usage posted successfully.',
    //     ]);
    // }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'denominations' => 'nullable|array',
        ]);

        $petty_cash = PettyCash::create([
            'date' => $request->date,
            'total_amount' => $request->total_amount,
            'amount_used' => 0,
            'remaining' => $request->total_amount,
            'denominations' => $request->denominations,
            'notes' => $request->notes,
            'posted_by' => auth()->id(),
            'updated_by' => null,
        ]);
    
        $petty_cash->load(['postedByUser', 'updatedByUser']);

        return response()->json([
            'pettyCash' => $petty_cash,
            'message' => 'Petty Cash posted successfully.'
        ]);
    }

    public function storeDetail(Request $request, PettyCash $pettyCash)
    {
        $request->validate([
            'purpose' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'denominations' => 'nullable|array', // since model casts to array
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $pettyCash) {

            // Compute new total BEFORE inserting (prevent overspending)
            $currentTotalUsed = $pettyCash->details()->sum('amount');
            $newTotalUsed = $currentTotalUsed + $request->amount;

            if ($newTotalUsed > $pettyCash->total_amount) {
                return response()->json([
                    'message' => 'Amount exceeds remaining petty cash balance.'
                ], 422);
            }

            // Create detail
            $detail = $pettyCash->details()->create([
                'purpose' => $request->purpose,
                'amount' => $request->amount,
                'denominations' => $request->denominations,
                'notes' => $request->notes,
                'posted_by' => auth()->id(),
            ]);

            // Update petty cash totals
            $pettyCash->update([
                'amount_used' => $newTotalUsed,
                'remaining' => $pettyCash->total_amount - $newTotalUsed,
                'updated_by' => auth()->id(),
            ]);

            // Reload with user relationships
            $pettyCash->load([
                'postedByUser',
                'updatedByUser',
                'details.postedByUser'
            ]);

            return response()->json([
                'pettyCash' => $pettyCash,
                'detail' => $detail,
                'message' => 'Usage posted successfully.',
            ]);
        });
    }
}
