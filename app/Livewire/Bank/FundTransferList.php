<?php

namespace App\Livewire\Bank;

use App\Models\FundTransfer;
use App\Services\BankService;
use App\Services\BusinessUnitService;
use Livewire\Component;

class FundTransferList extends Component
{
    public $search = '';
    public $filterUnit = '';
    public $filterSourceType = '';
    public $filterDestType = '';
    public $sortField = 'transfer_date';
    public $sortDirection = 'desc';

    protected $listeners = ['refreshFundTransferList' => '$refresh'];

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function getTransfersProperty()
    {
        $query = FundTransfer::with(['businessUnit', 'sourceBankAccount.bank', 'destinationBankAccount.bank']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('reference_no', 'like', "%{$this->search}%")
                  ->orWhere('notes', 'like', "%{$this->search}%");
            });
        }

        BusinessUnitService::applyBusinessUnitFilter($query, $this->filterUnit);

        if ($this->filterSourceType !== '') {
            $query->where('source_type', $this->filterSourceType);
        }

        if ($this->filterDestType !== '') {
            $query->where('destination_type', $this->filterDestType);
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->get();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function deleteTransfer($id)
    {
        $transfer = FundTransfer::findOrFail($id);
        $service = new BankService();
        $service->deleteTransfer($transfer);

        $this->dispatch('alert', type: 'success', message: 'Transfer berhasil dihapus dan saldo dikembalikan.');
        $this->dispatch('refreshBankAccountList');
    }

    public function render()
    {
        return view('livewire.bank.fund-transfer-list', [
            'transfers' => $this->transfers,
            'units' => $this->units,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
