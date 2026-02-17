<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class Vendor extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'code',
        'name',
        'type',
        'phone',
        'email',
        'address',
        'city',
        'contact_person',
        'npwp',
        'nik',
        'is_pph23',
        'pph23_rate',
        'is_net_pph23',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'website',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_pph23' => 'boolean',
        'is_net_pph23' => 'boolean',
        'is_active' => 'boolean',
        'pph23_rate' => 'decimal:2',
    ];

    public const TYPES = [
        'distributor' => 'Distributor',
        'supplier_bahan' => 'Supplier Bahan',
        'jasa' => 'Jasa',
        'lainnya' => 'Lainnya',
    ];

    // Relationships
    public function businessUnits()
    {
        return $this->belongsToMany(BusinessUnit::class, 'business_unit_vendor')
            ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to vendors attached to a specific business unit
     */
    public function scopeByBusinessUnit($query, $businessUnitId)
    {
        return $query->whereHas('businessUnits', function ($q) use ($businessUnitId) {
            $q->where('business_units.id', $businessUnitId);
        });
    }

    /**
     * Get vendor type label
     */
    public static function getTypes(): array
    {
        return self::TYPES;
    }
}
