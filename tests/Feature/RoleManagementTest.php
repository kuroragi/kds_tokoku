<?php

namespace Tests\Feature;

use App\Livewire\UserManagement\RoleList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'superadmin']);

        $this->user = User::withoutEvents(function () {
            return User::factory()->create();
        });
        $this->user->assignRole('superadmin');
        $this->actingAs($this->user);
    }

    // ==================== PAGE ACCESS ====================

    /** @test */
    public function role_page_is_accessible()
    {
        $response = $this->get(route('role.index'));
        $response->assertStatus(200);
        $response->assertSee('Role');
    }

    /** @test */
    public function guest_cannot_access_role_page()
    {
        auth()->logout();
        $this->get(route('role.index'))->assertRedirect(route('login'));
    }

    // ==================== ROLE LIST ====================

    /** @test */
    public function role_list_renders_successfully()
    {
        Livewire::test(RoleList::class)
            ->assertStatus(200);
    }

    /** @test */
    public function role_list_shows_roles()
    {
        Livewire::test(RoleList::class)
            ->assertSee('Superadmin');
    }

    /** @test */
    public function role_list_can_search()
    {
        Role::create(['name' => 'kasir']);

        Livewire::test(RoleList::class)
            ->set('search', 'kasir')
            ->assertSee('Kasir')
            ->assertDontSee('Superadmin');
    }

    /** @test */
    public function role_list_can_create_role()
    {
        Livewire::test(RoleList::class)
            ->call('openModal')
            ->assertSet('showModal', true)
            ->set('roleName', 'manager')
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseHas('roles', ['name' => 'manager']);
    }

    /** @test */
    public function role_list_validates_role_name_required()
    {
        Livewire::test(RoleList::class)
            ->call('openModal')
            ->set('roleName', '')
            ->call('save')
            ->assertHasErrors(['roleName']);
    }

    /** @test */
    public function role_list_can_edit_role()
    {
        $role = Role::create(['name' => 'old_name']);

        Livewire::test(RoleList::class)
            ->call('editRole', $role->id)
            ->assertSet('isEditing', true)
            ->assertSet('roleName', 'old_name')
            ->set('roleName', 'new_name')
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'new_name']);
    }

    /** @test */
    public function role_list_prevents_renaming_superadmin()
    {
        $role = Role::where('name', 'superadmin')->first();

        Livewire::test(RoleList::class)
            ->call('editRole', $role->id)
            ->set('roleName', 'not_superadmin')
            ->call('save')
            ->assertDispatched('alert', type: 'error');

        $this->assertDatabaseHas('roles', ['name' => 'superadmin']);
    }

    /** @test */
    public function role_list_can_assign_permissions()
    {
        Permission::create(['name' => 'user.view']);
        Permission::create(['name' => 'user.create']);

        Livewire::test(RoleList::class)
            ->call('openModal')
            ->set('roleName', 'admin')
            ->set('selectedPermissions', ['user.view', 'user.create'])
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $role = Role::where('name', 'admin')->first();
        $this->assertTrue($role->hasPermissionTo('user.view'));
        $this->assertTrue($role->hasPermissionTo('user.create'));
    }

    /** @test */
    public function role_list_can_delete_role()
    {
        $role = Role::create(['name' => 'temp_role']);

        Livewire::test(RoleList::class)
            ->call('deleteRole', $role->id)
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    /** @test */
    public function role_list_prevents_deleting_superadmin()
    {
        $role = Role::where('name', 'superadmin')->first();

        Livewire::test(RoleList::class)
            ->call('deleteRole', $role->id)
            ->assertDispatched('alert', type: 'error');

        $this->assertDatabaseHas('roles', ['name' => 'superadmin']);
    }

    /** @test */
    public function role_list_prevents_deleting_role_with_users()
    {
        $role = Role::create(['name' => 'admin']);

        $other = User::withoutEvents(function () {
            return User::factory()->create();
        });
        $other->assignRole('admin');

        Livewire::test(RoleList::class)
            ->call('deleteRole', $role->id)
            ->assertDispatched('alert', type: 'error');

        $this->assertDatabaseHas('roles', ['name' => 'admin']);
    }

    /** @test */
    public function role_list_modal_closes_properly()
    {
        Livewire::test(RoleList::class)
            ->call('openModal')
            ->assertSet('showModal', true)
            ->call('closeModal')
            ->assertSet('showModal', false)
            ->assertSet('roleName', '')
            ->assertSet('selectedPermissions', []);
    }
}
