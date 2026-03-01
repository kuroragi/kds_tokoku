<?php

namespace App\Livewire\Closing;

use App\Models\Period;
use App\Services\BusinessUnitService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PeriodForm extends Component
{
    public bool $showModal = false;
    public ?int $periodId = null;
    public bool $isEditing = false;

    // Fields
    public $business_unit_id = '';
    public $year = '';
    public $month = '';
    public $description = '';
    public $is_active = true;

    protected $listeners = ['openPeriodModal', 'editPeriod'];

    public function openPeriodModal()
    {
        $this->resetForm();
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->year = date('Y');
        $this->month = date('m');
        $this->showModal = true;
    }

    public function editPeriod($id)
    {
        $period = Period::findOrFail($id);

        if ($period->is_closed) {
            $this->dispatch('alert', type: 'error', message: 'Periode yang sudah ditutup tidak bisa diedit.');
            return;
        }

        $this->periodId = $period->id;
        $this->isEditing = true;
        $this->business_unit_id = $period->business_unit_id ?? '';
        $this->year = $period->year;
        $this->month = $period->month;
        $this->description = $period->description ?? '';
        $this->is_active = $period->is_active;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->periodId = null;
        $this->isEditing = false;
        $this->business_unit_id = '';
        $this->year = '';
        $this->month = '';
        $this->description = '';
        $this->is_active = true;
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'business_unit_id' => 'required|exists:business_units,id',
            'year' => 'required|integer|min:2020|max:2099',
            'month' => 'required|integer|min:1|max:12',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'business_unit_id.required' => 'Unit usaha wajib dipilih.',
        'year.required' => 'Tahun wajib diisi.',
        'year.min' => 'Tahun minimal 2020.',
        'month.required' => 'Bulan wajib dipilih.',
    ];

    public function save()
    {
        $this->business_unit_id = BusinessUnitService::resolveBusinessUnitId($this->business_unit_id);
        $this->validate();

        $year = (int) $this->year;
        $month = (int) $this->month;
        $code = sprintf('%04d%02d', $year, $month);
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $name = $monthNames[$month] . ' ' . $year;

        // Calculate dates
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        // Check for duplicate code within business unit
        $existing = Period::where('code', $code)
            ->where('business_unit_id', $this->business_unit_id)
            ->where('id', '!=', $this->periodId)
            ->first();

        if ($existing) {
            $this->addError('month', "Periode {$name} sudah ada untuk unit usaha ini.");
            return;
        }

        $data = [
            'business_unit_id' => $this->business_unit_id,
            'code' => $code,
            'name' => $name,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'year' => $year,
            'month' => $month,
            'description' => $this->description ?: null,
            'is_active' => $this->is_active,
        ];

        DB::beginTransaction();
        try {
            if ($this->isEditing) {
                $period = Period::findOrFail($this->periodId);
                $period->update($data);
            } else {
                Period::create($data);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatch('alert', type: 'error', message: "Gagal menyimpan periode: {$e->getMessage()}");
            return;
        }

        $action = $this->isEditing ? 'diperbarui' : 'dibuat';
        $this->dispatch('alert', type: 'success', message: "Periode '{$name}' berhasil {$action}.");
        $this->dispatch('refreshPeriodList');
        $this->closeModal();
    }

    /**
     * Generate periods for an entire fiscal year (12 months).
     */
    public function generateYear()
    {
        $this->business_unit_id = BusinessUnitService::resolveBusinessUnitId($this->business_unit_id);

        if (!$this->business_unit_id) {
            $this->dispatch('alert', type: 'error', message: 'Unit usaha wajib dipilih.');
            return;
        }

        if (!$this->year || $this->year < 2020 || $this->year > 2099) {
            $this->dispatch('alert', type: 'error', message: 'Tahun tidak valid.');
            return;
        }

        $year = (int) $this->year;
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        DB::beginTransaction();
        try {
            $created = 0;
            for ($m = 1; $m <= 12; $m++) {
                $code = sprintf('%04d%02d', $year, $m);
                $exists = Period::where('code', $code)
                    ->where('business_unit_id', $this->business_unit_id)
                    ->exists();

                if (!$exists) {
                    $startDate = sprintf('%04d-%02d-01', $year, $m);
                    Period::create([
                        'business_unit_id' => $this->business_unit_id,
                        'code' => $code,
                        'name' => $monthNames[$m] . ' ' . $year,
                        'start_date' => $startDate,
                        'end_date' => date('Y-m-t', strtotime($startDate)),
                        'year' => $year,
                        'month' => $m,
                        'is_active' => true,
                        'is_closed' => false,
                    ]);
                    $created++;
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatch('alert', type: 'error', message: "Gagal generate periode: {$e->getMessage()}");
            return;
        }

        if ($created === 0) {
            $this->dispatch('alert', type: 'warning', message: "Semua periode tahun {$year} sudah ada.");
        } else {
            $this->dispatch('alert', type: 'success', message: "{$created} periode tahun {$year} berhasil dibuat.");
        }

        $this->dispatch('refreshPeriodList');
        $this->closeModal();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function render()
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return view('livewire.closing.period-form', [
            'units' => $this->units,
            'months' => $months,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
