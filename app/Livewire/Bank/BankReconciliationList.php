<?php

namespace App\Livewire\Bank;

use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use App\Models\FundTransfer;
use App\Models\JournalMaster;
use App\Services\BankReconciliationService;
use App\Services\BusinessUnitService;
use Livewire\Component;

class BankReconciliationList extends Component
{
    // Filters
    public $filterUnit = '';
    public $filterAccount = '';
    public $filterStatus = '';

    // Create modal
    public $showCreateModal = false;
    public $create_bank_account_id = '';
    public $create_start_date = '';
    public $create_end_date = '';
    public $create_bank_statement_balance = 0;
    public $create_notes = '';

    // Detail modal
    public $showDetailModal = false;
    public $detailRecon = null;
    public $detailItems = [];
    public $detailFilter = ''; // all, unmatched, matched, ignored

    // Manual match modal
    public $showMatchModal = false;
    public $matchItemId = null;
    public $matchItemInfo = null;
    public $matchType = 'journal';     // journal, fund_transfer
    public $matchSearchQuery = '';
    public $matchResults = [];
    public $selectedMatchId = null;

    protected $listeners = ['refreshBankReconciliationList' => '$refresh'];

    // ==================== CREATE RECONCILIATION ====================

    public function openCreate()
    {
        $this->resetCreateForm();
        $this->showCreateModal = true;
    }

    public function closeCreate()
    {
        $this->showCreateModal = false;
        $this->resetCreateForm();
    }

    private function resetCreateForm()
    {
        $this->create_bank_account_id = '';
        $this->create_start_date = '';
        $this->create_end_date = '';
        $this->create_bank_statement_balance = 0;
        $this->create_notes = '';
        $this->resetValidation();
    }

