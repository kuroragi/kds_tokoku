<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ViewTrialBalance extends Model
{
    protected $table = 'view_trial_balance';
    public $timestamps = false;
    public $incrementing = false;

    protected $casts = [
        'total_debit' => 'biginteger',
        'total_credit' => 'biginteger',
        'balance' => 'biginteger',
        'normal_balance' => 'biginteger',
        'year' => 'integer',
        'month' => 'integer',
    ];

    // Relationships
    public function period()
    {
        return $this->belongsTo(Period::class, 'id_period');
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

    public function scopeAssets($query)
    {
        return $query->where('coa_type', 'asset');
    }

    public function scopeLiabilities($query)
    {
        return $query->where('coa_type', 'liability');
    }

    public function scopeEquity($query)
    {
        return $query->where('coa_type', 'equity');
    }

    public function scopeRevenue($query)
    {
        return $query->where('coa_type', 'revenue');
    }

    public function scopeExpense($query)
    {
        return $query->where('coa_type', 'expense');
    }
}
