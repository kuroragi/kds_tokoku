<?php

namespace App\Livewire\Purchase;

use App\Models\Purchase;
use App\Services\BusinessUnitService;
use App\Services\PurchaseService;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseList extends Component
{
    use WithPagination;

    public $search = '';
    public $filterUnit = '';
    public $filterStatus = '';
    public $filterPaymentStatus = '';
    public $filterPaymentType = '';
    public $sortField = 'purchase_date';
    public $sortDirection = 'desc';

    protected $listeners = ['refreshPurchaseList' => '$refresh'];

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function getPurchasesProperty()
    {
        $query = Purchase::with(['businessUnit', 'vendor', 'purchaseOrder', 'payments']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('invoice_number', 'like', "%{$this->search}%")
                  ->orWhere('notes', 'like', "%{$this->search}%")
                  ->orWhereHas('vendor', fn($vq) => $vq->where('name', 'like', "%{$this->search}%"));
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

    public function cancelPurchase($id)
    {
        $purchase = Purchase::findOrFail($id);
        $service = new PurchaseService();
        $service->cancelPurchase($purchase);
        $this->dispatch('alert', type: 'success', message: 'Pembelian berhasil dibatalkan.');
    }

    public function deletePurchase($id)
    {
        $purchase = Purchase::findOrFail($id);
        $service = new PurchaseService();
        $service->deletePurchase($purchase);
        $this->dispatch('alert', type: 'success', message: 'Pembelian berhasil dihapus.');
    }

    public function render()
    {
        return view('livewire.purchase.purchase-list', [
            'purchases' => $this->purchases,
            'units' => $this->units,
            'statuses' => Purchase::STATUSES,
            'paymentStatuses' => Purchase::PAYMENT_STATUSES,
            'paymentTypes' => Purchase::PAYMENT_TYPES,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
