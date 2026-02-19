<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class BankAccount extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'business_unit_id',
        'bank_id',
        'account_number',
        'account_name',
        'description',
        'initial_balance',
        'current_balance',
        'is_active',
    ];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // ─── Relationships ───

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function sourceTransfers()
    {
        return $this->hasMany(FundTransfer::class, 'source_bank_account_id');
    }

    public function destinationTransfers()
    {
        return $this->hasMany(FundTransfer::class, 'destination_bank_account_id');
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

    public function scopeByBank($query, $bankId)
    {
        return $query->where('bank_id', $bankId);
    }

    // ─── Helpers ───

    /**
     * Display label: "BankName - AccountNumber (AccountName)"
     */
    public function getDisplayLabelAttribute(): string
    {
        $bankName = $this->bank ? $this->bank->name : '';
        return "{$bankName} - {$this->account_number} ({$this->account_name})";
    }
}
