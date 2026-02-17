<?php

namespace Tests\Feature;

use App\Livewire\NameCard\EmployeeForm;
use App\Livewire\NameCard\EmployeeList;
use App\Models\BusinessUnit;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BusinessUnit $unit;
    protected Position $position;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'superadmin']);

        $this->user = User::withoutEvents(fn() => User::factory()->create());
        $this->user->assignRole('superadmin');
        $this->actingAs($this->user);

        $this->unit = BusinessUnit::withoutEvents(fn() => BusinessUnit::create([
            'code' => 'UNT-001', 'name' => 'Test Unit', 'is_active' => true,
        ]));

        $this->position = Position::withoutEvents(fn() => Position::create([
            'business_unit_id' => $this->unit->id,
            'code' => 'MGR',
            'name' => 'Manager',
            'is_active' => true,
        ]));
    }

    protected function createEmployee(array $overrides = []): Employee
    {
        return Employee::withoutEvents(function () use ($overrides) {
            return Employee::create(array_merge([
                'business_unit_id' => $this->unit->id,
                'position_id' => $this->position->id,
                'code' => 'EMP-001',
                'name' => 'John Doe',
                'is_active' => true,
            ], $overrides));
        });
    }

    // ==================== PAGE ACCESS TESTS ====================

    /** @test */
    public function employee_index_page_is_accessible()
    {
        $response = $this->get(route('employee.index'));
        $response->assertStatus(200);
        $response->assertSee('Karyawan');
    }

    /** @test */
    public function guest_cannot_access_employee_page()
    {
        auth()->logout();
        $this->get(route('employee.index'))->assertRedirect(route('login'));
    }

    // ==================== LIST COMPONENT TESTS ====================

    /** @test */
    public function employee_list_renders_successfully()
    {
        Livewire::test(EmployeeList::class)->assertStatus(200);
    }

    /** @test */
    public function employee_list_shows_employees()
    {
        $this->createEmployee(['code' => 'EMP-001', 'name' => 'John Doe']);
        $this->createEmployee(['code' => 'EMP-002', 'name' => 'Jane Doe']);

        Livewire::test(EmployeeList::class)
            ->assertSee('John Doe')
            ->assertSee('Jane Doe');
    }

    /** @test */
    public function employee_list_can_search()
    {
        $this->createEmployee(['code' => 'EMP-001', 'name' => 'John Doe']);
        $this->createEmployee(['code' => 'EMP-002', 'name' => 'Jane Doe']);

        Livewire::test(EmployeeList::class)
            ->set('search', 'John')
            ->assertSee('John Doe')
            ->assertDontSee('Jane Doe');
    }

    /** @test */
    public function employee_list_can_filter_by_unit()
    {
        $unit2 = BusinessUnit::withoutEvents(fn() =>
            BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true])
        );

        $this->createEmployee(['code' => 'EMP-001', 'name' => 'Employee Unit 1']);
        Employee::withoutEvents(fn() => Employee::create([
            'business_unit_id' => $unit2->id, 'code' => 'EMP-002', 'name' => 'Employee Unit 2', 'is_active' => true,
        ]));

        Livewire::test(EmployeeList::class)
            ->set('filterUnit', $this->unit->id)
            ->assertSee('Employee Unit 1')
            ->assertDontSee('Employee Unit 2');
    }

    /** @test */
    public function employee_list_can_filter_by_position()
    {
        $pos2 = Position::withoutEvents(fn() => Position::create([
            'business_unit_id' => $this->unit->id, 'code' => 'ADM', 'name' => 'Admin', 'is_active' => true,
        ]));

        $this->createEmployee(['code' => 'EMP-001', 'name' => 'Manager Person', 'position_id' => $this->position->id]);
        $this->createEmployee(['code' => 'EMP-002', 'name' => 'Admin Person', 'position_id' => $pos2->id]);

        Livewire::test(EmployeeList::class)
            ->set('filterPosition', $this->position->id)
            ->assertSee('Manager Person')
            ->assertDontSee('Admin Person');
    }

    /** @test */
    public function employee_list_can_filter_by_status()
    {
        $this->createEmployee(['code' => 'EMP-001', 'name' => 'Active Employee', 'is_active' => true]);
        $this->createEmployee(['code' => 'EMP-002', 'name' => 'Inactive Employee', 'is_active' => false]);

        Livewire::test(EmployeeList::class)
            ->set('filterStatus', '1')
            ->assertSee('Active Employee')
            ->assertDontSee('Inactive Employee');
    }

    /** @test */
    public function employee_list_can_sort()
    {
        $this->createEmployee(['code' => 'ZZZ', 'name' => 'Zebra']);
        $this->createEmployee(['code' => 'AAA', 'name' => 'Alpha']);

        Livewire::test(EmployeeList::class)
            ->call('sortBy', 'name')
            ->assertSeeInOrder(['Alpha', 'Zebra']);
    }

    /** @test */
    public function employee_list_can_toggle_status()
    {
        $employee = $this->createEmployee();

        Livewire::test(EmployeeList::class)
            ->call('toggleStatus', $employee->id)
            ->assertDispatched('alert');

        $this->assertFalse($employee->fresh()->is_active);
    }

    /** @test */
    public function employee_list_can_delete_employee()
    {
        $employee = $this->createEmployee();

        Livewire::test(EmployeeList::class)
            ->call('deleteEmployee', $employee->id)
            ->assertDispatched('alert');

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }

    // ==================== FORM COMPONENT TESTS ====================

    /** @test */
    public function employee_form_renders_successfully()
    {
        Livewire::test(EmployeeForm::class)->assertStatus(200);
    }

    /** @test */
    public function employee_form_can_open_modal()
    {
        Livewire::test(EmployeeForm::class)
            ->call('openEmployeeModal')
            ->assertSet('showModal', true)
            ->assertSet('isEditing', false);
    }

    /** @test */
    public function employee_form_can_create_employee()
    {
        Livewire::test(EmployeeForm::class)
            ->call('openEmployeeModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('position_id', $this->position->id)
            ->set('code', 'EMP-NEW')
            ->set('name', 'New Employee')
            ->set('phone', '08123456789')
            ->call('save')
            ->assertDispatched('alert', type: 'success')
            ->assertDispatched('refreshEmployeeList')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('employees', [
            'code' => 'EMP-NEW',
            'name' => 'New Employee',
            'position_id' => $this->position->id,
        ]);
    }

    /** @test */
    public function employee_form_can_create_without_position()
    {
        Livewire::test(EmployeeForm::class)
            ->call('openEmployeeModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('code', 'EMP-NOPOS')
            ->set('name', 'No Position')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employees', [
            'code' => 'EMP-NOPOS',
            'position_id' => null,
        ]);
    }

    /** @test */
    public function employee_form_can_edit_employee()
    {
        $employee = $this->createEmployee();

        Livewire::test(EmployeeForm::class)
            ->call('editEmployee', $employee->id)
            ->assertSet('showModal', true)
            ->assertSet('isEditing', true)
            ->assertSet('code', 'EMP-001')
            ->set('name', 'Updated Name')
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'name' => 'Updated Name']);
    }

    /** @test */
    public function employee_form_validates_required_fields()
    {
        Livewire::test(EmployeeForm::class)
            ->call('openEmployeeModal')
            ->set('business_unit_id', '')
            ->set('code', '')
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['business_unit_id', 'code', 'name']);
    }

    /** @test */
    public function employee_form_validates_unique_code_per_unit()
    {
        $this->createEmployee(['code' => 'EMP-001']);

        Livewire::test(EmployeeForm::class)
            ->call('openEmployeeModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('code', 'EMP-001')
            ->set('name', 'Duplicate')
            ->call('save')
            ->assertHasErrors(['code']);
    }

    /** @test */
    public function employee_form_allows_same_code_in_different_units()
    {
        $this->createEmployee(['code' => 'EMP-001']);

        $unit2 = BusinessUnit::withoutEvents(fn() =>
            BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true])
        );

        Livewire::test(EmployeeForm::class)
            ->call('openEmployeeModal')
            ->set('business_unit_id', $unit2->id)
            ->set('code', 'EMP-001')
            ->set('name', 'Same Code Different Unit')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('employees', 2);
    }

    /** @test */
    public function employee_form_can_close_modal()
    {
        Livewire::test(EmployeeForm::class)
            ->call('openEmployeeModal')
            ->assertSet('showModal', true)
            ->call('closeModal')
            ->assertSet('showModal', false);
    }

    /** @test */
    public function employee_form_resets_position_on_unit_change()
    {
        Livewire::test(EmployeeForm::class)
            ->call('openEmployeeModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('position_id', $this->position->id)
            ->set('business_unit_id', 999)
            ->assertSet('position_id', '');
    }

    // ==================== MODEL TESTS ====================

    /** @test */
    public function employee_belongs_to_business_unit()
    {
        $employee = $this->createEmployee();
        $this->assertInstanceOf(BusinessUnit::class, $employee->businessUnit);
    }

    /** @test */
    public function employee_belongs_to_position()
    {
        $employee = $this->createEmployee();
        $this->assertInstanceOf(Position::class, $employee->position);
    }

    /** @test */
    public function employee_active_scope_works()
    {
        $this->createEmployee(['code' => 'EMP-001', 'is_active' => true]);
        $this->createEmployee(['code' => 'EMP-002', 'is_active' => false]);

        $this->assertEquals(1, Employee::active()->where('business_unit_id', $this->unit->id)->count());
    }

    // ==================== BUSINESS UNIT SCOPING TESTS ====================

    /** @test */
    public function non_superadmin_list_only_sees_own_unit_employees()
    {
        $unit2 = BusinessUnit::withoutEvents(fn() => BusinessUnit::create(['code' => 'UNT-002', 'name' => 'Unit 2', 'is_active' => true]));

        $this->createEmployee(['code' => 'EMP-001', 'name' => 'Employee Unit 1']);
        Employee::withoutEvents(fn() => Employee::create([
            'business_unit_id' => $unit2->id, 'code' => 'EMP-002', 'name' => 'Employee Unit 2', 'is_active' => true,
        ]));

        $regularUser = User::withoutEvents(fn() => User::factory()->create(['business_unit_id' => $this->unit->id]));
        $this->actingAs($regularUser);

        Livewire::test(EmployeeList::class)
            ->assertSee('Employee Unit 1')
            ->assertDontSee('Employee Unit 2');
    }

    /** @test */
    public function non_superadmin_form_auto_fills_business_unit_id()
    {
        $regularUser = User::withoutEvents(fn() => User::factory()->create(['business_unit_id' => $this->unit->id]));
        $this->actingAs($regularUser);

        Livewire::test(EmployeeForm::class)
            ->call('openEmployeeModal')
            ->assertSet('business_unit_id', $this->unit->id);
    }

    /** @test */
    public function non_superadmin_save_uses_own_business_unit_id()
    {
        $regularUser = User::withoutEvents(fn() => User::factory()->create(['business_unit_id' => $this->unit->id]));
        $this->actingAs($regularUser);

        Livewire::test(EmployeeForm::class)
            ->call('openEmployeeModal')
            ->set('code', 'EMP-AUTO')
            ->set('name', 'Auto Employee')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employees', [
            'code' => 'EMP-AUTO',
            'business_unit_id' => $this->unit->id,
        ]);
    }
}
