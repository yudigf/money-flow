<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'amount',
        'month',
        'year',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'month' => 'integer',
            'year' => 'integer',
        ];
    }

    /**
     * Get the user that owns the budget.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category for this budget.
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get spent amount for this budget period.
     */
    public function getSpentAttribute(): string
    {
        return (string) Transaction::where('user_id', $this->user_id)
            ->where('category_id', $this->category_id)
            ->where('type', 'EXPENSE')
            ->whereMonth('date', $this->month)
            ->whereYear('date', $this->year)
            ->sum('amount');
    }

    /**
     * Get remaining budget.
     */
    public function getRemainingAttribute(): string
    {
        return bcsub((string) $this->amount, (string) $this->spent, 2);
    }

    /**
     * Get percentage used.
     */
    public function getPercentageAttribute(): float
    {
        if ((float) $this->amount === 0.0) {
            return 0;
        }
        $percentage = ((float) $this->spent / (float) $this->amount) * 100;
        return min(round($percentage, 1), 100);
    }

    /**
     * Check if budget is over limit.
     */
    public function getIsOverAttribute(): bool
    {
        return (float) $this->spent > (float) $this->amount;
    }
}
