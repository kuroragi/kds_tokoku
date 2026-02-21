<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class TaxInvoice extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'business_unit_id',
        'invoice_type',
        'faktur_number',
        'invoice_date',
        'tax_period',
        'partner_name',
        'partner_npwp',
        'dpp',
        'ppn',
        'ppnbm',
        'status',
        'sale_id',
        'purchase_id',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'dpp' => 'decimal:2',
        'ppn' => 'decimal:2',
        'ppnbm' => 'decimal:2',
    ];

    public const TYPES = [
        'keluaran' => 'Faktur Pajak Keluaran',
        'masukan' => 'Faktur Pajak Masukan',
    ];

    public const STATUSES = [
        'draft' => 'Draft',
        'approved' => 'Disetujui',
        'reported' => 'Dilaporkan',
        'cancelled' => 'Dibatalkan',
    ];

    // Relationships
    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    // Scopes
    public function scopeByBusinessUnit($query, $id)
    {
        return $query->where('business_unit_id', $id);
    }

    public function scopeKeluaran($query)
    {
        return $query->where('invoice_type', 'keluaran');
    }

    public function scopeMasukan($query)
    {
        return $query->where('invoice_type', 'masukan');
    }

    public function scopeByPeriod($query, string $period)
    {
        return $query->where('tax_period', $period);
    }

    // Accessors
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->invoice_type] ?? $this->invoice_type;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getTotalPajakAttribute(): float
    {
        return (float) $this->ppn + (float) $this->ppnbm;
    }
}
