<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Investment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'symbol',
        'type',
        'quantity',
        'buy_price',
        'current_price',
        'buy_date',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:8',
            'buy_price' => 'decimal:2',
            'current_price' => 'decimal:2',
            'buy_date' => 'date',
        ];
    }

    /**
     * Get the user that owns the investment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get total cost (invested amount).
     */
    public function getTotalCostAttribute(): float
    {
        return (float) bcmul((string) $this->quantity, (string) $this->buy_price, 2);
    }

    /**
     * Get current value.
     */
    public function getCurrentValueAttribute(): float
    {
        return (float) bcmul((string) $this->quantity, (string) $this->current_price, 2);
    }

    /**
     * Get profit/loss in currency.
     */
    public function getProfitLossAttribute(): float
    {
        return $this->current_value - $this->total_cost;
    }

    /**
     * Get profit/loss percentage.
     */
    public function getProfitLossPercentAttribute(): float
    {
        if ($this->total_cost == 0) {
            return 0;
        }
        return round(($this->profit_loss / $this->total_cost) * 100, 2);
    }

    /**
     * Check if investment is profitable.
     */
    public function getIsProfitableAttribute(): bool
    {
        return $this->profit_loss > 0;
    }

    /**
     * Get type icon.
     */
    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'STOCK' => '📈',
            'CRYPTO' => '₿',
            'MUTUAL_FUND' => '📊',
            'GOLD' => '🥇',
            'BOND' => '📜',
            'PROPERTY' => '🏠',
            'OTHER' => '💼',
            default => '💰',
        };
    }
}
