<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class SaldoTransaction extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'business_unit_id',
        'saldo_provider_id',
        'saldo_product_id',
        'customer_name',
        'customer_phone',
        'buy_price',
        'sell_price',
        'profit',
        'transaction_date',
        'notes',
    ];

    protected $casts = [
        'buy_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'profit' => 'decimal:2',
        'transaction_date' => 'date',
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

    public function saldoProduct()
    {
        return $this->belongsTo(SaldoProduct::class);
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

    public function scopeByProduct($query, $productId)
    {
        return $query->where('saldo_product_id', $productId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }
}
