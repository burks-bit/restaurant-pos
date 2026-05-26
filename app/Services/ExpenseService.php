<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Shift;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

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
            'current_shift' => $current_shift,
            'today'         => $now->toDateString(),
        ]);
    }

    public function store(Request $request)
    {
        Log::info('store expense');
        Log::info($request->all());

        // 🔥 FORCE category = 5 if shift_id exists
        if ($request->filled('shift_id')) {
            $request->merge([
                'expense_category_id' => 5
            ]);
        }

        $request->validate([
            'expense_category_id' => 'required|integer',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        Expense::create([
            'expense_category_id' => $request->expense_category_id,
            'description' => $request->description,
            'amount' => $request->amount,
            'expense_date' => now()->toDateString(),
            'shift_id' => $request->shift_id,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Expense added successfully.');
    }

    public function getFilteredExpenses(Request $request)
    {
        $search = $request->search;
        $category = $request->category;
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        Log::info('Filtering expenses with: ', [
            'search' => $search,
            'category' => $category,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

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

    public function fetchCashierExpenses(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $role = Auth::user()->role;

        Log::info('Authenticated role: ' . $role);

        $query = Expense::with('creator')
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'desc');

        // Only restrict if NOT role 0 or 1
        if (!in_array($role, [0, 1])) {
            Log::info('Applying created_by filter for user ID: ' . Auth::id());
            $query->where('created_by', Auth::id());
        } else {
            Log::info('Role has full access, no created_by filter applied.');
        }

        $expenses = $query->get();

        return response()->json(['expenses' => $expenses]);
    }

    public function updateCashierExpenses(Request $request, $id)
    {
        $request->validate([
            'amount'      => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
        ]);

        $expense = Expense::findOrFail($id);

        $expense->update([
            'amount'      => $request->amount,
            'description' => $request->description,
        ]);

        return response()->json([
            'expense' => $expense->load('creator'),
            'message' => 'Expense updated successfully.',
        ]);
    }
}