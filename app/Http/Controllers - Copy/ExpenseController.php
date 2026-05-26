<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    // public function index(Request $request)
    // {
    //     $search = $request->search;
    //     $category = $request->category;

    //     $today = Carbon::today()->toDateString(); // e.g., "2026-02-24"

    //     $expenses = Expense::with(['category', 'creator', 'updater']) // eager load category
    //         ->when($search, fn($q) => $q->where('description', 'like', "%{$search}%"))
    //         ->when($category, fn($q) => $q->where('expense_category_id', $category))
    //         // ->whereDate('expense_date', $today) // only today's expenses
    //         ->orderBy('expense_date', 'desc')
    //         ->get();

    //     $categories = ExpenseCategory::where('status', 1)->get();

    //     $totalExpenses = $expenses->sum('amount');

    //     return Inertia::render('Expenses/Index', [
    //         'expenses' => $expenses,
    //         'categories' => $categories,
    //         'totalExpenses' => $totalExpenses,
    //         'filters' => [
    //             'search' => $search,
    //             'category' => $category,
    //             'date' => $today
    //         ]
    //     ]);
    // }

    public function index()
    {
        $today = Carbon::today()->toDateString();

        $expenses = Expense::with(['category', 'creator', 'updater'])
            ->whereDate('expense_date', $today)
            ->orderBy('expense_date', 'desc')
            ->get();

        $categories = ExpenseCategory::where('status', 1)->get();

        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses,
            'categories' => $categories,
            'today' => $today,
        ]);
    }

    public function store(Request $request)
    {
        Log::info('request expense');
        Log::info($request->all());

        $request->validate([
            'expense_category_id' => 'required|integer',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
        ]);

        Expense::create([
            'expense_category_id' => $request->expense_category_id,
            'description' => $request->description,
            'amount' => $request->amount,
            'expense_date' => $request->expense_date,
            'created_by' => auth()->id(),
            'updated_by' => NULL,
        ]);

        return redirect()->back()->with('success', 'Expense added successfully.');
    }

    public function getFilteredExpenses(Request $request)
    {
        $search     = $request->search;
        $category   = $request->category;
        $startDate  = $request->start_date;
        $endDate    = $request->end_date;

        $today = Carbon::today()->toDateString();

        $query = Expense::with(['category', 'creator', 'updater']);

        /*
        |--------------------------------------------------------------------------
        | If ALL filters are empty → return today's records only
        |--------------------------------------------------------------------------
        */
        if (!$search && !$category && !$startDate && !$endDate) {
            $query->whereDate('expense_date', $today);
        } else {

            // Apply filters only if provided
            if ($search) {
                $query->where('description', 'like', "%{$search}%");
            }

            if ($category) {
                $query->where('expense_category_id', $category);
            }

            // If BOTH dates provided → range
            if ($startDate && $endDate) {
                $query->whereBetween('expense_date', [$startDate, $endDate]);
            }

            // If ONLY start date provided
            if ($startDate && !$endDate) {
                $query->whereDate('expense_date', '>=', $startDate);
            }

            // If ONLY end date provided
            if (!$startDate && $endDate) {
                $query->whereDate('expense_date', '<=', $endDate);
            }
        }

        $expenses = $query->orderBy('expense_date', 'desc')->get();

        return response()->json([
            'expenses' => $expenses,
        ]);
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return redirect()->back()->with('success', 'Expense deleted successfully.');
    }
}
