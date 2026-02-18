<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class SaldoTopup extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'business_unit_id',
        'saldo_provider_id',
        'amount',
        'fee',
        'topup_date',
        'method',
        'reference_no',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'topup_date' => 'date',
    ];

    // ─── Relationships ───

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function saldoProvider()
    {
        return $this->belongsTo(SaldoProvider::class);
    }

    // ─── Scopes ───

    public function scopeByBusinessUnit($query, $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    public function scopeByProvider($query, $providerId)
    {
        return $query->where('saldo_provider_id', $providerId);
    }

    // ─── Helpers ───

    public static function getMethods(): array
    {
        return [
            'transfer' => 'Transfer Bank',
            'cash' => 'Tunai',
            'e-wallet' => 'E-Wallet',
            'other' => 'Lainnya',
        ];
    }

    /**
     * Net amount after fee.
     */
    public function getNetAmountAttribute(): float
    {
        return $this->amount - $this->fee;
    }
}
