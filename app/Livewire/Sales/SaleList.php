<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use App\Services\BusinessUnitService;
use App\Services\SalesService;
use Livewire\Component;
use Livewire\WithPagination;

class SaleList extends Component
{
    use WithPagination;

    public $search = '';
    public $filterUnit = '';
    public $filterStatus = '';
    public $filterPaymentStatus = '';
    public $filterPaymentType = '';
    public $sortField = 'sale_date';
    public $sortDirection = 'desc';

    protected $listeners = ['refreshSaleList' => '$refresh'];

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function getSalesProperty()
    {
        $query = Sale::with(['businessUnit', 'customer', 'payments']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('invoice_number', 'like', "%{$this->search}%")
                  ->orWhere('notes', 'like', "%{$this->search}%")
                  ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$this->search}%"));
            });
        }

        BusinessUnitService::applyBusinessUnitFilter($query, $this->filterUnit);

        if ($this->filterStatus !== '') {
            $query->where('status', $this->filterStatus);
        }
        if ($this->filterPaymentStatus !== '') {
            $query->where('payment_status', $this->filterPaymentStatus);
        }
        if ($this->filterPaymentType !== '') {
            $query->where('payment_type', $this->filterPaymentType);
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->paginate(15);
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function cancelSale($id)
    {
        $sale = Sale::findOrFail($id);
        $service = new SalesService();
        $service->cancelSale($sale);
        $this->dispatch('alert', type: 'success', message: 'Penjualan berhasil dibatalkan.');
    }

    public function deleteSale($id)
    {
        $sale = Sale::findOrFail($id);
        $service = new SalesService();
        $service->deleteSale($sale);
        $this->dispatch('alert', type: 'success', message: 'Penjualan berhasil dihapus.');
    }

    public function render()
    {
        return view('livewire.sales.sale-list', [
            'sales' => $this->sales,
            'units' => $this->units,
            'statuses' => Sale::STATUSES,
            'paymentStatuses' => Sale::PAYMENT_STATUSES,
            'paymentTypes' => Sale::PAYMENT_TYPES,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
