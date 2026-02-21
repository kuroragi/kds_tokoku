<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class Project extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'business_unit_id',
        'project_code',
        'name',
        'description',
        'customer_id',
        'start_date',
        'end_date',
        'budget',
        'actual_cost',
        'revenue',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'revenue' => 'decimal:2',
    ];

    public const STATUSES = [
        'planning' => 'Perencanaan',
        'active' => 'Aktif',
        'on_hold' => 'Ditunda',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ];

    public const COST_CATEGORIES = [
        'material' => 'Material / Bahan',
        'labor' => 'Tenaga Kerja',
        'overhead' => 'Overhead',
        'other' => 'Lain-lain',
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

    public function costs()
    {
        return $this->hasMany(ProjectCost::class);
    }

    public function revenues()
    {
        return $this->hasMany(ProjectRevenue::class);
    }

    // Scopes
    public function scopeByBusinessUnit($query, $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Accessors
    public function getProfitAttribute(): float
    {
        return (float) $this->revenue - (float) $this->actual_cost;
    }

    public function getProfitMarginAttribute(): float
    {
        return $this->revenue > 0
            ? round($this->profit / $this->revenue * 100, 1)
            : 0;
    }

    public function getBudgetUsageAttribute(): float
    {
        return $this->budget > 0
            ? round($this->actual_cost / $this->budget * 100, 1)
            : 0;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Recalculate actual_cost and revenue from line items.
     */
    public function recalculate(): void
    {
        $this->actual_cost = $this->costs()->sum('amount');
        $this->revenue = $this->revenues()->sum('amount');
        $this->save();
    }
}
