<?php

namespace App\Http\Controllers;

use App\Models\EmployeeDeductionTemplate;
use App\Services\DeductionTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeductionTemplateController extends Controller
{
    protected $deductionTemplateService;

    public function __construct(DeductionTemplateService $deductionTemplateService)
    {
        $this->deductionTemplateService = $deductionTemplateService;
    }

    public function index()
    {
        try {
            return $this->deductionTemplateService->index();
        } catch (\Throwable $e) {
            Log::error('DeductionTemplateController@index failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to load deduction templates.');
        }
    }

    public function store(Request $request)
    {
        try {
            return $this->deductionTemplateService->store($request);
        } catch (\Throwable $e) {
            Log::error('DeductionTemplateController@store failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to save deduction template.');
        }
    }

    public function update(Request $request, EmployeeDeductionTemplate $employeeDeductionTemplate)
    {
        try {
            return $this->deductionTemplateService->update($request, $employeeDeductionTemplate);
        } catch (\Throwable $e) {
            Log::error('DeductionTemplateController@update failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to update deduction template.');
        }
    }

    public function destroy(EmployeeDeductionTemplate $employeeDeductionTemplate)
    {
        try {
            return $this->deductionTemplateService->destroy($employeeDeductionTemplate);
        } catch (\Throwable $e) {
            Log::error('DeductionTemplateController@destroy failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to delete deduction template.');
        }
    }
}
