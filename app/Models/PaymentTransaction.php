<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentTransaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'transaction_id',
        'gateway',
        'gateway_reference',
        'payable_type',
        'payable_id',
        'user_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'payment_channel',
        'gateway_data',
        'callback_data',
        'notes',
        'failure_reason',
        'paid_at',
        'expired_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'gateway_data' => 'array',
            'callback_data' => 'array',
            'paid_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }

    // ── Status Constants ──

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    public const GATEWAY_MANUAL = 'manual';

    // ── Relationships ──

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ──

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    public function scopeForGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ── Status Helpers ──

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function isFinalized(): bool
    {
        return in_array($this->status, [
            self::STATUS_SUCCESS,
            self::STATUS_FAILED,
            self::STATUS_EXPIRED,
            self::STATUS_CANCELLED,
            self::STATUS_REFUNDED,
        ]);
    }

    // ── Actions ──

    public function markAsSuccess(?string $gatewayReference = null, ?array $callbackData = null): void
    {
        $this->update([
            'status' => self::STATUS_SUCCESS,
            'gateway_reference' => $gatewayReference ?? $this->gateway_reference,
            'callback_data' => $callbackData,
            'paid_at' => now(),
        ]);
    }

    public function markAsFailed(?string $reason = null, ?array $callbackData = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failure_reason' => $reason,
            'callback_data' => $callbackData,
        ]);
    }

    public function markAsExpired(): void
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
            'expired_at' => now(),
        ]);
    }

    public function markAsCancelled(?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'failure_reason' => $reason,
        ]);
    }

    // ── Transaction ID Generator ──

    public static function generateTransactionId(): string
    {
        $prefix = 'TRX';
        $date = now()->format('Ymd');
        $lastTrx = static::withTrashed()
            ->where('transaction_id', 'like', "{$prefix}-{$date}-%")
            ->orderByDesc('transaction_id')
            ->first();

        if ($lastTrx) {
            $lastSeq = (int) substr($lastTrx->transaction_id, -5);
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }

        return sprintf('%s-%s-%05d', $prefix, $date, $nextSeq);
    }

    // ── Formatted Attributes ──

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_PROCESSING => 'info',
            self::STATUS_SUCCESS => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_EXPIRED => 'secondary',
            self::STATUS_CANCELLED => 'dark',
            self::STATUS_REFUNDED => 'primary',
            default => 'light',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Menunggu Pembayaran',
            self::STATUS_PROCESSING => 'Sedang Diproses',
            self::STATUS_SUCCESS => 'Berhasil',
            self::STATUS_FAILED => 'Gagal',
            self::STATUS_EXPIRED => 'Kadaluarsa',
            self::STATUS_CANCELLED => 'Dibatalkan',
            self::STATUS_REFUNDED => 'Dikembalikan',
            default => $this->status,
        };
    }
}
