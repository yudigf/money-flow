<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bill extends Model
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
        'wallet_id',
        'name',
        'amount',
        'due_date',
        'frequency',
        'is_active',
        'auto_pay',
        'notes',
        'last_paid_at',
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
            'due_date' => 'date',
            'is_active' => 'boolean',
            'auto_pay' => 'boolean',
            'last_paid_at' => 'date',
        ];
    }

    /**
     * Get the user that owns the bill.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category for this bill.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the wallet for this bill.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Check if bill is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date->isPast() && $this->is_active;
    }

    /**
     * Check if bill is due soon (within 7 days).
     */
    public function getIsDueSoonAttribute(): bool
    {
        return $this->due_date->isBetween(now(), now()->addDays(7)) && $this->is_active;
    }

    /**
     * Get days until due.
     */
    public function getDaysUntilDueAttribute(): int
    {
        return (int) now()->diffInDays($this->due_date, false);
    }

    /**
     * Get status label.
     */
    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }
        if ($this->is_overdue) {
            return 'overdue';
        }
        if ($this->is_due_soon) {
            return 'due_soon';
        }
        return 'upcoming';
    }

    /**
     * Calculate next due date after payment.
     */
    public function calculateNextDueDate(): Carbon
    {
        return match ($this->frequency) {
            'WEEKLY' => $this->due_date->addWeek(),
            'MONTHLY' => $this->due_date->addMonth(),
            'YEARLY' => $this->due_date->addYear(),
            'ONCE' => $this->due_date,
        };
    }

    /**
     * Mark as paid and update next due date.
     */
    public function markAsPaid(): void
    {
        $this->last_paid_at = now();

        if ($this->frequency === 'ONCE') {
            $this->is_active = false;
        } else {
            $this->due_date = $this->calculateNextDueDate();
        }

        $this->save();
    }
}
