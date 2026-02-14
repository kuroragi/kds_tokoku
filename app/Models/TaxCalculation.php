<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class TaxCalculation extends Model
{
    use SoftDeletes, Blameable;

    protected $fillable = [
        'year',
        'commercial_profit',
        'total_positive_correction',
        'total_negative_correction',
        'fiscal_profit',
        'loss_compensation_amount',
        'taxable_income',
        'tax_rate',
        'tax_amount',
        'status',
        'finalized_at',
        'id_journal_master',
    ];

    protected $casts = [
        'year' => 'integer',
        'commercial_profit' => 'integer',
        'total_positive_correction' => 'integer',
        'total_negative_correction' => 'integer',
        'fiscal_profit' => 'integer',
        'loss_compensation_amount' => 'integer',
        'taxable_income' => 'integer',
        'tax_amount' => 'integer',
        'tax_rate' => 'decimal:2',
        'finalized_at' => 'datetime',
    ];

    // Relationships
    public function journalMaster()
    {
        return $this->belongsTo(JournalMaster::class, 'id_journal_master');
    }

    // Scopes
    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeFinalized($query)
    {
        return $query->where('status', 'finalized');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    // Helpers
    public function isFinalized(): bool
    {
        return $this->status === 'finalized';
    }

    public function hasJournal(): bool
    {
        return $this->id_journal_master !== null;
    }
}
