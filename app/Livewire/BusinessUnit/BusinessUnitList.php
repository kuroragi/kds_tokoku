<?php

namespace App\Livewire\BusinessUnit;

use App\Models\BusinessUnit;
use Livewire\Component;

class BusinessUnitList extends Component
{
    public $search = '';
    public $filterStatus = '';
    public $sortField = 'code';
    public $sortDirection = 'asc';

    protected $listeners = ['refreshBusinessUnitList' => '$refresh'];

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
        $query = BusinessUnit::withCount('users');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', "%{$this->search}%")
                  ->orWhere('name', 'like', "%{$this->search}%")
                  ->orWhere('owner_name', 'like', "%{$this->search}%");
            });
        }

        if ($this->filterStatus !== '') {
            $query->where('is_active', $this->filterStatus);
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->get();
    }

    public function deleteUnit($id)
    {
        $unit = BusinessUnit::findOrFail($id);

        if ($unit->users()->count() > 0) {
            $this->dispatch('alert', type: 'error', message: "Tidak dapat menghapus unit '{$unit->name}' karena masih memiliki user terdaftar.");
            return;
        }

        $name = $unit->name;
        $unit->delete();

        $this->dispatch('alert', type: 'success', message: "Unit usaha '{$name}' berhasil dihapus.");
    }

    public function toggleStatus($id)
    {
        $unit = BusinessUnit::findOrFail($id);
        $unit->is_active = !$unit->is_active;
        $unit->save();

        $status = $unit->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->dispatch('alert', type: 'success', message: "Unit usaha '{$unit->name}' berhasil {$status}.");
    }

    public function render()
    {
        return view('livewire.business-unit.business-unit-list', [
            'units' => $this->units,
        ]);
    }
}
