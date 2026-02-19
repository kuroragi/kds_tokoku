<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class CashAccount extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'business_unit_id',
        'name',
        'initial_balance',
        'current_balance',
        'is_active',
    ];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // ─── Relationships ───

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    // ─── Scopes ───

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByBusinessUnit($query, $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    // ─── Helpers ───

    /**
     * Get or create default cash account for a business unit.
     */
    public static function getOrCreateDefault(int $businessUnitId): self
    {
        return self::withoutEvents(function () use ($businessUnitId) {
            return self::firstOrCreate(
                ['business_unit_id' => $businessUnitId],
                [
                    'name' => 'Kas Utama',
                    'initial_balance' => 0,
                    'current_balance' => 0,
                    'is_active' => true,
                ]
            );
        });
    }
}
