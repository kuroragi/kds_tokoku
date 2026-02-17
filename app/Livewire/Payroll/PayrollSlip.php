<?php

namespace App\Livewire\Payroll;

use App\Models\PayrollEntry;
use Livewire\Component;

class PayrollSlip extends Component
{
    public $entryId = null;
    public $entry = null;
    public $showSlip = false;

    protected $listeners = ['openPayrollSlip'];

    public function openPayrollSlip($entryId)
    {
        $this->entryId = $entryId;
        $this->entry = PayrollEntry::with([
            'employee.position',
            'employee.businessUnit',
            'payrollPeriod',
            'details.salaryComponent',
        ])->findOrFail($entryId);
        $this->showSlip = true;
        $this->dispatch('showPayrollSlipModal');
    }

    public function getEarningsProperty()
    {
        if (!$this->entry) return collect();
        return $this->entry->details->where('type', 'earning');
    }

    public function getBenefitsProperty()
    {
        if (!$this->entry) return collect();
        return $this->entry->details->where('type', 'benefit');
    }

    public function getDeductionsProperty()
    {
        if (!$this->entry) return collect();
        return $this->entry->details->where('type', 'deduction');
    }

    public function render()
    {
        return view('livewire.payroll.payroll-slip', [
            'earnings' => $this->earnings,
            'benefits' => $this->benefits,
            'deductions' => $this->deductions,
        ]);
    }
}
