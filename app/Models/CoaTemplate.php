<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class CoaTemplate extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $table = 'coa_templates';

    protected $fillable = [
        'code',
        'name',
        'type',
        'parent_code',
        'level',
        'order',
        'description',
        'is_active',
        'is_leaf_account',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_leaf_account' => 'boolean',
        'level' => 'integer',
        'order' => 'integer',
    ];

    // ── Relationships ───────────────────────────────────────────

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_code', 'id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_code', 'id');
    }

    /**
     * COA records that were cloned from this template.
     */
    public function clonedCoas()
    {
        return $this->hasMany(COA::class, 'template_id');
    }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLeafAccounts($query)
    {
        return $query->where('is_leaf_account', true);
    }

    public function scopeParentAccounts($query)
    {
        return $query->where('is_leaf_account', false);
    }

    public function scopeRootLevel($query)
    {
        return $query->whereNull('parent_code');
    }

    // ── Helpers ─────────────────────────────────────────────────

    /**
     * Clone all active templates to a specific business unit.
     * Returns the number of COA records created.
     */
    public static function cloneToBusinessUnit(int $businessUnitId): int
    {
        $templates = self::active()->orderBy('level')->orderBy('order')->get();

        if ($templates->isEmpty()) {
            return 0;
        }

        // Map: template_id => new COA id  (for resolving parent_code)
        $idMap = [];
        $count = 0;

        foreach ($templates as $template) {
            $parentCoaId = null;
            if ($template->parent_code && isset($idMap[$template->parent_code])) {
                $parentCoaId = $idMap[$template->parent_code];
            }

            $coa = COA::create([
                'business_unit_id' => $businessUnitId,
                'template_id'      => $template->id,
                'code'             => $template->code,
                'name'             => $template->name,
                'type'             => $template->type,
                'parent_code'      => $parentCoaId,
                'level'            => $template->level,
                'order'            => $template->order,
                'description'      => $template->description,
                'is_active'        => $template->is_active,
                'is_leaf_account'  => $template->is_leaf_account,
                'is_locked'        => true, // template-originated = locked by default
            ]);

            $idMap[$template->id] = $coa->id;
            $count++;
        }

        return $count;
    }
}
