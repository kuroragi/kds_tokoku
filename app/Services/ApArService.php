<?php

namespace App\Services;

use App\Models\BusinessUnit;
use App\Models\COA;
use App\Models\Payable;
use App\Models\PayablePayment;
use App\Models\Period;
use App\Models\Receivable;
use App\Models\ReceivablePayment;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;

class ApArService
{
    protected JournalService $journalService;

    public function __construct(JournalService $journalService)
    {
        $this->journalService = $journalService;
    }

    // ==================== PPh23 CALCULATION ====================

    /**
     * Calculate PPh23 amounts.
     *
     * @param int $inputAmount  The amount user entered
     * @param float $rate       PPh23 rate (e.g. 2.00)
     * @param bool $isNetBasis  Whether input is the net amount vendor wants to receive
     * @return array ['dpp' => int, 'pph23_amount' => int, 'amount_due' => int]
     */
    public static function calculatePph23(int $inputAmount, float $rate, bool $isNetBasis = false): array
    {
        if ($rate <= 0) {
            return [
                'dpp' => $inputAmount,
                'pph23_amount' => 0,
                'amount_due' => $inputAmount,
            ];
        }

        $rateDecimal = $rate / 100;

        if ($isNetBasis) {
            // Gross-up: DPP = Net / (1 - rate)
            $dpp = (int) round($inputAmount / (1 - $rateDecimal));
            $pph23Amount = $dpp - $inputAmount;
            $amountDue = $inputAmount; // vendor receives exactly what they asked
        } else {
            // Normal: DPP = input amount
            $dpp = $inputAmount;
            $pph23Amount = (int) round($dpp * $rateDecimal);
            $amountDue = $dpp - $pph23Amount;
        }

        return [
            'dpp' => $dpp,
            'pph23_amount' => $pph23Amount,
            'amount_due' => $amountDue,
        ];
    }

    // ==================== PAYABLE (AP) ====================

    /**
     * Create a new payable and its initial journal entry.
     */
    public function createPayable(array $data): Payable
    {
        return DB::transaction(function () use ($data) {
            $vendor = Vendor::findOrFail($data['vendor_id']);
            $unit = BusinessUnit::findOrFail($data['business_unit_id']);

            // Calculate PPh23
            $isPph23 = $vendor->is_pph23;
            $rate = $isPph23 ? (float) $vendor->pph23_rate : 0;
            $isNetBasis = $data['is_net_basis'] ?? ($vendor->is_net_pph23 ?? false);
            $inputAmount = (int) $data['input_amount'];

            $calc = self::calculatePph23($inputAmount, $rate, $isNetBasis);

            $payable = Payable::create([
                'business_unit_id' => $data['business_unit_id'],
                'vendor_id' => $data['vendor_id'],
                'invoice_number' => $data['invoice_number'],
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'],
                'description' => $data['description'] ?? null,
                'debit_coa_id' => $data['debit_coa_id'] ?? null,
                'input_amount' => $inputAmount,
                'is_net_basis' => $isNetBasis,
                'dpp' => $calc['dpp'],
                'pph23_rate' => $rate,
                'pph23_amount' => $calc['pph23_amount'],
                'amount_due' => $calc['amount_due'],
                'paid_amount' => 0,
                'status' => 'unpaid',
                'notes' => $data['notes'] ?? null,
            ]);

            // Create journal entry
            $journal = $this->createPayableJournal($payable, $unit);
            $payable->update(['journal_master_id' => $journal->id]);

            return $payable->fresh(['vendor', 'businessUnit', 'debitCoa', 'journalMaster']);
        });
    }

