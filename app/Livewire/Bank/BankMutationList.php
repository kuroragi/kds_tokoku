<?php

namespace App\Livewire\Bank;

use App\Models\BankAccount;
use App\Models\BankMutation;
use App\Services\BankReconciliationService;
use App\Services\BusinessUnitService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class BankMutationList extends Component
{
    use WithPagination, WithFileUploads;

    // Filters
    public $search = '';
    public $filterUnit = '';
    public $filterAccount = '';
    public $filterStatus = '';
    public $filterBatch = '';
    public $sortField = 'transaction_date';
    public $sortDirection = 'desc';

    // Import modal
    public $showImportModal = false;
    public $importFile = null;
    public $import_bank_account_id = '';
    public $import_preset = 'bca';
    public $import_date_format = 'd/m/Y';

    // Custom column mapping
    public $col_date = 'tanggal';
    public $col_description = 'keterangan';
    public $col_debit = 'mutasi_debet';
    public $col_credit = 'mutasi_kredit';
    public $col_balance = 'saldo';
    public $col_reference = '';

    // Import result
    public $importResult = null;

    protected $listeners = ['refreshBankMutationList' => '$refresh'];

    public function updatedImportPreset($value)
    {
        $presets = BankReconciliationService::getColumnPresets();
        if (isset($presets[$value]) && $value !== 'custom') {
            $preset = $presets[$value];
            $this->col_date = $preset['date'];
            $this->col_description = $preset['description'];
            $this->col_debit = $preset['debit'];
            $this->col_credit = $preset['credit'];
            $this->col_balance = $preset['balance'];
            $this->col_reference = $preset['reference'] ?? '';
            $this->import_date_format = $preset['date_format'];
        }
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function openImport()
    {
        $this->resetImportForm();
        $this->showImportModal = true;
        $this->import_bank_account_id = '';
        $this->updatedImportPreset('bca');
    }

    public function closeImport()
    {
        $this->showImportModal = false;
        $this->resetImportForm();
    }

    private function resetImportForm()
    {
        $this->importFile = null;
        $this->import_bank_account_id = '';
        $this->import_preset = 'bca';
        $this->import_date_format = 'd/m/Y';
        $this->importResult = null;
        $this->resetValidation();
    }

    public function importMutations()
    {
        $this->validate([
            'importFile' => 'required|file|mimes:csv,xlsx,xls|max:5120',
            'import_bank_account_id' => 'required|exists:bank_accounts,id',
        ], [
            'importFile.required' => 'File wajib dipilih.',
            'importFile.mimes' => 'Format file harus CSV, XLSX, atau XLS.',
            'importFile.max' => 'Ukuran file maksimal 5MB.',
            'import_bank_account_id.required' => 'Rekening bank wajib dipilih.',
        ]);

        $bankAccount = BankAccount::findOrFail($this->import_bank_account_id);
        $businessUnitId = $bankAccount->business_unit_id;

        $columnMapping = [
            'date' => $this->col_date,
            'description' => $this->col_description,
            'debit' => $this->col_debit,
            'credit' => $this->col_credit,
            'balance' => $this->col_balance,
            'reference' => $this->col_reference,
        ];

        try {
            $service = new BankReconciliationService();
            $result = $service->importMutations(
                $this->importFile->getRealPath(),
                $bankAccount->id,
                $businessUnitId,
                $columnMapping,
                $this->import_date_format
            );

            $this->importResult = $result;
            $this->dispatch('alert', type: 'success', message: "Berhasil import {$result['imported']} mutasi ({$result['skipped']} dilewati).");
            $this->importFile = null;
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    public function deleteBatch($batch)
    {
        $service = new BankReconciliationService();
        $deleted = $service->deleteBatch($batch);
        $this->dispatch('alert', type: 'success', message: "{$deleted} mutasi berhasil dihapus.");
    }

    // ─── Computed Properties ───

    public function getMutationsProperty()
    {
        $query = BankMutation::with(['bankAccount.bank']);

        BusinessUnitService::applyBusinessUnitFilter($query, $this->filterUnit);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('description', 'like', "%{$this->search}%")
                  ->orWhere('reference_no', 'like', "%{$this->search}%");
            });
        }

        if ($this->filterAccount) {
            $query->where('bank_account_id', $this->filterAccount);
        }
        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }
        if ($this->filterBatch) {
            $query->where('import_batch', $this->filterBatch);
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->paginate(20);
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

    public function getBatchesProperty()
    {
        return BankMutation::select('import_batch')
            ->distinct()
            ->whereNotNull('import_batch')
            ->orderBy('import_batch', 'desc')
            ->pluck('import_batch');
    }

    public function getPresetsProperty()
    {
        return BankReconciliationService::getColumnPresets();
    }

    public function render()
    {
        return view('livewire.bank.bank-mutation-list', [
            'mutations' => $this->mutations,
        ]);
    }
}
