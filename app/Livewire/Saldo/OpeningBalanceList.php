<?php

namespace App\Livewire\Saldo;

use App\Models\OpeningBalance;
use App\Services\BusinessUnitService;
use App\Services\OpeningBalanceService;
use Livewire\Component;

class OpeningBalanceList extends Component
{
    public $search = '';
    public $filterUnit = '';
    public $filterStatus = '';

    protected $listeners = ['refreshOpeningBalanceList' => '$refresh'];

    public function getOpeningBalancesProperty()
    {
        $query = OpeningBalance::with('businessUnit', 'period', 'journalMaster')
            ->withCount('entries');

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('businessUnit', fn($bq) => $bq->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%"))
                  ->orWhereHas('period', fn($pq) => $pq->where('name', 'like', "%{$this->search}%"));
            });
        }

        BusinessUnitService::applyBusinessUnitFilter($query, $this->filterUnit);

        if ($this->filterStatus !== '') {
            $query->where('status', $this->filterStatus);
        }

        return $query->orderBy('balance_date', 'desc')->get();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function getIsSuperAdminProperty(): bool
    {
        return BusinessUnitService::isSuperAdmin();
    }

    public function postBalance($id)
    {
        try {
            $openingBalance = OpeningBalance::findOrFail($id);
            $service = new OpeningBalanceService();
            $service->postOpeningBalance($openingBalance);
            $this->dispatch('alert', type: 'success', message: 'Saldo awal berhasil diposting ke jurnal.');
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    public function unpostBalance($id)
    {
        try {
            $openingBalance = OpeningBalance::findOrFail($id);
            $service = new OpeningBalanceService();
            $service->unpostOpeningBalance($openingBalance);
            $this->dispatch('alert', type: 'success', message: 'Jurnal saldo awal berhasil dibatalkan (unpost).');
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    public function deleteBalance($id)
    {
        try {
            $openingBalance = OpeningBalance::findOrFail($id);
            $service = new OpeningBalanceService();
            $service->deleteOpeningBalance($openingBalance);
            $this->dispatch('alert', type: 'success', message: 'Saldo awal berhasil dihapus.');
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    public function editBalance($id)
    {
        $this->dispatch('editOpeningBalance', id: $id);
    }

    public function render()
    {
        return view('livewire.saldo.opening-balance-list');
    }
}
