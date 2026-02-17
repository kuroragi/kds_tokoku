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
