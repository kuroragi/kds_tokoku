<?php

namespace App\Livewire\NameCard;

use App\Models\Vendor;
use App\Services\BusinessUnitService;
use Livewire\Component;

class VendorList extends Component
{
    public $search = '';
    public $filterUnit = '';
    public $filterType = '';
    public $filterStatus = '';
    public $sortField = 'code';
    public $sortDirection = 'asc';

    protected $listeners = ['refreshVendorList' => '$refresh'];

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

    public function deleteVendor($id)
    {
        $vendor = Vendor::findOrFail($id);
        $name = $vendor->name;

        if (BusinessUnitService::isSuperAdmin()) {
            // Superadmin: soft-delete the vendor entirely
            $vendor->businessUnits()->detach();
            $vendor->delete();
            $this->dispatch('alert', type: 'success', message: "Vendor '{$name}' berhasil dihapus.");
        } else {
            // Non-superadmin: detach from their unit only
            $unitId = BusinessUnitService::getUserBusinessUnitId();
            if ($unitId) {
                $vendor->businessUnits()->detach($unitId);
            }
            $this->dispatch('alert', type: 'success', message: "Vendor '{$name}' berhasil dihapus dari unit usaha Anda.");
        }
    }

    public function toggleStatus($id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->update(['is_active' => !$vendor->is_active]);
        $status = $vendor->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->dispatch('alert', type: 'success', message: "Vendor '{$vendor->name}' berhasil {$status}.");
    }

    public function render()
    {
        $query = Vendor::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', "%{$this->search}%")
                    ->orWhere('name', 'like', "%{$this->search}%")
                    ->orWhere('contact_person', 'like', "%{$this->search}%");
            });
        }

        // Non-superadmin: only see vendors attached to their unit
        if (!BusinessUnitService::isSuperAdmin()) {
            $unitId = BusinessUnitService::getUserBusinessUnitId();
            if ($unitId) {
                $query->byBusinessUnit($unitId);
            }
        } elseif ($this->filterUnit) {
            // Superadmin with unit filter
            $query->byBusinessUnit($this->filterUnit);
        }

        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        if ($this->filterStatus !== '') {
            $query->where('is_active', $this->filterStatus);
        }

        $query->orderBy($this->sortField, $this->sortDirection);

        return view('livewire.name-card.vendor-list', [
            'vendors' => $query->get(),
            'units' => $this->units,
            'types' => Vendor::getTypes(),
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
