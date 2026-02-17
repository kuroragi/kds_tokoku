<?php

namespace App\Livewire\NameCard;

use App\Models\Partner;
use App\Services\BusinessUnitService;
use Livewire\Component;

class PartnerList extends Component
{
    public $search = '';
    public $filterUnit = '';
    public $filterStatus = '';
    public $sortField = 'code';
    public $sortDirection = 'asc';

    protected $listeners = ['refreshPartnerList' => '$refresh'];

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

    public function deletePartner($id)
    {
        $partner = Partner::findOrFail($id);
        $name = $partner->name;
        $partner->delete();
        $this->dispatch('alert', type: 'success', message: "Partner '{$name}' berhasil dihapus.");
    }

    public function toggleStatus($id)
    {
        $partner = Partner::findOrFail($id);
        $partner->update(['is_active' => !$partner->is_active]);
        $status = $partner->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->dispatch('alert', type: 'success', message: "Partner '{$partner->name}' berhasil {$status}.");
    }

    public function render()
    {
        $query = Partner::with('businessUnit');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', "%{$this->search}%")
                    ->orWhere('name', 'like', "%{$this->search}%")
                    ->orWhere('contact_person', 'like', "%{$this->search}%");
            });
        }

        BusinessUnitService::applyBusinessUnitFilter($query, $this->filterUnit);

        if ($this->filterStatus !== '') {
            $query->where('is_active', $this->filterStatus);
        }

        $query->orderBy($this->sortField, $this->sortDirection);

        return view('livewire.name-card.partner-list', [
            'partners' => $query->get(),
            'units' => $this->units,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
