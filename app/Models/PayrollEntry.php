<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class PayrollEntry extends Model
{
    use SoftDeletes, Blameable;

    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'base_salary',
        'total_earnings',
        'total_benefits',
        'total_deductions',
        'pph21_amount',
        'pph21_rate',
        'gross_salary',
        'net_salary',
        'notes',
    ];

    protected $casts = [
        'base_salary' => 'integer',
        'total_earnings' => 'integer',
        'total_benefits' => 'integer',
        'total_deductions' => 'integer',
        'pph21_amount' => 'integer',
        'pph21_rate' => 'decimal:2',
        'gross_salary' => 'integer',
        'net_salary' => 'integer',
    ];

    // Relationships
    public function payrollPeriod()
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function details()
    {
        return $this->hasMany(PayrollEntryDetail::class);
    }

    // Filtered details
    public function earnings()
    {
        return $this->details()->where('type', 'earning');
    }

    public function deductions()
    {
        return $this->details()->where('type', 'deduction');
    }

    public function benefits()
    {
        return $this->details()->where('type', 'benefit');
    }

    /**
     * Recalculate totals from details.
     */
    public function recalculateFromDetails(): void
    {
        $this->total_earnings = $this->details()->where('type', 'earning')->sum('amount');
        $this->total_benefits = $this->details()->where('type', 'benefit')->sum('amount');
        $this->total_deductions = $this->details()->where('type', 'deduction')->sum('amount');
        $this->pph21_amount = $this->details()->where('category', 'pph21')->sum('amount');
        $this->gross_salary = $this->total_earnings + $this->total_benefits;
        $this->net_salary = $this->total_earnings - $this->total_deductions;
        $this->save();
    }
}
