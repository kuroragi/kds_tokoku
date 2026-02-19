<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class FundTransfer extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'business_unit_id',
        'source_type',
        'source_bank_account_id',
        'destination_type',
        'destination_bank_account_id',
        'amount',
        'admin_fee',
        'transfer_date',
        'reference_no',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'admin_fee' => 'decimal:2',
        'transfer_date' => 'date',
    ];

    // ─── Relationships ───

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function sourceBankAccount()
    {
        return $this->belongsTo(BankAccount::class, 'source_bank_account_id');
    }

    public function destinationBankAccount()
    {
        return $this->belongsTo(BankAccount::class, 'destination_bank_account_id');
    }

    // ─── Scopes ───

    public function scopeByBusinessUnit($query, $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transfer_date', [$startDate, $endDate]);
    }

    // ─── Helpers ───

    /**
     * Total amount deducted from source (amount + admin_fee).
     */
    public function getTotalDeductedAttribute(): float
    {
        return (float) $this->amount + (float) $this->admin_fee;
    }

    /**
     * Get source label for display.
     */
    public function getSourceLabelAttribute(): string
    {
        if ($this->source_type === 'cash') {
            return 'Kas';
        }
        return $this->sourceBankAccount ? $this->sourceBankAccount->display_label : '-';
    }

    /**
     * Get destination label for display.
     */
    public function getDestinationLabelAttribute(): string
    {
        if ($this->destination_type === 'cash') {
            return 'Kas';
        }
        return $this->destinationBankAccount ? $this->destinationBankAccount->display_label : '-';
    }
}
