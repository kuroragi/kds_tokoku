<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class CategoryGroup extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'business_unit_id',
        'stock_category_id',
        'code',
        'name',
        'description',
        'coa_inventory_id',
        'coa_revenue_id',
        'coa_expense_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function stockCategory()
    {
        return $this->belongsTo(StockCategory::class);
    }

    public function coaInventory()
    {
        return $this->belongsTo(COA::class, 'coa_inventory_id');
    }

    public function coaRevenue()
    {
        return $this->belongsTo(COA::class, 'coa_revenue_id');
    }

    public function coaExpense()
    {
        return $this->belongsTo(COA::class, 'coa_expense_id');
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
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
}
