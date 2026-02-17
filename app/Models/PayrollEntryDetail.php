<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollEntryDetail extends Model
{
    protected $fillable = [
        'payroll_entry_id',
        'salary_component_id',
        'component_name',
        'type',
        'category',
        'amount',
        'is_auto_calculated',
        'notes',
    ];

    protected $casts = [
        'amount' => 'integer',
        'is_auto_calculated' => 'boolean',
    ];

    // Relationships
    public function payrollEntry()
    {
        return $this->belongsTo(PayrollEntry::class);
    }

    public function salaryComponent()
    {
        return $this->belongsTo(SalaryComponent::class);
    }
}
