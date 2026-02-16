<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class UnitOfMeasure extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'business_unit_id',
        'code',
        'name',
        'symbol',
        'description',
        'is_system_default',
        'is_active',
    ];

    protected $casts = [
        'is_system_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
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

    public function scopeSystemDefaults($query)
    {
        return $query->where('is_system_default', true)->whereNull('business_unit_id');
    }

    public function scopeByBusinessUnit($query, $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    /**
     * System default units that will be duplicated for each business unit
     */
    public static function getSystemDefaults(): array
    {
        return [
            ['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'pcs'],
            ['code' => 'UNIT', 'name' => 'Unit', 'symbol' => 'unit'],
            ['code' => 'BOX', 'name' => 'Box', 'symbol' => 'box'],
            ['code' => 'KG', 'name' => 'Kilogram', 'symbol' => 'kg'],
            ['code' => 'GR', 'name' => 'Gram', 'symbol' => 'gr'],
            ['code' => 'LTR', 'name' => 'Liter', 'symbol' => 'ltr'],
            ['code' => 'MTR', 'name' => 'Meter', 'symbol' => 'mtr'],
            ['code' => 'SET', 'name' => 'Set', 'symbol' => 'set'],
            ['code' => 'PAK', 'name' => 'Pak', 'symbol' => 'pak'],
            ['code' => 'DZN', 'name' => 'Lusin', 'symbol' => 'dzn'],
            ['code' => 'ROLL', 'name' => 'Roll', 'symbol' => 'roll'],
            ['code' => 'LBR', 'name' => 'Lembar', 'symbol' => 'lbr'],
        ];
    }

    /**
     * Duplicate system default units for a given business unit.
     * Returns the collection of newly created UnitOfMeasure records.
     */
    public static function duplicateDefaultsForBusinessUnit(int $businessUnitId): \Illuminate\Support\Collection
    {
        $created = collect();

        foreach (self::getSystemDefaults() as $default) {
            // Skip if already exists for this business unit
            $exists = self::where('business_unit_id', $businessUnitId)
                ->where('code', $default['code'])
                ->exists();

            if (!$exists) {
                $unit = self::create([
                    'business_unit_id' => $businessUnitId,
                    'code' => $default['code'],
                    'name' => $default['name'],
                    'symbol' => $default['symbol'],
                    'is_system_default' => false, // it's a copy, not the system template
                    'is_active' => true,
                ]);
                $created->push($unit);
            }
        }

        return $created;
    }
}
