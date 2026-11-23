<?php

namespace App\Services;

use App\Models\EarningType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class EarningTypeService
{
    public function index()
    {
        return Inertia::render('EarningTypes/Index', [
            'earning_types' => EarningType::orderBy('name')
                ->get(['id', 'name', 'description', 'status']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRequest($request);

        EarningType::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? true,
        ]);

        return back()->with('success', 'Earning type created successfully.');
    }

    public function update(Request $request, EarningType $earningType)
    {
        $validated = $this->validateRequest($request);

        $earningType->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? true,
        ]);

        return back()->with('success', 'Earning type updated successfully.');
    }

    public function destroy(EarningType $earningType)
    {
        try {
            $earningType->delete();

            return back()->with('success', 'Earning type deleted successfully.');
        } catch (\Throwable $e) {
            Log::error('EarningTypeService@destroy failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to delete earning type.');
        }
    }

    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'boolean',
        ]);
    }
}
