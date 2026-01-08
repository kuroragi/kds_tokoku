<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class JournalMaster extends Model
{
    use SoftDeletes, Blameable;

    protected $fillable = [
        'id_period',
        'journal_no',
        'journal_date',
        'reference',
        'description',
        'total_debit',
        'total_credit',
        'status',
        'posted_at',
    ];

    protected $casts = [
        'journal_date' => 'date',
        'posted_at' => 'datetime',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
    ];

    // Relationships
    public function period()
    {
        return $this->belongsTo(Period::class, 'id_period');
    }

    public function journals()
    {
        return $this->hasMany(Journal::class, 'id_journal_master');
    }

    // Scopes
    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    // Accessors & Mutators
    public function getIsBalancedAttribute()
    {
        return $this->total_debit == $this->total_credit;
    }
}
