<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class PayrollPeriod extends Model
{
    use SoftDeletes, Blameable;

    protected $fillable = [
        'business_unit_id',
        'period_id',
        'month',
        'year',
        'name',
        'start_date',
        'end_date',
        'status',
        'total_earnings',
        'total_benefits',
        'total_deductions',
        'total_net',
        'total_tax',
        'payment_coa_id',
        'journal_master_id',
        'paid_date',
        'notes',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'total_earnings' => 'integer',
        'total_benefits' => 'integer',
        'total_deductions' => 'integer',
        'total_net' => 'integer',
        'total_tax' => 'integer',
        'paid_date' => 'date',
    ];

    const STATUSES = [
        'draft' => 'Draft',
        'calculated' => 'Dihitung',
        'approved' => 'Disetujui',
        'paid' => 'Dibayar',
        'void' => 'Batal',
    ];

    const STATUS_COLORS = [
        'draft' => 'gray',
        'calculated' => 'blue',
        'approved' => 'yellow',
        'paid' => 'green',
        'void' => 'red',
    ];

    // Relationships
    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function period()
    {
        return $this->belongsTo(Period::class);
    }

    public function paymentCoa()
    {
        return $this->belongsTo(COA::class, 'payment_coa_id');
    }

    public function journalMaster()
    {
        return $this->belongsTo(JournalMaster::class);
    }

    public function entries()
    {
        return $this->hasMany(PayrollEntry::class);
    }

    // Scopes
    public function scopeByBusinessUnit($query, $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // Helpers
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isCalculated(): bool
    {
        return $this->status === 'calculated';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isVoid(): bool
    {
        return $this->status === 'void';
    }

    public function canCalculate(): bool
    {
        return in_array($this->status, ['draft', 'calculated']);
    }

    public function canApprove(): bool
    {
        return $this->status === 'calculated';
    }

    public function canPay(): bool
    {
        return $this->status === 'approved';
    }

    public function canVoid(): bool
    {
        return in_array($this->status, ['draft', 'calculated', 'approved']);
    }

    /**
     * Recalculate totals from entries.
     */
    public function recalculateTotals(): void
    {
        $this->total_earnings = $this->entries()->sum('total_earnings');
        $this->total_benefits = $this->entries()->sum('total_benefits');
        $this->total_deductions = $this->entries()->sum('total_deductions');
        $this->total_net = $this->entries()->sum('net_salary');
        $this->total_tax = $this->entries()->sum('pph21_amount');
        $this->save();
    }
}
