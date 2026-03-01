<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class AssetCategory extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'business_unit_id',
        'code',
        'name',
        'description',
        'useful_life_months',
        'depreciation_method',
        'coa_asset_key',
        'coa_accumulated_dep_key',
        'coa_expense_dep_key',
        'is_active',
    ];

    protected $casts = [
        'useful_life_months' => 'integer',
        'is_active' => 'boolean',
    ];

    public const DEPRECIATION_METHODS = [
        'straight_line' => 'Garis Lurus (Straight Line)',
        'declining_balance' => 'Saldo Menurun (Declining Balance)',
    ];

    /**
     * Predefined COA key mappings per category type.
     * Digunakan sebagai default saat membuat kategori baru.
     */
    public const COA_KEY_PRESETS = [
        'bangunan' => [
            'coa_asset_key' => 'aset_bangunan',
            'coa_accumulated_dep_key' => 'akum_peny_bangunan',
            'coa_expense_dep_key' => 'beban_peny_bangunan',
        ],
        'kendaraan' => [
            'coa_asset_key' => 'aset_kendaraan',
            'coa_accumulated_dep_key' => 'akum_peny_kendaraan',
            'coa_expense_dep_key' => 'beban_peny_kendaraan',
        ],
        'peralatan' => [
            'coa_asset_key' => 'aset_peralatan',
            'coa_accumulated_dep_key' => 'akum_peny_peralatan',
            'coa_expense_dep_key' => 'beban_peny_peralatan',
        ],
        'inventaris' => [
            'coa_asset_key' => 'aset_inventaris',
            'coa_accumulated_dep_key' => 'akum_peny_inventaris',
            'coa_expense_dep_key' => 'beban_peny_inventaris',
        ],
        'mesin' => [
            'coa_asset_key' => 'aset_mesin',
            'coa_accumulated_dep_key' => 'akum_peny_mesin',
            'coa_expense_dep_key' => 'beban_peny_mesin',
        ],
        'tanah' => [
            'coa_asset_key' => 'aset_tanah',
            'coa_accumulated_dep_key' => null, // Tanah tidak disusutkan
            'coa_expense_dep_key' => null,
        ],
    ];

    // Relationships
    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function assets()
    {
        return $this->hasMany(Asset::class);
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