    /**
     * Create journal for a new payable:
     * Debit: [chosen account] = DPP
     * Credit: Hutang Usaha = amount_due
     * Credit: Hutang Pajak = pph23_amount (if any)
     */
    protected function createPayableJournal(Payable $payable, BusinessUnit $unit)
    {
        $period = Period::current()->open()->first();
        if (!$period) {
            throw new \Exception('Tidak ada periode aktif yang terbuka.');
        }

        // Resolve COA accounts
        $debitCoa = $payable->debit_coa_id ? COA::find($payable->debit_coa_id) : null;
        if (!$debitCoa) {
            throw new \Exception('Akun debit (beban/aset) harus dipilih.');
        }

        $hutangUsahaCoa = $unit->getCoaByKey('hutang_usaha');
        if (!$hutangUsahaCoa) {
            throw new \Exception('Mapping akun Hutang Usaha belum diatur untuk unit usaha ini.');
        }

        $entries = [
            [
                'coa_code' => $debitCoa->code,
                'description' => "Hutang - {$payable->vendor->name} ({$payable->invoice_number})",
                'debit' => $payable->dpp,
                'credit' => 0,
            ],
            [
                'coa_code' => $hutangUsahaCoa->code,
                'description' => "Hutang Usaha - {$payable->vendor->name}",
                'debit' => 0,
                'credit' => $payable->amount_due,
            ],
        ];

        // Add PPh23 entry if applicable
        if ($payable->pph23_amount > 0) {
            $hutangPajakCoa = $unit->getCoaByKey('hutang_pajak');
            if (!$hutangPajakCoa) {
                throw new \Exception('Mapping akun Hutang Pajak belum diatur untuk unit usaha ini.');
            }

            $entries[] = [
                'coa_code' => $hutangPajakCoa->code,
                'description' => "PPh23 ({$payable->pph23_rate}%) - {$payable->vendor->name}",
                'debit' => 0,
                'credit' => $payable->pph23_amount,
            ];
        }

        return $this->journalService->createJournalEntry([
            'journal_date' => $payable->invoice_date->format('Y-m-d'),
            'reference' => $payable->invoice_number,
            'description' => "Pencatatan Hutang - {$payable->vendor->name}",
            'id_period' => $period->id,
            'type' => 'general',
            'status' => 'posted',
            'entries' => $entries,
        ]);
    }

    /**
     * Void/cancel a payable.
     */
    public function voidPayable(Payable $payable): Payable
    {
        if ($payable->paid_amount > 0) {
            throw new \Exception('Tidak dapat membatalkan hutang yang sudah ada pembayaran.');
        }

        $payable->update(['status' => 'void']);
        return $payable;
    }

    // ==================== PAYABLE PAYMENT ====================

    /**
     * Record a payment for a payable.
     */
    public function createPayablePayment(Payable $payable, array $data): PayablePayment
    {
        return DB::transaction(function () use ($payable, $data) {
            if ($payable->status === 'paid') {
                throw new \Exception('Hutang ini sudah lunas.');
            }
            if ($payable->status === 'void') {
                throw new \Exception('Hutang ini sudah dibatalkan.');
            }

            $amount = (int) $data['amount'];
            $remaining = $payable->remaining;

            if ($amount <= 0) {
                throw new \Exception('Jumlah pembayaran harus lebih dari 0.');
            }
            if ($amount > $remaining) {
                throw new \Exception("Jumlah pembayaran melebihi sisa hutang (Rp " . number_format($remaining, 0, ',', '.') . ").");
            }

            $unit = $payable->businessUnit;

            // Create journal for payment
            $journal = $this->createPayablePaymentJournal($payable, $data, $unit);

            $payment = PayablePayment::create([
                'payable_id' => $payable->id,
                'payment_date' => $data['payment_date'],
                'amount' => $amount,
                'payment_coa_id' => $data['payment_coa_id'],
                'reference' => $data['reference'] ?? null,
                'journal_master_id' => $journal->id,
                'notes' => $data['notes'] ?? null,
            ]);

            // Update payable
            $newPaid = $payable->paid_amount + $amount;
            $status = $newPaid >= $payable->amount_due ? 'paid' : 'partial';

            $payable->update([
                'paid_amount' => $newPaid,
                'status' => $status,
            ]);

            return $payment->fresh(['payable', 'paymentCoa', 'journalMaster']);
        });
    }

    /**
     * Create journal for payable payment:
     * Debit: Hutang Usaha
     * Credit: Kas/Bank
     */
    protected function createPayablePaymentJournal(Payable $payable, array $data, BusinessUnit $unit)
    {
        $period = Period::current()->open()->first();
        if (!$period) {
            throw new \Exception('Tidak ada periode aktif yang terbuka.');
        }

        $hutangUsahaCoa = $unit->getCoaByKey('hutang_usaha');
        if (!$hutangUsahaCoa) {
            throw new \Exception('Mapping akun Hutang Usaha belum diatur untuk unit usaha ini.');
        }

        $paymentCoa = COA::findOrFail($data['payment_coa_id']);

        return $this->journalService->createJournalEntry([
            'journal_date' => $data['payment_date'],
            'reference' => $data['reference'] ?? $payable->invoice_number,
            'description' => "Pembayaran Hutang - {$payable->vendor->name}",
            'id_period' => $period->id,
            'type' => 'general',
            'status' => 'posted',
            'entries' => [
                [
                    'coa_code' => $hutangUsahaCoa->code,
                    'description' => "Pelunasan Hutang - {$payable->vendor->name}",
                    'debit' => (int) $data['amount'],
                    'credit' => 0,
                ],
                [
                    'coa_code' => $paymentCoa->code,
                    'description' => "Pembayaran via {$paymentCoa->name}",
                    'debit' => 0,
                    'credit' => (int) $data['amount'],
                ],
            ],
        ]);
    }

