<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kuroragi\GeneralHelper\Traits\Blameable;

class StockOpnameDetail extends Model
{
    use HasFactory, Blameable;

    protected $fillable = [
        'stock_opname_id',
        'stock_id',
        'system_qty',
        'actual_qty',
        'difference',
        'notes',
    ];

    protected $casts = [
        'system_qty' => 'decimal:2',
        'actual_qty' => 'decimal:2',
        'difference' => 'decimal:2',
    ];

    // ─── Relationships ───

    public function stockOpname()
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }
}
