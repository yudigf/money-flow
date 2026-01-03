<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvestmentRequest;
use App\Http\Requests\UpdateInvestmentRequest;
use App\Models\Investment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class InvestmentController extends Controller
{
    /**
     * Display a listing of investments.
     */
    public function index(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $investments = Investment::where('user_id', $user->id)
            ->orderBy('current_price', 'desc')
            ->get()
            ->map(fn($inv) => $this->formatInvestment($inv));

        return response()->json([
            'success' => true,
            'data' => $investments,
        ]);
    }

    /**
     * Get portfolio summary.
     */
    public function summary(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $investments = Investment::where('user_id', $user->id)->get();

        $totalCost = $investments->sum('total_cost');
        $totalValue = $investments->sum('current_value');
        $totalProfitLoss = $totalValue - $totalCost;
        $profitLossPercent = $totalCost > 0 ? round(($totalProfitLoss / $totalCost) * 100, 2) : 0;

        // Group by type
        $byType = $investments->groupBy('type')->map(function ($group, $type) {
            return [
                'type' => $type,
                'count' => $group->count(),
                'total_value' => $group->sum('current_value'),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'total_cost' => $totalCost,
                'total_value' => $totalValue,
                'profit_loss' => $totalProfitLoss,
                'profit_loss_percent' => $profitLossPercent,
                'is_profitable' => $totalProfitLoss > 0,
                'investments_count' => $investments->count(),
                'by_type' => $byType,
            ],
        ]);
    }

    /**
     * Store a newly created investment.
     */
    public function store(StoreInvestmentRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $investment = Investment::create([
            'user_id' => $user->id,
            'name' => $request->validated('name'),
            'symbol' => $request->validated('symbol'),
            'type' => $request->validated('type', 'STOCK'),
            'quantity' => $request->validated('quantity'),
            'buy_price' => $request->validated('buy_price'),
            'current_price' => $request->validated('current_price', $request->validated('buy_price')),
            'buy_date' => $request->validated('buy_date'),
            'notes' => $request->validated('notes'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Investment added successfully.',
            'data' => $this->formatInvestment($investment),
        ], 201);
    }

    /**
     * Update the specified investment.
     */
    public function update(UpdateInvestmentRequest $request, Investment $investment): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($investment->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Investment not found.',
            ], 404);
        }

        $investment->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Investment updated successfully.',
            'data' => $this->formatInvestment($investment),
        ]);
    }

    /**
     * Remove the specified investment.
     */
    public function destroy(Investment $investment): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($investment->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Investment not found.',
            ], 404);
        }

        $investment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Investment deleted successfully.',
        ]);
    }

    /**
     * Update current price for an investment.
     */
    public function updatePrice(Investment $investment): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($investment->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Investment not found.',
            ], 404);
        }

        $investment->update([
            'current_price' => request('current_price'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Price updated successfully.',
            'data' => $this->formatInvestment($investment),
        ]);
    }

    /**
     * Format investment for response.
     */
    private function formatInvestment(Investment $investment): array
    {
        return [
            'id' => $investment->id,
            'name' => $investment->name,
            'symbol' => $investment->symbol,
            'type' => $investment->type,
            'type_icon' => $investment->type_icon,
            'quantity' => (float) $investment->quantity,
            'buy_price' => (float) $investment->buy_price,
            'current_price' => (float) $investment->current_price,
            'buy_date' => $investment->buy_date?->format('Y-m-d'),
            'notes' => $investment->notes,
            'total_cost' => $investment->total_cost,
            'current_value' => $investment->current_value,
            'profit_loss' => $investment->profit_loss,
            'profit_loss_percent' => $investment->profit_loss_percent,
            'is_profitable' => $investment->is_profitable,
        ];
    }
}
