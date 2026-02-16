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
