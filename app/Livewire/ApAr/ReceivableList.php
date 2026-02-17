<?php

namespace App\Livewire\ApAr;

use App\Models\Customer;
use App\Models\Receivable;
use App\Services\ApArService;
use App\Services\BusinessUnitService;
use Livewire\Component;

class ReceivableList extends Component
{
    public $search = '';
    public $filterUnit = '';
    public $filterStatus = '';
    public $filterCustomer = '';
    public $sortField = 'invoice_date';
    public $sortDirection = 'desc';

    protected $listeners = ['refreshReceivableList' => '$refresh'];

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

    public function getCustomersProperty()
    {
        $query = Customer::active();
        if (!BusinessUnitService::isSuperAdmin()) {
            $unitId = BusinessUnitService::getUserBusinessUnitId();
            if ($unitId) $query->byBusinessUnit($unitId);
        } elseif ($this->filterUnit) {
            $query->byBusinessUnit($this->filterUnit);
        }
        return $query->orderBy('name')->get();
    }

    public function voidReceivable($id)
    {
        try {
            $receivable = Receivable::findOrFail($id);
            $service = app(ApArService::class);
            $service->voidReceivable($receivable);
            $this->dispatch('alert', type: 'success', message: "Piutang '{$receivable->invoice_number}' berhasil dibatalkan.");
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    public function deleteReceivable($id)
    {
        $receivable = Receivable::findOrFail($id);

        if ($receivable->payments()->count() > 0) {
            $this->dispatch('alert', type: 'error', message: "Piutang '{$receivable->invoice_number}' tidak bisa dihapus karena sudah ada pembayaran.");
            return;
        }

        $invoiceNumber = $receivable->invoice_number;
        $receivable->delete();
        $this->dispatch('alert', type: 'success', message: "Piutang '{$invoiceNumber}' berhasil dihapus.");
    }

    public function render()
    {
        $query = Receivable::with(['businessUnit', 'customer', 'creditCoa']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('invoice_number', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%")
                    ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$this->search}%"));
            });
        }

        if (!BusinessUnitService::isSuperAdmin()) {
            $unitId = BusinessUnitService::getUserBusinessUnitId();
            if ($unitId) $query->byBusinessUnit($unitId);
        } elseif ($this->filterUnit) {
            $query->byBusinessUnit($this->filterUnit);
        }

        if ($this->filterStatus) {
            $query->byStatus($this->filterStatus);
        }

        if ($this->filterCustomer) {
            $query->where('customer_id', $this->filterCustomer);
        }

        $receivables = $query->orderBy($this->sortField, $this->sortDirection)->get();

        return view('livewire.apar.receivable-list', [
            'receivables' => $receivables,
            'units' => $this->units,
            'customers' => $this->customers,
            'statuses' => Receivable::STATUSES,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
