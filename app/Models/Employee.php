<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class Employee extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'business_unit_id',
        'position_id',
        'user_id',
        'code',
        'name',
        'nik',
        'phone',
        'email',
        'address',
        'join_date',
        'base_salary',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'npwp',
        'ptkp_status',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'join_date' => 'date',
        'base_salary' => 'integer',
    ];

    const PTKP_STATUSES = [
        'TK/0' => 'TK/0 - Tidak Kawin, 0 Tanggungan',
        'TK/1' => 'TK/1 - Tidak Kawin, 1 Tanggungan',
        'TK/2' => 'TK/2 - Tidak Kawin, 2 Tanggungan',
        'TK/3' => 'TK/3 - Tidak Kawin, 3 Tanggungan',
        'K/0' => 'K/0 - Kawin, 0 Tanggungan',
        'K/1' => 'K/1 - Kawin, 1 Tanggungan',
        'K/2' => 'K/2 - Kawin, 2 Tanggungan',
        'K/3' => 'K/3 - Kawin, 3 Tanggungan',
    ];

    // Relationships
    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function salaryComponents()
    {
        return $this->hasMany(EmployeeSalaryComponent::class);
    }

    public function payrollEntries()
    {
        return $this->hasMany(PayrollEntry::class);
    }

    public function loans()
    {
        return $this->hasMany(EmployeeLoan::class);
    }

    public function activeLoans()
    {
        return $this->hasMany(EmployeeLoan::class)->where('status', 'active');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByBusinessUnit($query, $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    /**
     * Get effective amount for a salary component.
     * Priority: employee override > position template > component default
     */
    public function getEffectiveSalaryAmount(SalaryComponent $component): ?int
    {
        // Employee override
        $empComp = $this->salaryComponents()
            ->where('salary_component_id', $component->id)
            ->first();
        if ($empComp) {
            return $empComp->amount;
        }

        // Position template
        if ($this->position_id) {
            $posComp = PositionSalaryComponent::where('position_id', $this->position_id)
                ->where('salary_component_id', $component->id)
                ->first();
            if ($posComp) {
                return $posComp->amount;
            }
        }

        // Component default
        return $component->default_amount;
    }
}
