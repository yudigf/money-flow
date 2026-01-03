<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreBillRequest;
use App\Http\Requests\UpdateBillRequest;
use App\Models\Bill;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BillController extends Controller
{
    /**
     * Display a listing of bills.
     */
    public function index(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $bills = Bill::where('user_id', $user->id)
            ->with(['category', 'wallet'])
            ->orderByRaw("CASE WHEN is_active = 1 THEN 0 ELSE 1 END")
            ->orderBy('due_date')
            ->get()
            ->map(fn($bill) => $this->formatBill($bill));

        return response()->json([
            'success' => true,
            'data' => $bills,
        ]);
    }

    /**
     * Get upcoming/overdue bills summary.
     */
    public function summary(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $bills = Bill::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        $overdue = $bills->filter(fn($b) => $b->is_overdue);
        $dueSoon = $bills->filter(fn($b) => $b->is_due_soon);
        $upcoming = $bills->filter(fn($b) => !$b->is_overdue && !$b->is_due_soon);

        return response()->json([
            'success' => true,
            'data' => [
                'overdue_count' => $overdue->count(),
                'overdue_total' => (float) $overdue->sum('amount'),
                'due_soon_count' => $dueSoon->count(),
                'due_soon_total' => (float) $dueSoon->sum('amount'),
                'upcoming_count' => $upcoming->count(),
                'upcoming_total' => (float) $upcoming->sum('amount'),
                'total_active' => $bills->count(),
                'total_monthly' => (float) $bills->where('frequency', 'MONTHLY')->sum('amount'),
            ],
        ]);
    }

    /**
     * Store a newly created bill.
     */
    public function store(StoreBillRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $bill = Bill::create([
            'user_id' => $user->id,
            'category_id' => $request->validated('category_id'),
            'wallet_id' => $request->validated('wallet_id'),
            'name' => $request->validated('name'),
            'amount' => $request->validated('amount'),
            'due_date' => $request->validated('due_date'),
            'frequency' => $request->validated('frequency', 'MONTHLY'),
            'is_active' => $request->validated('is_active', true),
            'auto_pay' => $request->validated('auto_pay', false),
            'notes' => $request->validated('notes'),
        ]);

        $bill->load(['category', 'wallet']);

        return response()->json([
            'success' => true,
            'message' => 'Bill created successfully.',
            'data' => $this->formatBill($bill),
        ], 201);
    }

    /**
     * Update the specified bill.
     */
    public function update(UpdateBillRequest $request, Bill $bill): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($bill->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bill not found.',
            ], 404);
        }

        $bill->update($request->validated());
        $bill->load(['category', 'wallet']);

        return response()->json([
            'success' => true,
            'message' => 'Bill updated successfully.',
            'data' => $this->formatBill($bill),
        ]);
    }

    /**
     * Remove the specified bill.
     */
    public function destroy(Bill $bill): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($bill->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bill not found.',
            ], 404);
        }

        $bill->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bill deleted successfully.',
        ]);
    }

    /**
     * Mark bill as paid and optionally create transaction.
     */
    public function markPaid(Bill $bill): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($bill->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bill not found.',
            ], 404);
        }

        $createTransaction = request()->boolean('create_transaction', true);

        DB::transaction(function () use ($bill, $user, $createTransaction): void {
            // Create expense transaction if requested
            if ($createTransaction && $bill->wallet_id && $bill->category_id) {
                Transaction::create([
                    'user_id' => $user->id,
                    'wallet_id' => $bill->wallet_id,
                    'category_id' => $bill->category_id,
                    'amount' => $bill->amount,
                    'type' => 'EXPENSE',
                    'date' => now(),
                    'description' => 'Bill payment: ' . $bill->name,
                ]);

                // Update wallet balance
                $bill->wallet->decrement('balance', $bill->amount);
            }

            // Update bill due date
            $bill->markAsPaid();
        });

        $bill->load(['category', 'wallet']);

        return response()->json([
            'success' => true,
            'message' => 'Bill marked as paid.',
            'data' => $this->formatBill($bill),
        ]);
    }

    /**
     * Format bill for response.
     */
    private function formatBill(Bill $bill): array
    {
        return [
            'id' => $bill->id,
            'name' => $bill->name,
            'amount' => $bill->amount,
            'due_date' => $bill->due_date->format('Y-m-d'),
            'frequency' => $bill->frequency,
            'is_active' => $bill->is_active,
            'auto_pay' => $bill->auto_pay,
            'notes' => $bill->notes,
            'category_id' => $bill->category_id,
            'category' => $bill->category,
            'wallet_id' => $bill->wallet_id,
            'wallet' => $bill->wallet,
            'last_paid_at' => $bill->last_paid_at?->format('Y-m-d'),
            'is_overdue' => $bill->is_overdue,
            'is_due_soon' => $bill->is_due_soon,
            'days_until_due' => $bill->days_until_due,
            'status' => $bill->status,
        ];
    }
}
