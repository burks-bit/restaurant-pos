<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Shift;
use App\Exports\ExpensesExport;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ExpenseService
{
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

    public function cashierExpenseIndex()
    {
        $now = Carbon::now();
        $time = $now->format('H:i:s');

        $current_shift = Shift::where(function ($q) use ($time) {
            // Normal shift: start <= now <= end  (e.g. 08:00 - 17:00)
            $q->where(function ($q2) use ($time) {
                $q2->whereRaw('start_time <= end_time')
                    ->where('start_time', '<=', $time)
                    ->where('end_time', '>=', $time);
            })
            // Overnight shift: start > end  (e.g. 22:00 - 06:00)
            // valid if now >= start  OR  now <= end
            ->orWhere(function ($q2) use ($time) {
                $q2->whereRaw('start_time > end_time')
                    ->where(function ($q3) use ($time) {
                        $q3->where('start_time', '<=', $time)
                            ->orWhere('end_time', '>=', $time);
                    });
            });
        })->first();

        $expenses = Expense::with(['category', 'creator', 'updater'])
            ->when($current_shift, function ($query) use ($current_shift, $now) {
                $query->where('shift_id', $current_shift->id);

                // For overnight shifts that span midnight, expenses may have been
                // created yesterday (before midnight) or today (after midnight).
                // Fetch from shift start up to now to cover both calendar days.
                if ($current_shift->start_time > $current_shift->end_time) {
                    $shiftStartedYesterday = $now->copy()->isBefore(
                        Carbon::today()->setTimeFromTimeString($current_shift->start_time)
                    );

                    $from = $shiftStartedYesterday
                        ? Carbon::yesterday()->setTimeFromTimeString($current_shift->start_time)
                        : Carbon::today()->setTimeFromTimeString($current_shift->start_time);

                    $query->where('created_at', '>=', $from)
                        ->where('created_at', '<=', $now);
                } else {
                    $query->whereDate('expense_date', $now->toDateString());
                }
            }, function ($query) use ($now) {
                // No active shift found — fall back to today with no shift filter
                $query->whereDate('expense_date', $now->toDateString())
                    ->whereNotNull('shift_id');
            })
            ->orderBy('expense_date', 'desc')
            ->get();

        return Inertia::render('Expenses/CashierExpensePage', [
            'expenses'      => $expenses,
            'categories'    => ExpenseCategory::where('status', 1)->get(),
            'current_shift' => $current_shift,
            'today'         => $now->toDateString(),
        ]);
    }

    public function store(Request $request)
    {
        // Default cashier/shift expenses to "Others" only when no category is provided
        if ($request->filled('shift_id') && !$request->filled('expense_category_id')) {
            $request->merge([
                'expense_category_id' => 5
            ]);
        }

        $request->validate([
            'expense_category_id' => 'required|integer|exists:expense_categories,id',
            'requesting_name' => 'nullable|string|max:255',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'account_title' => 'nullable|string|max:255',
        ]);

        Expense::create([
            'expense_category_id' => $request->expense_category_id,
            'requesting_name' => $request->requesting_name,
            'description' => $request->description,
            'amount' => $request->amount,
            'account_title' => $request->account_title,
            'expense_date' => now()->toDateString(),
            'shift_id' => $request->shift_id,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Expense added successfully.');
    }

    public function getFilteredExpenses(Request $request)
    {
        $expenses = $this->buildFilteredExpensesQuery($request)->get();

        return response()->json([
            'expenses' => $expenses,
        ]);
    }

    protected function buildFilteredExpensesQuery(Request $request)
    {
        $search    = $request->input('search');
        $category  = $request->input('category');
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        $today = Carbon::today()->toDateString();

        $query = Expense::with(['category', 'creator', 'updater']);

        if (!$search && !$category && !$startDate && !$endDate) {
            $query->whereDate('expense_date', $today);
        } else {
            if ($search) {
                $query->where('description', 'like', "%{$search}%");
            }

            if ($category) {
                $query->where('expense_category_id', $category);
            }

            if ($startDate && $endDate) {
                $query->whereBetween('expense_date', [$startDate, $endDate]);
            }

            if ($startDate && !$endDate) {
                $query->whereDate('expense_date', '>=', $startDate);
            }

            if (!$startDate && $endDate) {
                $query->whereDate('expense_date', '<=', $endDate);
            }
        }

        return $query->orderBy('expense_date', 'desc');
    }

    public function exportExpensesExcel(Request $request)
    {
        $expenses = $this->buildFilteredExpensesQuery($request)->get();

        $filename = 'HSB_Expenses_' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new ExpensesExport($expenses, auth()->user()?->name ?? ''),
            $filename
        );
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return redirect()->back()->with('success', 'Expense deleted successfully.');
    }

    public function fetchCashierExpenses(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $role = Auth::user()->role;

        $query = Expense::with('creator')
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'desc');

        // Only restrict if NOT role 0 or 1
        if (!in_array($role, [0, 1])) {
            $query->where('created_by', Auth::id());
        }

        $expenses = $query->get();

        return response()->json(['expenses' => $expenses]);
    }

    public function updateCashierExpenses(Request $request, $id)
    {
        $request->validate([
            'expense_category_id' => 'sometimes|integer|exists:expense_categories,id',
            'requesting_name'     => 'nullable|string|max:255',
            'amount'              => 'required|numeric|min:0.01',
            'description'         => 'required|string|max:255',
            'account_title'       => 'nullable|string|max:255',
        ]);

        $expense = Expense::findOrFail($id);

        $expense->update([
            'expense_category_id' => $request->input('expense_category_id', $expense->expense_category_id),
            'requesting_name'     => $request->requesting_name,
            'amount'              => $request->amount,
            'description'         => $request->description,
            'account_title'       => $request->account_title,
        ]);

        return response()->json([
            'expense' => $expense->load('creator'),
            'message' => 'Expense updated successfully.',
        ]);
    }
}