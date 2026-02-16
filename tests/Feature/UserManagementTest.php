<?php

namespace Tests\Feature;

use App\Livewire\UserManagement\UserForm;
use App\Livewire\UserManagement\UserList;
use App\Models\BusinessUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Role $superadminRole;
    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadminRole = Role::create(['name' => 'superadmin']);
        $this->adminRole = Role::create(['name' => 'admin']);

        $this->user = User::withoutEvents(function () {
            return User::factory()->create();
        });
        $this->user->assignRole('superadmin');
        $this->actingAs($this->user);
    }

    // ==================== PAGE ACCESS TESTS ====================

    /** @test */
    public function user_page_is_accessible()
    {
        $response = $this->get(route('user.index'));
        $response->assertStatus(200);
        $response->assertSee('User');
    }

    /** @test */
    public function guest_cannot_access_user_page()
    {
        auth()->logout();
        $this->get(route('user.index'))->assertRedirect(route('login'));
    }

    // ==================== USER LIST TESTS ====================

    /** @test */
    public function user_list_renders_successfully()
    {
        Livewire::test(UserList::class)
            ->assertStatus(200);
    }

    /** @test */
    public function user_list_shows_users()
    {
        Livewire::test(UserList::class)
            ->assertSee($this->user->name);
    }

    /** @test */
    public function user_list_can_search()
    {
        $other = User::withoutEvents(function () {
            return User::factory()->create(['name' => 'Specific User Name']);
        });

        Livewire::test(UserList::class)
            ->set('search', 'Specific User')
            ->assertSee('Specific User Name')
            ->assertDontSee($this->user->name);
    }

    /** @test */
    public function user_list_can_filter_by_role()
    {
        $admin = User::withoutEvents(function () {
            return User::factory()->create(['name' => 'Admin User']);
        });
        $admin->assignRole('admin');

        Livewire::test(UserList::class)
            ->set('filterRole', 'admin')
            ->assertSee('Admin User')
            ->assertDontSee($this->user->name);
    }

    /** @test */
    public function user_list_can_filter_by_unit()
    {
        $unit = BusinessUnit::withoutEvents(function () {
            return BusinessUnit::create(['code' => 'UNT-001', 'name' => 'Test Unit', 'is_active' => true]);
        });

        $unitUser = User::withoutEvents(function () use ($unit) {
            return User::factory()->create(['name' => 'Unit User', 'business_unit_id' => $unit->id]);
        });

        Livewire::test(UserList::class)
            ->set('filterUnit', $unit->id)
            ->assertSee('Unit User')
            ->assertDontSee($this->user->name);
    }

    /** @test */
    public function user_list_can_filter_by_status()
    {
        $inactive = User::withoutEvents(function () {
            return User::factory()->create(['name' => 'Inactive User', 'is_active' => false]);
        });

        Livewire::test(UserList::class)
            ->set('filterStatus', '0')
            ->assertSee('Inactive User')
            ->assertDontSee($this->user->name);
    }

    /** @test */
    public function user_list_can_toggle_status()
    {
        $other = User::withoutEvents(function () {
            return User::factory()->create(['is_active' => true]);
        });

        Livewire::test(UserList::class)
            ->call('toggleStatus', $other->id)
            ->assertDispatched('alert', type: 'success');

        $this->assertFalse($other->fresh()->is_active);
    }

    /** @test */
    public function user_list_prevents_deactivating_superadmin()
    {
        Livewire::test(UserList::class)
            ->call('toggleStatus', $this->user->id)
            ->assertDispatched('alert', type: 'error');

        $this->assertTrue($this->user->fresh()->is_active);
    }

    /** @test */
    public function user_list_can_delete_user()
    {
        $other = User::withoutEvents(function () {
            return User::factory()->create();
        });

        Livewire::test(UserList::class)
            ->call('deleteUser', $other->id)
            ->assertDispatched('alert', type: 'success');

        $this->assertSoftDeleted('users', ['id' => $other->id]);
    }

    /** @test */
    public function user_list_prevents_deleting_superadmin()
    {
        Livewire::test(UserList::class)
            ->call('deleteUser', $this->user->id)
            ->assertDispatched('alert', type: 'error');

        $this->assertDatabaseHas('users', ['id' => $this->user->id, 'deleted_at' => null]);
    }

    /** @test */
    public function user_list_prevents_deleting_self()
    {
        // Create another superadmin to be the actor
        $actor = User::withoutEvents(function () {
            return User::factory()->create();
        });
        $this->actingAs($actor);

        Livewire::test(UserList::class)
            ->call('deleteUser', $actor->id)
            ->assertDispatched('alert', type: 'error');
    }

    // ==================== USER FORM TESTS ====================

    /** @test */
    public function user_form_renders_successfully()
    {
        Livewire::test(UserForm::class)
            ->assertStatus(200);
    }

    /** @test */
    public function user_form_opens_and_closes_modal()
    {
        Livewire::test(UserForm::class)
            ->assertSet('showModal', false)
            ->dispatch('openUserModal')
            ->assertSet('showModal', true)
            ->call('closeModal')
            ->assertSet('showModal', false);
    }

    /** @test */
    public function user_form_can_create_user()
    {
        Livewire::test(UserForm::class)
            ->dispatch('openUserModal')
            ->set('name', 'New User')
            ->set('username', 'newuser')
            ->set('email', 'newuser@test.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('selectedRole', 'admin')
            ->set('is_active', true)
            ->call('save')
            ->assertDispatched('alert', type: 'success')
            ->assertDispatched('refreshUserList');

        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'username' => 'newuser',
            'email' => 'newuser@test.com',
        ]);

        $newUser = User::where('username', 'newuser')->first();
        $this->assertTrue($newUser->hasRole('admin'));
    }

    /** @test */
    public function user_form_validates_required_fields()
    {
        Livewire::test(UserForm::class)
            ->dispatch('openUserModal')
            ->set('name', '')
            ->set('username', '')
            ->set('email', '')
            ->set('password', '')
            ->set('selectedRole', '')
            ->call('save')
            ->assertHasErrors(['name', 'username', 'email', 'password', 'selectedRole']);
    }

    /** @test */
    public function user_form_validates_unique_username()
    {
        User::withoutEvents(function () {
            return User::factory()->create(['username' => 'existing']);
        });

        Livewire::test(UserForm::class)
            ->dispatch('openUserModal')
            ->set('name', 'Test')
            ->set('username', 'existing')
            ->set('email', 'test@test.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('selectedRole', 'admin')
            ->call('save')
            ->assertHasErrors(['username']);
    }

    /** @test */
    public function user_form_validates_password_confirmation()
    {
        Livewire::test(UserForm::class)
            ->dispatch('openUserModal')
            ->set('name', 'Test')
            ->set('username', 'testuser')
            ->set('email', 'test@test.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'different')
            ->set('selectedRole', 'admin')
            ->call('save')
            ->assertHasErrors(['password']);
    }

    /** @test */
    public function user_form_can_edit_user()
    {
        $other = User::withoutEvents(function () {
            return User::factory()->create(['name' => 'Original']);
        });
        $other->assignRole('admin');

        Livewire::test(UserForm::class)
            ->dispatch('editUser', id: $other->id)
            ->assertSet('isEditing', true)
            ->assertSet('name', 'Original')
            ->set('name', 'Updated')
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseHas('users', ['id' => $other->id, 'name' => 'Updated']);
    }

    /** @test */
    public function user_form_password_optional_on_edit()
    {
        $other = User::withoutEvents(function () {
            return User::factory()->create();
        });
        $other->assignRole('admin');

        $originalPassword = $other->password;

        Livewire::test(UserForm::class)
            ->dispatch('editUser', id: $other->id)
            ->set('name', 'Updated Name')
            ->set('password', '')
            ->set('password_confirmation', '')
            ->call('save')
            ->assertHasNoErrors(['password']);

        // Password should remain unchanged
        $this->assertEquals($originalPassword, $other->fresh()->password);
    }

    /** @test */
    public function user_form_assigns_business_unit()
    {
        $unit = BusinessUnit::withoutEvents(function () {
            return BusinessUnit::create(['code' => 'UNT-001', 'name' => 'Test Unit', 'is_active' => true]);
        });

        Livewire::test(UserForm::class)
            ->dispatch('openUserModal')
            ->set('name', 'Unit User')
            ->set('username', 'unituser')
            ->set('email', 'unit@test.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('selectedRole', 'admin')
            ->set('business_unit_id', $unit->id)
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $user = User::where('username', 'unituser')->first();
        $this->assertEquals($unit->id, $user->business_unit_id);
    }
}
