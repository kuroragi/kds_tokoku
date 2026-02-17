<?php

namespace App\Livewire\ApAr\Report;

use App\Models\PayablePayment;
use App\Models\ReceivablePayment;
use App\Services\BusinessUnitService;
use Livewire\Component;

class PaymentHistoryReport extends Component
{
    public $filterUnit = '';
    public $reportType = 'payable';
    public $dateFrom = '';
    public $dateTo = '';

    public function mount()
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function render()
    {
        if ($this->reportType === 'payable') {
            $payments = $this->getPayablePayments();
            $label = 'Pembayaran Hutang';
        } else {
            $payments = $this->getReceivablePayments();
            $label = 'Penerimaan Piutang';
        }

        $totalAmount = $payments->sum('amount');

        return view('livewire.apar.report.payment-history-report', [
            'payments' => $payments,
            'totalAmount' => $totalAmount,
            'label' => $label,
            'units' => $this->units,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }

    protected function getPayablePayments()
    {
        $query = PayablePayment::with(['payable.vendor', 'payable.businessUnit', 'paymentCoa']);

        if ($this->dateFrom) {
            $query->where('payment_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->where('payment_date', '<=', $this->dateTo);
        }

        if (!BusinessUnitService::isSuperAdmin()) {
            $unitId = BusinessUnitService::getUserBusinessUnitId();
            if ($unitId) {
                $query->whereHas('payable', fn($q) => $q->where('business_unit_id', $unitId));
            }
        } elseif ($this->filterUnit) {
            $query->whereHas('payable', fn($q) => $q->where('business_unit_id', $this->filterUnit));
        }

        return $query->orderBy('payment_date', 'desc')->get();
    }

    protected function getReceivablePayments()
    {
        $query = ReceivablePayment::with(['receivable.customer', 'receivable.businessUnit', 'paymentCoa']);

        if ($this->dateFrom) {
            $query->where('payment_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->where('payment_date', '<=', $this->dateTo);
        }

        if (!BusinessUnitService::isSuperAdmin()) {
            $unitId = BusinessUnitService::getUserBusinessUnitId();
            if ($unitId) {
                $query->whereHas('receivable', fn($q) => $q->where('business_unit_id', $unitId));
            }
        } elseif ($this->filterUnit) {
            $query->whereHas('receivable', fn($q) => $q->where('business_unit_id', $this->filterUnit));
        }

        return $query->orderBy('payment_date', 'desc')->get();
    }
}
