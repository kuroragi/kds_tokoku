<?php

namespace App\Livewire\Purchase;

use App\Models\PurchaseOrder;
use App\Services\BusinessUnitService;
use App\Services\PurchaseService;
use Livewire\Component;

class PurchaseOrderList extends Component
{
    public $search = '';
    public $filterUnit = '';
    public $filterStatus = '';
    public $sortField = 'po_date';
    public $sortDirection = 'desc';

    protected $listeners = ['refreshPurchaseOrderList' => '$refresh'];

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function getPurchaseOrdersProperty()
    {
        $query = PurchaseOrder::with(['businessUnit', 'vendor']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('po_number', 'like', "%{$this->search}%")
                  ->orWhere('notes', 'like', "%{$this->search}%")
                  ->orWhereHas('vendor', fn($vq) => $vq->where('name', 'like', "%{$this->search}%"));
            });
        }

        BusinessUnitService::applyBusinessUnitFilter($query, $this->filterUnit);

        if ($this->filterStatus !== '') {
            $query->where('status', $this->filterStatus);
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->get();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function confirmPO($id)
    {
        $po = PurchaseOrder::findOrFail($id);
        $service = new PurchaseService();
        $service->confirmPurchaseOrder($po);
        $this->dispatch('alert', type: 'success', message: 'Purchase Order berhasil dikonfirmasi.');
    }

    public function cancelPO($id)
    {
        $po = PurchaseOrder::findOrFail($id);
        $service = new PurchaseService();
        $service->cancelPurchaseOrder($po);
        $this->dispatch('alert', type: 'success', message: 'Purchase Order berhasil dibatalkan.');
    }

    public function deletePO($id)
    {
        $po = PurchaseOrder::findOrFail($id);
        $service = new PurchaseService();
        $service->deletePurchaseOrder($po);
        $this->dispatch('alert', type: 'success', message: 'Purchase Order berhasil dihapus.');
    }

    public function render()
    {
        return view('livewire.purchase.purchase-order-list', [
            'purchaseOrders' => $this->purchaseOrders,
            'units' => $this->units,
            'statuses' => PurchaseOrder::STATUSES,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