    public function createReconciliation()
    {
        $this->validate([
            'create_bank_account_id' => 'required|exists:bank_accounts,id',
            'create_start_date' => 'required|date',
            'create_end_date' => 'required|date|after_or_equal:create_start_date',
            'create_bank_statement_balance' => 'required|numeric',
        ], [
            'create_bank_account_id.required' => 'Rekening bank wajib dipilih.',
            'create_start_date.required' => 'Tanggal mulai wajib diisi.',
            'create_end_date.required' => 'Tanggal selesai wajib diisi.',
            'create_end_date.after_or_equal' => 'Tanggal selesai harus >= tanggal mulai.',
            'create_bank_statement_balance.required' => 'Saldo rekening koran wajib diisi.',
        ]);

        try {
            $bankAccount = BankAccount::findOrFail($this->create_bank_account_id);

            $service = new BankReconciliationService();
            $recon = $service->createReconciliation([
                'business_unit_id' => $bankAccount->business_unit_id,
                'bank_account_id' => $this->create_bank_account_id,
                'start_date' => $this->create_start_date,
                'end_date' => $this->create_end_date,
                'bank_statement_balance' => $this->create_bank_statement_balance,
                'notes' => $this->create_notes ?: null,
            ]);

            $this->dispatch('alert', type: 'success', message: "Rekonsiliasi berhasil dibuat. {$recon->matched_count} mutasi otomatis dicocokkan.");
            $this->closeCreate();
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    // ==================== DETAIL / MATCHING ====================

    public function openDetail($reconId)
    {
        $this->detailRecon = BankReconciliation::with(['bankAccount.bank', 'items.mutation', 'items.matchedJournal', 'items.matchedFundTransfer'])
            ->findOrFail($reconId);
        $this->detailFilter = '';
        $this->loadDetailItems();
        $this->showDetailModal = true;
    }

    public function closeDetail()
    {
        $this->showDetailModal = false;
        $this->detailRecon = null;
        $this->detailItems = [];
    }

    public function updatedDetailFilter()
    {
        $this->loadDetailItems();
    }

    private function loadDetailItems()
    {
        if (!$this->detailRecon) return;

        $query = BankReconciliationItem::with(['mutation', 'matchedJournal', 'matchedFundTransfer'])
            ->where('bank_reconciliation_id', $this->detailRecon->id);

        if ($this->detailFilter === 'unmatched') {
            $query->where('match_type', 'unmatched');
        } elseif ($this->detailFilter === 'matched') {
            $query->whereIn('match_type', ['auto_matched', 'manual_matched']);
        } elseif ($this->detailFilter === 'ignored') {
            $query->where('match_type', 'ignored');
        }

        $this->detailItems = $query->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'mutation_date' => $item->mutation?->transaction_date?->format('d/m/Y') ?? '-',
                'mutation_desc' => $item->mutation?->description ?? '-',
                'mutation_ref' => $item->mutation?->reference_no ?? '-',
                'mutation_debit' => (float) ($item->mutation?->debit ?? 0),
                'mutation_credit' => (float) ($item->mutation?->credit ?? 0),
                'match_type' => $item->match_type,
                'match_type_label' => $item->match_type_label,
                'matched_ref' => $item->matchedJournal?->journal_no
                    ?? ($item->matchedFundTransfer ? 'TRF-' . $item->matchedFundTransfer->id : '-'),
                'notes' => $item->notes ?? '',
            ];
        })->toArray();
    }

    // ==================== MANUAL MATCH ====================

    public function openMatchModal($itemId)
    {
        $item = BankReconciliationItem::with('mutation')->findOrFail($itemId);
        $this->matchItemId = $itemId;
        $this->matchItemInfo = [
            'date' => $item->mutation?->transaction_date?->format('d/m/Y'),
            'description' => $item->mutation?->description,
            'debit' => (float) ($item->mutation?->debit ?? 0),
            'credit' => (float) ($item->mutation?->credit ?? 0),
        ];
        $this->matchType = 'journal';
        $this->matchSearchQuery = '';
        $this->matchResults = [];
        $this->selectedMatchId = null;
        $this->showMatchModal = true;
    }

    public function closeMatchModal()
    {
        $this->showMatchModal = false;
        $this->matchItemId = null;
        $this->matchItemInfo = null;
        $this->matchResults = [];
    }

    public function searchMatch()
    {
        if (!$this->matchSearchQuery && !$this->matchItemInfo) {
            $this->matchResults = [];
            return;
        }

        if ($this->matchType === 'journal') {
            $query = JournalMaster::with('journals')
                ->where('business_unit_id', $this->detailRecon->business_unit_id);

            if ($this->matchSearchQuery) {
                $query->where(function ($q) {
                    $q->where('journal_no', 'like', "%{$this->matchSearchQuery}%")
                      ->orWhere('description', 'like', "%{$this->matchSearchQuery}%")
                      ->orWhere('reference', 'like', "%{$this->matchSearchQuery}%");
                });
            } else {
                // Auto-suggest by date/amount
                $amount = $this->matchItemInfo['debit'] > 0 ? $this->matchItemInfo['debit'] : $this->matchItemInfo['credit'];
                $item = BankReconciliationItem::find($this->matchItemId);
                if ($item?->mutation) {
                    $query->where('date', $item->mutation->transaction_date);
                }
            }

            $this->matchResults = $query->orderBy('date', 'desc')->limit(10)->get()
                ->map(fn($j) => [
                    'id' => $j->id,
                    'ref' => $j->journal_no,
                    'date' => $j->date?->format('d/m/Y'),
                    'desc' => $j->description,
                    'amount' => $j->journals->sum('debit'),
                ])->toArray();
        } else {
            $query = FundTransfer::with(['sourceBankAccount.bank', 'destinationBankAccount.bank'])
                ->where('business_unit_id', $this->detailRecon->business_unit_id);

            if ($this->matchSearchQuery) {
                $query->where(function ($q) {
                    $q->where('reference_no', 'like', "%{$this->matchSearchQuery}%")
                      ->orWhere('notes', 'like', "%{$this->matchSearchQuery}%");
                });
            } else {
                $item = BankReconciliationItem::find($this->matchItemId);
                if ($item?->mutation) {
                    $query->where('transfer_date', $item->mutation->transaction_date);
                }
            }

            $this->matchResults = $query->orderBy('transfer_date', 'desc')->limit(10)->get()
                ->map(fn($t) => [
                    'id' => $t->id,
                    'ref' => $t->reference_no ?? 'TRF-' . $t->id,
                    'date' => $t->transfer_date?->format('d/m/Y'),
                    'desc' => ($t->sourceBankAccount?->display_label ?? 'Kas') . ' → ' . ($t->destinationBankAccount?->display_label ?? 'Kas'),
                    'amount' => (float) $t->amount,
                ])->toArray();
        }
    }

    public function confirmMatch()
    {
        if (!$this->matchItemId || !$this->selectedMatchId) {
            $this->dispatch('alert', type: 'error', message: 'Pilih item yang akan dicocokkan.');
            return;
        }

        try {
            $service = new BankReconciliationService();
            $journalId = $this->matchType === 'journal' ? $this->selectedMatchId : null;
            $transferId = $this->matchType === 'fund_transfer' ? $this->selectedMatchId : null;

            $service->manualMatch($this->matchItemId, $journalId, $transferId);

            $this->dispatch('alert', type: 'success', message: 'Berhasil dicocokkan.');
            $this->closeMatchModal();

            // Reload detail
            $this->detailRecon = $this->detailRecon->fresh(['bankAccount.bank']);
            $this->detailRecon->recalculateCounts();
            $this->loadDetailItems();
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    public function unmatchItem($itemId)
    {
        try {
            $service = new BankReconciliationService();
            $service->unmatchItem($itemId);
            $this->dispatch('alert', type: 'success', message: 'Pencocokan dibatalkan.');
            $this->detailRecon = $this->detailRecon->fresh();
            $this->loadDetailItems();
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    public function ignoreItem($itemId)
    {
        try {
            $service = new BankReconciliationService();
            $service->ignoreItem($itemId);
            $this->dispatch('alert', type: 'success', message: 'Mutasi diabaikan.');
            $this->detailRecon = $this->detailRecon->fresh();
            $this->loadDetailItems();
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    // ==================== COMPLETE / DELETE ====================

    public function completeRecon($id)
    {
        try {
            $service = new BankReconciliationService();
            $service->completeReconciliation($id);
            $this->dispatch('alert', type: 'success', message: 'Rekonsiliasi berhasil diselesaikan.');
            if ($this->showDetailModal) {
                $this->detailRecon = $this->detailRecon?->fresh();
            }
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    public function reopenRecon($id)
    {
        try {
            $service = new BankReconciliationService();
            $service->reopenReconciliation($id);
            $this->dispatch('alert', type: 'success', message: 'Rekonsiliasi dibuka kembali.');
            if ($this->showDetailModal) {
                $this->detailRecon = $this->detailRecon?->fresh();
            }
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    public function deleteRecon($id)
    {
        try {
            $service = new BankReconciliationService();
            $service->deleteReconciliation($id);
            $this->dispatch('alert', type: 'success', message: 'Rekonsiliasi berhasil dihapus.');
            $this->closeDetail();
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    // ─── Computed Properties ───

    public function getReconciliationsProperty()
    {
        $query = BankReconciliation::with(['bankAccount.bank', 'businessUnit']);

        BusinessUnitService::applyBusinessUnitFilter($query, $this->filterUnit);

        if ($this->filterAccount) {
            $query->where('bank_account_id', $this->filterAccount);
        }
        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function getIsSuperAdminProperty(): bool
    {
        return BusinessUnitService::isSuperAdmin();
    }

    public function getBankAccountsProperty()
    {
        $query = BankAccount::active()->with('bank');
        if (!BusinessUnitService::isSuperAdmin()) {
            $unitId = BusinessUnitService::getUserBusinessUnitId();
            if ($unitId) $query->where('business_unit_id', $unitId);
        }
        return $query->orderBy('account_name')->get();
    }

    public function render()
    {
        return view('livewire.bank.bank-reconciliation-list');
    }
}
