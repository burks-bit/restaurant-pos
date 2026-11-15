<?php

namespace App\Services;

use App\Models\DeductionType;
use App\Models\Employee;
use App\Models\EmployeeDeductionTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DeductionTemplateService
{
    public function index()
    {
        $employees = Employee::orderBy('first_name')
            ->get(['id', 'employee_code', 'first_name', 'last_name']);

        $deduction_types = DeductionType::where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $templates = EmployeeDeductionTemplate::with([
            'employee:id,employee_code,first_name,last_name',
            'details.deductionType:id,name',
        ])->latest()->get();

        return Inertia::render('DeductionTemplates/Index', [
            'employees' => $employees,
            'deduction_types' => $deduction_types,
            'templates' => $templates,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRequest($request);

        try {
            DB::transaction(function () use ($validated) {
                $template = EmployeeDeductionTemplate::updateOrCreate(
                    ['employee_id' => $validated['employee_id']]
                );

                $this->syncDetails($template, $validated['details'] ?? []);
            });

            return redirect()->back()->with('success', 'Deduction template saved successfully.');
        } catch (\Throwable $e) {
            Log::error('DeductionTemplateService@store failed: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Failed to save deduction template.');
        }
    }

    public function update(Request $request, EmployeeDeductionTemplate $employeeDeductionTemplate)
    {
        $validated = $this->validateRequest($request);

        try {
            DB::transaction(function () use ($validated, $employeeDeductionTemplate) {
                $employeeDeductionTemplate->update([
                    'employee_id' => $validated['employee_id'],
                ]);

                $this->syncDetails($employeeDeductionTemplate, $validated['details'] ?? []);
            });

            return redirect()->back()->with('success', 'Deduction template updated successfully.');
        } catch (\Throwable $e) {
            Log::error('DeductionTemplateService@update failed: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Failed to update deduction template.');
        }
    }

    public function destroy(EmployeeDeductionTemplate $employeeDeductionTemplate)
    {
        try {
            $employeeDeductionTemplate->delete();

            return redirect()->back()->with('success', 'Deduction template deleted successfully.');
        } catch (\Throwable $e) {
            Log::error('DeductionTemplateService@destroy failed: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Failed to delete deduction template.');
        }
    }

    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'employee_id'                    => 'required|exists:employees,id',
            'details'                        => 'nullable|array',
            'details.*.deduction_type_id'    => 'required|exists:deduction_types,id',
            'details.*.cutoff'               => 'required|in:15th,30th',
            'details.*.default_value'        => 'nullable|numeric|min:0',
        ]);
    }

    private function syncDetails(EmployeeDeductionTemplate $template, array $details): void
    {
        $template->details()->delete();

        foreach ($details as $detail) {
            if (empty($detail['deduction_type_id'])) {
                continue;
            }

            $template->details()->create([
                'deduction_type_id' => $detail['deduction_type_id'],
                'cutoff'            => $detail['cutoff'] ?? '15th',
                'default_value'     => $detail['default_value'] ?? 0,
            ]);
        }
    }
}
