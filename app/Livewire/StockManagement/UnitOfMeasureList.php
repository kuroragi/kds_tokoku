<?php

namespace App\Livewire\StockManagement;

use App\Models\BusinessUnit;
use App\Models\UnitOfMeasure;
use Livewire\Component;

class UnitOfMeasureList extends Component
{
    public $search = '';
    public $filterUnit = '';
    public $filterStatus = '';
    public $sortField = 'code';
    public $sortDirection = 'asc';

    protected $listeners = ['refreshUnitOfMeasureList' => '$refresh'];

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function getMeasuresProperty()
    {
        $query = UnitOfMeasure::with('businessUnit')
            ->withCount('stocks')
            ->whereNotNull('business_unit_id'); // Only show business-unit-level units

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', "%{$this->search}%")
                  ->orWhere('name', 'like', "%{$this->search}%")
                  ->orWhere('symbol', 'like', "%{$this->search}%");
            });
        }

        if ($this->filterUnit) {
            $query->where('business_unit_id', $this->filterUnit);
        }

        if ($this->filterStatus !== '') {
            $query->where('is_active', $this->filterStatus);
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->get();
    }

    public function getUnitsProperty()
    {
        return BusinessUnit::active()->orderBy('name')->get();
    }

    /**
     * Duplicate system defaults for the selected business unit
     */
    public function duplicateDefaults($businessUnitId)
    {
        $created = UnitOfMeasure::duplicateDefaultsForBusinessUnit($businessUnitId);

        if ($created->count() > 0) {
            $this->dispatch('alert', type: 'success', message: "{$created->count()} satuan bawaan berhasil ditambahkan.");
        } else {
            $this->dispatch('alert', type: 'info', message: 'Semua satuan bawaan sudah ada untuk unit usaha ini.');
        }
    }

    public function deleteMeasure($id)
    {
        $measure = UnitOfMeasure::findOrFail($id);

        if ($measure->stocks()->count() > 0) {
            $this->dispatch('alert', type: 'error', message: "Tidak dapat menghapus satuan '{$measure->name}' karena masih digunakan oleh stok.");
            return;
        }

        $name = $measure->name;
        $measure->delete();

        $this->dispatch('alert', type: 'success', message: "Satuan '{$name}' berhasil dihapus.");
    }

    public function toggleStatus($id)
    {
        $measure = UnitOfMeasure::findOrFail($id);
        $measure->is_active = !$measure->is_active;
        $measure->save();

        $status = $measure->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->dispatch('alert', type: 'success', message: "Satuan '{$measure->name}' berhasil {$status}.");
    }

    public function render()
    {
        return view('livewire.stock-management.unit-of-measure-list', [
            'measures' => $this->measures,
            'units' => $this->units,
        ]);
    }
}
