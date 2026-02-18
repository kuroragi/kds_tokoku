<?php

namespace App\Livewire\Saldo;

use App\Models\SaldoProduct;
use App\Models\SaldoProvider;
use App\Models\SaldoTransaction;
use App\Services\BusinessUnitService;
use App\Services\SaldoService;
use Livewire\Component;

class SaldoTransactionList extends Component
{
    public $search = '';
    public $filterUnit = '';
    public $filterProvider = '';
    public $filterProduct = '';
    public $sortField = 'transaction_date';
    public $sortDirection = 'desc';

    protected $listeners = ['refreshSaldoTransactionList' => '$refresh'];

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function getTransactionsProperty()
    {
        $query = SaldoTransaction::with(['businessUnit', 'saldoProvider', 'saldoProduct']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('customer_name', 'like', "%{$this->search}%")
                  ->orWhere('customer_phone', 'like', "%{$this->search}%")
                  ->orWhere('notes', 'like', "%{$this->search}%");
            });
        }

        BusinessUnitService::applyBusinessUnitFilter($query, $this->filterUnit);

        if ($this->filterProvider !== '') {
            $query->where('saldo_provider_id', $this->filterProvider);
        }

        if ($this->filterProduct !== '') {
            $query->where('saldo_product_id', $this->filterProduct);
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->get();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function getAvailableProvidersProperty()
    {
        $query = SaldoProvider::active();
        BusinessUnitService::applyBusinessUnitFilter($query, $this->filterUnit);
        return $query->orderBy('name')->get();
    }

    public function getAvailableProductsProperty()
    {
        $query = SaldoProduct::active();
        BusinessUnitService::applyBusinessUnitFilter($query, $this->filterUnit);
        return $query->orderBy('name')->get();
    }

    public function deleteTransaction($id)
    {
        $transaction = SaldoTransaction::findOrFail($id);
        $service = new SaldoService();
        $service->deleteTransaction($transaction);

        $this->dispatch('alert', type: 'success', message: 'Transaksi berhasil dihapus dan saldo dikembalikan.');
    }

    public function render()
    {
        return view('livewire.saldo.saldo-transaction-list', [
            'transactions' => $this->transactions,
            'units' => $this->units,
            'availableProviders' => $this->availableProviders,
            'availableProducts' => $this->availableProducts,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
