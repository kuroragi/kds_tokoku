<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ViewJournalDetails extends Model
{
    protected $table = 'view_journal_details';
    public $timestamps = false;
    public $incrementing = false;

    protected $casts = [
        'debit' => 'biginteger',
        'credit' => 'biginteger',
        'amount' => 'biginteger',
        'sequence' => 'integer',
        'journal_date' => 'date',
    ];

    // Relationships
    public function period()
    {
        return $this->belongsTo(Period::class, 'id_period');
    }

    public function journalMaster()
    {
        return $this->belongsTo(JournalMaster::class, 'journal_master_id');
    }

    // Scopes
    public function scopeByPeriod($query, $periodId)
    {
        return $query->where('id_period', $periodId);
    }

    public function scopeByCoaType($query, $type)
    {
        return $query->where('coa_type', $type);
    }

    public function scopeDebitEntries($query)
    {
        return $query->where('entry_type', 'debit');
    }

    public function scopeCreditEntries($query)
    {
        return $query->where('entry_type', 'credit');
    }

    public function scopePosted($query)
    {
        return $query->where('journal_status', 'posted');
    }
}
