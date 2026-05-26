<?php

namespace App\Services;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\PettyCash;
use Illuminate\Support\Facades\DB;

class PettyCashService
{
    public function index()
    {
        $pettyCashes = PettyCash::with([
                'details',
                'postedByUser',
                'updatedByUser',
                'details.postedByUser'
            ])
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($pc) {
                $pc->total_used = $pc->details->sum('amount');
                $pc->remaining = $pc->total_amount - $pc->total_used;
                return $pc;
            });

        return Inertia::render('PettyCashes/Index', [
            'pettyCashes' => $pettyCashes,
        ]);
    }

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
            'denominations' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $pettyCash) {
            $currentTotalUsed = $pettyCash->details()->sum('amount');
            $newTotalUsed = $currentTotalUsed + $request->amount;

            if ($newTotalUsed > $pettyCash->total_amount) {
                return response()->json([
                    'message' => 'Amount exceeds remaining petty cash balance.'
                ], 422);
            }

            $detail = $pettyCash->details()->create([
                'purpose' => $request->purpose,
                'amount' => $request->amount,
                'denominations' => $request->denominations,
                'notes' => $request->notes,
                'posted_by' => auth()->id(),
            ]);

            $pettyCash->update([
                'amount_used' => $newTotalUsed,
                'remaining' => $pettyCash->total_amount - $newTotalUsed,
                'updated_by' => auth()->id(),
            ]);

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