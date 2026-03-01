<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class Asset extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'business_unit_id',
        'asset_category_id',
        'vendor_id',
        'code',
        'name',
        'description',
        'acquisition_date',
        'acquisition_cost',
        'useful_life_months',
        'salvage_value',
        'depreciation_method',
        'location',
        'serial_number',
        'condition',
        'status',
        'acquisition_type',
        'funding_source',
        'initial_accumulated_depreciation',
        'remaining_debt_amount',
        'journal_master_id',
        'notes',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'acquisition_cost' => 'integer',
        'useful_life_months' => 'integer',
        'salvage_value' => 'integer',
        'initial_accumulated_depreciation' => 'integer',
        'remaining_debt_amount' => 'integer',
    ];

    public const STATUSES = [
        'active' => 'Aktif',
        'disposed' => 'Dilepas',
        'under_repair' => 'Dalam Perbaikan',
    ];

    public const CONDITIONS = [
        'good' => 'Baik',
        'fair' => 'Cukup',
        'poor' => 'Buruk',
    ];

    public const ACQUISITION_TYPES = [
        'opening_balance' => 'Saldo Awal',
        'purchase_cash' => 'Pembelian Tunai',
        'purchase_credit' => 'Pembelian Kredit',
    ];

    public const FUNDING_SOURCES = [
        'equity' => 'Modal Pemilik',
        'debt' => 'Hutang',
        'mixed' => 'Campuran (Modal + Hutang)',
    ];

    // Relationships
    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function assetCategory()
    {
        return $this->belongsTo(AssetCategory::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function journalMaster()
    {
        return $this->belongsTo(JournalMaster::class, 'journal_master_id');
    }

    public function depreciations()
    {
        return $this->hasMany(AssetDepreciation::class);
    }

    public function transfers()
    {
        return $this->hasMany(AssetTransfer::class);
    }

    public function disposals()
    {
        return $this->hasMany(AssetDisposal::class);
    }

    public function repairs()
    {
        return $this->hasMany(AssetRepair::class);
    }

    // Accessors
    public function getAccumulatedDepreciationAttribute(): int
    {
        return $this->initial_accumulated_depreciation + (int) $this->depreciations()->sum('depreciation_amount');
    }

    public function getBookValueAttribute(): int
    {
        return max(0, $this->acquisition_cost - $this->accumulated_depreciation);
    }

    public function getIsOpeningBalanceAttribute(): bool
    {
        return $this->acquisition_type === 'opening_balance';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByBusinessUnit($query, $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('asset_category_id', $categoryId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
