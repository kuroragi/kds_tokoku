<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class SaldoProvider extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'business_unit_id',
        'code',
        'name',
        'type',
        'description',
        'initial_balance',
        'current_balance',
        'min_balance',
        'is_active',
    ];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'min_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // ─── Relationships ───

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function products()
    {
        return $this->hasMany(SaldoProduct::class);
    }

    public function topups()
    {
        return $this->hasMany(SaldoTopup::class);
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

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeLowBalance($query)
    {
        return $query->whereColumn('current_balance', '<=', 'min_balance')
                     ->where('min_balance', '>', 0);
    }

    public function isLowBalance(): bool
    {
        return $this->min_balance > 0 && $this->current_balance <= $this->min_balance;
    }

    // ─── Helpers ───

    public static function getTypes(): array
    {
        return [
            'e-wallet' => 'E-Wallet',
            'bank' => 'Bank',
            'other' => 'Lainnya',
        ];
    }

    /**
     * Recalculate current balance from initial + topups - transaction costs.
     */
    public function recalculateBalance(): void
    {
        $totalTopups = $this->topups()->sum('amount') - $this->topups()->sum('fee');
        $totalDeductions = $this->transactions()->sum('buy_price');

        $this->current_balance = $this->initial_balance + $totalTopups - $totalDeductions;
        $this->save();
    }
}
