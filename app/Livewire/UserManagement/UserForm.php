<?php

namespace App\Livewire\UserManagement;

use App\Models\BusinessUnit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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

        DB::beginTransaction();
        try {
            if ($this->isEditing) {
                $user = User::findOrFail($this->userId);
                $user->update($data);
            } else {
                // Auto-verify team members added by existing users
                $data['email_verified_at'] = now();
                $data['skip_email_verification'] = true;

                // Non-superadmin: force same business_unit_id
                $authUser = auth()->user();
                if (!$authUser->hasRole('superadmin')) {
                    $data['business_unit_id'] = $authUser->business_unit_id;
                }

                $user = User::create($data);
            }

            // Sync role
            $user->syncRoles([$this->selectedRole]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatch('alert', type: 'error', message: "Gagal menyimpan user: {$e->getMessage()}");
            return;
        }

        $action = $this->isEditing ? 'diperbarui' : 'dibuat';
        $this->dispatch('alert', type: 'success', message: "User '{$user->name}' berhasil {$action}.");
        $this->dispatch('refreshUserList');
        $this->closeModal();
    }

    public function getRolesProperty()
    {
        $query = Role::orderBy('name');
        // Non-superadmin can't assign superadmin role
        if (!auth()->user()->hasRole('superadmin')) {
            $query->where('name', '!=', 'superadmin');
        }
        return $query->get();
    }

    public function getUnitsProperty()
    {
        $user = auth()->user();
        // Non-superadmin: only their own business unit
        if (!$user->hasRole('superadmin')) {
            return BusinessUnit::where('id', $user->business_unit_id)->get();
        }
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
