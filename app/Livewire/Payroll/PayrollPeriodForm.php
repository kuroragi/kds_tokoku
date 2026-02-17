<?php

namespace App\Livewire\Payroll;

use App\Models\PayrollPeriod;
use App\Services\BusinessUnitService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PayrollPeriodForm extends Component
{
    public bool $showModal = false;
    public $business_unit_id = '';
    public $month = '';
    public $year = '';
    public $notes = '';

    protected $listeners = ['openPayrollPeriodModal'];

    public function openPayrollPeriodModal()
    {
        $this->resetForm();
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->month = (int) date('m');
        $this->year = (int) date('Y');
        $this->showModal = true;
        $this->dispatch('showPayrollPeriodModal');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('closePayrollPeriodModal');
    }

    private function resetForm()
    {
        $this->business_unit_id = '';
        $this->month = '';
        $this->year = '';
        $this->notes = '';
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'business_unit_id' => 'required|exists:business_units,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2099',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    protected $messages = [
        'business_unit_id.required' => 'Unit usaha wajib dipilih.',
        'month.required' => 'Bulan wajib diisi.',
        'year.required' => 'Tahun wajib diisi.',
    ];

    public function save()
    {
        $this->business_unit_id = BusinessUnitService::resolveBusinessUnitId($this->business_unit_id);
        $this->validate();

        // Check for existing
        $exists = PayrollPeriod::where('business_unit_id', $this->business_unit_id)
            ->where('month', $this->month)
            ->where('year', $this->year)
            ->exists();

        if ($exists) {
            $this->addError('month', 'Payroll untuk bulan dan tahun ini sudah ada.');
            return;
        }

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $name = 'Gaji ' . $monthNames[(int) $this->month] . ' ' . $this->year;
        $startDate = sprintf('%d-%02d-01', $this->year, $this->month);
        $endDate = date('Y-m-t', strtotime($startDate));

        PayrollPeriod::create([
            'business_unit_id' => $this->business_unit_id,
            'month' => $this->month,
            'year' => $this->year,
            'name' => $name,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'draft',
            'notes' => $this->notes ?: null,
        ]);

        $this->dispatch('alert', type: 'success', message: "Payroll '{$name}' berhasil dibuat.");
        $this->dispatch('refreshPayrollPeriodList');
        $this->closeModal();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function render()
    {
        return view('livewire.payroll.payroll-period-form', [
            'units' => $this->units,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
