<?php

namespace App\Livewire\Asset;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Vendor;
use App\Services\AssetService;
use App\Services\BusinessUnitService;
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

    // Opsi jurnal pengadaan
    public $create_journal = false;
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
        $this->create_journal = false;
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
        $this->create_journal = false;
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
        return [
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
            'acquisition_cost' => 'required|integer|min:0',
            'useful_life_months' => 'required|integer|min:1|max:600',
            'salvage_value' => 'required|integer|min:0',
            'depreciation_method' => 'required|in:straight_line,declining_balance',
            'location' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:100',
            'condition' => 'required|in:good,fair,poor',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function save()
    {
        $unitId = BusinessUnitService::resolveBusinessUnitId($this->business_unit_id);
        $this->business_unit_id = $unitId;
        $this->validate();

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
        ];

        if ($this->isEditing) {
            $asset = Asset::findOrFail($this->assetId);
            $asset->update($data);
        } else {
            $asset = Asset::create($data);

            // Buat jurnal pengadaan jika diminta
            if ($this->create_journal && $this->acquisition_cost > 0) {
                $service = app(AssetService::class);
                $journal = $service->createAcquisitionJournal($asset, $this->payment_coa_key);
                if (!$journal) {
                    $this->dispatch('alert', type: 'warning', message: 'Aset berhasil dibuat, tetapi jurnal pengadaan gagal (periksa mapping COA & periode).');
                    $this->dispatch('refreshAssetList');
                    $this->closeModal();
                    return;
                }
            }
        }

        $action = $this->isEditing ? 'diperbarui' : 'dibuat';
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
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
            'paymentOptions' => [
                'kas_utama' => 'Kas Utama',
                'kas_kecil' => 'Kas Kecil',
                'bank_utama' => 'Bank Utama',
            ],
        ]);
    }
}
