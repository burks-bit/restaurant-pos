<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DTRService;
use Illuminate\Support\Facades\Log;

class DTRController extends Controller
{
    protected $dtrService;

    public function __construct(DTRService $dtrService)
    {
        $this->dtrService = $dtrService;
    }

    public function generatePayroll()
    {
        try {
            return $this->dtrService->generatePayroll();
        } catch (\Throwable $e) {
            Log::error('DTRController@generatePayroll failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load payroll generator.');
        }
    }

    public function clockIn(Request $request)
    {
        try {
            return $this->dtrService->clockIn($request);
        } catch (\Throwable $e) {
            Log::error('DTRController@clockIn failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to clock in.'
            ], 500);
        }
    }

    public function clockOut(Request $request)
    {
        try {
            return $this->dtrService->clockOut($request);
        } catch (\Throwable $e) {
            Log::error('DTRController@clockOut failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to clock out.'
            ], 500);
        }
    }

    public function getAllEmployeeOvertime()
    {
        try {
            return $this->dtrService->getAllEmployeeOvertime();
        } catch (\Throwable $e) {
            Log::error('DTRController@getAllEmployeeOvertime failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load employee overtime.');
        }
    }

    public function getAllEmployeeDailyLogs(Request $request)
    {
        try {
            $validated = $request->validate([
                'date' => ['nullable', 'date'],
            ]);

            return $this->dtrService->getAllEmployeeDailyLogs($validated['date'] ?? null);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Unable to load employee daily logs. Please try again.');
        }
    }

    public function saveOvertime(Request $request)
    {
        try {
            return $this->dtrService->saveOvertime($request);
        } catch (\Throwable $e) {
            Log::error('DTRController@saveOvertime failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to save overtime.'
            ], 500);
        }
    }

    public function filterAllEmployeeOvertime(Request $request)
    {
        try {
            return $this->dtrService->filterAllEmployeeOvertime($request);
        } catch (\Throwable $e) {
            Log::error('DTRController@filterAllEmployeeOvertime failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to filter overtime.'
            ], 500);
        }
    }

    public function updateEmployeeOTStatus(Request $request, $id)
    {
        try {
            return $this->dtrService->updateEmployeeOTStatus($request, $id);
        } catch (\Throwable $e) {
            Log::error('DTRController@updateEmployeeOTStatus failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update overtime status.'
            ], 500);
        }
    }

    public function summary(Request $request)
    {
        try {
            return $this->dtrService->summary($request);
        } catch (\Throwable $e) {
            Log::error('DTRController@summary failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to generate payroll summary.'
            ], 500);
        }
    }

    public function printPayrollSummary(Request $request)
    {
        try {
            return $this->dtrService->printPayrollSummary($request);
        } catch (\Throwable $e) {
            Log::error('DTRController@printPayrollSummary failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to print payroll summary.');
        }
    }
}