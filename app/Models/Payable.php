<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class Payable extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'business_unit_id',
        'vendor_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'description',
        'debit_coa_id',
        'input_amount',
        'is_net_basis',
        'dpp',
        'pph23_rate',
        'pph23_amount',
        'amount_due',
        'paid_amount',
        'status',
        'journal_master_id',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'is_net_basis' => 'boolean',
        'pph23_rate' => 'decimal:2',
        'input_amount' => 'integer',
        'dpp' => 'integer',
        'pph23_amount' => 'integer',
        'amount_due' => 'integer',
        'paid_amount' => 'integer',
    ];

    public const STATUSES = [
        'unpaid' => 'Belum Dibayar',
        'partial' => 'Dibayar Sebagian',
        'paid' => 'Lunas',
        'void' => 'Batal',
    ];

    // Relationships
    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function debitCoa()
    {
        return $this->belongsTo(COA::class, 'debit_coa_id');
    }

    public function journalMaster()
    {
        return $this->belongsTo(JournalMaster::class);
    }

    public function payments()
    {
        return $this->hasMany(PayablePayment::class);
    }

    // Computed
    public function getRemainingAttribute(): int
    {
        return $this->amount_due - $this->paid_amount;
    }

    public function getIsOverdueAttribute(): bool
    {
        return in_array($this->status, ['unpaid', 'partial']) && $this->due_date->lt(now()->startOfDay());
    }

    // Scopes
    public function scopeByBusinessUnit($query, $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOverdue($query)
    {
        return $query->whereIn('status', ['unpaid', 'partial'])
            ->where('due_date', '<', now()->startOfDay());
    }

    public function scopeOutstanding($query)
    {
        return $query->whereIn('status', ['unpaid', 'partial']);
    }
}
