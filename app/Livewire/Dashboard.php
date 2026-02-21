<?php

namespace App\Livewire;

use App\Services\BusinessUnitService;
use App\Services\DashboardService;
use Carbon\Carbon;
use Livewire\Component;

class Dashboard extends Component
{
    public string $startDate = '';
    public string $endDate = '';
    public string $filterUnit = '';
    public array $data = [];

    public function mount()
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->loadData();
    }

    public function updatedStartDate()
    {
        $this->loadData();
    }

    public function updatedEndDate()
    {
        $this->loadData();
    }

    public function updatedFilterUnit()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $buId = $this->filterUnit
            ? (int) $this->filterUnit
            : BusinessUnitService::getUserBusinessUnitId();

        $this->data = DashboardService::getData($buId, $this->startDate, $this->endDate);

        $this->dispatch('dashboard-updated', data: $this->data);
    }

    public function getIsSuperAdminProperty(): bool
    {
        return BusinessUnitService::isSuperAdmin();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
