<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class OpeningBalance extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'business_unit_id',
        'period_id',
        'balance_date',
        'description',
        'total_debit',
        'total_credit',
        'status',
        'journal_master_id',
    ];

    protected $casts = [
        'balance_date' => 'date',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
    ];

    public const STATUSES = [
        'draft' => 'Draft',
        'posted' => 'Diposting',
    ];

    // ─── Relationships ───

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function period()
    {
        return $this->belongsTo(Period::class);
    }

    public function entries()
    {
        return $this->hasMany(OpeningBalanceEntry::class);
    }

    public function journalMaster()
    {
        return $this->belongsTo(JournalMaster::class);
    }

    // ─── Scopes ───

    public function scopeByBusinessUnit($query, $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    // ─── Helpers ───

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isBalanced(): bool
    {
        return abs((float) $this->total_debit - (float) $this->total_credit) < 0.01;
    }

    public function recalculateTotals(): void
    {
        $this->total_debit = $this->entries()->sum('debit');
        $this->total_credit = $this->entries()->sum('credit');
        $this->save();
    }
}
