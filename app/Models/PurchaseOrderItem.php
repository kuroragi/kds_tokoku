<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kuroragi\GeneralHelper\Traits\Blameable;

class PurchaseOrderItem extends Model
{
    use HasFactory, Blameable;

    protected $fillable = [
        'purchase_order_id',
        'stock_id',
        'quantity',
        'received_quantity',
        'unit_price',
        'discount',
        'subtotal',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'received_quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    // ─── Relationships ───

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    // ─── Helpers ───

    public function getRemainingQuantityAttribute(): float
    {
        return (float) $this->quantity - (float) $this->received_quantity;
    }

    public function isFullyReceived(): bool
    {
        return $this->received_quantity >= $this->quantity;
    }
}
