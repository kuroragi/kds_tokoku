<?php

namespace App\Livewire\Payroll;

use App\Models\PayrollSetting;
use App\Services\BusinessUnitService;
use Livewire\Component;

class PayrollSettingForm extends Component
{
    public $business_unit_id = '';
    public $settings = [];

    public function mount()
    {
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->loadSettings();
    }

    public function updatedBusinessUnitId()
    {
        $this->loadSettings();
    }

    public function loadSettings()
    {
        $buId = $this->business_unit_id;
        if (!$buId) {
            $this->settings = [];
            return;
        }

        // Ensure defaults exist
        PayrollSetting::seedDefaultsForBusinessUnit($buId);

        $this->settings = PayrollSetting::where('business_unit_id', $buId)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'key' => $s->key,
                'label' => $s->label,
                'description' => $s->description,
                'type' => $s->type,
                'value' => $s->value,
            ])
            ->toArray();
    }

    public function save()
    {
        foreach ($this->settings as $setting) {
            PayrollSetting::where('id', $setting['id'])->update([
                'value' => $setting['value'],
            ]);
        }

        $this->dispatch('alert', type: 'success', message: 'Pengaturan payroll berhasil disimpan.');
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function render()
    {
        return view('livewire.payroll.payroll-setting-form', [
            'units' => $this->units,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