    // ==================== RECEIVABLE (AR) ====================

    /**
     * Create a new receivable and its initial journal entry.
     */
    public function createReceivable(array $data): Receivable
    {
        return DB::transaction(function () use ($data) {
            $unit = BusinessUnit::findOrFail($data['business_unit_id']);

            $receivable = Receivable::create([
                'business_unit_id' => $data['business_unit_id'],
                'customer_id' => $data['customer_id'],
                'invoice_number' => $data['invoice_number'],
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'],
                'description' => $data['description'] ?? null,
                'credit_coa_id' => $data['credit_coa_id'] ?? null,
                'amount' => (int) $data['amount'],
                'paid_amount' => 0,
                'status' => 'unpaid',
                'notes' => $data['notes'] ?? null,
            ]);

            // Create journal entry
            $journal = $this->createReceivableJournal($receivable, $unit);
            $receivable->update(['journal_master_id' => $journal->id]);

            return $receivable->fresh(['customer', 'businessUnit', 'creditCoa', 'journalMaster']);
        });
    }

    /**
     * Create journal for a new receivable:
     * Debit: Piutang Usaha = amount
     * Credit: [chosen revenue account] = amount
     */
    protected function createReceivableJournal(Receivable $receivable, BusinessUnit $unit)
    {
        $period = Period::current()->open()->first();
        if (!$period) {
            throw new \Exception('Tidak ada periode aktif yang terbuka.');
        }

        $piutangUsahaCoa = $unit->getCoaByKey('piutang_usaha');
        if (!$piutangUsahaCoa) {
            throw new \Exception('Mapping akun Piutang Usaha belum diatur untuk unit usaha ini.');
        }

        $creditCoa = $receivable->credit_coa_id ? COA::find($receivable->credit_coa_id) : null;
        if (!$creditCoa) {
            throw new \Exception('Akun pendapatan (kredit) harus dipilih.');
        }

        return $this->journalService->createJournalEntry([
            'journal_date' => $receivable->invoice_date->format('Y-m-d'),
            'reference' => $receivable->invoice_number,
            'description' => "Pencatatan Piutang - {$receivable->customer->name}",
            'id_period' => $period->id,
            'type' => 'general',
            'status' => 'posted',
            'entries' => [
                [
                    'coa_code' => $piutangUsahaCoa->code,
                    'description' => "Piutang Usaha - {$receivable->customer->name}",
                    'debit' => $receivable->amount,
                    'credit' => 0,
                ],
                [
                    'coa_code' => $creditCoa->code,
                    'description' => "Pendapatan - {$receivable->customer->name}",
                    'debit' => 0,
                    'credit' => $receivable->amount,
                ],
            ],
        ]);
    }

    /**
     * Void/cancel a receivable.
     */
    public function voidReceivable(Receivable $receivable): Receivable
    {
        if ($receivable->paid_amount > 0) {
            throw new \Exception('Tidak dapat membatalkan piutang yang sudah ada pembayaran.');
        }

        $receivable->update(['status' => 'void']);
        return $receivable;
    }

    // ==================== RECEIVABLE PAYMENT ====================

    /**
     * Record a payment received for a receivable.
     */
    public function createReceivablePayment(Receivable $receivable, array $data): ReceivablePayment
    {
        return DB::transaction(function () use ($receivable, $data) {
            if ($receivable->status === 'paid') {
                throw new \Exception('Piutang ini sudah lunas.');
            }
            if ($receivable->status === 'void') {
                throw new \Exception('Piutang ini sudah dibatalkan.');
            }

            $amount = (int) $data['amount'];
            $remaining = $receivable->remaining;

            if ($amount <= 0) {
                throw new \Exception('Jumlah penerimaan harus lebih dari 0.');
            }
            if ($amount > $remaining) {
                throw new \Exception("Jumlah penerimaan melebihi sisa piutang (Rp " . number_format($remaining, 0, ',', '.') . ").");
            }

            $unit = $receivable->businessUnit;

            // Create journal for payment
            $journal = $this->createReceivablePaymentJournal($receivable, $data, $unit);

            $payment = ReceivablePayment::create([
                'receivable_id' => $receivable->id,
                'payment_date' => $data['payment_date'],
                'amount' => $amount,
                'payment_coa_id' => $data['payment_coa_id'],
                'reference' => $data['reference'] ?? null,
                'journal_master_id' => $journal->id,
                'notes' => $data['notes'] ?? null,
            ]);

            // Update receivable
            $newPaid = $receivable->paid_amount + $amount;
            $status = $newPaid >= $receivable->amount ? 'paid' : 'partial';

            $receivable->update([
                'paid_amount' => $newPaid,
                'status' => $status,
            ]);

            return $payment->fresh(['receivable', 'paymentCoa', 'journalMaster']);
        });
    }

