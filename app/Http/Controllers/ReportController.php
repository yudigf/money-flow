<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Get overview summary for current month.
     */
    public function overview(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $month = (int) request('month', date('n'));
        $year = (int) request('year', date('Y'));

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $income = Transaction::where('user_id', $user->id)
            ->where('type', 'INCOME')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $expense = Transaction::where('user_id', $user->id)
            ->where('type', 'EXPENSE')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $transactionCount = Transaction::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'month' => $month,
                'year' => $year,
                'income' => (float) $income,
                'expense' => (float) $expense,
                'net' => (float) bcsub((string) $income, (string) $expense, 2),
                'transaction_count' => $transactionCount,
            ],
        ]);
    }

    /**
     * Get monthly trend for the year.
     */
    public function monthlyTrend(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $year = (int) request('year', date('Y'));

        $data = Transaction::where('user_id', $user->id)
            ->whereYear('date', $year)
            ->select(
                DB::raw('MONTH(date) as month'),
                DB::raw('type'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('month', 'type')
            ->orderBy('month')
            ->get();

        // Format data for chart
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $income = $data->where('month', $m)->where('type', 'INCOME')->first();
            $expense = $data->where('month', $m)->where('type', 'EXPENSE')->first();

            $months[] = [
                'month' => $m,
                'label' => Carbon::create($year, $m, 1)->format('M'),
                'income' => (float) ($income?->total ?? 0),
                'expense' => (float) ($expense?->total ?? 0),
            ];
        }

        // Calculate totals for the year
        $totalIncome = collect($months)->sum('income');
        $totalExpense = collect($months)->sum('expense');

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'months' => $months,
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net' => $totalIncome - $totalExpense,
            ],
        ]);
    }

    /**
     * Get expense breakdown by category.
     */
    public function categoryBreakdown(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $month = (int) request('month', date('n'));
        $year = (int) request('year', date('Y'));
        $type = request('type', 'EXPENSE');

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $data = Transaction::where('user_id', $user->id)
            ->where('type', $type)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('category')
            ->select(
                'category_id',
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get();

        $total = $data->sum('total');

        $categories = $data->map(function ($item) use ($total) {
            return [
                'category_id' => $item->category_id,
                'category' => $item->category,
                'total' => (float) $item->total,
                'count' => $item->count,
                'percentage' => $total > 0 ? round(($item->total / $total) * 100, 1) : 0,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'month' => $month,
                'year' => $year,
                'type' => $type,
                'total' => (float) $total,
                'categories' => $categories,
            ],
        ]);
    }

    /**
     * Get daily transactions for a month.
     */
    public function dailyBreakdown(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $month = (int) request('month', date('n'));
        $year = (int) request('year', date('Y'));

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        $daysInMonth = $endDate->day;

        $data = Transaction::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->select(
                DB::raw('DAY(date) as day'),
                DB::raw('type'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('day', 'type')
            ->get();

        $days = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $income = $data->where('day', $d)->where('type', 'INCOME')->first();
            $expense = $data->where('day', $d)->where('type', 'EXPENSE')->first();

            $days[] = [
                'day' => $d,
                'income' => (float) ($income?->total ?? 0),
                'expense' => (float) ($expense?->total ?? 0),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'month' => $month,
                'year' => $year,
                'days' => $days,
            ],
        ]);
    }
}
