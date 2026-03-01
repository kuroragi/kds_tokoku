<?php

namespace App\Livewire\UserManagement;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleList extends Component
{
    public $search = '';

    // Form fields
    public bool $showModal = false;
    public ?int $roleId = null;
    public bool $isEditing = false;
    public $roleName = '';
    public array $selectedPermissions = [];

    protected $listeners = ['refreshRoleList' => '$refresh'];

    public function getRolesProperty()
    {
        $query = Role::with('permissions')->withCount('users');

        if ($this->search) {
            $query->where('name', 'like', "%{$this->search}%");
        }

        return $query->orderBy('name')->get();
    }

    public function getPermissionsProperty()
    {
        return Permission::orderBy('name')->get();
    }

    public function openModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function editRole($id)
    {
        $role = Role::with('permissions')->findOrFail($id);

        $this->roleId = $role->id;
        $this->isEditing = true;
        $this->roleName = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->roleId = null;
        $this->isEditing = false;
        $this->roleName = '';
        $this->selectedPermissions = [];
        $this->resetValidation();
    }

    public function save()
    {
        $this->validate([
            'roleName' => 'required|string|max:255',
        ], [
            'roleName.required' => 'Nama role wajib diisi.',
        ]);

        DB::beginTransaction();
        try {
            if ($this->isEditing) {
                $role = Role::findOrFail($this->roleId);

                if ($role->name === 'superadmin') {
                    $this->dispatch('alert', type: 'error', message: 'Role superadmin tidak dapat diubah namanya.');
                    DB::rollBack();
                    return;
                }

                $role->update(['name' => $this->roleName]);
            } else {
                $role = Role::create(['name' => $this->roleName, 'guard_name' => 'web']);
            }

            $role->syncPermissions($this->selectedPermissions);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatch('alert', type: 'error', message: "Gagal menyimpan role: {$e->getMessage()}");
            return;
        }

        $action = $this->isEditing ? 'diperbarui' : 'dibuat';
        $this->dispatch('alert', type: 'success', message: "Role '{$role->name}' berhasil {$action}.");
        $this->closeModal();
    }

    public function deleteRole($id)
    {
        $role = Role::findOrFail($id);

        if ($role->name === 'superadmin') {
            $this->dispatch('alert', type: 'error', message: 'Role superadmin tidak dapat dihapus.');
            return;
        }

        if ($role->users()->count() > 0) {
            $this->dispatch('alert', type: 'error', message: "Role '{$role->name}' tidak dapat dihapus karena masih digunakan oleh user.");
            return;
        }

        $name = $role->name;
        $role->delete();

        $this->dispatch('alert', type: 'success', message: "Role '{$name}' berhasil dihapus.");
    }

    public function render()
    {
        return view('livewire.user-management.role-list', [
            'roles' => $this->roles,
            'permissions' => $this->permissions,
        ]);
    }
}
