<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ViewJournalRecap extends Model
{
    protected $table = 'view_journal_recap';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'id_period';

    protected $casts = [
        'total_journals' => 'integer',
        'total_debit' => 'biginteger',
        'total_credit' => 'biginteger',
        'posted_journals' => 'integer',
        'draft_journals' => 'integer',
        'year' => 'integer',
        'month' => 'integer',
    ];

    // Relationships
    public function period()
    {
        return $this->belongsTo(Period::class, 'id_period');
    }

    // Scopes
    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }

    public function scopeByPeriod($query, $periodId)
    {
        return $query->where('id_period', $periodId);
    }
}
