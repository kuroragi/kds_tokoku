<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class ReceivablePayment extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'receivable_id',
        'payment_date',
        'amount',
        'payment_coa_id',
        'reference',
        'journal_master_id',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'integer',
    ];

    // Relationships
    public function receivable()
    {
        return $this->belongsTo(Receivable::class);
    }

    public function paymentCoa()
    {
        return $this->belongsTo(COA::class, 'payment_coa_id');
    }

    public function journalMaster()
    {
        return $this->belongsTo(JournalMaster::class);
    }
}
