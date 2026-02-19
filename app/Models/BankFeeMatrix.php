<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kuroragi\GeneralHelper\Traits\Blameable;

class BankFeeMatrix extends Model
{
    use Blameable;

    protected $table = 'bank_fee_matrix';

    protected $fillable = [
        'source_bank_id',
        'destination_bank_id',
        'transfer_type',
        'fee',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'fee' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // ─── Relationships ───

    public function sourceBank()
    {
        return $this->belongsTo(Bank::class, 'source_bank_id');
    }

    public function destinationBank()
    {
        return $this->belongsTo(Bank::class, 'destination_bank_id');
    }

    // ─── Scopes ───

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ─── Helpers ───

    public static function getTransferTypes(): array
    {
        return [
            'online' => 'Online Transfer',
            'bi-fast' => 'BI-Fast',
            'rtgs' => 'RTGS',
            'sknbi' => 'SKN/BI',
            'other' => 'Lainnya',
        ];
    }

    /**
     * Find fee for a transfer between two banks.
     */
    public static function findFee(int $sourceBankId, int $destinationBankId, string $transferType = 'online'): ?float
    {
        $matrix = self::active()
            ->where('source_bank_id', $sourceBankId)
            ->where('destination_bank_id', $destinationBankId)
            ->where('transfer_type', $transferType)
            ->first();

        return $matrix ? (float) $matrix->fee : null;
    }
}
