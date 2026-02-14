<?php

namespace Tests\Feature;

use App\Livewire\FiscalCorrection\FiscalCorrectionIndex;
use App\Models\COA;
use App\Models\FiscalCorrection;
use App\Models\Period;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FiscalCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Period $currentPeriod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::withoutEvents(function () {
            return User::factory()->create();
        });
        $this->actingAs($this->user);

        $now = Carbon::now();
        $this->currentPeriod = Period::create([
            'code' => $now->format('Ym'),
            'name' => $now->translatedFormat('F') . ' ' . $now->year,
            'start_date' => $now->copy()->startOfMonth()->format('Y-m-d'),
            'end_date' => $now->copy()->endOfMonth()->format('Y-m-d'),
            'year' => $now->year,
            'month' => $now->month,
            'is_active' => true,
            'is_closed' => false,
        ]);
    }

    // ======================================
    // Route & Page Tests
    // ======================================

    /** @test */
    public function fiscal_correction_page_accessible()
    {
        $response = $this->get(route('fiscal-correction'));
        $response->assertStatus(200);
        $response->assertSee('Koreksi Fiskal');
    }

    /** @test */
    public function fiscal_correction_page_requires_authentication()
    {
        auth()->logout();
        $response = $this->get(route('fiscal-correction'));
        $response->assertRedirect(route('login'));
    }

    // ======================================
    // Livewire Component Tests
    // ======================================

    /** @test */
    public function livewire_component_renders()
    {
        Livewire::test(FiscalCorrectionIndex::class)
            ->assertStatus(200)
            ->assertSee('Tidak ada koreksi fiskal');
    }

    /** @test */
    public function component_defaults_to_current_year()
    {
        $component = Livewire::test(FiscalCorrectionIndex::class);
        $this->assertEquals((int) date('Y'), $component->get('selectedYear'));
    }

    /** @test */
    public function component_shows_year_filter()
    {
        Livewire::test(FiscalCorrectionIndex::class)
            ->assertSee('Tahun');
    }

    // ======================================
    // CRUD: Create
    // ======================================

    /** @test */
    public function can_open_modal_for_new_correction()
    {
        Livewire::test(FiscalCorrectionIndex::class)
            ->call('openModal')
            ->assertSet('showModal', true)
            ->assertSet('isEditing', false);
    }

    /** @test */
    public function can_create_positive_beda_tetap_correction()
    {
        Livewire::test(FiscalCorrectionIndex::class)
            ->call('openModal')
            ->set('description', 'Biaya entertainment tanpa daftar nominatif')
            ->set('correction_type', 'positive')
            ->set('category', 'beda_tetap')
            ->set('amount', 5000000)
            ->set('notes', 'Tidak memenuhi syarat fiskal')
            ->call('save');

        $this->assertDatabaseHas('fiscal_corrections', [
            'year' => (int) date('Y'),
            'description' => 'Biaya entertainment tanpa daftar nominatif',
            'correction_type' => 'positive',
            'category' => 'beda_tetap',
            'amount' => 5000000,
        ]);
    }

    /** @test */
    public function can_create_negative_beda_waktu_correction()
    {
        Livewire::test(FiscalCorrectionIndex::class)
            ->call('openModal')
            ->set('description', 'Pendapatan bunga deposito')
            ->set('correction_type', 'negative')
            ->set('category', 'beda_waktu')
            ->set('amount', 2000000)
            ->call('save');

        $this->assertDatabaseHas('fiscal_corrections', [
            'correction_type' => 'negative',
            'category' => 'beda_waktu',
            'amount' => 2000000,
        ]);
    }

    /** @test */
    public function modal_closes_after_save()
    {
        Livewire::test(FiscalCorrectionIndex::class)
            ->call('openModal')
            ->set('description', 'Test item')
            ->set('amount', 1000000)
            ->call('save')
            ->assertSet('showModal', false);
    }

    /** @test */
    public function validates_required_fields()
    {
        Livewire::test(FiscalCorrectionIndex::class)
            ->call('openModal')
            ->set('description', '')
            ->set('amount', 0)
            ->call('save')
            ->assertHasErrors(['description', 'amount']);
    }

    /** @test */
    public function validates_amount_must_be_positive()
    {
        Livewire::test(FiscalCorrectionIndex::class)
            ->call('openModal')
            ->set('description', 'Test')
            ->set('amount', -100)
            ->call('save')
            ->assertHasErrors(['amount']);
    }

    /** @test */
    public function validates_correction_type_values()
    {
        Livewire::test(FiscalCorrectionIndex::class)
            ->call('openModal')
            ->set('description', 'Test')
            ->set('amount', 1000)
            ->set('correction_type', 'invalid')
            ->call('save')
            ->assertHasErrors(['correction_type']);
    }

    /** @test */
    public function validates_category_values()
    {
        Livewire::test(FiscalCorrectionIndex::class)
            ->call('openModal')
            ->set('description', 'Test')
            ->set('amount', 1000)
            ->set('category', 'invalid')
            ->call('save')
            ->assertHasErrors(['category']);
    }

    // ======================================
    // CRUD: Read / Display
    // ======================================

    /** @test */
    public function displays_correction_items_for_selected_year()
    {
        $year = (int) date('Y');
        FiscalCorrection::create([
            'year' => $year,
            'description' => 'Denda Pajak',
            'correction_type' => 'positive',
            'category' => 'beda_tetap',
            'amount' => 3000000,
        ]);
        FiscalCorrection::create([
            'year' => $year,
            'description' => 'Pendapatan Hibah',
            'correction_type' => 'negative',
            'category' => 'beda_tetap',
            'amount' => 1000000,
        ]);

        Livewire::test(FiscalCorrectionIndex::class)
            ->assertSee('Denda Pajak')
            ->assertSee('Pendapatan Hibah')
            ->assertSee('3.000.000')
            ->assertSee('1.000.000');
    }

    /** @test */
    public function shows_summary_totals()
    {
        $year = (int) date('Y');
        FiscalCorrection::create([
            'year' => $year,
            'description' => 'Koreksi Positif 1',
            'correction_type' => 'positive',
            'category' => 'beda_tetap',
            'amount' => 5000000,
        ]);
        FiscalCorrection::create([
            'year' => $year,
            'description' => 'Koreksi Positif 2',
            'correction_type' => 'positive',
            'category' => 'beda_waktu',
            'amount' => 3000000,
        ]);
        FiscalCorrection::create([
            'year' => $year,
            'description' => 'Koreksi Negatif 1',
            'correction_type' => 'negative',
            'category' => 'beda_tetap',
            'amount' => 2000000,
        ]);

        $component = Livewire::test(FiscalCorrectionIndex::class);
        $summary = $component->get('summary');

        $this->assertEquals(3, $summary['count']);
        $this->assertEquals(8000000, $summary['total_positive']);
        $this->assertEquals(2000000, $summary['total_negative']);
    }

    /** @test */
    public function filters_by_year()
    {
        // Create data for current year
        FiscalCorrection::create([
            'year' => (int) date('Y'),
            'description' => 'Current Year Item',
            'correction_type' => 'positive',
            'category' => 'beda_tetap',
            'amount' => 1000000,
        ]);

        // Create period and data for previous year
        $prevYear = (int) date('Y') - 1;
        Period::create([
            'code' => "{$prevYear}01",
            'name' => "Januari {$prevYear}",
            'start_date' => "{$prevYear}-01-01",
            'end_date' => "{$prevYear}-01-31",
            'year' => $prevYear,
            'month' => 1,
            'is_active' => true,
            'is_closed' => false,
        ]);
        FiscalCorrection::create([
            'year' => $prevYear,
            'description' => 'Previous Year Item',
            'correction_type' => 'negative',
            'category' => 'beda_tetap',
            'amount' => 500000,
        ]);

        // Default: shows current year
        Livewire::test(FiscalCorrectionIndex::class)
            ->assertSee('Current Year Item')
            ->assertDontSee('Previous Year Item');

        // Switch to previous year
        Livewire::test(FiscalCorrectionIndex::class)
            ->set('selectedYear', $prevYear)
            ->assertSee('Previous Year Item')
            ->assertDontSee('Current Year Item');
    }

    /** @test */
    public function shows_badges_for_types_and_categories()
    {
        FiscalCorrection::create([
            'year' => (int) date('Y'),
            'description' => 'Test Badge',
            'correction_type' => 'positive',
            'category' => 'beda_tetap',
            'amount' => 1000000,
        ]);

        Livewire::test(FiscalCorrectionIndex::class)
            ->assertSee('Positif (+)')
            ->assertSee('Beda Tetap');
    }

    // ======================================
    // CRUD: Update
    // ======================================

    /** @test */
    public function can_edit_correction()
    {
        $correction = FiscalCorrection::create([
            'year' => (int) date('Y'),
            'description' => 'Original Description',
            'correction_type' => 'positive',
            'category' => 'beda_tetap',
            'amount' => 1000000,
        ]);

        Livewire::test(FiscalCorrectionIndex::class)
            ->call('edit', $correction->id)
            ->assertSet('showModal', true)
            ->assertSet('isEditing', true)
            ->assertSet('editId', $correction->id)
            ->assertSet('description', 'Original Description')
            ->assertSet('correction_type', 'positive')
            ->assertSet('category', 'beda_tetap')
            ->assertSet('amount', 1000000);
    }

    /** @test */
    public function can_update_correction()
    {
        $correction = FiscalCorrection::create([
            'year' => (int) date('Y'),
            'description' => 'Original',
            'correction_type' => 'positive',
            'category' => 'beda_tetap',
            'amount' => 1000000,
        ]);

        Livewire::test(FiscalCorrectionIndex::class)
            ->call('edit', $correction->id)
            ->set('description', 'Updated Description')
            ->set('amount', 2000000)
            ->set('correction_type', 'negative')
            ->call('save');

        $this->assertDatabaseHas('fiscal_corrections', [
            'id' => $correction->id,
            'description' => 'Updated Description',
            'amount' => 2000000,
            'correction_type' => 'negative',
        ]);
    }

    // ======================================
    // CRUD: Delete
    // ======================================

    /** @test */
    public function can_delete_correction()
    {
        $correction = FiscalCorrection::create([
            'year' => (int) date('Y'),
            'description' => 'To Delete',
            'correction_type' => 'positive',
            'category' => 'beda_tetap',
            'amount' => 1000000,
        ]);

        Livewire::test(FiscalCorrectionIndex::class)
            ->call('delete', $correction->id);

        $this->assertSoftDeleted('fiscal_corrections', ['id' => $correction->id]);
    }

    /** @test */
    public function deleted_correction_not_shown_in_list()
    {
        $correction = FiscalCorrection::create([
            'year' => (int) date('Y'),
            'description' => 'Deleted Item',
            'correction_type' => 'positive',
            'category' => 'beda_tetap',
            'amount' => 1000000,
        ]);

        $correction->delete();

        Livewire::test(FiscalCorrectionIndex::class)
            ->assertDontSee('Deleted Item');
    }

    // ======================================
    // Model Tests
    // ======================================

    /** @test */
    public function fiscal_correction_model_scopes_work()
    {
        $year = (int) date('Y');
        FiscalCorrection::create(['year' => $year, 'description' => 'A', 'correction_type' => 'positive', 'category' => 'beda_tetap', 'amount' => 100]);
        FiscalCorrection::create(['year' => $year, 'description' => 'B', 'correction_type' => 'negative', 'category' => 'beda_waktu', 'amount' => 200]);
        FiscalCorrection::create(['year' => $year - 1, 'description' => 'C', 'correction_type' => 'positive', 'category' => 'beda_tetap', 'amount' => 300]);

        $this->assertEquals(2, FiscalCorrection::forYear($year)->count());
        $this->assertEquals(1, FiscalCorrection::forYear($year)->positive()->count());
        $this->assertEquals(1, FiscalCorrection::forYear($year)->negative()->count());
        $this->assertEquals(1, FiscalCorrection::forYear($year)->bedaTetap()->count());
        $this->assertEquals(1, FiscalCorrection::forYear($year)->bedaWaktu()->count());
    }

    /** @test */
    public function close_modal_resets_form()
    {
        $correction = FiscalCorrection::create([
            'year' => (int) date('Y'),
            'description' => 'Existing',
            'correction_type' => 'positive',
            'category' => 'beda_tetap',
            'amount' => 1000000,
        ]);

        Livewire::test(FiscalCorrectionIndex::class)
            ->call('edit', $correction->id)
            ->assertSet('isEditing', true)
            ->call('closeModal')
            ->assertSet('showModal', false)
            ->assertSet('isEditing', false)
            ->assertSet('editId', null)
            ->assertSet('description', '');
    }

    /** @test */
    public function empty_state_shown_when_no_corrections()
    {
        Livewire::test(FiscalCorrectionIndex::class)
            ->assertSee('Tidak ada koreksi fiskal untuk tahun');
    }
}
