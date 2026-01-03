<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        if (!$user) {
            return;
        }

        $wallets = [
            ['name' => 'Cash', 'balance' => '0.00', 'color' => '#10B981'],
            ['name' => 'Bank Account', 'balance' => '0.00', 'color' => '#3B82F6'],
            ['name' => 'E-Wallet', 'balance' => '0.00', 'color' => '#8B5CF6'],
        ];

        foreach ($wallets as $wallet) {
            Wallet::create([
                'user_id' => $user->id,
                'name' => $wallet['name'],
                'balance' => $wallet['balance'],
                'color' => $wallet['color'],
            ]);
        }
    }
}
