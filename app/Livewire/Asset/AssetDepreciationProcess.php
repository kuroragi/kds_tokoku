<?php

namespace App\Livewire\Asset;

use App\Services\AssetService;
use App\Services\BusinessUnitService;
use App\Models\Period;
use Livewire\Component;

class AssetDepreciationProcess extends Component
{
    public bool $showModal = false;
    public $business_unit_id = '';
    public $period_id = '';
    public $preview = [];
    public $totalAmount = 0;

    protected $listeners = ['openDepreciationProcess'];

    public function openDepreciationProcess()
    {
        $this->reset(['preview', 'totalAmount', 'period_id']);
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['preview', 'totalAmount', 'period_id', 'business_unit_id']);
    }

    public function previewDepreciation()
    {
        if (!$this->business_unit_id || !$this->period_id) {
            $this->dispatch('alert', type: 'error', message: 'Pilih unit usaha dan periode terlebih dahulu.');
            return;
        }

        $service = app(AssetService::class);
        $this->preview = $service->previewDepreciation($this->business_unit_id, $this->period_id);
        $this->totalAmount = collect($this->preview)->sum('depreciation_amount');

        if (empty($this->preview)) {
            $this->dispatch('alert', type: 'info', message: 'Tidak ada aset yang perlu disusutkan untuk periode ini.');
        }
    }

    public function processDepreciation()
    {
        if (empty($this->preview)) {
            $this->dispatch('alert', type: 'error', message: 'Tidak ada data penyusutan untuk diproses.');
            return;
        }

        try {
            $service = app(AssetService::class);
            $results = $service->processDepreciation($this->business_unit_id, $this->period_id);

            $count = count($results);
            $total = number_format(collect($results)->sum('depreciation_amount'));
            $this->dispatch('alert', type: 'success', message: "Penyusutan berhasil diproses untuk {$count} aset (Total: Rp {$total}).");
            $this->dispatch('refreshAssetDepreciationList');
            $this->closeModal();
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: 'Gagal memproses penyusutan: ' . $e->getMessage());
        }
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function getPeriodsProperty()
    {
        return Period::active()->open()->orderBy('year', 'desc')->orderBy('month', 'desc')->get();
    }

    public function render()
    {
        return view('livewire.asset.asset-depreciation-process', [
            'units' => $this->units,
            'periods' => $this->periods,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
