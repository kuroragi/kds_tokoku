<?php

namespace App\Livewire\Asset;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Vendor;
use App\Services\AssetService;
use App\Services\BusinessUnitService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AssetForm extends Component
{
    public bool $showModal = false;
    public ?int $assetId = null;
    public bool $isEditing = false;

    public $business_unit_id = '';
    public $asset_category_id = '';
    public $vendor_id = '';
    public $code = '';
    public $name = '';
    public $description = '';
    public $acquisition_date = '';
    public $acquisition_cost = 0;
    public $useful_life_months = 60;
    public $salvage_value = 0;
    public $depreciation_method = 'straight_line';
    public $location = '';
    public $serial_number = '';
    public $condition = 'good';
    public $notes = '';

    // Tipe perolehan & jurnal
    public $acquisition_type = 'purchase_cash';
    public $funding_source = 'equity';
    public $initial_accumulated_depreciation = 0;
    public $remaining_debt_amount = 0;
    public $payment_coa_key = 'kas_utama';

    protected $listeners = ['openAssetModal', 'editAsset'];

    public function openAssetModal()
    {
        $this->resetForm();
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->acquisition_date = now()->format('Y-m-d');
        $this->showModal = true;
    }

    public function editAsset($id)
    {
        $asset = Asset::findOrFail($id);
        $this->assetId = $asset->id;
        $this->isEditing = true;
        $this->business_unit_id = $asset->business_unit_id;
        $this->asset_category_id = $asset->asset_category_id;
        $this->vendor_id = $asset->vendor_id ?? '';
        $this->code = $asset->code;
        $this->name = $asset->name;
        $this->description = $asset->description ?? '';
        $this->acquisition_date = $asset->acquisition_date->format('Y-m-d');
        $this->acquisition_cost = $asset->acquisition_cost;
        $this->useful_life_months = $asset->useful_life_months;
        $this->salvage_value = $asset->salvage_value;
        $this->depreciation_method = $asset->depreciation_method;
        $this->location = $asset->location ?? '';
        $this->serial_number = $asset->serial_number ?? '';
        $this->condition = $asset->condition;
        $this->notes = $asset->notes ?? '';
        $this->acquisition_type = $asset->acquisition_type ?? 'purchase_cash';
        $this->funding_source = $asset->funding_source ?? 'equity';
        $this->initial_accumulated_depreciation = $asset->initial_accumulated_depreciation ?? 0;
        $this->remaining_debt_amount = $asset->remaining_debt_amount ?? 0;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->assetId = null;
        $this->isEditing = false;
        $this->business_unit_id = '';
        $this->asset_category_id = '';
        $this->vendor_id = '';
        $this->code = '';
        $this->name = '';
        $this->description = '';
        $this->acquisition_date = '';
        $this->acquisition_cost = 0;
        $this->useful_life_months = 60;
        $this->salvage_value = 0;
        $this->depreciation_method = 'straight_line';
        $this->location = '';
        $this->serial_number = '';
        $this->condition = 'good';
        $this->notes = '';
        $this->acquisition_type = 'purchase_cash';
        $this->funding_source = 'equity';
        $this->initial_accumulated_depreciation = 0;
        $this->remaining_debt_amount = 0;
        $this->payment_coa_key = 'kas_utama';
        $this->resetValidation();
    }

    public function updatedBusinessUnitId()
    {
        $this->asset_category_id = '';
    }

    public function updatedAssetCategoryId($value)
    {
        if ($value) {
            $category = AssetCategory::find($value);
            if ($category && !$this->isEditing) {
                $this->useful_life_months = $category->useful_life_months;
                $this->depreciation_method = $category->depreciation_method;
            }
        }
    }

    protected function rules(): array
    {
        $rules = [
            'business_unit_id' => 'required|exists:business_units,id',
            'asset_category_id' => 'required|exists:asset_categories,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'code' => [
                'required', 'string', 'max:30',
                Rule::unique('assets', 'code')
                    ->where('business_unit_id', $this->business_unit_id)
                    ->ignore($this->assetId),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'acquisition_date' => 'required|date',
            'acquisition_cost' => 'required|integer|min:1',
            'useful_life_months' => 'required|integer|min:1|max:600',
            'salvage_value' => 'required|integer|min:0',
            'depreciation_method' => 'required|in:straight_line,declining_balance',
            'location' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:100',
            'condition' => 'required|in:good,fair,poor',
            'notes' => 'nullable|string|max:1000',
            'acquisition_type' => 'required|in:opening_balance,purchase_cash,purchase_credit',
        ];

        // Conditional rules per tipe perolehan
        if ($this->acquisition_type === 'opening_balance') {
            $rules['funding_source'] = 'required|in:equity,debt,mixed';
            $rules['initial_accumulated_depreciation'] = 'required|integer|min:0|lt:acquisition_cost';
            if (in_array($this->funding_source, ['debt', 'mixed'])) {
                $rules['remaining_debt_amount'] = 'required|integer|min:1';
            }
        }

        if ($this->acquisition_type === 'purchase_cash') {
            $rules['payment_coa_key'] = 'required|in:kas_utama,kas_kecil,bank_utama';
        }

        if ($this->acquisition_type === 'purchase_credit') {
            $rules['vendor_id'] = 'required|exists:vendors,id';
        }

        return $rules;
    }

    public function save()
    {
        $unitId = BusinessUnitService::resolveBusinessUnitId($this->business_unit_id);
        $this->business_unit_id = $unitId;
        $this->validate();

        // Validasi COA key di kategori sebelum proses
        if (!$this->isEditing) {
            $category = AssetCategory::find($this->asset_category_id);
            if (!$category?->coa_asset_key) {
                $this->addError('asset_category_id',
                    "Kategori '{$category->name}' belum memiliki mapping COA aset. " .
                    "Atur terlebih dahulu di menu Kategori Aset (pilih COA Preset)."
                );
                return;
            }
        }

        $data = [
            'business_unit_id' => $unitId,
            'asset_category_id' => $this->asset_category_id,
            'vendor_id' => $this->vendor_id ?: null,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description ?: null,
            'acquisition_date' => $this->acquisition_date,
            'acquisition_cost' => $this->acquisition_cost,
            'useful_life_months' => $this->useful_life_months,
            'salvage_value' => $this->salvage_value,
            'depreciation_method' => $this->depreciation_method,
            'location' => $this->location ?: null,
            'serial_number' => $this->serial_number ?: null,
            'condition' => $this->condition,
            'status' => 'active',
            'notes' => $this->notes ?: null,
            'acquisition_type' => $this->acquisition_type,
        ];

        // Tambahkan field khusus saldo awal
        if ($this->acquisition_type === 'opening_balance') {
            $data['funding_source'] = $this->funding_source;
            $data['initial_accumulated_depreciation'] = $this->initial_accumulated_depreciation;
            $data['remaining_debt_amount'] = in_array($this->funding_source, ['debt', 'mixed'])
                ? $this->remaining_debt_amount : 0;
        }

        if ($this->isEditing) {
            DB::beginTransaction();
            try {
                $asset = Asset::findOrFail($this->assetId);
                $asset->update($data);
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->dispatch('alert', type: 'error', message: "Gagal memperbarui aset: {$e->getMessage()}");
                return;
            }
            $action = 'diperbarui';
        } else {
            DB::beginTransaction();
            try {
                $asset = Asset::create($data);

                // Jurnal pengadaan SELALU dibuat untuk aset baru
                $service = app(AssetService::class);
                $paymentKey = $this->acquisition_type === 'purchase_cash' ? $this->payment_coa_key : null;
                $service->createAcquisitionJournal($asset, $paymentKey);

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->dispatch('alert', type: 'error', message: "Gagal membuat aset & jurnal: {$e->getMessage()}");
                return;
            }

            $action = 'dibuat (dengan jurnal)';
        }

        $this->dispatch('alert', type: 'success', message: "Aset '{$this->name}' berhasil {$action}.");
        $this->dispatch('refreshAssetList');
        $this->closeModal();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function getCategoriesProperty()
    {
        if (!$this->business_unit_id) return collect();
        return AssetCategory::active()
            ->byBusinessUnit($this->business_unit_id)
            ->orderBy('name')->get();
    }

    public function getVendorsProperty()
    {
        if (!BusinessUnitService::isSuperAdmin()) {
            $unitId = BusinessUnitService::getUserBusinessUnitId();
            return $unitId ? Vendor::active()->byBusinessUnit($unitId)->orderBy('name')->get() : collect();
        }
        return Vendor::active()->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.asset.asset-form', [
            'units' => $this->units,
            'categories' => $this->categories,
            'vendors' => $this->vendors,
            'conditions' => Asset::CONDITIONS,
            'methods' => AssetCategory::DEPRECIATION_METHODS,
            'acquisitionTypes' => Asset::ACQUISITION_TYPES,
            'fundingSources' => Asset::FUNDING_SOURCES,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
            'paymentOptions' => [
                'kas_utama' => 'Kas Utama',
                'kas_kecil' => 'Kas Kecil',
                'bank_utama' => 'Bank Utama',
            ],
        ]);
    }
}
