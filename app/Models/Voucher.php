<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Voucher extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'plan_id',
        'duration_days',
        'max_uses',
        'used_count',
        'valid_from',
        'valid_until',
        'is_active',
        'description',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_until' => 'date',
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ──

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(VoucherRedemption::class);
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        return $query->where('valid_from', '<=', now()->toDateString())
                     ->where('valid_until', '>=', now()->toDateString());
    }

    public function scopeAvailable($query)
    {
        return $query->active()
                     ->valid()
                     ->whereColumn('used_count', '<', 'max_uses');
    }

    // ── Helpers ──

    public function isValid(): bool
    {
        return $this->is_active
            && $this->valid_from->lte(now())
            && $this->valid_until->gte(now())
            && $this->used_count < $this->max_uses;
    }

    public function isFullyRedeemed(): bool
    {
        return $this->used_count >= $this->max_uses;
    }

    public function hasBeenRedeemedBy(int $userId): bool
    {
        return $this->redemptions()->where('user_id', $userId)->exists();
    }
}
