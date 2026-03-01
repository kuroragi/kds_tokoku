<?php

namespace App\Livewire\Coa;

use App\Http\Requests\COARequest;
use App\Models\COA;
use App\Services\COAService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class CoaForm extends Component
{
    // Form Properties
    public $coaId;
    public $code = '';
    public $name = '';
    public $parent_code = null;
    public $type = 'aktiva';
    public $description = '';
    public $is_active = true;
    public $is_leaf_account = false;
    public $maxOrder = 1;
    public $order = 1;
    public $orderOptions = [];
    public $level = 0;

    // UI State
    public $showModal = false;
    public $isEditing = false;

    protected function rules(): array
    {
        return $this->getCoaRequest()->rules();
    }

    protected function messages(): array
    {
        return $this->getCoaRequest()->messages();
    }

    protected function getCoaRequest(): COARequest
    {
        $request = new COARequest();

        if ($this->isEditing && $this->coaId) {
            $request->setRouteResolver(fn() => new class($this->coaId) {
                public function __construct(private $coaId) {}
                public function parameter($key) { return $key === 'coa' ? $this->coaId : null; }
            });
        }

        return $request;
    }

    protected $listeners = [
        'openCoaModal' => 'openModal',
        'editCoa' => 'edit'
    ];

    public function updated($propertyName, $value): void
    {
        if (!$this->showModal) {
            return;
        }

        // Normalize parent_code: empty string to null
        if ($propertyName === 'parent_code') {
            $this->parent_code = $this->parent_code !== '' && $this->parent_code !== null
                ? (int) $this->parent_code
                : null;
            $this->recalculateOrderOptions();
        }

        $formFields = ['code', 'name', 'parent_code', 'type', 'order', 'description'];
        if (in_array($propertyName, $formFields)) {
            $this->validateOnly($propertyName);
        }
    }

    protected function getBusinessUnitId(): ?int
    {
        return auth()->user()?->business_unit_id;
    }

    protected function recalculateOrderOptions(): void
    {
        $businessUnitId = $this->getBusinessUnitId();

        $query = $this->parent_code
            ? COA::where('parent_code', $this->parent_code)
            : COA::whereNull('parent_code');

        $query->when($businessUnitId, fn($q) => $q->where('business_unit_id', $businessUnitId))
              ->when(!$businessUnitId, fn($q) => $q->whereNull('business_unit_id'));

        // When editing, exclude current item from sibling count
        // then the max becomes siblingCount (others) + 1 (self) = siblingCount + 1... 
        // which equals total siblings including self. Same as just counting all.
        // But if parent changed during edit, item is NOT in new parent group,
        // so we need +1 for the new slot.
        $siblingCount = $query->when($this->isEditing && $this->coaId, function ($q) {
            $q->where('id', '!=', $this->coaId);
        })->count();

        // Always add 1: for new item (create) or for the item being placed (edit)
        $this->maxOrder = $siblingCount + 1;
        $this->maxOrder = max($this->maxOrder, 1);

        $this->orderOptions = collect(range(1, $this->maxOrder))
            ->mapWithKeys(fn($i) => [$i => "urutan ke-$i"])->toArray();

        // Clamp order to valid range
        if ($this->order > $this->maxOrder) {
            $this->order = $this->maxOrder;
        }
    }

    public function openModal(): void
    {
        if (!$this->isEditing) {
            $this->resetForm();
        }

        $this->recalculateOrderOptions();
        $this->showModal = true;
    }

    public function edit($coaId): void
    {
        $this->isEditing = true;

        $coa = COA::find($coaId);

        if (!$coa) {
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Chart of Account tidak ditemukan.'
            ]);
            return;
        }

        $this->coaId = $coa->id;
        $this->code = $coa->code;
        $this->name = $coa->name;
        $this->parent_code = $coa->parent_code;
        $this->type = $coa->type;
        $this->description = $coa->description ?? '';
        $this->is_active = $coa->is_active;
        $this->is_leaf_account = $coa->is_leaf_account;
        $this->order = $coa->order;
        $this->level = $coa->level ?? 0;

        $this->recalculateOrderOptions();
        $this->showModal = true;
    }

    public function save(COAService $coaService): void
    {
        $this->validate();

        try {
            $data = $this->getFormData();

            if ($this->isEditing) {
                $coa = COA::findOrFail($this->coaId);
                $coaService->update($coa, $data);
                $message = "Chart of Account '{$coa->name}' berhasil diperbarui.";
            } else {
                $coa = $coaService->create($data);
                $message = "Chart of Account '{$coa->name}' berhasil dibuat.";
            }

            $this->dispatch('showAlert', [
                'type' => 'success',
                'message' => $message
            ]);

            $this->dispatch('refreshCoaList');
            $this->closeModal();

        } catch (ValidationException $e) {
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    protected function getFormData(): array
    {
        return [
            'business_unit_id' => $this->getBusinessUnitId(),
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'parent_code' => $this->parent_code,
            'order' => $this->order,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'is_leaf_account' => $this->is_leaf_account,
        ];
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->coaId = null;
        $this->code = '';
        $this->name = '';
        $this->parent_code = null;
        $this->type = 'aktiva';
        $this->description = '';
        $this->is_active = true;
        $this->is_leaf_account = false;
        $this->order = 1;
        $this->level = 0;
        $this->isEditing = false;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function getParentOptionsProperty(COAService $coaService)
    {
        $excludeId = $this->isEditing ? $this->coaId : null;
        return $coaService->getParentOptions($excludeId, $this->getBusinessUnitId());
    }

    public function render()
    {
        return view('livewire.coa.coa-form', [
            'parentOptions' => $this->parentOptions,
            'types' => [
                'aktiva' => 'Aktiva',
                'pasiva' => 'Pasiva',
                'modal' => 'Modal',
                'pendapatan' => 'Pendapatan',
                'beban' => 'Beban'
            ]
        ]);
    }
}