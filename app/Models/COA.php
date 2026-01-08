<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class COA extends Model
{
    use SoftDeletes, Blameable;

    protected $table = 'c_o_a_s';

    protected $fillable = [
        'code',
        'name',
        'type',
        'parent_id',
        'level',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'level' => 'integer',
    ];

    // Relationships
    public function parent()
    {
        return $this->belongsTo(COA::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(COA::class, 'parent_id');
    }

    public function journals()
    {
        return $this->hasMany(Journal::class, 'id_coa');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}
