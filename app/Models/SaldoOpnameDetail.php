<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kuroragi\GeneralHelper\Traits\Blameable;

class SaldoOpnameDetail extends Model
{
    use HasFactory, Blameable;

    protected $fillable = [
        'saldo_opname_id',
        'saldo_provider_id',
        'system_balance',
        'actual_balance',
        'difference',
        'notes',
    ];

    protected $casts = [
        'system_balance' => 'decimal:2',
        'actual_balance' => 'decimal:2',
        'difference' => 'decimal:2',
    ];

    // ─── Relationships ───

    public function saldoOpname()
    {
        return $this->belongsTo(SaldoOpname::class);
    }

    public function saldoProvider()
    {
        return $this->belongsTo(SaldoProvider::class);
    }
}
