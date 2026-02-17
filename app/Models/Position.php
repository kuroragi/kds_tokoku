<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class Position extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'business_unit_id',
        'code',
        'name',
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

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function salaryComponentDefaults()
    {
        return $this->hasMany(PositionSalaryComponent::class);
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
     * System default positions that will be duplicated for each business unit
     */
    public static function getSystemDefaults(): array
    {
        return [
            ['code' => 'MGR', 'name' => 'Manager'],
            ['code' => 'SPV', 'name' => 'Supervisor'],
            ['code' => 'ADM', 'name' => 'Admin'],
            ['code' => 'STF', 'name' => 'Staff'],
            ['code' => 'KSR', 'name' => 'Kasir'],
            ['code' => 'GDG', 'name' => 'Gudang'],
            ['code' => 'KRR', 'name' => 'Kurir'],
            ['code' => 'TKN', 'name' => 'Teknisi'],
            ['code' => 'STP', 'name' => 'Satpam'],
            ['code' => 'CS', 'name' => 'Cleaning Service'],
        ];
    }

    /**
     * Duplicate system default positions for a given business unit.
     */
    public static function duplicateDefaultsForBusinessUnit(int $businessUnitId): \Illuminate\Support\Collection
    {
        $created = collect();

        foreach (self::getSystemDefaults() as $default) {
            $exists = self::where('business_unit_id', $businessUnitId)
                ->where('code', $default['code'])
                ->exists();

            if (!$exists) {
                $position = self::create([
                    'business_unit_id' => $businessUnitId,
                    'code' => $default['code'],
                    'name' => $default['name'],
                    'is_system_default' => false,
                    'is_active' => true,
                ]);
                $created->push($position);
            }
        }

        return $created;
    }
}
