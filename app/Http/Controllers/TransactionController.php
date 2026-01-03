<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class TransactionController extends Controller
{
    /**
     * Store a newly created transaction in storage.
     *
     * This method creates a transaction and atomically updates the related wallet's balance.
     * - INCOME transactions add to the wallet balance.
     * - EXPENSE transactions subtract from the wallet balance.
     *
     * The entire operation is wrapped in a database transaction to ensure data integrity.
     * If any part fails, both the transaction record and balance update are rolled back.
     *
     * @param StoreTransactionRequest $request
     * @return JsonResponse
     */
    public function store(StoreTransactionRequest $request): JsonResponse
    {
        try {
            $result = DB::transaction(function () use ($request): Transaction {
                /** @var \App\Models\User $user */
                $user = Auth::user();

                // Lock the wallet row for update to prevent race conditions
                $wallet = Wallet::where('id', $request->validated('wallet_id'))
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Parse the transaction type
                $type = TransactionType::from($request->validated('type'));
                $amount = (string) $request->validated('amount');

                // Create the transaction record
                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'category_id' => $request->validated('category_id'),
                    'amount' => $amount,
                    'type' => $type->value,
                    'date' => $request->validated('date'),
                    'description' => $request->validated('description'),
                ]);

                // Update wallet balance based on transaction type
                // Using bcmath for precise decimal arithmetic
                if ($type->isIncome()) {
                    $newBalance = bcadd($wallet->balance, $amount, 2);
                } else {
                    $newBalance = bcsub($wallet->balance, $amount, 2);
                }

                $wallet->update(['balance' => $newBalance]);

                // Reload the transaction with its relationships
                return $transaction->load(['wallet', 'category']);
            });

            return response()->json([
                'success' => true,
                'message' => 'Transaction created successfully.',
                'data' => $result,
            ], 201);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create transaction.',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred.',
            ], 500);
        }
    }

    /**
     * Display a listing of the user's transactions.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $transactions = Transaction::where('user_id', $user->id)
            ->with(['wallet', 'category'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Display the specified transaction.
     *
     * @param Transaction $transaction
     * @return JsonResponse
     */
    public function show(Transaction $transaction): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Ensure the transaction belongs to the authenticated user
        if ($transaction->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $transaction->load(['wallet', 'category']),
        ]);
    }

    /**
     * Remove the specified transaction from storage.
     *
     * This method also reverses the wallet balance change atomically.
     *
     * @param Transaction $transaction
     * @return JsonResponse
     */
    public function destroy(Transaction $transaction): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Ensure the transaction belongs to the authenticated user
        if ($transaction->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found.',
            ], 404);
        }

        try {
            DB::transaction(function () use ($transaction): void {
                // Lock the wallet row for update
                $wallet = Wallet::where('id', $transaction->wallet_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Reverse the balance change
                $type = $transaction->type;
                $amount = $transaction->amount;

                if ($type->isIncome()) {
                    // If it was income, subtract from balance
                    $newBalance = bcsub($wallet->balance, $amount, 2);
                } else {
                    // If it was expense, add back to balance
                    $newBalance = bcadd($wallet->balance, $amount, 2);
                }

                $wallet->update(['balance' => $newBalance]);

                // Delete the transaction
                $transaction->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Transaction deleted successfully.',
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete transaction.',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred.',
            ], 500);
        }
    }
}
