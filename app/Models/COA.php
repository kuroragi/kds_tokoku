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

    // Relationships
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

    // Scopes
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
    
    // Helper Methods
    public function isLeafAccount()
    {
        return $this->is_leaf_account;
    }
    
    public function isParentAccount()
    {
        return !$this->is_leaf_account;
    }
}
