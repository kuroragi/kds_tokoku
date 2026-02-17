<?php

namespace App\Livewire\ApAr;

use App\Models\Payable;
use App\Models\Vendor;
use App\Services\ApArService;
use App\Services\BusinessUnitService;
use Livewire\Component;

class PayableList extends Component
{
    public $search = '';
    public $filterUnit = '';
    public $filterStatus = '';
    public $filterVendor = '';
    public $sortField = 'invoice_date';
    public $sortDirection = 'desc';

    protected $listeners = ['refreshPayableList' => '$refresh'];

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

    public function getVendorsProperty()
    {
        $query = Vendor::active();
        if (!BusinessUnitService::isSuperAdmin()) {
            $unitId = BusinessUnitService::getUserBusinessUnitId();
            if ($unitId) $query->byBusinessUnit($unitId);
        } elseif ($this->filterUnit) {
            $query->byBusinessUnit($this->filterUnit);
        }
        return $query->orderBy('name')->get();
    }

    public function voidPayable($id)
    {
        try {
            $payable = Payable::findOrFail($id);
            $service = app(ApArService::class);
            $service->voidPayable($payable);
            $this->dispatch('alert', type: 'success', message: "Hutang '{$payable->invoice_number}' berhasil dibatalkan.");
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    public function deletePayable($id)
    {
        $payable = Payable::findOrFail($id);

        if ($payable->payments()->count() > 0) {
            $this->dispatch('alert', type: 'error', message: "Hutang '{$payable->invoice_number}' tidak bisa dihapus karena sudah ada pembayaran.");
            return;
        }

        $invoiceNumber = $payable->invoice_number;
        $payable->delete();
        $this->dispatch('alert', type: 'success', message: "Hutang '{$invoiceNumber}' berhasil dihapus.");
    }

    public function render()
    {
        $query = Payable::with(['businessUnit', 'vendor', 'debitCoa']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('invoice_number', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%")
                    ->orWhereHas('vendor', fn($vq) => $vq->where('name', 'like', "%{$this->search}%"));
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

        if ($this->filterVendor) {
            $query->where('vendor_id', $this->filterVendor);
        }

        $payables = $query->orderBy($this->sortField, $this->sortDirection)->get();

        return view('livewire.apar.payable-list', [
            'payables' => $payables,
            'units' => $this->units,
            'vendors' => $this->vendors,
            'statuses' => Payable::STATUSES,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
