<?php

namespace App\Livewire\Asset;

use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Services\AssetService;
use App\Services\BusinessUnitService;
use Livewire\Component;

class AssetDisposalForm extends Component
{
    public bool $showModal = false;
    public ?int $disposalId = null;
    public bool $isEditing = false;

    public $business_unit_id = '';
    public $asset_id = '';
    public $disposal_date = '';
    public $disposal_method = 'scrapped';
    public $disposal_amount = 0;
    public $buyer_info = '';
    public $reason = '';
    public $notes = '';

    // Jurnal options
    public $create_journal = false;

    // Computed display fields
    public $asset_name = '';
    public $book_value = 0;
    public $gain_loss = 0;

    protected $listeners = ['openAssetDisposalModal', 'editAssetDisposal'];

    public function openAssetDisposalModal()
    {
        $this->resetForm();
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->disposal_date = now()->format('Y-m-d');
        $this->showModal = true;
    }

    public function editAssetDisposal($id)
    {
        $disposal = AssetDisposal::with('asset')->findOrFail($id);
        $this->disposalId = $disposal->id;
        $this->isEditing = true;
        $this->business_unit_id = $disposal->asset->business_unit_id;
        $this->asset_id = $disposal->asset_id;
        $this->asset_name = $disposal->asset->name;
        $this->book_value = $disposal->book_value_at_disposal;
        $this->disposal_date = $disposal->disposal_date->format('Y-m-d');
        $this->disposal_method = $disposal->disposal_method;
        $this->disposal_amount = $disposal->disposal_amount;
        $this->gain_loss = $disposal->gain_loss;
        $this->buyer_info = $disposal->buyer_info ?? '';
        $this->reason = $disposal->reason ?? '';
        $this->notes = $disposal->notes ?? '';
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
        $this->disposalId = null;
        $this->isEditing = false;
        $this->business_unit_id = '';
        $this->asset_id = '';
        $this->disposal_date = '';
        $this->disposal_method = 'scrapped';
        $this->disposal_amount = 0;
        $this->buyer_info = '';
        $this->reason = '';
        $this->notes = '';
        $this->create_journal = false;
        $this->asset_name = '';
        $this->book_value = 0;
        $this->gain_loss = 0;
        $this->resetValidation();
    }

    public function updatedAssetId($value)
    {
        if ($value) {
            $asset = Asset::find($value);
            if ($asset) {
                $service = app(AssetService::class);
                $this->asset_name = $asset->name;
                $this->book_value = $service->getCurrentBookValue($asset);
                $this->calculateGainLoss();
            }
        } else {
            $this->asset_name = '';
            $this->book_value = 0;
            $this->gain_loss = 0;
        }
    }

    public function updatedDisposalAmount()
    {
        $this->calculateGainLoss();
    }

    private function calculateGainLoss()
    {
        $this->gain_loss = (int) $this->disposal_amount - $this->book_value;
    }

    protected function rules(): array
    {
        return [
            'asset_id' => 'required|exists:assets,id',
            'disposal_date' => 'required|date',
            'disposal_method' => 'required|in:sold,scrapped,donated',
            'disposal_amount' => 'required|integer|min:0',
            'buyer_info' => 'nullable|string|max:255',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function save()
    {
        $this->validate();

        $asset = Asset::findOrFail($this->asset_id);
        $service = app(AssetService::class);
        $bookValue = $service->getCurrentBookValue($asset);
        $gainLoss = (int) $this->disposal_amount - $bookValue;

        $data = [
            'asset_id' => $this->asset_id,
            'disposal_date' => $this->disposal_date,
            'disposal_method' => $this->disposal_method,
            'disposal_amount' => $this->disposal_amount,
            'book_value_at_disposal' => $bookValue,
            'gain_loss' => $gainLoss,
            'buyer_info' => $this->buyer_info ?: null,
            'reason' => $this->reason ?: null,
            'notes' => $this->notes ?: null,
        ];

        if ($this->isEditing) {
            AssetDisposal::findOrFail($this->disposalId)->update($data);
        } else {
            $disposal = AssetDisposal::create($data);

            // Update status aset
            $asset->update(['status' => 'disposed']);

            // Buat jurnal disposal
            if ($this->create_journal) {
                $journal = $service->createDisposalJournal($disposal);
                if (!$journal) {
                    $this->dispatch('alert', type: 'warning', message: 'Disposal berhasil dicatat, tetapi jurnal gagal dibuat (periksa mapping COA & periode).');
                    $this->dispatch('refreshAssetDisposalList');
                    $this->closeModal();
                    return;
                }
            }
        }

        $action = $this->isEditing ? 'diperbarui' : 'dicatat';
        $this->dispatch('alert', type: 'success', message: "Disposal aset berhasil {$action}.");
        $this->dispatch('refreshAssetDisposalList');
        $this->closeModal();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function getAssetsProperty()
    {
        $query = Asset::active();
        if (!BusinessUnitService::isSuperAdmin()) {
            $unitId = BusinessUnitService::getUserBusinessUnitId();
            if ($unitId) $query->byBusinessUnit($unitId);
        } elseif ($this->business_unit_id) {
            $query->byBusinessUnit($this->business_unit_id);
        }
        return $query->orderBy('code')->get();
    }

    public function render()
    {
        return view('livewire.asset.asset-disposal-form', [
            'units' => $this->units,
            'assets' => $this->assets,
            'disposalMethods' => AssetDisposal::METHODS,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
