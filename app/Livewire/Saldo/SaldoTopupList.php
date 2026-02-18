<?php

namespace App\Livewire\Saldo;

use App\Models\SaldoProvider;
use App\Models\SaldoTopup;
use App\Services\BusinessUnitService;
use App\Services\SaldoService;
use Livewire\Component;

class SaldoTopupList extends Component
{
    public $search = '';
    public $filterUnit = '';
    public $filterProvider = '';
    public $filterMethod = '';
    public $sortField = 'topup_date';
    public $sortDirection = 'desc';

    protected $listeners = ['refreshSaldoTopupList' => '$refresh'];

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function getTopupsProperty()
    {
        $query = SaldoTopup::with(['businessUnit', 'saldoProvider']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('reference_no', 'like', "%{$this->search}%")
                  ->orWhere('notes', 'like', "%{$this->search}%")
                  ->orWhereHas('saldoProvider', fn($pq) => $pq->where('name', 'like', "%{$this->search}%"));
            });
        }

        BusinessUnitService::applyBusinessUnitFilter($query, $this->filterUnit);

        if ($this->filterProvider !== '') {
            $query->where('saldo_provider_id', $this->filterProvider);
        }

        if ($this->filterMethod !== '') {
            $query->where('method', $this->filterMethod);
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

    public function deleteTopup($id)
    {
        $topup = SaldoTopup::findOrFail($id);
        $service = new SaldoService();
        $service->deleteTopup($topup);

        $this->dispatch('alert', type: 'success', message: 'Top up berhasil dihapus dan saldo dikembalikan.');
    }

    public function render()
    {
        return view('livewire.saldo.saldo-topup-list', [
            'topups' => $this->topups,
            'units' => $this->units,
            'availableProviders' => $this->availableProviders,
            'methods' => SaldoTopup::getMethods(),
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
