<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class SalePayment extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'sale_id',
        'amount',
        'payment_date',
        'payment_method',
        'payment_source',
        'reference_no',
        'notes',
        'journal_master_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public const PAYMENT_METHODS = [
        'cash' => 'Tunai',
        'bank_transfer' => 'Transfer Bank',
        'giro' => 'Giro',
        'e_wallet' => 'E-Wallet',
        'other' => 'Lainnya',
    ];

    // ─── Relationships ───

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function journalMaster()
    {
        return $this->belongsTo(JournalMaster::class);
    }

    // ─── Helpers ───

    public static function getMethods(): array
    {
        return self::PAYMENT_METHODS;
    }
}
