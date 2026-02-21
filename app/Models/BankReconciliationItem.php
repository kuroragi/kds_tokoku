<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankReconciliationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_reconciliation_id',
        'bank_mutation_id',
        'match_type',
        'matched_journal_id',
        'matched_fund_transfer_id',
        'notes',
    ];

    public const MATCH_TYPES = [
        'auto_matched' => 'Otomatis',
        'manual_matched' => 'Manual',
        'unmatched' => 'Belum Dicocokkan',
        'ignored' => 'Diabaikan',
        'adjustment' => 'Penyesuaian',
    ];

    // ─── Relationships ───

    public function reconciliation()
    {
        return $this->belongsTo(BankReconciliation::class, 'bank_reconciliation_id');
    }

    public function mutation()
    {
        return $this->belongsTo(BankMutation::class, 'bank_mutation_id');
    }

    public function matchedJournal()
    {
        return $this->belongsTo(JournalMaster::class, 'matched_journal_id');
    }

    public function matchedFundTransfer()
    {
        return $this->belongsTo(FundTransfer::class, 'matched_fund_transfer_id');
    }

    // ─── Helpers ───

    public function isMatched(): bool
    {
        return in_array($this->match_type, ['auto_matched', 'manual_matched']);
    }

    public function getMatchTypeLabelAttribute(): string
    {
        return self::MATCH_TYPES[$this->match_type] ?? $this->match_type;
    }
}
