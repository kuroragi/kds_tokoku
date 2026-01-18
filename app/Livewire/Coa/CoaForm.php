<?php

namespace App\Livewire\Coa;

use App\Models\COA;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Kuroragi\GeneralHelper\ActivityLog\ActivityLogger;
use Livewire\Component;

class CoaForm extends Component
{
    // Form Properties
    public $coaId;
    public $code = '';
    public $name = '';
    public $parent_code = null;
    public $type = 'aktiva';
    public $is_active = true;
    public $is_leaf_account = false;
    public $order = 1;
    public $level = 0;

    // UI State
    public $showModal = false;
    public $isEditing = false;

    // Validation Rules
    protected function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'parent_code' => 'nullable|exists:c_o_a_s,id',
            'type' => 'required|in:aktiva,pasiva,modal,pendapatan,beban',
            'is_active' => 'boolean',
            'is_leaf_account' => 'boolean',
            'order' => 'required|integer|min:1'
        ];

        // Add unique rule for code
        if ($this->isEditing) {
            $rules['code'] = 'required|string|max:255|unique:c_o_a_s,code,' . $this->coaId;
        } else {
            $rules['code'] = 'required|string|max:255|unique:c_o_a_s,code';
        }

        return $rules;
    }

    // Custom validation messages
    protected $messages = [
        'code.required' => 'Account code is required.',
        'code.unique' => 'This account code already exists.',
        'name.required' => 'Account name is required.',
        'type.required' => 'Account type is required.',
        'type.in' => 'Invalid account type.',
        'parent_code.exists' => 'Parent account not found.',
        'order.required' => 'Order is required.',
        'order.min' => 'Order must be at least 1.'
    ];

    // Listeners
    protected $listeners = [
        'openCoaModal' => 'openModal',
        'editCoa' => 'edit'
    ];

    /**
     * Real-time validation
     */
    public function updated($propertyName)
    {
        // Skip validation if modal is being closed
        if (!$this->showModal) {
            return;
        }

        // Only validate form fields
        if (in_array($propertyName, ['code', 'name', 'parent_code', 'type', 'order'])) {
            $this->validateOnly($propertyName);
        }
    }

    /**
     * Calculate level when parent changes and determine if it should be leaf account
     */
    public function updatedParentCode($value)
    {
        if ($value) {
            $parent = COA::find($value);
            $this->level = $parent ? ($parent->level + 1) : 0;
            // By default, new accounts under parent are leaf accounts unless they will have children
            $this->is_leaf_account = true;
        } else {
            $this->level = 0;
            // Top level accounts are usually parent accounts
            $this->is_leaf_account = false;
        }
    }

    /**
     * Open modal for creating new COA
     */
    public function openModal()
    {
        $this->isEditing ? '' : $this->resetForm();
        $this->showModal = true;
    }

    /**
     * Open modal for editing COA
     */
    public function edit($coaId)
    {
        // Set editing state FIRST
        $this->isEditing = true;
        
        try {
            $coa = COA::findOrFail($coaId);
            
            $this->coaId = $coa->id;
            $this->code = $coa->code;
            $this->name = $coa->name;
            $this->parent_code = $coa->parent_code;
            $this->type = $coa->type;
            $this->is_active = $coa->is_active;
            $this->is_leaf_account = $coa->is_leaf_account;
            $this->order = $coa->order;
            $this->level = $coa->level ?? 0;
            
            $this->showModal = true;
            
        } catch (\Throwable $th) {
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Chart of Account not found.'
            ]);
        }
    }

    /**
     * Save COA (create or update)
     */
    public function save()
    {
        $this->validate();

        DB::beginTransaction();
        
        try {
            // Calculate level if parent exists
            if ($this->parent_code) {
                $parent = COA::find($this->parent_code);
                $this->level = $parent ? ($parent->level + 1) : 0;
            } else {
                $this->level = 0;
            }

            // Reorder logic: shift orders for siblings
            // Get all COAs with the same parent and order >= new order
            COA::where('parent_code', $this->parent_code)
                ->where('order', '>=', $this->order)
                ->when($this->isEditing, function ($query) {
                    // Exclude current COA when editing
                    $query->where('id', '!=', $this->coaId);
                })
                ->increment('order'); // Add +1 to all affected orders

            $data = [
                'code' => $this->code,
                'name' => $this->name,
                'parent_code' => $this->parent_code,
                'type' => $this->type,
                'is_active' => $this->is_active,
                'is_leaf_account' => $this->is_leaf_account,
                'order' => $this->order,
                'level' => $this->level
            ];

            if ($this->isEditing) {
                // Update existing COA
                $coa = COA::findOrFail($this->coaId);
                $coa->update($data);

                $message = "Chart of Account '{$coa->name}' has been updated successfully.";
                $action = 'coa_updated';
                
            } else {
                // Create new COA
                $coa = COA::create($data);

                $message = "Chart of Account '{$coa->name}' has been created successfully.";
                $action = 'coa_created';
            }

            // Log the activity
            $logger = new ActivityLogger(storage_path('logs/activity'));
            $logger->log([
                'level' => 'info',
                'category' => 'coa_management',
                'message' => $this->isEditing ? 'Chart of Account updated successfully' : 'Chart of Account created successfully',
                'meta' => [
                    'action' => $action,
                    'coa_id' => $coa->id,
                    'coa_code' => $coa->code,
                    'coa_name' => $coa->name,
                    'coa_type' => $coa->type,
                    'user_id' => Auth::id(),
                    'user_name' => Auth::user()->name
                ]
            ]);

            DB::commit();

            $this->dispatch('showAlert', [
                'type' => 'success',
                'message' => $message
            ]);

            $this->dispatch('refreshCoaList');
            $this->closeModal();

        } catch (\Throwable $th) {
            DB::rollBack();
            
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Failed to save Chart of Account.'. $th->getMessage()
            ]);
        }
    }

    /**
     * Close modal and reset form
     */
    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    /**
     * Reset form to initial state
     */
    private function resetForm()
    {
        $this->coaId = null;
        $this->code = '';
        $this->name = '';
        $this->parent_code = null;
        $this->type = 'aktiva';
        $this->is_active = true;
        $this->is_leaf_account = false;
        $this->order = 1;
        $this->level = 0;
        $this->isEditing = false;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    /**
     * Get parent options for dropdown
     */
    public function getParentOptionsProperty()
    {
        return COA::when($this->isEditing, function ($query) {
                // Exclude current COA and its descendants when editing
                $query->where('id', '!=', $this->coaId);
            })
            ->orderBy('code')
            ->get();
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