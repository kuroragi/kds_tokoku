<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class BusinessUnit extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'code',
        'name',
        'owner_name',
        'phone',
        'email',
        'address',
        'city',
        'province',
        'postal_code',
        'tax_id',
        'business_type',
        'description',
        'logo',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function users()
    {
        return $this->hasMany(User::class, 'business_unit_id');
    }

    public function coaMappings()
    {
        return $this->hasMany(BusinessUnitCoaMapping::class);
    }

    public function stockCategories()
    {
        return $this->hasMany(StockCategory::class);
    }

    public function categoryGroups()
    {
        return $this->hasMany(CategoryGroup::class);
    }

    public function unitOfMeasures()
    {
        return $this->hasMany(UnitOfMeasure::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function positions()
    {
        return $this->hasMany(Position::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function vendors()
    {
        return $this->belongsToMany(Vendor::class, 'business_unit_vendor')
            ->withTimestamps();
    }

    public function partners()
    {
        return $this->hasMany(Partner::class);
    }

    public function assetCategories()
    {
        return $this->hasMany(AssetCategory::class);
    }

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    // Helpers
    public function getCoaByKey(string $accountKey): ?COA
    {
        $mapping = $this->coaMappings()->where('account_key', $accountKey)->first();
        return $mapping?->coa;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
