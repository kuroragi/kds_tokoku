<?php

namespace App\Livewire\Saldo;

use App\Models\SaldoProduct;
use App\Models\SaldoProvider;
use App\Services\BusinessUnitService;
use Livewire\Component;

class SaldoProductList extends Component
{
    public $search = '';
    public $filterUnit = '';
    public $filterProvider = '';
    public $filterStatus = '';
    public $sortField = 'code';
    public $sortDirection = 'asc';

    protected $listeners = ['refreshSaldoProductList' => '$refresh'];

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function getProductsProperty()
    {
        $query = SaldoProduct::with(['businessUnit', 'saldoProvider']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', "%{$this->search}%")
                  ->orWhere('name', 'like', "%{$this->search}%");
            });
        }

        BusinessUnitService::applyBusinessUnitFilter($query, $this->filterUnit);

        if ($this->filterProvider !== '') {
            $query->where('saldo_provider_id', $this->filterProvider);
        }

        if ($this->filterStatus !== '') {
            $query->where('is_active', (bool) $this->filterStatus);
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

    public function deleteProduct($id)
    {
        $product = SaldoProduct::findOrFail($id);

        if ($product->transactions()->count() > 0) {
            $this->dispatch('alert', type: 'error', message: "Produk '{$product->name}' tidak dapat dihapus karena memiliki transaksi.");
            return;
        }

        $product->delete();
        $this->dispatch('alert', type: 'success', message: "Produk saldo '{$product->name}' berhasil dihapus.");
    }

    public function toggleStatus($id)
    {
        $product = SaldoProduct::findOrFail($id);
        $product->is_active = !$product->is_active;
        $product->save();

        $status = $product->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->dispatch('alert', type: 'success', message: "Produk '{$product->name}' berhasil {$status}.");
    }

    public function render()
    {
        return view('livewire.saldo.saldo-product-list', [
            'products' => $this->products,
            'units' => $this->units,
            'availableProviders' => $this->availableProviders,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
