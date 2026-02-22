<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'plan_id',
        'starts_at',
        'ends_at',
        'status',
        'amount_paid',
        'payment_method',
        'payment_reference',
        'voucher_code',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'amount_paid' => 'decimal:2',
        ];
    }

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeNotExpired($query)
    {
        return $query->where('ends_at', '>=', now()->toDateString());
    }

    // ── Helpers ──

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->ends_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->ends_at->isPast();
    }

    public function isInGracePeriod(): bool
    {
        return $this->status === 'grace'
            && $this->ends_at->isPast()
            && $this->ends_at->addDays(3)->isFuture();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function daysRemaining(): int
    {
        return max(0, now()->diffInDays($this->ends_at, false));
    }
}
