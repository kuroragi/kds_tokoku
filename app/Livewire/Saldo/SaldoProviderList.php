<?php

namespace App\Livewire\Saldo;

use App\Models\SaldoProvider;
use App\Services\BusinessUnitService;
use Livewire\Component;

class SaldoProviderList extends Component
{
    public $search = '';
    public $filterUnit = '';
    public $filterType = '';
    public $filterStatus = '';
    public $sortField = 'code';
    public $sortDirection = 'asc';

    protected $listeners = ['refreshSaldoProviderList' => '$refresh'];

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function getProvidersProperty()
    {
        $query = SaldoProvider::with('businessUnit')
            ->withCount(['topups', 'transactions']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', "%{$this->search}%")
                  ->orWhere('name', 'like', "%{$this->search}%");
            });
        }

        BusinessUnitService::applyBusinessUnitFilter($query, $this->filterUnit);

        if ($this->filterType !== '') {
            $query->where('type', $this->filterType);
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

    public function deleteProvider($id)
    {
        $provider = SaldoProvider::findOrFail($id);

        if ($provider->topups()->count() > 0 || $provider->transactions()->count() > 0) {
            $this->dispatch('alert', type: 'error', message: "Penyedia '{$provider->name}' tidak dapat dihapus karena memiliki data transaksi.");
            return;
        }

        if ($provider->products()->count() > 0) {
            $this->dispatch('alert', type: 'error', message: "Penyedia '{$provider->name}' tidak dapat dihapus karena memiliki produk terkait.");
            return;
        }

        $provider->delete();
        $this->dispatch('alert', type: 'success', message: "Penyedia '{$provider->name}' berhasil dihapus.");
    }

    public function toggleStatus($id)
    {
        $provider = SaldoProvider::findOrFail($id);
        $provider->is_active = !$provider->is_active;
        $provider->save();

        $status = $provider->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->dispatch('alert', type: 'success', message: "Penyedia '{$provider->name}' berhasil {$status}.");
    }

    public function render()
    {
        return view('livewire.saldo.saldo-provider-list', [
            'providers' => $this->providers,
            'units' => $this->units,
            'types' => SaldoProvider::getTypes(),
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
