<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'business_unit_id',
        'vendor_id',
        'po_number',
        'po_date',
        'expected_date',
        'notes',
        'subtotal',
        'discount',
        'tax',
        'grand_total',
        'status',
    ];

    protected $casts = [
        'po_date' => 'date',
        'expected_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public const STATUSES = [
        'draft' => 'Draft',
        'confirmed' => 'Dikonfirmasi',
        'partial_received' => 'Diterima Sebagian',
        'received' => 'Diterima Semua',
        'cancelled' => 'Dibatalkan',
    ];

    // ─── Relationships ───

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
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

    /**
     * PO that can still receive goods (confirmed or partial)
     */
    public function scopeReceivable($query)
    {
        return $query->whereIn('status', ['confirmed', 'partial_received']);
    }

    // ─── Helpers ───

    public function recalculateTotals(): void
    {
        $this->subtotal = $this->items()->sum('subtotal');
        $this->grand_total = $this->subtotal - $this->discount + $this->tax;
        $this->save();
    }

    /**
     * Check if all items have been fully received
     */
    public function isFullyReceived(): bool
    {
        return $this->items->every(fn ($item) => $item->received_quantity >= $item->quantity);
    }
}
