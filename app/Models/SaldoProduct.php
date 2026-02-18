<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class SaldoProduct extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'business_unit_id',
        'code',
        'name',
        'saldo_provider_id',
        'buy_price',
        'sell_price',
        'description',
        'is_active',
    ];

    protected $casts = [
        'buy_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'is_active' => 'boolean',
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

    public function transactions()
    {
        return $this->hasMany(SaldoTransaction::class);
    }

    // ─── Scopes ───

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByBusinessUnit($query, $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    // ─── Helpers ───

    public function getProfitMarginAttribute(): float
    {
        if ($this->buy_price <= 0) {
            return 0;
        }

        return round((($this->sell_price - $this->buy_price) / $this->buy_price) * 100, 2);
    }
}
