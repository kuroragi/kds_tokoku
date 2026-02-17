<?php

namespace App\Livewire\NameCard;

use App\Models\Customer;
use App\Services\BusinessUnitService;
use Livewire\Component;

class CustomerList extends Component
{
    public $search = '';
    public $filterUnit = '';
    public $filterStatus = '';
    public $sortField = 'code';
    public $sortDirection = 'asc';

    protected $listeners = ['refreshCustomerList' => '$refresh'];

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

    public function deleteCustomer($id)
    {
        $customer = Customer::findOrFail($id);
        $name = $customer->name;
        $customer->delete();
        $this->dispatch('alert', type: 'success', message: "Pelanggan '{$name}' berhasil dihapus.");
    }

    public function toggleStatus($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->update(['is_active' => !$customer->is_active]);
        $status = $customer->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->dispatch('alert', type: 'success', message: "Pelanggan '{$customer->name}' berhasil {$status}.");
    }

    public function render()
    {
        $query = Customer::with('businessUnit');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', "%{$this->search}%")
                    ->orWhere('name', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%");
            });
        }

        BusinessUnitService::applyBusinessUnitFilter($query, $this->filterUnit);

        if ($this->filterStatus !== '') {
            $query->where('is_active', $this->filterStatus);
        }

        $query->orderBy($this->sortField, $this->sortDirection);

        return view('livewire.name-card.customer-list', [
            'customers' => $query->get(),
            'units' => $this->units,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
