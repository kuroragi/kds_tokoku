<?php

namespace App\Livewire\Payroll;

use App\Models\SalaryComponent;
use App\Services\BusinessUnitService;
use Livewire\Component;

class SalaryComponentList extends Component
{
    public $search = '';
    public $filterUnit = '';
    public $filterType = '';
    public $filterCategory = '';
    public $sortField = 'sort_order';
    public $sortDirection = 'asc';

    protected $listeners = ['refreshSalaryComponentList' => '$refresh'];

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function deleteComponent($id)
    {
        $component = SalaryComponent::findOrFail($id);
        $name = $component->name;
        $component->delete();
        $this->dispatch('alert', type: 'success', message: "Komponen '{$name}' berhasil dihapus.");
    }

    public function toggleStatus($id)
    {
        $component = SalaryComponent::findOrFail($id);
        $component->update(['is_active' => !$component->is_active]);
        $status = $component->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->dispatch('alert', type: 'success', message: "Komponen '{$component->name}' berhasil {$status}.");
    }

    public function seedDefaults()
    {
        $buId = BusinessUnitService::resolveBusinessUnitId($this->filterUnit);
        if (!$buId) {
            $this->dispatch('alert', type: 'error', message: 'Pilih unit usaha terlebih dahulu.');
            return;
        }
        SalaryComponent::seedDefaultsForBusinessUnit($buId);
        $this->dispatch('alert', type: 'success', message: 'Komponen gaji default berhasil ditambahkan.');
    }

    public function render()
    {
        $query = SalaryComponent::with('businessUnit');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', "%{$this->search}%")
                    ->orWhere('name', 'like', "%{$this->search}%");
            });
        }

        BusinessUnitService::applyBusinessUnitFilter($query, $this->filterUnit);

        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        if ($this->filterCategory) {
            $query->where('category', $this->filterCategory);
        }

        $query->orderBy($this->sortField, $this->sortDirection);

        return view('livewire.payroll.salary-component-list', [
            'components' => $query->get(),
            'units' => $this->units,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
