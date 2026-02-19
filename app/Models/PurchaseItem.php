<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kuroragi\GeneralHelper\Traits\Blameable;

class PurchaseItem extends Model
{
    use HasFactory, Blameable;

    protected $fillable = [
        'purchase_id',
        'item_type',
        'description',
        'stock_id',
        'saldo_provider_id',
        'purchase_order_item_id',
        'quantity',
        'unit_price',
        'discount',
        'subtotal',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    // ─── Relationships ───

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function saldoProvider()
    {
        return $this->belongsTo(SaldoProvider::class);
    }

    // ─── Constants ───

    public const ITEM_TYPES = [
        'goods' => 'Barang',
        'saldo' => 'Saldo',
        'service' => 'Jasa',
    ];
}
