<?php

namespace App\Livewire\UserManagement;

use Livewire\Component;
use Spatie\Permission\Models\Permission;

class PermissionList extends Component
{
    public $search = '';

    // Form
    public bool $showModal = false;
    public ?int $permissionId = null;
    public bool $isEditing = false;
    public $permissionName = '';

    protected $listeners = ['refreshPermissionList' => '$refresh'];

    public function getPermissionsProperty()
    {
        $query = Permission::withCount('roles');

        if ($this->search) {
            $query->where('name', 'like', "%{$this->search}%");
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Group permissions by module (first part of name before dot)
     */
    public function getGroupedPermissionsProperty()
    {
        return $this->permissions->groupBy(function ($permission) {
            $parts = explode('.', $permission->name);
            return $parts[0] ?? 'general';
        });
    }

    public function openModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function editPermission($id)
    {
        $permission = Permission::findOrFail($id);

        $this->permissionId = $permission->id;
        $this->isEditing = true;
        $this->permissionName = $permission->name;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->permissionId = null;
        $this->isEditing = false;
        $this->permissionName = '';
        $this->resetValidation();
    }

    public function save()
    {
        $this->validate([
            'permissionName' => 'required|string|max:255',
        ], [
            'permissionName.required' => 'Nama permission wajib diisi.',
        ]);

        if ($this->isEditing) {
            $permission = Permission::findOrFail($this->permissionId);
            $permission->update(['name' => $this->permissionName]);
        } else {
            $permission = Permission::create(['name' => $this->permissionName, 'guard_name' => 'web']);
        }

        $action = $this->isEditing ? 'diperbarui' : 'dibuat';
        $this->dispatch('alert', type: 'success', message: "Permission '{$permission->name}' berhasil {$action}.");
        $this->closeModal();
    }

    public function deletePermission($id)
    {
        $permission = Permission::findOrFail($id);

        if ($permission->roles()->count() > 0) {
            $this->dispatch('alert', type: 'error', message: "Permission '{$permission->name}' tidak dapat dihapus karena masih digunakan oleh role.");
            return;
        }

        $name = $permission->name;
        $permission->delete();

        $this->dispatch('alert', type: 'success', message: "Permission '{$name}' berhasil dihapus.");
    }

    public function render()
    {
        return view('livewire.user-management.permission-list', [
            'permissions' => $this->permissions,
            'groupedPermissions' => $this->groupedPermissions,
        ]);
    }
}
