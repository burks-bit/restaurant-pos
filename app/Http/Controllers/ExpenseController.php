<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Support\Facades\Log;

class ExpenseController extends Controller
{
    protected $expenseService;

    public function __construct(ExpenseService $expenseService)
    {
        $this->expenseService = $expenseService;
    }

    public function index()
    {
        try {
            return $this->expenseService->index();
        } catch (\Throwable $e) {
            Log::error('ExpenseController@index failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load expenses.');
        }
    }

    public function store(Request $request)
    {
        try {
            return $this->expenseService->store($request);
        } catch (\Throwable $e) {
            Log::error('ExpenseController@store failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to save expense.');
        }
    }

    public function getFilteredExpenses(Request $request)
    {
        try {
            return $this->expenseService->getFilteredExpenses($request);
        } catch (\Throwable $e) {
            Log::error('ExpenseController@getFilteredExpenses failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to filter expenses.'
            ], 500);
        }
    }

    public function destroy(Expense $expense)
    {
        try {
            return $this->expenseService->destroy($expense);
        } catch (\Throwable $e) {
            Log::error('ExpenseController@destroy failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete expense.');
        }
    }

    public function cashierExpenseIndex()
    {
        try {
            return $this->expenseService->cashierExpenseIndex();
        } catch (\Throwable $e) {
            Log::error('ExpenseController@cashierExpenseIndex failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load cashier expenses.');
        }
    }

    public function fetchCashierExpenses(Request $request)
    {
        try {
            return $this->expenseService->fetchCashierExpenses($request);
        } catch (\Throwable $e) {
            Log::error('ExpenseController@fetchCashierExpenses failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load cashier expenses.');
        }
    
        }
    public function updateCashierExpenses(Request $request, $id)
    {
        try {
            return $this->expenseService->updateCashierExpenses($request, $id);
        } catch (\Throwable $e) {
            Log::error('ExpenseController@updateCashierExpenses failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load cashier expenses.');
        }
    }
}