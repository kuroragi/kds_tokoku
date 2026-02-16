<?php

namespace App\Livewire\BusinessUnit;

use App\Models\BusinessUnit;
use App\Models\BusinessUnitCoaMapping;
use App\Models\COA;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class BusinessUnitForm extends Component
{
    public ?int $unitId = null;
    public bool $isEditing = false;
    public string $activeTab = 'profile';

    // Profile fields
    public $code = '';
    public $name = '';
    public $owner_name = '';
    public $phone = '';
    public $email = '';
    public $address = '';
    public $city = '';
    public $province = '';
    public $postal_code = '';
    public $tax_id = '';
    public $business_type = '';
    public $description = '';
    public $is_active = true;

    // COA Mapping — key => coa_id
    public array $coaMappings = [];

    protected $listeners = ['editUnit', 'createUnit'];

    public function mount($unitId = null)
    {
        if ($unitId) {
            $this->editUnit($unitId);
        }

        $this->initCoaMappings();
    }

    private function initCoaMappings(): void
    {
        $definitions = BusinessUnitCoaMapping::getAccountKeyDefinitions();
        foreach ($definitions as $type => $keys) {
            foreach ($keys as $def) {
                if (!isset($this->coaMappings[$def['key']])) {
                    $this->coaMappings[$def['key']] = '';
                }
            }
        }
    }

    public function editUnit($id)
    {
        $unit = BusinessUnit::with('coaMappings')->findOrFail($id);

        $this->unitId = $unit->id;
        $this->isEditing = true;
        $this->code = $unit->code;
        $this->name = $unit->name;
        $this->owner_name = $unit->owner_name ?? '';
        $this->phone = $unit->phone ?? '';
        $this->email = $unit->email ?? '';
        $this->address = $unit->address ?? '';
        $this->city = $unit->city ?? '';
        $this->province = $unit->province ?? '';
        $this->postal_code = $unit->postal_code ?? '';
        $this->tax_id = $unit->tax_id ?? '';
        $this->business_type = $unit->business_type ?? '';
        $this->description = $unit->description ?? '';
        $this->is_active = $unit->is_active;

        $this->coaMappings = [];
        $this->initCoaMappings();
        foreach ($unit->coaMappings as $mapping) {
            $this->coaMappings[$mapping->account_key] = (string) $mapping->coa_id;
        }
    }

    public function createUnit()
    {
        $this->reset();
        $this->isEditing = false;
        $this->is_active = true;
        $this->coaMappings = [];
        $this->initCoaMappings();
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    protected function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('business_units', 'code')->ignore($this->unitId)],
            'name' => 'required|string|max:255',
            'owner_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'tax_id' => 'nullable|string|max:30',
            'business_type' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'code.required' => 'Kode unit usaha wajib diisi.',
        'code.unique' => 'Kode unit usaha sudah digunakan.',
        'name.required' => 'Nama unit usaha wajib diisi.',
        'email.email' => 'Format email tidak valid.',
    ];

    public function save()
    {
        $this->validate();

        DB::beginTransaction();
        try {
            $data = [
                'code' => $this->code,
                'name' => $this->name,
                'owner_name' => $this->owner_name ?: null,
                'phone' => $this->phone ?: null,
                'email' => $this->email ?: null,
                'address' => $this->address ?: null,
                'city' => $this->city ?: null,
                'province' => $this->province ?: null,
                'postal_code' => $this->postal_code ?: null,
                'tax_id' => $this->tax_id ?: null,
                'business_type' => $this->business_type ?: null,
                'description' => $this->description ?: null,
                'is_active' => $this->is_active,
            ];

            if ($this->isEditing) {
                $unit = BusinessUnit::findOrFail($this->unitId);
                $unit->update($data);
            } else {
                $unit = BusinessUnit::create($data);
                $this->unitId = $unit->id;
                $this->isEditing = true;
            }

            // Save COA mappings
            $this->saveCoaMappings($unit);

            DB::commit();

            $this->dispatch('alert', type: 'success', message: $this->isEditing ? "Unit usaha '{$unit->name}' berhasil diperbarui." : "Unit usaha '{$unit->name}' berhasil dibuat.");
            $this->dispatch('refreshBusinessUnitList');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('alert', type: 'error', message: 'Gagal menyimpan unit usaha: ' . $e->getMessage());
        }
    }

    private function saveCoaMappings(BusinessUnit $unit): void
    {
        $definitions = BusinessUnitCoaMapping::getAccountKeyDefinitions();

        foreach ($definitions as $type => $keys) {
            foreach ($keys as $def) {
                $key = $def['key'];
                $coaId = $this->coaMappings[$key] ?? '';

                if ($coaId) {
                    BusinessUnitCoaMapping::updateOrCreate(
                        ['business_unit_id' => $unit->id, 'account_key' => $key],
                        ['label' => $def['label'], 'coa_id' => $coaId]
                    );
                } else {
                    BusinessUnitCoaMapping::where('business_unit_id', $unit->id)
                        ->where('account_key', $key)
                        ->delete();
                }
            }
        }
    }

    public function getLeafCoasProperty()
    {
        return COA::where('is_active', true)
            ->where('is_leaf_account', true)
            ->orderBy('code')
            ->get();
    }

    public function getCoasByTypeProperty()
    {
        $coas = $this->leafCoas;

        return [
            'aktiva' => $coas->where('type', 'aktiva')->values(),
            'pasiva' => $coas->where('type', 'pasiva')->values(),
            'modal' => $coas->where('type', 'modal')->values(),
            'pendapatan' => $coas->where('type', 'pendapatan')->values(),
            'beban' => $coas->where('type', 'beban')->values(),
        ];
    }

    public function getAccountKeyDefinitionsProperty(): array
    {
        return BusinessUnitCoaMapping::getAccountKeyDefinitions();
    }

    public function render()
    {
        return view('livewire.business-unit.business-unit-form', [
            'coasByType' => $this->coasByType,
            'accountKeyDefinitions' => $this->accountKeyDefinitions,
        ]);
    }
}
