<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class COA extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $table = 'c_o_a_s';

    protected $fillable = [
        'business_unit_id',
        'template_id',
        'code',
        'name',
        'type',
        'parent_code',
        'level',
        'order',
        'description',
        'is_active',
        'is_leaf_account',
        'is_locked',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_leaf_account' => 'boolean',
        'is_locked' => 'boolean',
        'level' => 'integer',
        'order' => 'integer',
    ];

    // ── Relationships ───────────────────────────────────────────

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function template()
    {
        return $this->belongsTo(CoaTemplate::class, 'template_id');
    }

    public function parent()
    {
        return $this->belongsTo(COA::class, 'parent_code', 'id');
    }

    public function children()
    {
        return $this->hasMany(COA::class, 'parent_code', 'id');
    }

    public function journals()
    {
        return $this->hasMany(Journal::class, 'id_coa');
    }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeLeafAccounts($query)
    {
        return $query->where('is_leaf_account', true);
    }

    public function scopeParentAccounts($query)
    {
        return $query->where('is_leaf_account', false);
    }

    public function scopeForBusinessUnit($query, ?int $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }

    // ── Helper Methods ──────────────────────────────────────────

    public function isLeafAccount(): bool
    {
        return $this->is_leaf_account;
    }

    public function isParentAccount(): bool
    {
        return !$this->is_leaf_account;
    }

    public function isLocked(): bool
    {
        return $this->is_locked;
    }

    public function isFromTemplate(): bool
    {
        return $this->template_id !== null;
    }
}
