<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sale_id',
        'item_type',
        'description',
        'stock_id',
        'saldo_provider_id',
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

    public const ITEM_TYPES = [
        'goods' => 'Barang',
        'saldo' => 'Saldo',
        'service' => 'Jasa',
    ];

    // ─── Relationships ───

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function saldoProvider()
    {
        return $this->belongsTo(SaldoProvider::class);
    }
}
