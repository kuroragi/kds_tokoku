<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class Journal extends Model
{
    use SoftDeletes, Blameable;

    protected $fillable = [
        'id_journal_master',
        'id_coa',
        'description',
        'debit',
        'credit',
        'sequence',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'sequence' => 'integer',
    ];

    // Relationships
    public function journalMaster()
    {
        return $this->belongsTo(JournalMaster::class, 'id_journal_master');
    }

    public function coa()
    {
        return $this->belongsTo(COA::class, 'id_coa');
    }

    // Scopes
    public function scopeDebit($query)
    {
        return $query->where('debit', '>', 0);
    }

    public function scopeCredit($query)
    {
        return $query->where('credit', '>', 0);
    }

    // Accessors
    public function getAmountAttribute()
    {
        return $this->debit > 0 ? $this->debit : $this->credit;
    }

    public function getTypeAttribute()
    {
        return $this->debit > 0 ? 'debit' : 'credit';
    }
}
