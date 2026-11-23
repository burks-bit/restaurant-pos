<?php

namespace App\Services;

use App\Models\DeductionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DeductionTypeService
{
    public function index()
    {
        return Inertia::render('DeductionTypes/Index', [
            'deduction_types' => DeductionType::orderBy('name')
                ->get(['id', 'name', 'description', 'status']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRequest($request);

        DeductionType::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? true,
        ]);

        return back()->with('success', 'Deduction type created successfully.');
    }

    public function update(Request $request, DeductionType $deductionType)
    {
        $validated = $this->validateRequest($request);

        $deductionType->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? true,
        ]);

        return back()->with('success', 'Deduction type updated successfully.');
    }

    public function destroy(DeductionType $deductionType)
    {
        // employee_deduction_template_details.deduction_type_id cascades on delete,
        // so block deletion while the type is still referenced by a template.
        if ($deductionType->templateDetails()->exists()) {
            return back()->with('error', 'Cannot delete deduction type: it is used by one or more deduction templates. Deactivate it instead.');
        }

        try {
            $deductionType->delete();

            return back()->with('success', 'Deduction type deleted successfully.');
        } catch (\Throwable $e) {
            Log::error('DeductionTypeService@destroy failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to delete deduction type.');
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
