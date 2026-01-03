<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
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

        $incomeCategories = [
            ['name' => 'Salary', 'icon' => 'briefcase'],
            ['name' => 'Freelance', 'icon' => 'laptop'],
            ['name' => 'Investment', 'icon' => 'trending-up'],
            ['name' => 'Gift', 'icon' => 'gift'],
            ['name' => 'Other Income', 'icon' => 'plus-circle'],
        ];

        $expenseCategories = [
            ['name' => 'Food & Dining', 'icon' => 'utensils'],
            ['name' => 'Transportation', 'icon' => 'car'],
            ['name' => 'Shopping', 'icon' => 'shopping-bag'],
            ['name' => 'Bills & Utilities', 'icon' => 'file-text'],
            ['name' => 'Entertainment', 'icon' => 'film'],
            ['name' => 'Health', 'icon' => 'heart'],
            ['name' => 'Education', 'icon' => 'book'],
            ['name' => 'Travel', 'icon' => 'plane'],
            ['name' => 'Other Expense', 'icon' => 'minus-circle'],
        ];

        foreach ($incomeCategories as $category) {
            Category::create([
                'user_id' => $user->id,
                'name' => $category['name'],
                'type' => 'INCOME',
                'icon' => $category['icon'],
            ]);
        }

        foreach ($expenseCategories as $category) {
            Category::create([
                'user_id' => $user->id,
                'name' => $category['name'],
                'type' => 'EXPENSE',
                'icon' => $category['icon'],
            ]);
        }
    }
}
