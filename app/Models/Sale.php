<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class Sale extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'business_unit_id',
        'customer_id',
        'invoice_number',
        'sale_type',
        'sale_date',
        'due_date',
        'notes',
        'subtotal',
        'discount',
        'tax',
        'grand_total',
        'payment_type',
        'payment_source',
        'paid_amount',
        'down_payment_amount',
        'prepaid_deduction_amount',
        'remaining_amount',
        'payment_status',
        'status',
        'journal_master_id',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'down_payment_amount' => 'decimal:2',
        'prepaid_deduction_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    public const PAYMENT_TYPES = [
        'cash' => 'Tunai (Lunas)',
        'credit' => 'Piutang (Kredit)',
        'partial' => 'Bayar Sebagian',
        'down_payment' => 'Uang Muka (DP)',
        'prepaid_deduction' => 'Potong Pendapatan Diterima Dimuka',
    ];

    public const SALE_TYPES = [
        'goods' => 'Barang',
        'saldo' => 'Saldo',
        'service' => 'Jasa',
        'mix' => 'Campuran',
    ];

    public const PAYMENT_STATUSES = [
        'unpaid' => 'Belum Dibayar',
        'partial' => 'Dibayar Sebagian',
        'paid' => 'Lunas',
    ];

    public const STATUSES = [
        'draft' => 'Draft',
        'confirmed' => 'Dikonfirmasi',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ];

    // ─── Relationships ───

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(SalePayment::class);
    }

    public function journalMaster()
    {
        return $this->belongsTo(JournalMaster::class);
    }

    // ─── Scopes ───

    public function scopeByBusinessUnit($query, $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPaymentStatus($query, $status)
    {
        return $query->where('payment_status', $status);
    }

    // ─── Helpers ───

    public function recalculateTotals(): void
    {
        $this->subtotal = $this->items()->sum('subtotal');
        $this->grand_total = $this->subtotal - $this->discount + $this->tax;
        $this->remaining_amount = $this->grand_total - $this->paid_amount;
        $this->save();
    }

    public function recalculatePayments(): void
    {
        $totalPaid = $this->payments()->sum('amount')
            + (float) $this->down_payment_amount
            + (float) $this->prepaid_deduction_amount;
        $this->paid_amount = $totalPaid;
        $this->remaining_amount = (float) $this->grand_total - $totalPaid;

        if ($this->remaining_amount <= 0) {
            $this->remaining_amount = 0;
            $this->payment_status = 'paid';
        } elseif ($totalPaid > 0) {
            $this->payment_status = 'partial';
        } else {
            $this->payment_status = 'unpaid';
        }

        $this->save();
    }

    public function getSaleTypeLabel(): string
    {
        return self::SALE_TYPES[$this->sale_type] ?? $this->sale_type;
    }

    public function getPaymentTypeLabel(): string
    {
        return self::PAYMENT_TYPES[$this->payment_type] ?? $this->payment_type;
    }

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getPaymentStatusLabel(): string
    {
        return self::PAYMENT_STATUSES[$this->payment_status] ?? $this->payment_status;
    }

    public function taxInvoices()
    {
        return $this->hasMany(TaxInvoice::class);
    }
}
