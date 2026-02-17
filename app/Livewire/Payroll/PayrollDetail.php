<?php

namespace App\Livewire\Payroll;

use App\Models\COA;
use App\Models\PayrollEntry;
use App\Models\PayrollEntryDetail;
use App\Models\PayrollPeriod;
use App\Models\SalaryComponent;
use App\Services\BusinessUnitService;
use App\Services\PayrollService;
use Livewire\Component;

class PayrollDetail extends Component
{
    public PayrollPeriod $payrollPeriod;
    public $search = '';

    // Manual item form
    public $showManualForm = false;
    public $manualEmployeeId = '';
    public $manualComponentId = '';
    public $manualComponentName = '';
    public $manualType = 'deduction';
    public $manualCategory = 'potongan';
    public $manualAmount = '';
    public $manualNotes = '';

    // Payment form
    public $showPaymentForm = false;
    public $paymentCoaId = '';

    protected $listeners = ['refreshPayrollDetail' => '$refresh'];

    public function mount(PayrollPeriod $payrollPeriod)
    {
        $this->payrollPeriod = $payrollPeriod;
    }

    public function calculate()
    {
        try {
            $service = app(PayrollService::class);
            $this->payrollPeriod = $service->calculatePayroll($this->payrollPeriod);
            $this->dispatch('alert', type: 'success', message: 'Payroll berhasil dihitung.');
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    public function approve()
    {
        try {
            $service = app(PayrollService::class);
            $this->payrollPeriod = $service->approvePayroll($this->payrollPeriod);
            $this->dispatch('alert', type: 'success', message: 'Payroll berhasil disetujui.');
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    public function openPaymentForm()
    {
        $this->showPaymentForm = true;
        $this->paymentCoaId = '';
    }

    public function pay()
    {
        $this->validate([
            'paymentCoaId' => 'required|exists:c_o_a_s,id',
        ], [
            'paymentCoaId.required' => 'Pilih akun pembayaran.',
        ]);

        try {
            $service = app(PayrollService::class);
            $this->payrollPeriod = $service->payPayroll($this->payrollPeriod, $this->paymentCoaId);
            $this->showPaymentForm = false;
            $this->dispatch('alert', type: 'success', message: 'Payroll berhasil dibayar dan jurnal dibuat.');
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    public function voidPayroll()
    {
        try {
            $service = app(PayrollService::class);
            $this->payrollPeriod = $service->voidPayroll($this->payrollPeriod);
            $this->dispatch('alert', type: 'success', message: 'Payroll berhasil dibatalkan.');
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    // Manual item management
    public function openManualForm($entryId)
    {
        $entry = PayrollEntry::findOrFail($entryId);
        $this->manualEmployeeId = $entryId;
        $this->manualComponentId = '';
        $this->manualComponentName = '';
        $this->manualType = 'deduction';
        $this->manualCategory = 'potongan';
        $this->manualAmount = '';
        $this->manualNotes = '';
        $this->showManualForm = true;
    }

    public function updatedManualComponentId($value)
    {
        if ($value) {
            $comp = SalaryComponent::find($value);
            if ($comp) {
                $this->manualComponentName = $comp->name;
                $this->manualType = $comp->type;
                $this->manualCategory = $comp->category;
            }
        }
    }

    public function addManualItem()
    {
        $this->validate([
            'manualComponentName' => 'required|string',
            'manualAmount' => 'required|numeric|min:1',
        ], [
            'manualComponentName.required' => 'Nama komponen wajib diisi.',
            'manualAmount.required' => 'Nominal wajib diisi.',
        ]);

        try {
            $entry = PayrollEntry::findOrFail($this->manualEmployeeId);
            $service = app(PayrollService::class);
            $service->addManualItem(
                $entry,
                $this->manualComponentId ?: null,
                $this->manualComponentName,
                $this->manualType,
                $this->manualCategory,
                (int) $this->manualAmount,
                $this->manualNotes ?: null
            );

            $this->payrollPeriod->refresh();
            $this->showManualForm = false;
            $this->dispatch('alert', type: 'success', message: 'Item manual berhasil ditambahkan.');
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    public function removeManualItem($detailId)
    {
        try {
            $detail = PayrollEntryDetail::findOrFail($detailId);
            $service = app(PayrollService::class);
            $service->removeManualItem($detail);
            $this->payrollPeriod->refresh();
            $this->dispatch('alert', type: 'success', message: 'Item manual berhasil dihapus.');
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    public function getPaymentCoasProperty()
    {
        $bu = $this->payrollPeriod->businessUnit;
        if (!$bu) {
            return collect();
        }

        // Get kas and bank accounts
        return COA::where('type', 'aktiva')
            ->where(function ($q) {
                $q->where('name', 'like', '%Kas%')
                    ->orWhere('name', 'like', '%Bank%');
            })
            ->where('level', '>', 1)
            ->orderBy('code')
            ->get();
    }

    public function getManualComponentsProperty()
    {
        return SalaryComponent::byBusinessUnit($this->payrollPeriod->business_unit_id)
            ->active()
            ->manual()
            ->orderBy('sort_order')
            ->get();
    }

    public function render()
    {
        $entries = $this->payrollPeriod->entries()
            ->with(['employee.position', 'details.salaryComponent'])
            ->get();

        if ($this->search) {
            $entries = $entries->filter(function ($entry) {
                return str_contains(strtolower($entry->employee->name ?? ''), strtolower($this->search))
                    || str_contains(strtolower($entry->employee->code ?? ''), strtolower($this->search));
            })->values();
        }

        return view('livewire.payroll.payroll-detail', [
            'entries' => $entries,
            'paymentCoas' => $this->paymentCoas,
            'manualComponents' => $this->manualComponents,
        ]);
    }
}
