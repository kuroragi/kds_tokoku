<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PositionSalaryComponent extends Model
{
    protected $fillable = [
        'position_id',
        'salary_component_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function salaryComponent()
    {
        return $this->belongsTo(SalaryComponent::class);
    }
}
