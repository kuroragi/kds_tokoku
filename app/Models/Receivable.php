<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class Receivable extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'business_unit_id',
        'customer_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'description',
        'credit_coa_id',
        'amount',
        'paid_amount',
        'status',
        'journal_master_id',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'amount' => 'integer',
        'paid_amount' => 'integer',
    ];

    public const STATUSES = [
        'unpaid' => 'Belum Diterima',
        'partial' => 'Diterima Sebagian',
        'paid' => 'Lunas',
        'void' => 'Batal',
    ];

    // Relationships
    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creditCoa()
    {
        return $this->belongsTo(COA::class, 'credit_coa_id');
    }

    public function journalMaster()
    {
        return $this->belongsTo(JournalMaster::class);
    }

    public function payments()
    {
        return $this->hasMany(ReceivablePayment::class);
    }

    // Computed
    public function getRemainingAttribute(): int
    {
        return $this->amount - $this->paid_amount;
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
