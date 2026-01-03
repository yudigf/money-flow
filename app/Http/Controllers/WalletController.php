<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreWalletRequest;
use App\Http\Requests\UpdateWalletRequest;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    /**
     * Display a listing of the user's wallets.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $wallets = Wallet::where('user_id', $user->id)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $wallets,
        ]);
    }

    /**
     * Store a newly created wallet in storage.
     *
     * @param StoreWalletRequest $request
     * @return JsonResponse
     */
    public function store(StoreWalletRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'name' => $request->validated('name'),
            'balance' => $request->validated('balance') ?? '0.00',
            'color' => $request->validated('color'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Wallet created successfully.',
            'data' => $wallet,
        ], 201);
    }

    /**
     * Display the specified wallet.
     *
     * @param Wallet $wallet
     * @return JsonResponse
     */
    public function show(Wallet $wallet): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($wallet->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $wallet->load('transactions'),
        ]);
    }

    /**
     * Update the specified wallet in storage.
     *
     * @param UpdateWalletRequest $request
     * @param Wallet $wallet
     * @return JsonResponse
     */
    public function update(UpdateWalletRequest $request, Wallet $wallet): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($wallet->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found.',
            ], 404);
        }

        $wallet->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Wallet updated successfully.',
            'data' => $wallet,
        ]);
    }

    /**
     * Remove the specified wallet from storage.
     *
     * @param Wallet $wallet
     * @return JsonResponse
     */
    public function destroy(Wallet $wallet): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($wallet->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found.',
            ], 404);
        }

        $wallet->delete();

        return response()->json([
            'success' => true,
            'message' => 'Wallet deleted successfully.',
        ]);
    }
}
