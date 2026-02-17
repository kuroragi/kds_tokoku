<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class EmployeeLoan extends Model
{
    use SoftDeletes, Blameable;

    protected $fillable = [
        'business_unit_id',
        'employee_id',
        'loan_number',
        'description',
        'loan_amount',
        'installment_count',
        'installment_amount',
        'disbursed_date',
        'start_deduction_date',
        'payment_coa_id',
        'journal_master_id',
        'total_paid',
        'remaining_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'loan_amount' => 'integer',
        'installment_count' => 'integer',
        'installment_amount' => 'integer',
        'total_paid' => 'integer',
        'remaining_amount' => 'integer',
        'disbursed_date' => 'date',
        'start_deduction_date' => 'date',
    ];

    const STATUSES = [
        'active' => 'Aktif',
        'paid_off' => 'Lunas',
        'void' => 'Batal',
    ];

    const STATUS_COLORS = [
        'active' => 'primary',
        'paid_off' => 'success',
        'void' => 'secondary',
    ];

    // Relationships
    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function paymentCoa()
    {
        return $this->belongsTo(COA::class, 'payment_coa_id');
    }

    public function journalMaster()
    {
        return $this->belongsTo(JournalMaster::class);
    }

    public function payments()
    {
        return $this->hasMany(EmployeeLoanPayment::class);
    }

    // Computed
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getIsPaidOffAttribute(): bool
    {
        return $this->status === 'paid_off';
    }

    public function getRemainingInstallmentsAttribute(): int
    {
        if ($this->installment_amount <= 0) return 0;
        return (int) ceil($this->remaining_amount / $this->installment_amount);
    }

    public function getProgressPercentAttribute(): float
    {
        if ($this->loan_amount <= 0) return 100;
        return round(($this->total_paid / $this->loan_amount) * 100, 1);
    }

    /**
     * Get the deduction amount for the current payroll period.
     * Returns installment_amount or remaining_amount if it's the last installment.
     */
    public function getDeductionAmount(): int
    {
        if ($this->status !== 'active') return 0;
        if ($this->remaining_amount <= 0) return 0;

        return min($this->installment_amount, $this->remaining_amount);
    }

    /**
     * Check if deduction should start for a given month/year.
     */
    public function shouldDeductForPeriod(int $month, int $year): bool
    {
        if ($this->status !== 'active') return false;
        if ($this->remaining_amount <= 0) return false;

        if ($this->start_deduction_date) {
            $periodStart = \Carbon\Carbon::create($year, $month, 1);
            return $periodStart->gte($this->start_deduction_date->startOfMonth());
        }

        // Default: start deducting from disbursed month
        $periodStart = \Carbon\Carbon::create($year, $month, 1);
        return $periodStart->gte($this->disbursed_date->startOfMonth());
    }

    /**
     * Record a payment and update balance.
     */
    public function recordPayment(int $amount): void
    {
        $this->total_paid += $amount;
        $this->remaining_amount -= $amount;

        if ($this->remaining_amount <= 0) {
            $this->remaining_amount = 0;
            $this->status = 'paid_off';
        }

        $this->save();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByBusinessUnit($query, $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: loans that should be deducted for a given payroll period.
     */
    public function scopeDeductibleForPeriod($query, int $month, int $year)
    {
        $periodStart = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();

        return $query->where('status', 'active')
            ->where('remaining_amount', '>', 0)
            ->where(function ($q) use ($periodStart) {
                $q->where(function ($q2) use ($periodStart) {
                    // Has explicit start_deduction_date
                    $q2->whereNotNull('start_deduction_date')
                        ->where('start_deduction_date', '<=', $periodStart->endOfMonth());
                })->orWhere(function ($q2) use ($periodStart) {
                    // No start_deduction_date, use disbursed_date
                    $q2->whereNull('start_deduction_date')
                        ->where('disbursed_date', '<=', $periodStart->endOfMonth());
                });
            });
    }

    /**
     * Generate next loan number for a business unit.
     */
    public static function generateLoanNumber(int $businessUnitId): string
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        $prefix = "LOAN-{$year}{$month}";

        $lastLoan = self::where('business_unit_id', $businessUnitId)
            ->where('loan_number', 'like', "{$prefix}%")
            ->withTrashed()
            ->orderByDesc('loan_number')
            ->first();

        if ($lastLoan) {
            $lastNumber = (int) substr($lastLoan->loan_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
