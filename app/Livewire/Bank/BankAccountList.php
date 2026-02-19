<?php

namespace App\Livewire\Bank;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\CashAccount;
use App\Services\BusinessUnitService;
use Livewire\Component;

class BankAccountList extends Component
{
    public $search = '';
    public $filterUnit = '';
    public $filterBank = '';
    public $filterStatus = '';
    public $sortField = 'account_number';
    public $sortDirection = 'asc';

    protected $listeners = ['refreshBankAccountList' => '$refresh'];

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function getBankAccountsProperty()
    {
        $query = BankAccount::with(['businessUnit', 'bank'])
            ->withCount(['sourceTransfers', 'destinationTransfers']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('account_number', 'like', "%{$this->search}%")
                  ->orWhere('account_name', 'like', "%{$this->search}%");
            });
        }

        BusinessUnitService::applyBusinessUnitFilter($query, $this->filterUnit);

        if ($this->filterBank !== '') {
            $query->byBank($this->filterBank);
        }

        if ($this->filterStatus !== '') {
            $query->where('is_active', (bool) $this->filterStatus);
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->get();
    }

    public function getCashAccountsProperty()
    {
        $query = CashAccount::with('businessUnit');

        BusinessUnitService::applyBusinessUnitFilter($query, $this->filterUnit);

        return $query->orderBy('name')->get();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function getAvailableBanksProperty()
    {
        return Bank::active()->orderBy('name')->get();
    }

    public function toggleStatus($id)
    {
        $account = BankAccount::findOrFail($id);
        $account->is_active = !$account->is_active;
        $account->save();

        $status = $account->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->dispatch('alert', type: 'success', message: "Rekening '{$account->account_name}' berhasil {$status}.");
    }

    public function deleteAccount($id)
    {
        $account = BankAccount::findOrFail($id);

        if ($account->sourceTransfers()->count() > 0 || $account->destinationTransfers()->count() > 0) {
            $this->dispatch('alert', type: 'error', message: "Rekening '{$account->account_name}' tidak dapat dihapus karena memiliki data transfer.");
            return;
        }

        $account->delete();
        $this->dispatch('alert', type: 'success', message: "Rekening '{$account->account_name}' berhasil dihapus.");
    }

    public function render()
    {
        return view('livewire.bank.bank-account-list', [
            'bankAccounts' => $this->bankAccounts,
            'cashAccounts' => $this->cashAccounts,
            'units' => $this->units,
            'availableBanks' => $this->availableBanks,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
