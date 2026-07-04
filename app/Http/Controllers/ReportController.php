<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReportService;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index(Request $request)
    {
        try {
            return $this->reportService->index($request);
        } catch (\Throwable $e) {
            Log::error('ReportController@index failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load reports.');
        }
    }

    public function exportPdf(Request $request)
    {
        try {
            return $this->reportService->exportPdf($request);
        } catch (\Throwable $e) {
            Log::error('ReportController@exportPdf failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to export PDF.');
        }
    }

    public function exportExcel()
    {
        try {
            return $this->reportService->exportExcel();
        } catch (\Throwable $e) {
            Log::error('ReportController@exportExcel failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to export Excel.');
        }
    }

    public function fetchInventoryReport(Request $request)
    {
        try {
            return $this->reportService->fetchInventoryReport($request);
        } catch (\Throwable $e) {
            Log::error('ReportController@fetchInventoryReport failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch inventory report.'
            ], 500);
        }
    }

    public function expenseReportIndex()
    {
        try {
            return $this->reportService->expenseReportIndex();
        } catch (\Throwable $e) {
            Log::error('ReportController@expenseReportIndex failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load expense report page.');
        }
    }

    public function fetchExpenseReport(Request $request)
    {
        try {
            return $this->reportService->fetchExpenseReport($request);
        } catch (\Throwable $e) {
            Log::error('ReportController@fetchExpenseReport failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch expense report.'
            ], 500);
        }
    }

    public function printInventoryReport(Request $request)
    {
        try {
            return $this->reportService->printInventoryReport($request);
        } catch (\Throwable $e) {
            Log::error('ReportController@printInventoryReport failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to print inventory report.');
        }
    }

    public function printExpensesReport(Request $request)
    {
        try {
            return $this->reportService->printExpensesReport($request);
        } catch (\Throwable $e) {
            Log::error('ReportController@printExpensesReport failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to print expenses report.');
        }
    }

    public function salesReportIndex()
    {
        try {
            return $this->reportService->salesReportIndex();
        } catch (\Throwable $e) {
            Log::error('ReportController@salesReportIndex failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load sales report page.');
        }
    }

    public function fetchSalesReport(Request $request)
    {
        try {
            return $this->reportService->fetchSalesReport($request);
        } catch (\Throwable $e) {
            Log::error('ReportController@fetchSalesReport failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch sales report.'
            ], 500);
        }
    }

    public function printSalesReport(Request $request)
    {
        try {
            return $this->reportService->printSalesReport($request);
        } catch (\Throwable $e) {
            Log::error('ReportController@printSalesReport failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to print sales report.');
        }
    }

    public function printSalesSummaryReport(Request $request)
    {
        try {
            return $this->reportService->printSalesSummaryReport($request);
        } catch (\Throwable $e) {
            Log::error('ReportController@printSalesSummaryReport failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to print sales report.');
        }
    }

    public function exportSalesReportExcel(Request $request)
    {
        try {
            return $this->reportService->exportSalesReportExcel($request);
        } catch (\Throwable $e) {
            Log::error('ReportController@exportSalesReportExcel failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to print sales report.');
        }
    }

    public function exportSalesSummaryReport(Request $request)
    {
        try {
            return $this->reportService->exportSalesSummaryReport($request);
        } catch (\Throwable $e) {
            Log::error('ReportController@exportSalesSummaryReport failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to print sales summary report.');
        }
    }

    public function exportInventoryReportExcel(Request $request)
    {
        try {
            return $this->reportService->exportInventoryReportExcel($request);
        } catch (\Throwable $e) {
            Log::error('ReportController@exportInventoryReportExcel failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to print inventory report.');
        }
    }
}