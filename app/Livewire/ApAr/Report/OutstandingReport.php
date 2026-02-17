<?php

namespace App\Livewire\ApAr\Report;

use App\Models\Payable;
use App\Models\Receivable;
use App\Services\BusinessUnitService;
use Livewire\Component;

class OutstandingReport extends Component
{
    public $filterUnit = '';
    public $reportType = 'payable';

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function render()
    {
        if ($this->reportType === 'payable') {
            $data = $this->getPayableOutstanding();
        } else {
            $data = $this->getReceivableOutstanding();
        }

        return view('livewire.apar.report.outstanding-report', [
            'data' => $data,
            'units' => $this->units,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }

    protected function getPayableOutstanding()
    {
        $query = Payable::with('vendor')->outstanding();

        if (!BusinessUnitService::isSuperAdmin()) {
            $unitId = BusinessUnitService::getUserBusinessUnitId();
            if ($unitId) $query->byBusinessUnit($unitId);
        } elseif ($this->filterUnit) {
            $query->byBusinessUnit($this->filterUnit);
        }

        $payables = $query->get();

        // Group by vendor
        $grouped = $payables->groupBy('vendor_id')->map(function ($items) {
            $vendor = $items->first()->vendor;
            return [
                'partner_name' => $vendor->name,
                'partner_code' => $vendor->code,
                'total_amount_due' => $items->sum('amount_due'),
                'total_paid' => $items->sum('paid_amount'),
                'total_remaining' => $items->sum(fn($p) => $p->remaining),
                'count' => $items->count(),
                'items' => $items,
            ];
        })->sortByDesc('total_remaining')->values();

        return $grouped;
    }

    protected function getReceivableOutstanding()
    {
        $query = Receivable::with('customer')->outstanding();

        if (!BusinessUnitService::isSuperAdmin()) {
            $unitId = BusinessUnitService::getUserBusinessUnitId();
            if ($unitId) $query->byBusinessUnit($unitId);
        } elseif ($this->filterUnit) {
            $query->byBusinessUnit($this->filterUnit);
        }

        $receivables = $query->get();

        $grouped = $receivables->groupBy('customer_id')->map(function ($items) {
            $customer = $items->first()->customer;
            return [
                'partner_name' => $customer->name,
                'partner_code' => $customer->code,
                'total_amount_due' => $items->sum('amount'),
                'total_paid' => $items->sum('paid_amount'),
                'total_remaining' => $items->sum(fn($r) => $r->remaining),
                'count' => $items->count(),
                'items' => $items,
            ];
        })->sortByDesc('total_remaining')->values();

        return $grouped;
    }
}
