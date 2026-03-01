<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class StockCategory extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'business_unit_id',
        'code',
        'name',
        'type',
        'coa_inventory_key',
        'coa_hpp_key',
        'coa_revenue_key',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Predefined COA key mappings per stock category type.
     * Digunakan sebagai default saat membuat kategori stok baru.
     */
    public const COA_KEY_PRESETS = [
        'barang' => [
            'coa_inventory_key' => 'persediaan_barang',
            'coa_hpp_key'       => 'hpp',
            'coa_revenue_key'   => 'pendapatan_utama',
        ],
        'jasa' => [
            'coa_inventory_key' => null, // Jasa tidak punya persediaan
            'coa_hpp_key'       => 'beban_lain',
            'coa_revenue_key'   => 'pendapatan_jasa',
        ],
        'saldo' => [
            'coa_inventory_key' => 'persediaan_saldo',
            'coa_hpp_key'       => 'hpp',
            'coa_revenue_key'   => 'pendapatan_utama',
        ],
    ];

    // Relationships
    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function categoryGroups()
    {
        return $this->hasMany(CategoryGroup::class);
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

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Available category types
     */
    public static function getTypes(): array
    {
        return [
            'barang' => 'Barang',
            'jasa' => 'Jasa',
            'saldo' => 'Saldo',
        ];
    }
}
