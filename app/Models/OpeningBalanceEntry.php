<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpeningBalanceEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'opening_balance_id',
        'coa_id',
        'coa_code',
        'coa_name',
        'debit',
        'credit',
        'notes',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    // ─── Relationships ───

    public function openingBalance()
    {
        return $this->belongsTo(OpeningBalance::class);
    }

    public function coa()
    {
        return $this->belongsTo(COA::class, 'coa_id');
    }

    // ─── Helpers ───

    public function getAmountAttribute(): float
    {
        return max((float) $this->debit, (float) $this->credit);
    }

    public function getTypeAttribute(): string
    {
        return (float) $this->debit > 0 ? 'debit' : 'credit';
    }
}
