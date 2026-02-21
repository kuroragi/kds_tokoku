<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankMutation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_unit_id',
        'bank_account_id',
        'transaction_date',
        'description',
        'reference_no',
        'debit',
        'credit',
        'balance',
        'status',
        'matched_journal_id',
        'matched_fund_transfer_id',
        'import_batch',
        'raw_data',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public const STATUSES = [
        'unmatched' => 'Belum Dicocokkan',
        'matched' => 'Cocok',
        'ignored' => 'Diabaikan',
    ];

    // ─── Relationships ───

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function matchedJournal()
    {
        return $this->belongsTo(JournalMaster::class, 'matched_journal_id');
    }

    public function matchedFundTransfer()
    {
        return $this->belongsTo(FundTransfer::class, 'matched_fund_transfer_id');
    }

    public function reconciliationItems()
    {
        return $this->hasMany(BankReconciliationItem::class);
    }

    // ─── Scopes ───

    public function scopeByBusinessUnit($query, $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    public function scopeByBankAccount($query, $bankAccountId)
    {
        return $query->where('bank_account_id', $bankAccountId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeUnmatched($query)
    {
        return $query->where('status', 'unmatched');
    }

    public function scopeMatched($query)
    {
        return $query->where('status', 'matched');
    }

    public function scopeByBatch($query, $batch)
    {
        return $query->where('import_batch', $batch);
    }

    // ─── Helpers ───

    public function isMatched(): bool
    {
        return $this->status === 'matched';
    }

    public function isIgnored(): bool
    {
        return $this->status === 'ignored';
    }

    /**
     * Net amount: positive = debit (masuk), negative = credit (keluar)
     */
    public function getNetAmountAttribute(): float
    {
        return (float) $this->debit - (float) $this->credit;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
