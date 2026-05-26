<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Services\PayrollService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayrollController extends Controller
{
    protected $payrollService;

    public function __construct(PayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    public function payrollIndex()
    {
        try {
            return $this->payrollService->payrollIndex();
        } catch (\Throwable $e) {
            Log::error('PayrollController@payrollIndex failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load payrolls.');
        }
    }

    public function postPayroll(Request $request)
    {
        try {
            return $this->payrollService->postPayroll($request);
        } catch (\Throwable $e) {
            Log::error('PayrollController@postPayroll failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to post payroll.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function viewPayrollDetails(Payroll $payroll)
    {
        try {
            return $this->payrollService->viewPayrollDetails($payroll);
        } catch (\Throwable $e) {
            Log::error('PayrollController@viewPayrollDetails failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load payroll details.');
        }
    }

    public function postEmployeeDeduction(Request $request, Payroll $payroll)
    {
        try {
            return $this->payrollService->postEmployeeDeduction($request, $payroll);
        } catch (\Throwable $e) {
            Log::error('PayrollController@postEmployeeDeduction failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to save deductions.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function postEmployeeEarnings(Request $request, Payroll $payroll)
    {
        try {
            return $this->payrollService->postEmployeeEarnings($request, $payroll);
        } catch (\Throwable $e) {
            Log::error('PayrollController@postEmployeeEarnings failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to save earnings.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function printPayslipSingle(Payroll $payroll, PayrollItem $payrollItem)
    {
        try {
            return $this->payrollService->printPayslipSingle($payroll, $payrollItem);
        } catch (\Throwable $e) {
            Log::error('PayrollController@printPayslipSingle failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to generate payslip.');
        }
    }

    public function printPayslipSelected(Request $request, Payroll $payroll)
    {
        try {
            return $this->payrollService->printPayslipSelected($request, $payroll);
        } catch (\Throwable $e) {
            Log::error('PayrollController@printPayslipSelected failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to generate selected payslips.');
        }
    }
}