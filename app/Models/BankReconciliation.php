<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class BankReconciliation extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'business_unit_id',
        'bank_account_id',
        'start_date',
        'end_date',
        'bank_statement_balance',
        'system_balance',
        'difference',
        'total_mutations',
        'matched_count',
        'unmatched_count',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'bank_statement_balance' => 'decimal:2',
        'system_balance' => 'decimal:2',
        'difference' => 'decimal:2',
        'total_mutations' => 'integer',
        'matched_count' => 'integer',
        'unmatched_count' => 'integer',
    ];

    public const STATUSES = [
        'draft' => 'Draft',
        'completed' => 'Selesai',
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

    public function items()
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

    // ─── Helpers ───

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function recalculateCounts(): void
    {
        $this->total_mutations = $this->items()->count();
        $this->matched_count = $this->items()
            ->whereIn('match_type', ['auto_matched', 'manual_matched'])
            ->count();
        $this->unmatched_count = $this->items()
            ->where('match_type', 'unmatched')
            ->count();
        $this->difference = (float) $this->bank_statement_balance - (float) $this->system_balance;
        $this->save();
    }

    public function getMatchPercentageAttribute(): float
    {
        if ($this->total_mutations === 0) return 0;
        return round(($this->matched_count / $this->total_mutations) * 100, 1);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
