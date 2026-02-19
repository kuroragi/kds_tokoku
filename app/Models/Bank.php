<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class Bank extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'code',
        'name',
        'swift_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ─── Relationships ───

    public function bankAccounts()
    {
        return $this->hasMany(BankAccount::class);
    }

    public function sourceFeeMatrix()
    {
        return $this->hasMany(BankFeeMatrix::class, 'source_bank_id');
    }

    public function destinationFeeMatrix()
    {
        return $this->hasMany(BankFeeMatrix::class, 'destination_bank_id');
    }

    // ─── Scopes ───

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
