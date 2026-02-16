<?php

namespace App\Services;

use App\Models\BusinessUnit;
use Illuminate\Support\Collection;

class BusinessUnitService
{
    /**
     * Check if the current authenticated user is a superadmin.
     */
    public static function isSuperAdmin(): bool
    {
        $user = auth()->user();
        return $user && $user->hasRole('superadmin');
    }

    /**
     * Get the business_unit_id of the current authenticated user.
     * Returns null if not set (e.g. superadmin without unit).
     */
    public static function getUserBusinessUnitId(): ?int
    {
        return auth()->user()?->business_unit_id;
    }

    /**
     * Whether the current user should see a business unit selector (dropdown).
     * Only superadmin can select from multiple units.
     */
    public static function shouldShowUnitSelector(): bool
    {
        return static::isSuperAdmin();
    }

    /**
     * Get available business units for the current user.
     * Superadmin: all active units.
     * Others: only their own unit.
     */
    public static function getAvailableUnits(): Collection
    {
        if (static::isSuperAdmin()) {
            return BusinessUnit::active()->orderBy('name')->get();
        }

        $unitId = static::getUserBusinessUnitId();
        if ($unitId) {
            return BusinessUnit::where('id', $unitId)->get();
        }

        return collect();
    }

    /**
     * Resolve business_unit_id for forms.
     * Superadmin: use the provided form value.
     * Others: always use user's own business_unit_id.
     */
    public static function resolveBusinessUnitId($formValue = null): ?int
    {
        if (static::isSuperAdmin()) {
            return $formValue ? (int) $formValue : null;
        }

        return static::getUserBusinessUnitId();
    }

    /**
     * Get the default business_unit_id for a new form.
     * Superadmin: empty (must pick).
     * Others: their own business_unit_id.
     */
    public static function getDefaultBusinessUnitId(): mixed
    {
        if (static::isSuperAdmin()) {
            return '';
        }

        return static::getUserBusinessUnitId() ?? '';
    }

    /**
     * Apply business unit scope to a query for list/filter.
     * Superadmin with filterUnit: filter by selected unit.
     * Non-superadmin: always scope to user's unit.
     */
    public static function applyBusinessUnitFilter($query, string $filterUnit = '', string $column = 'business_unit_id')
    {
        if (!static::isSuperAdmin()) {
            $unitId = static::getUserBusinessUnitId();
            if ($unitId) {
                $query->where($column, $unitId);
            }
            return $query;
        }

        // Superadmin with explicit filter
        if ($filterUnit) {
            $query->where($column, $filterUnit);
        }

        return $query;
    }
}
