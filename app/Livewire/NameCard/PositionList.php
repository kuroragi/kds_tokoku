<?php

namespace App\Livewire\NameCard;

use App\Models\Position;
use App\Services\BusinessUnitService;
use Livewire\Component;

class PositionList extends Component
{
    public $search = '';
    public $filterUnit = '';
    public $filterStatus = '';
    public $sortField = 'code';
    public $sortDirection = 'asc';

    protected $listeners = ['refreshPositionList' => '$refresh'];

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function getPositionsProperty()
    {
        $query = Position::with('businessUnit')
            ->withCount('employees')
            ->whereNotNull('business_unit_id');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', "%{$this->search}%")
                  ->orWhere('name', 'like', "%{$this->search}%");
            });
        }

        BusinessUnitService::applyBusinessUnitFilter($query, $this->filterUnit);

        if ($this->filterStatus !== '') {
            $query->where('is_active', $this->filterStatus);
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->get();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function duplicateDefaults($businessUnitId)
    {
        $created = Position::duplicateDefaultsForBusinessUnit($businessUnitId);

        if ($created->count() > 0) {
            $this->dispatch('alert', type: 'success', message: "{$created->count()} jabatan bawaan berhasil ditambahkan.");
        } else {
            $this->dispatch('alert', type: 'info', message: 'Semua jabatan bawaan sudah ada untuk unit usaha ini.');
        }
    }

    public function deletePosition($id)
    {
        $position = Position::findOrFail($id);

        if ($position->employees()->count() > 0) {
            $this->dispatch('alert', type: 'error', message: "Tidak dapat menghapus jabatan '{$position->name}' karena masih digunakan oleh karyawan.");
            return;
        }

        $name = $position->name;
        $position->delete();

        $this->dispatch('alert', type: 'success', message: "Jabatan '{$name}' berhasil dihapus.");
    }

    public function toggleStatus($id)
    {
        $position = Position::findOrFail($id);
        $position->is_active = !$position->is_active;
        $position->save();

        $status = $position->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->dispatch('alert', type: 'success', message: "Jabatan '{$position->name}' berhasil {$status}.");
    }

    public function render()
    {
        return view('livewire.name-card.position-list', [
            'positions' => $this->positions,
            'units' => $this->units,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
