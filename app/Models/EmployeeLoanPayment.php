<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class EmployeeLoanPayment extends Model
{
    use SoftDeletes, Blameable;

    protected $fillable = [
        'employee_loan_id',
        'payroll_period_id',
        'payroll_entry_detail_id',
        'payment_date',
        'amount',
        'reference',
        'journal_master_id',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'integer',
    ];

    // Relationships
    public function loan()
    {
        return $this->belongsTo(EmployeeLoan::class, 'employee_loan_id');
    }

    public function payrollPeriod()
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function payrollEntryDetail()
    {
        return $this->belongsTo(PayrollEntryDetail::class);
    }

    public function journalMaster()
    {
        return $this->belongsTo(JournalMaster::class);
    }

    // Computed
    public function getIsFromPayrollAttribute(): bool
    {
        return $this->payroll_period_id !== null;
    }

    // Scopes
    public function scopeByLoan($query, $loanId)
    {
        return $query->where('employee_loan_id', $loanId);
    }

    public function scopeFromPayroll($query)
    {
        return $query->whereNotNull('payroll_period_id');
    }

    public function scopeManual($query)
    {
        return $query->whereNull('payroll_period_id');
    }
}
