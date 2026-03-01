<?php

namespace App\Services;

use App\Models\JournalMaster;
use App\Models\Journal;
use App\Models\COA;
use App\Models\Period;
use Illuminate\Support\Facades\DB;

class JournalService
{
    /**
     * Create automatic journal entry
     * 
     * @param array $data [
     *   'journal_date' => '2026-01-17',
     *   'reference' => 'INV-001',
     *   'description' => 'Sales Invoice',
     *   'id_period' => 1,
     *   'entries' => [
     *     ['coa_code' => '1101', 'description' => 'Cash receipt', 'debit' => 1000000, 'credit' => 0],
     *     ['coa_code' => '4101', 'description' => 'Sales revenue', 'debit' => 0, 'credit' => 1000000]
     *   ]
     * ]
     * 
     * @return JournalMaster
     * @throws \Exception
     */
    public function createJournalEntry(array $data)
    {
        DB::beginTransaction();
        
        try {
            // Validate required fields
            $this->validateJournalData($data);
            
            // Calculate totals
            $totalDebit = collect($data['entries'])->sum('debit');
            $totalCredit = collect($data['entries'])->sum('credit');
            
            // Check if balanced
            if ($totalDebit != $totalCredit) {
                throw new \Exception('Journal entry is not balanced. Total debit must equal total credit.');
            }
            
            // Generate journal number
            $type = $data['type'] ?? 'general';
            $journalNo = $this->generateJournalNumber($data['journal_date'], $type);
            
            // Create journal master
            $journalMaster = JournalMaster::create([
                'business_unit_id' => $data['business_unit_id'] ?? \App\Services\BusinessUnitService::getUserBusinessUnitId(),
                'type' => $type,
                'journal_no' => $journalNo,
                'journal_date' => $data['journal_date'],
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'] ?? null,
                'id_period' => $data['id_period'],
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'status' => $data['status'] ?? 'draft',
                'posted_at' => ($data['status'] ?? 'draft') === 'posted' ? now() : null,
            ]);
            
            // Create journal details
            $businessUnitId = $journalMaster->business_unit_id;
            foreach ($data['entries'] as $index => $entry) {
                $coa = COA::where('code', $entry['coa_code'])
                    ->where('business_unit_id', $businessUnitId)
                    ->first();
                
                if (!$coa) {
                    throw new \Exception("COA dengan kode '{$entry['coa_code']}' tidak ditemukan untuk unit usaha ini.");
                }
                
                Journal::create([
                    'id_journal_master' => $journalMaster->id,
                    'id_coa' => $coa->id,
                    'description' => $entry['description'] ?? null,
                    'debit' => $entry['debit'] ?? 0,
                    'credit' => $entry['credit'] ?? 0,
                    'sequence' => $index + 1,
                ]);
            }
            
            DB::commit();
            
            return $journalMaster->load(['journals.coa', 'period']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Create sales transaction journal
     * 
     * @param array $data
     * @return JournalMaster
     */
    public function createSalesJournal(array $data)
    {
        $journalData = [
            'journal_date' => $data['date'],
            'reference' => $data['invoice_no'] ?? null,
            'description' => $data['description'] ?? 'Sales Transaction',
            'id_period' => $data['id_period'],
            'status' => $data['auto_post'] ?? false ? 'posted' : 'draft',
            'entries' => [
                [
                    'coa_code' => $data['receivable_account'] ?? '1201', // Default: Piutang Dagang
                    'description' => 'Sales - ' . ($data['customer_name'] ?? 'Customer'),
                    'debit' => $data['amount'],
                    'credit' => 0
                ],
                [
                    'coa_code' => $data['sales_account'] ?? '4101', // Default: Pendapatan Penjualan
                    'description' => 'Sales Revenue',
                    'debit' => 0,
                    'credit' => $data['amount']
                ]
            ]
        ];
        
        return $this->createJournalEntry($journalData);
    }
    
    /**
     * Create purchase transaction journal
     * 
     * @param array $data
     * @return JournalMaster
     */
    public function createPurchaseJournal(array $data)
    {
        $journalData = [
            'journal_date' => $data['date'],
            'reference' => $data['purchase_no'] ?? null,
            'description' => $data['description'] ?? 'Purchase Transaction',
            'id_period' => $data['id_period'],
            'status' => $data['auto_post'] ?? false ? 'posted' : 'draft',
            'entries' => [
                [
                    'coa_code' => $data['inventory_account'] ?? '1301', // Default: Persediaan Barang
                    'description' => 'Purchase - ' . ($data['supplier_name'] ?? 'Supplier'),
                    'debit' => $data['amount'],
                    'credit' => 0
                ],
                [
                    'coa_code' => $data['payable_account'] ?? '2101', // Default: Hutang Dagang
                    'description' => 'Purchase Payable',
                    'debit' => 0,
                    'credit' => $data['amount']
                ]
            ]
        ];
        
        return $this->createJournalEntry($journalData);
    }
    
    /**
     * Create payment journal
     * 
     * @param array $data
     * @return JournalMaster
     */
    public function createPaymentJournal(array $data)
    {
        $journalData = [
            'journal_date' => $data['date'],
            'reference' => $data['payment_no'] ?? null,
            'description' => $data['description'] ?? 'Payment Transaction',
            'id_period' => $data['id_period'],
            'status' => $data['auto_post'] ?? false ? 'posted' : 'draft',
            'entries' => [
                [
                    'coa_code' => $data['expense_account'] ?? '2101', // Account being paid (e.g., Hutang Dagang)
                    'description' => 'Payment - ' . ($data['payee_name'] ?? 'Payee'),
                    'debit' => $data['amount'],
                    'credit' => 0
                ],
                [
                    'coa_code' => $data['cash_account'] ?? '1101', // Default: Kas di Tangan
                    'description' => 'Cash Payment',
                    'debit' => 0,
                    'credit' => $data['amount']
                ]
            ]
        ];
        
        return $this->createJournalEntry($journalData);
    }
    
    /**
     * Post a journal entry (change status from draft to posted)
     * 
     * @param int $journalId
     * @return JournalMaster
     * @throws \Exception
     */
    public function postJournal($journalId)
    {
        DB::beginTransaction();
        
        try {
            $journal = JournalMaster::findOrFail($journalId);
            
            if ($journal->status === 'posted') {
                throw new \Exception('Journal is already posted.');
            }
            
            if (!$journal->is_balanced) {
                throw new \Exception('Cannot post unbalanced journal entry.');
            }
            
            $journal->update([
                'status' => 'posted',
                'posted_at' => now()
            ]);
            
            DB::commit();
            
            return $journal;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Validate journal data
     * 
     * @param array $data
     * @throws \Exception
     */
    private function validateJournalData(array $data)
    {
        if (empty($data['journal_date'])) {
            throw new \Exception('Journal date is required.');
        }
        
        if (empty($data['id_period'])) {
            throw new \Exception('Period is required.');
        }
        
        if (empty($data['entries']) || !is_array($data['entries']) || count($data['entries']) < 2) {
            throw new \Exception('At least 2 journal entries are required.');
        }
        
        // Validate period exists
        $period = Period::find($data['id_period']);
        if (!$period) {
            throw new \Exception('Period not found.');
        }

        // Validate period is not closed (except for system-generated closing/tax/opening journals)
        $type = $data['type'] ?? 'general';
        if ($period->is_closed && !in_array($type, ['closing', 'tax', 'opening'])) {
            throw new \Exception("Periode '{$period->period_name}' sudah ditutup. Tidak dapat membuat jurnal pada periode ini.");
        }
        
        // Validate each entry
        foreach ($data['entries'] as $entry) {
            if (empty($entry['coa_code'])) {
                throw new \Exception('COA code is required for each entry.');
            }
            
            if (!isset($entry['debit']) && !isset($entry['credit'])) {
                throw new \Exception('Either debit or credit amount is required for each entry.');
            }
            
            if (($entry['debit'] ?? 0) < 0 || ($entry['credit'] ?? 0) < 0) {
                throw new \Exception('Debit and credit amounts cannot be negative.');
            }
            
            if (($entry['debit'] ?? 0) > 0 && ($entry['credit'] ?? 0) > 0) {
                throw new \Exception('An entry cannot have both debit and credit amounts.');
            }
        }
    }
    
    /**
     * Generate journal number
     * 
     * @param string $date
     * @param string $type 'general' or 'adjustment'
     * @return string
     */
    public function generateJournalNumber($date, $type = 'general')
    {
        $date = \Carbon\Carbon::parse($date);
        $prefixMap = [
            'general' => 'JRN',
            'adjustment' => 'AJE',
            'tax' => 'TAX',
            'closing' => 'CLO',
            'opening' => 'OPN',
        ];
        $prefix = $prefixMap[$type] ?? 'JRN';
        $year = $date->format('Y');
        $month = $date->format('m');
        
        $lastJournal = JournalMaster::where('journal_no', 'like', "{$prefix}/{$year}/{$month}/%")
            ->orderBy('journal_no', 'desc')
            ->first();
        
        if ($lastJournal) {
            $lastNumber = (int) substr($lastJournal->journal_no, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return "{$prefix}/{$year}/{$month}/" . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}