    /**
     * Create journal for receivable payment:
     * Debit: Kas/Bank
     * Credit: Piutang Usaha
     */
    protected function createReceivablePaymentJournal(Receivable $receivable, array $data, BusinessUnit $unit)
    {
        $period = Period::current()->open()->first();
        if (!$period) {
            throw new \Exception('Tidak ada periode aktif yang terbuka.');
        }

        $piutangUsahaCoa = $unit->getCoaByKey('piutang_usaha');
        if (!$piutangUsahaCoa) {
            throw new \Exception('Mapping akun Piutang Usaha belum diatur untuk unit usaha ini.');
        }

        $paymentCoa = COA::findOrFail($data['payment_coa_id']);

        return $this->journalService->createJournalEntry([
            'journal_date' => $data['payment_date'],
            'reference' => $data['reference'] ?? $receivable->invoice_number,
            'description' => "Penerimaan Piutang - {$receivable->customer->name}",
            'id_period' => $period->id,
            'type' => 'general',
            'status' => 'posted',
            'entries' => [
                [
                    'coa_code' => $paymentCoa->code,
                    'description' => "Penerimaan via {$paymentCoa->name}",
                    'debit' => (int) $data['amount'],
                    'credit' => 0,
                ],
                [
                    'coa_code' => $piutangUsahaCoa->code,
                    'description' => "Pelunasan Piutang - {$receivable->customer->name}",
                    'debit' => 0,
                    'credit' => (int) $data['amount'],
                ],
            ],
        ]);
    }

    // ==================== REPORTS / QUERIES ====================

    /**
     * Get aging data for payables.
     */
    public static function getPayableAging(?int $businessUnitId = null): array
    {
        $query = Payable::with('vendor')->outstanding();
        if ($businessUnitId) {
            $query->byBusinessUnit($businessUnitId);
        }

        $payables = $query->get();
        $today = now()->startOfDay();

        $aging = [
            'current' => ['items' => collect(), 'total' => 0],
            '1_30' => ['items' => collect(), 'total' => 0],
            '31_60' => ['items' => collect(), 'total' => 0],
            '61_90' => ['items' => collect(), 'total' => 0],
            'over_90' => ['items' => collect(), 'total' => 0],
        ];

        foreach ($payables as $p) {
            $remaining = $p->remaining;
            $days = $today->diffInDays($p->due_date, false); // negative = overdue

            if ($days >= 0) {
                $bucket = 'current';
            } elseif ($days >= -30) {
                $bucket = '1_30';
            } elseif ($days >= -60) {
                $bucket = '31_60';
            } elseif ($days >= -90) {
                $bucket = '61_90';
            } else {
                $bucket = 'over_90';
            }

            $aging[$bucket]['items']->push($p);
            $aging[$bucket]['total'] += $remaining;
        }

        return $aging;
    }

    /**
     * Get aging data for receivables.
     */
    public static function getReceivableAging(?int $businessUnitId = null): array
    {
        $query = Receivable::with('customer')->outstanding();
        if ($businessUnitId) {
            $query->byBusinessUnit($businessUnitId);
        }

        $receivables = $query->get();
        $today = now()->startOfDay();

        $aging = [
            'current' => ['items' => collect(), 'total' => 0],
            '1_30' => ['items' => collect(), 'total' => 0],
            '31_60' => ['items' => collect(), 'total' => 0],
            '61_90' => ['items' => collect(), 'total' => 0],
            'over_90' => ['items' => collect(), 'total' => 0],
        ];

        foreach ($receivables as $r) {
            $remaining = $r->remaining;
            $days = $today->diffInDays($r->due_date, false);

            if ($days >= 0) {
                $bucket = 'current';
            } elseif ($days >= -30) {
                $bucket = '1_30';
            } elseif ($days >= -60) {
                $bucket = '31_60';
            } elseif ($days >= -90) {
                $bucket = '61_90';
            } else {
                $bucket = 'over_90';
            }

            $aging[$bucket]['items']->push($r);
            $aging[$bucket]['total'] += $remaining;
        }

        return $aging;
    }
}
