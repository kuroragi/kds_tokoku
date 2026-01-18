<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class Period extends Model
{
    use SoftDeletes, Blameable;

    protected $fillable = [
        'code',
        'name',
        'start_date',
        'end_date',
        'year',
        'month',
        'is_active',
        'is_closed',
        'closed_at',
        'description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'datetime',
        'is_active' => 'boolean',
        'is_closed' => 'boolean',
        'year' => 'integer',
        'month' => 'integer',
    ];

    // Relationships
    public function journalMasters()
    {
        return $this->hasMany(JournalMaster::class, 'id_period');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOpen($query)
    {
        return $query->where('is_closed', false);
    }

    public function scopeClosed($query)
    {
        return $query->where('is_closed', true);
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }

    public function scopeByMonth($query, $month)
    {
        return $query->where('month', $month);
    }

    public function scopeCurrent($query)
    {
        $now = now();
        return $query->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now);
    }

    // Accessors
    public function getPeriodNameAttribute()
    {
        return $this->name ?: sprintf('%s %d', date('F', mktime(0, 0, 0, $this->month, 1)), $this->year);
    }

    public function getIsCurrentAttribute()
    {
        $now = now();
        return $this->start_date <= $now && $this->end_date >= $now;
    }

    public function getTotalJournalsAttribute()
    {
        return $this->journalMasters()->count();
    }

    public function getTotalAmountAttribute()
    {
        return $this->journalMasters()->sum('total_debit');
    }
}
