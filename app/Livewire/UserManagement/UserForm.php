<?php

namespace App\Livewire\UserManagement;

use App\Models\BusinessUnit;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class UserForm extends Component
{
    public bool $showModal = false;
    public ?int $userId = null;
    public bool $isEditing = false;

    // Fields
    public $name = '';
    public $username = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $business_unit_id = '';
    public $is_active = true;
    public $selectedRole = '';

    protected $listeners = ['openUserModal', 'editUser'];

    public function openUserModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function editUser($id)
    {
        $user = User::with('roles')->findOrFail($id);

        $this->userId = $user->id;
        $this->isEditing = true;
        $this->name = $user->name;
        $this->username = $user->username;
        $this->email = $user->email;
        $this->business_unit_id = $user->business_unit_id ?? '';
        $this->is_active = $user->is_active;
        $this->selectedRole = $user->roles->first()?->name ?? '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->userId = null;
        $this->isEditing = false;
        $this->name = '';
        $this->username = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->business_unit_id = '';
        $this->is_active = true;
        $this->selectedRole = '';
        $this->resetValidation();
    }

    protected function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($this->userId)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->userId)],
            'business_unit_id' => 'nullable|exists:business_units,id',
            'is_active' => 'boolean',
            'selectedRole' => 'required|exists:roles,name',
        ];

        if (!$this->isEditing) {
            $rules['password'] = 'required|string|min:8|confirmed';
        } else {
            $rules['password'] = 'nullable|string|min:8|confirmed';
        }

        return $rules;
    }

    protected $messages = [
        'name.required' => 'Nama wajib diisi.',
        'username.required' => 'Username wajib diisi.',
        'username.unique' => 'Username sudah digunakan.',
        'email.required' => 'Email wajib diisi.',
        'email.unique' => 'Email sudah digunakan.',
        'password.required' => 'Password wajib diisi.',
        'password.min' => 'Password minimal 8 karakter.',
        'password.confirmed' => 'Konfirmasi password tidak cocok.',
        'selectedRole.required' => 'Role wajib dipilih.',
    ];

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'business_unit_id' => $this->business_unit_id ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->isEditing) {
            $user = User::findOrFail($this->userId);
            $user->update($data);
        } else {
            $user = User::create($data);
        }

        // Sync role
        $user->syncRoles([$this->selectedRole]);

        $action = $this->isEditing ? 'diperbarui' : 'dibuat';
        $this->dispatch('alert', type: 'success', message: "User '{$user->name}' berhasil {$action}.");
        $this->dispatch('refreshUserList');
        $this->closeModal();
    }

    public function getRolesProperty()
    {
        return Role::orderBy('name')->get();
    }

    public function getUnitsProperty()
    {
        return BusinessUnit::active()->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.user-management.user-form', [
            'roles' => $this->roles,
            'units' => $this->units,
        ]);
    }
}
