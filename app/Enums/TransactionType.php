<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionType: string
{
    case INCOME = 'INCOME';
    case EXPENSE = 'EXPENSE';

    /**
     * Check if this is an income transaction.
     */
    public function isIncome(): bool
    {
        return $this === self::INCOME;
    }

    /**
     * Check if this is an expense transaction.
     */
    public function isExpense(): bool
    {
        return $this === self::EXPENSE;
    }
}
