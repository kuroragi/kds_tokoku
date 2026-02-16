<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class Stock extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'business_unit_id',
        'category_group_id',
        'unit_of_measure_id',
        'code',
        'name',
        'barcode',
        'description',
        'buy_price',
        'sell_price',
        'min_stock',
        'current_stock',
        'is_active',
    ];

    protected $casts = [
        'buy_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'min_stock' => 'decimal:2',
        'current_stock' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function categoryGroup()
    {
        return $this->belongsTo(CategoryGroup::class);
    }

    public function unitOfMeasure()
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByBusinessUnit($query, $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('current_stock', '<=', 'min_stock');
    }

    /**
     * Check if stock is below minimum level
     */
    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->min_stock;
    }

    /**
     * Get the stock category type via category group
     */
    public function getTypeAttribute(): ?string
    {
        return $this->categoryGroup?->stockCategory?->type;
    }
}
