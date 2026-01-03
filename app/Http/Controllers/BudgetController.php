<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreBudgetRequest;
use App\Http\Requests\UpdateBudgetRequest;
use App\Models\Budget;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class BudgetController extends Controller
{
    /**
     * Display a listing of budgets for current month.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $month = (int) request('month', date('n'));
        $year = (int) request('year', date('Y'));

        $budgets = Budget::where('user_id', $user->id)
            ->where('month', $month)
            ->where('year', $year)
            ->with('category')
            ->get()
            ->map(function ($budget) {
                return [
                    'id' => $budget->id,
                    'category_id' => $budget->category_id,
                    'category' => $budget->category,
                    'amount' => $budget->amount,
                    'spent' => $budget->spent,
                    'remaining' => $budget->remaining,
                    'percentage' => $budget->percentage,
                    'is_over' => $budget->is_over,
                    'month' => $budget->month,
                    'year' => $budget->year,
                ];
            });

        // Get categories without budget for this month
        $budgetedCategoryIds = $budgets->pluck('category_id')->toArray();
        $unbdgetedCategories = Category::where('user_id', $user->id)
            ->where('type', 'EXPENSE')
            ->whereNotIn('id', $budgetedCategoryIds)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'budgets' => $budgets,
                'unbudgeted_categories' => $unbdgetedCategories,
                'month' => $month,
                'year' => $year,
            ],
        ]);
    }

    /**
     * Store a newly created budget.
     *
     * @param StoreBudgetRequest $request
     * @return JsonResponse
     */
    public function store(StoreBudgetRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Check if budget already exists for this category/month/year
        $existing = Budget::where('user_id', $user->id)
            ->where('category_id', $request->validated('category_id'))
            ->where('month', $request->validated('month'))
            ->where('year', $request->validated('year'))
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Budget for this category already exists for the selected month.',
            ], 422);
        }

        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $request->validated('category_id'),
            'amount' => $request->validated('amount'),
            'month' => $request->validated('month'),
            'year' => $request->validated('year'),
        ]);

        $budget->load('category');

        return response()->json([
            'success' => true,
            'message' => 'Budget created successfully.',
            'data' => [
                'id' => $budget->id,
                'category_id' => $budget->category_id,
                'category' => $budget->category,
                'amount' => $budget->amount,
                'spent' => $budget->spent,
                'remaining' => $budget->remaining,
                'percentage' => $budget->percentage,
                'is_over' => $budget->is_over,
                'month' => $budget->month,
                'year' => $budget->year,
            ],
        ], 201);
    }

    /**
     * Update the specified budget.
     *
     * @param UpdateBudgetRequest $request
     * @param Budget $budget
     * @return JsonResponse
     */
    public function update(UpdateBudgetRequest $request, Budget $budget): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($budget->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Budget not found.',
            ], 404);
        }

        $budget->update([
            'amount' => $request->validated('amount'),
        ]);

        $budget->load('category');

        return response()->json([
            'success' => true,
            'message' => 'Budget updated successfully.',
            'data' => [
                'id' => $budget->id,
                'category_id' => $budget->category_id,
                'category' => $budget->category,
                'amount' => $budget->amount,
                'spent' => $budget->spent,
                'remaining' => $budget->remaining,
                'percentage' => $budget->percentage,
                'is_over' => $budget->is_over,
                'month' => $budget->month,
                'year' => $budget->year,
            ],
        ]);
    }

    /**
     * Remove the specified budget.
     *
     * @param Budget $budget
     * @return JsonResponse
     */
    public function destroy(Budget $budget): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($budget->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Budget not found.',
            ], 404);
        }

        $budget->delete();

        return response()->json([
            'success' => true,
            'message' => 'Budget deleted successfully.',
        ]);
    }

    /**
     * Get budget summary for dashboard.
     *
     * @return JsonResponse
     */
    public function summary(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $month = (int) date('n');
        $year = (int) date('Y');

        $budgets = Budget::where('user_id', $user->id)
            ->where('month', $month)
            ->where('year', $year)
            ->with('category')
            ->get();

        $totalBudget = $budgets->sum('amount');
        $totalSpent = $budgets->sum(fn($b) => (float) $b->spent);
        $overBudgetCount = $budgets->filter(fn($b) => $b->is_over)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_budget' => $totalBudget,
                'total_spent' => $totalSpent,
                'remaining' => $totalBudget - $totalSpent,
                'percentage' => $totalBudget > 0 ? round(($totalSpent / $totalBudget) * 100, 1) : 0,
                'over_budget_count' => $overBudgetCount,
                'budgets_count' => $budgets->count(),
            ],
        ]);
    }
}
