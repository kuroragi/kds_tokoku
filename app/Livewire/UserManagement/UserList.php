<?php

namespace App\Livewire\UserManagement;

use App\Models\BusinessUnit;
use App\Models\User;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class UserList extends Component
{
    public $search = '';
    public $filterRole = '';
    public $filterUnit = '';
    public $filterStatus = '';

    protected $listeners = ['refreshUserList' => '$refresh'];

    public function getUsersProperty()
    {
        $query = User::with(['roles', 'businessUnit']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('username', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        if ($this->filterRole) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $this->filterRole));
        }

        if ($this->filterUnit) {
            $query->where('business_unit_id', $this->filterUnit);
        }

        if ($this->filterStatus !== '') {
            $query->where('is_active', $this->filterStatus);
        }

        return $query->orderBy('name')->get();
    }

    public function getRolesProperty()
    {
        return Role::orderBy('name')->get();
    }

    public function getUnitsProperty()
    {
        return BusinessUnit::active()->orderBy('name')->get();
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);

        if ($user->hasRole('superadmin')) {
            $this->dispatch('alert', type: 'error', message: 'Tidak dapat menghapus Super Admin.');
            return;
        }

        if ($user->id === auth()->id()) {
            $this->dispatch('alert', type: 'error', message: 'Tidak dapat menghapus akun sendiri.');
            return;
        }

        $name = $user->name;
        $user->delete();

        $this->dispatch('alert', type: 'success', message: "User '{$name}' berhasil dihapus.");
    }

    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);

        if ($user->hasRole('superadmin') && $user->is_active) {
            $this->dispatch('alert', type: 'error', message: 'Tidak dapat menonaktifkan Super Admin.');
            return;
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->dispatch('alert', type: 'success', message: "User '{$user->name}' berhasil {$status}.");
    }

    public function render()
    {
        return view('livewire.user-management.user-list', [
            'users' => $this->users,
            'roles' => $this->roles,
            'units' => $this->units,
        ]);
    }
}
