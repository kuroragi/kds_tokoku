<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSalaryComponent extends Model
{
    protected $fillable = [
        'employee_id',
        'salary_component_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function salaryComponent()
    {
        return $this->belongsTo(SalaryComponent::class);
    }
}
