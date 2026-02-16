<?php

namespace Tests\Feature;

use App\Livewire\UserManagement\PermissionList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::withoutEvents(function () {
            return User::factory()->create();
        });
        $this->actingAs($this->user);
    }

    // ==================== PAGE ACCESS ====================

    /** @test */
    public function permission_page_is_accessible()
    {
        $response = $this->get(route('permission.index'));
        $response->assertStatus(200);
        $response->assertSee('Permission');
    }

    /** @test */
    public function guest_cannot_access_permission_page()
    {
        auth()->logout();
        $this->get(route('permission.index'))->assertRedirect(route('login'));
    }

    // ==================== PERMISSION LIST ====================

    /** @test */
    public function permission_list_renders_successfully()
    {
        Livewire::test(PermissionList::class)
            ->assertStatus(200);
    }

    /** @test */
    public function permission_list_shows_permissions()
    {
        Permission::create(['name' => 'user.view']);

        Livewire::test(PermissionList::class)
            ->assertSee('user.view');
    }

    /** @test */
    public function permission_list_can_search()
    {
        Permission::create(['name' => 'user.view']);
        Permission::create(['name' => 'journal.create']);

        Livewire::test(PermissionList::class)
            ->set('search', 'journal')
            ->assertSee('journal.create')
            ->assertDontSee('user.view');
    }

    /** @test */
    public function permission_list_groups_by_module()
    {
        Permission::create(['name' => 'user.view']);
        Permission::create(['name' => 'user.create']);
        Permission::create(['name' => 'journal.view']);

        $component = Livewire::test(PermissionList::class);
        $component->assertSee('user');
        $component->assertSee('journal');
    }

    /** @test */
    public function permission_list_can_create_permission()
    {
        Livewire::test(PermissionList::class)
            ->call('openModal')
            ->assertSet('showModal', true)
            ->set('permissionName', 'report.export')
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseHas('permissions', ['name' => 'report.export']);
    }

    /** @test */
    public function permission_list_validates_name_required()
    {
        Livewire::test(PermissionList::class)
            ->call('openModal')
            ->set('permissionName', '')
            ->call('save')
            ->assertHasErrors(['permissionName']);
    }

    /** @test */
    public function permission_list_can_edit_permission()
    {
        $perm = Permission::create(['name' => 'old.name']);

        Livewire::test(PermissionList::class)
            ->call('editPermission', $perm->id)
            ->assertSet('isEditing', true)
            ->assertSet('permissionName', 'old.name')
            ->set('permissionName', 'new.name')
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseHas('permissions', ['id' => $perm->id, 'name' => 'new.name']);
    }

    /** @test */
    public function permission_list_can_delete_permission()
    {
        $perm = Permission::create(['name' => 'temp.permission']);

        Livewire::test(PermissionList::class)
            ->call('deletePermission', $perm->id)
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseMissing('permissions', ['id' => $perm->id]);
    }

    /** @test */
    public function permission_list_prevents_deleting_permission_used_by_role()
    {
        $perm = Permission::create(['name' => 'protected.perm']);
        $role = Role::create(['name' => 'admin']);
        $role->givePermissionTo($perm);

        Livewire::test(PermissionList::class)
            ->call('deletePermission', $perm->id)
            ->assertDispatched('alert', type: 'error');

        $this->assertDatabaseHas('permissions', ['name' => 'protected.perm']);
    }

    /** @test */
    public function permission_list_modal_closes_properly()
    {
        Livewire::test(PermissionList::class)
            ->call('openModal')
            ->assertSet('showModal', true)
            ->call('closeModal')
            ->assertSet('showModal', false)
            ->assertSet('permissionName', '');
    }

    /** @test */
    public function permission_list_resets_form_on_new()
    {
        $perm = Permission::create(['name' => 'test.perm']);

        Livewire::test(PermissionList::class)
            ->call('editPermission', $perm->id)
            ->assertSet('permissionName', 'test.perm')
            ->call('closeModal')
            ->call('openModal')
            ->assertSet('permissionName', '')
            ->assertSet('permissionId', null)
            ->assertSet('isEditing', false);
    }
}
