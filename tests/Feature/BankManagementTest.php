<?php

namespace Tests\Feature;

use App\Livewire\Bank\BankAccountForm;
use App\Livewire\Bank\BankAccountList;
use App\Livewire\Bank\BankForm;
use App\Livewire\Bank\BankList;
use App\Livewire\Bank\FundTransferForm;
use App\Livewire\Bank\FundTransferList;
use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\BankFeeMatrix;
use App\Models\BusinessUnit;
use App\Models\CashAccount;
use App\Models\FundTransfer;
use App\Models\User;
use App\Services\BankService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BankManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BusinessUnit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'superadmin']);

        $this->user = User::withoutEvents(function () {
            return User::factory()->create();
        });
        $this->user->assignRole('superadmin');
        $this->actingAs($this->user);

        $this->unit = BusinessUnit::withoutEvents(function () {
            return BusinessUnit::create([
                'code' => 'UNT-001',
                'name' => 'Test Unit',
                'is_active' => true,
            ]);
        });
    }

    // ─── Helper Methods ───

    protected function createBank(array $overrides = []): Bank
    {
        return Bank::withoutEvents(function () use ($overrides) {
            return Bank::create(array_merge([
                'code' => 'BCA',
                'name' => 'Bank Central Asia',
                'swift_code' => 'CENAIDJA',
                'is_active' => true,
            ], $overrides));
        });
    }

    protected function createBankAccount(Bank $bank, array $overrides = []): BankAccount
    {
        return BankAccount::withoutEvents(function () use ($bank, $overrides) {
            return BankAccount::create(array_merge([
                'business_unit_id' => $this->unit->id,
                'bank_id' => $bank->id,
                'account_number' => '1234567890',
                'account_name' => 'PT Test',
                'initial_balance' => 1000000,
                'current_balance' => 1000000,
                'is_active' => true,
            ], $overrides));
        });
    }

    protected function createCashAccount(array $overrides = []): CashAccount
    {
        return CashAccount::withoutEvents(function () use ($overrides) {
            return CashAccount::create(array_merge([
                'business_unit_id' => $this->unit->id,
                'name' => 'Kas Utama',
                'initial_balance' => 5000000,
                'current_balance' => 5000000,
                'is_active' => true,
            ], $overrides));
        });
    }

    protected function createFeeMatrix(Bank $source, Bank $destination, array $overrides = []): BankFeeMatrix
    {
        return BankFeeMatrix::withoutEvents(function () use ($source, $destination, $overrides) {
            return BankFeeMatrix::create(array_merge([
                'source_bank_id' => $source->id,
                'destination_bank_id' => $destination->id,
                'transfer_type' => 'online',
                'fee' => 6500,
                'is_active' => true,
            ], $overrides));
        });
    }

    protected function createTransfer(array $overrides = []): FundTransfer
    {
        return FundTransfer::withoutEvents(function () use ($overrides) {
            return FundTransfer::create(array_merge([
                'business_unit_id' => $this->unit->id,
                'source_type' => 'cash',
                'source_bank_account_id' => null,
                'destination_type' => 'cash',
                'destination_bank_account_id' => null,
                'amount' => 500000,
                'admin_fee' => 0,
                'transfer_date' => now()->format('Y-m-d'),
                'reference_no' => 'TRF-001',
            ], $overrides));
        });
    }

    // ==================== PAGE ACCESS TESTS ====================

    /** @test */
    public function bank_index_page_is_accessible()
    {
        $response = $this->get(route('bank.index'));
        $response->assertStatus(200);
        $response->assertSee('Daftar Bank');
    }

    /** @test */
    public function bank_account_page_is_accessible()
    {
        $response = $this->get(route('bank-account.index'));
        $response->assertStatus(200);
        $response->assertSee('Rekening', false);
    }

    /** @test */
    public function fund_transfer_page_is_accessible()
    {
        $response = $this->get(route('fund-transfer.index'));
        $response->assertStatus(200);
        $response->assertSee('Transfer Dana');
    }

    /** @test */
    public function guest_cannot_access_bank_index_page()
    {
        auth()->logout();
        $this->get(route('bank.index'))->assertRedirect(route('login'));
    }

    /** @test */
    public function guest_cannot_access_bank_account_page()
    {
        auth()->logout();
        $this->get(route('bank-account.index'))->assertRedirect(route('login'));
    }

    /** @test */
    public function guest_cannot_access_fund_transfer_page()
    {
        auth()->logout();
        $this->get(route('fund-transfer.index'))->assertRedirect(route('login'));
    }

    // ==================== BANK LIST TESTS ====================

    /** @test */
    public function bank_list_renders_successfully()
    {
        Livewire::test(BankList::class)
            ->assertStatus(200);
    }

    /** @test */
    public function bank_list_shows_banks()
    {
        $this->createBank(['code' => 'BCA', 'name' => 'Bank Central Asia']);
        $this->createBank(['code' => 'BNI', 'name' => 'Bank Negara Indonesia']);

        Livewire::test(BankList::class)
            ->assertSee('Bank Central Asia')
            ->assertSee('Bank Negara Indonesia');
    }

    /** @test */
    public function bank_list_can_search()
    {
        $this->createBank(['code' => 'BCA', 'name' => 'Bank Central Asia']);
        $this->createBank(['code' => 'BNI', 'name' => 'Bank Negara Indonesia']);

        Livewire::test(BankList::class)
            ->set('search', 'Central')
            ->assertSee('Bank Central Asia')
            ->assertDontSee('Bank Negara Indonesia');
    }

    /** @test */
    public function bank_list_can_search_by_code()
    {
        $this->createBank(['code' => 'BCA', 'name' => 'Bank Central Asia']);
        $this->createBank(['code' => 'BNI', 'name' => 'Bank Negara Indonesia']);

        Livewire::test(BankList::class)
            ->set('search', 'BNI')
            ->assertDontSee('Bank Central Asia')
            ->assertSee('Bank Negara Indonesia');
    }

    /** @test */
    public function bank_list_can_filter_by_status()
    {
        $this->createBank(['code' => 'BCA', 'name' => 'Bank Aktif', 'is_active' => true]);
        $this->createBank(['code' => 'BNI', 'name' => 'Bank Non-aktif', 'is_active' => false]);

        Livewire::test(BankList::class)
            ->set('filterStatus', '1')
            ->assertSee('Bank Aktif')
            ->assertDontSee('Bank Non-aktif');

        Livewire::test(BankList::class)
            ->set('filterStatus', '0')
            ->assertDontSee('Bank Aktif')
            ->assertSee('Bank Non-aktif');
    }

    /** @test */
    public function bank_list_can_sort()
    {
        $bank1 = $this->createBank(['code' => 'AAA', 'name' => 'Alpha Bank']);
        $bank2 = $this->createBank(['code' => 'ZZZ', 'name' => 'Zeta Bank']);

        $component = Livewire::test(BankList::class)
            ->call('sortBy', 'code');

        $this->assertEquals('code', $component->get('sortField'));
    }

    /** @test */
    public function bank_list_can_toggle_sort_direction()
    {
        Livewire::test(BankList::class)
            ->set('sortField', 'code')
            ->set('sortDirection', 'asc')
            ->call('sortBy', 'code')
            ->assertSet('sortDirection', 'desc');
    }

    /** @test */
    public function bank_list_can_toggle_status()
    {
        $bank = $this->createBank(['is_active' => true]);

        Livewire::test(BankList::class)
            ->call('toggleStatus', $bank->id)
            ->assertDispatched('alert');

        $this->assertFalse($bank->fresh()->is_active);
    }

    /** @test */
    public function bank_list_can_delete_bank_without_accounts()
    {
        $bank = $this->createBank();

        Livewire::test(BankList::class)
            ->call('deleteBank', $bank->id)
            ->assertDispatched('alert');

        $this->assertSoftDeleted('banks', ['id' => $bank->id]);
    }

    /** @test */
    public function bank_list_cannot_delete_bank_with_accounts()
    {
        $bank = $this->createBank();
        $this->createBankAccount($bank);

        Livewire::test(BankList::class)
            ->call('deleteBank', $bank->id)
            ->assertDispatched('alert', type: 'error');

        $this->assertDatabaseHas('banks', ['id' => $bank->id, 'deleted_at' => null]);
    }

    /** @test */
    public function bank_list_shows_fee_matrix()
    {
        $bca = $this->createBank(['code' => 'BCA', 'name' => 'BCA']);
        $bni = $this->createBank(['code' => 'BNI', 'name' => 'BNI']);
        $this->createFeeMatrix($bca, $bni, ['fee' => 6500]);

        Livewire::test(BankList::class)
            ->assertSee('6.500');
    }

    /** @test */
    public function bank_list_can_delete_fee_matrix()
    {
        $bca = $this->createBank(['code' => 'BCA', 'name' => 'BCA']);
        $bni = $this->createBank(['code' => 'BNI', 'name' => 'BNI']);
        $matrix = $this->createFeeMatrix($bca, $bni);

        Livewire::test(BankList::class)
            ->call('deleteFeeMatrix', $matrix->id)
            ->assertDispatched('alert');

        $this->assertDatabaseMissing('bank_fee_matrix', ['id' => $matrix->id]);
    }

    // ==================== BANK FORM TESTS ====================

    /** @test */
    public function bank_form_renders_successfully()
    {
        Livewire::test(BankForm::class)
            ->assertStatus(200);
    }

    /** @test */
    public function bank_form_can_open_bank_modal()
    {
        Livewire::test(BankForm::class)
            ->call('openBankModal')
            ->assertSet('showModal', true);
    }

    /** @test */
    public function bank_form_can_create_bank()
    {
        Livewire::test(BankForm::class)
            ->call('openBankModal')
            ->set('code', 'BCA')
            ->set('name', 'Bank Central Asia')
            ->set('swift_code', 'CENAIDJA')
            ->call('saveBank')
            ->assertDispatched('alert', type: 'success')
            ->assertDispatched('refreshBankList')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('banks', [
            'code' => 'BCA',
            'name' => 'Bank Central Asia',
            'swift_code' => 'CENAIDJA',
        ]);
    }

    /** @test */
    public function bank_form_can_edit_bank()
    {
        $bank = $this->createBank(['code' => 'BCA', 'name' => 'Old Name']);

        Livewire::test(BankForm::class)
            ->call('editBank', $bank->id)
            ->assertSet('isEditing', true)
            ->assertSet('code', 'BCA')
            ->set('name', 'New Name')
            ->call('saveBank')
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseHas('banks', [
            'id' => $bank->id,
            'name' => 'New Name',
        ]);
    }

    /** @test */
    public function bank_form_validates_required_code()
    {
        Livewire::test(BankForm::class)
            ->call('openBankModal')
            ->set('code', '')
            ->set('name', 'Test Bank')
            ->call('saveBank')
            ->assertHasErrors(['code']);
    }

    /** @test */
    public function bank_form_validates_required_name()
    {
        Livewire::test(BankForm::class)
            ->call('openBankModal')
            ->set('code', 'TST')
            ->set('name', '')
            ->call('saveBank')
            ->assertHasErrors(['name']);
    }

    /** @test */
    public function bank_form_validates_unique_code()
    {
        $this->createBank(['code' => 'BCA']);

        Livewire::test(BankForm::class)
            ->call('openBankModal')
            ->set('code', 'BCA')
            ->set('name', 'Another Bank')
            ->call('saveBank')
            ->assertHasErrors(['code']);
    }

    /** @test */
    public function bank_form_allows_same_code_on_edit()
    {
        $bank = $this->createBank(['code' => 'BCA', 'name' => 'BCA Bank']);

        Livewire::test(BankForm::class)
            ->call('editBank', $bank->id)
            ->set('name', 'BCA Updated')
            ->call('saveBank')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('banks', [
            'id' => $bank->id,
            'name' => 'BCA Updated',
        ]);
    }

    /** @test */
    public function bank_form_can_close_bank_modal()
    {
        Livewire::test(BankForm::class)
            ->call('openBankModal')
            ->assertSet('showModal', true)
            ->call('closeBankModal')
            ->assertSet('showModal', false);
    }

    /** @test */
    public function bank_form_can_open_fee_modal()
    {
        Livewire::test(BankForm::class)
            ->call('openFeeMatrixModal')
            ->assertSet('showFeeModal', true);
    }

    /** @test */
    public function bank_form_can_create_fee_matrix()
    {
        $bca = $this->createBank(['code' => 'BCA', 'name' => 'BCA']);
        $bni = $this->createBank(['code' => 'BNI', 'name' => 'BNI']);

        Livewire::test(BankForm::class)
            ->call('openFeeMatrixModal')
            ->set('source_bank_id', $bca->id)
            ->set('destination_bank_id', $bni->id)
            ->set('transfer_type', 'online')
            ->set('fee', 6500)
            ->call('saveFee')
            ->assertDispatched('alert', type: 'success')
            ->assertDispatched('refreshBankList')
            ->assertSet('showFeeModal', false);

        $this->assertDatabaseHas('bank_fee_matrix', [
            'source_bank_id' => $bca->id,
            'destination_bank_id' => $bni->id,
            'fee' => 6500,
        ]);
    }

    /** @test */
    public function bank_form_can_edit_fee_matrix()
    {
        $bca = $this->createBank(['code' => 'BCA', 'name' => 'BCA']);
        $bni = $this->createBank(['code' => 'BNI', 'name' => 'BNI']);
        $matrix = $this->createFeeMatrix($bca, $bni, ['fee' => 6500]);

        Livewire::test(BankForm::class)
            ->call('editFeeMatrix', $matrix->id)
            ->assertSet('isEditingFee', true)
            ->set('fee', 2500)
            ->call('saveFee')
            ->assertDispatched('alert', type: 'success');

        $this->assertDatabaseHas('bank_fee_matrix', [
            'id' => $matrix->id,
            'fee' => 2500,
        ]);
    }

    /** @test */
    public function bank_form_validates_fee_matrix_source()
    {
        Livewire::test(BankForm::class)
            ->call('openFeeMatrixModal')
            ->set('source_bank_id', '')
            ->set('destination_bank_id', 999)
            ->set('fee', 1000)
            ->call('saveFee')
            ->assertHasErrors(['source_bank_id']);
    }

    /** @test */
    public function bank_form_validates_fee_matrix_destination()
    {
        $bca = $this->createBank(['code' => 'BCA']);

        Livewire::test(BankForm::class)
            ->call('openFeeMatrixModal')
            ->set('source_bank_id', $bca->id)
            ->set('destination_bank_id', '')
            ->set('fee', 1000)
            ->call('saveFee')
            ->assertHasErrors(['destination_bank_id']);
    }

    /** @test */
    public function bank_form_validates_fee_minimum()
    {
        $bca = $this->createBank(['code' => 'BCA']);
        $bni = $this->createBank(['code' => 'BNI']);

        Livewire::test(BankForm::class)
            ->call('openFeeMatrixModal')
            ->set('source_bank_id', $bca->id)
            ->set('destination_bank_id', $bni->id)
            ->set('fee', -100)
            ->call('saveFee')
            ->assertHasErrors(['fee']);
    }

    /** @test */
    public function bank_form_can_close_fee_modal()
    {
        Livewire::test(BankForm::class)
            ->call('openFeeMatrixModal')
            ->assertSet('showFeeModal', true)
            ->call('closeFeeModal')
            ->assertSet('showFeeModal', false);
    }

    // ==================== BANK ACCOUNT LIST TESTS ====================

    /** @test */
    public function bank_account_list_renders_successfully()
    {
        Livewire::test(BankAccountList::class)
            ->assertStatus(200);
    }

    /** @test */
    public function bank_account_list_shows_bank_accounts()
    {
        $bank = $this->createBank();
        $this->createBankAccount($bank, ['account_name' => 'PT Test Account']);

        Livewire::test(BankAccountList::class)
            ->assertSee('PT Test Account');
    }

    /** @test */
    public function bank_account_list_shows_cash_accounts()
    {
        $this->createCashAccount(['name' => 'Kas Utama']);

        Livewire::test(BankAccountList::class)
            ->assertSee('Kas Utama');
    }

    /** @test */
    public function bank_account_list_can_search()
    {
        $bank = $this->createBank();
        $this->createBankAccount($bank, ['account_number' => '1111111', 'account_name' => 'Account Alpha']);
        $this->createBankAccount($bank, ['account_number' => '2222222', 'account_name' => 'Account Beta']);

        Livewire::test(BankAccountList::class)
            ->set('search', 'Alpha')
            ->assertSee('Account Alpha')
            ->assertDontSee('Account Beta');
    }

    /** @test */
    public function bank_account_list_can_search_by_account_number()
    {
        $bank = $this->createBank();
        $this->createBankAccount($bank, ['account_number' => '1111111', 'account_name' => 'Account A']);
        $this->createBankAccount($bank, ['account_number' => '2222222', 'account_name' => 'Account B']);

        Livewire::test(BankAccountList::class)
            ->set('search', '2222222')
            ->assertDontSee('Account A')
            ->assertSee('Account B');
    }

    /** @test */
    public function bank_account_list_can_filter_by_bank()
    {
        $bca = $this->createBank(['code' => 'BCA', 'name' => 'BCA']);
        $bni = $this->createBank(['code' => 'BNI', 'name' => 'BNI']);
        $this->createBankAccount($bca, ['account_number' => '1111', 'account_name' => 'BCA Account']);
        $this->createBankAccount($bni, ['account_number' => '2222', 'account_name' => 'BNI Account']);

        Livewire::test(BankAccountList::class)
            ->set('filterBank', $bca->id)
            ->assertSee('BCA Account')
            ->assertDontSee('BNI Account');
    }

    /** @test */
    public function bank_account_list_can_filter_by_status()
    {
        $bank = $this->createBank();
        $this->createBankAccount($bank, ['account_number' => '1111', 'account_name' => 'Active Account', 'is_active' => true]);
        $this->createBankAccount($bank, ['account_number' => '2222', 'account_name' => 'Inactive Account', 'is_active' => false]);

        Livewire::test(BankAccountList::class)
            ->set('filterStatus', '1')
            ->assertSee('Active Account')
            ->assertDontSee('Inactive Account');
    }

    /** @test */
    public function bank_account_list_can_sort()
    {
        Livewire::test(BankAccountList::class)
            ->call('sortBy', 'account_name')
            ->assertSet('sortField', 'account_name');
    }

    /** @test */
    public function bank_account_list_can_toggle_status()
    {
        $bank = $this->createBank();
        $account = $this->createBankAccount($bank, ['is_active' => true]);

        Livewire::test(BankAccountList::class)
            ->call('toggleStatus', $account->id)
            ->assertDispatched('alert');

        $this->assertFalse($account->fresh()->is_active);
    }

    /** @test */
    public function bank_account_list_can_delete_account_without_transfers()
    {
        $bank = $this->createBank();
        $account = $this->createBankAccount($bank);

        Livewire::test(BankAccountList::class)
            ->call('deleteAccount', $account->id)
            ->assertDispatched('alert');

        $this->assertSoftDeleted('bank_accounts', ['id' => $account->id]);
    }

    /** @test */
    public function bank_account_list_cannot_delete_account_with_source_transfers()
    {
        $bank = $this->createBank();
        $account = $this->createBankAccount($bank);
        $this->createTransfer([
            'source_type' => 'bank',
            'source_bank_account_id' => $account->id,
            'destination_type' => 'cash',
        ]);

        Livewire::test(BankAccountList::class)
            ->call('deleteAccount', $account->id)
            ->assertDispatched('alert', type: 'error');

        $this->assertDatabaseHas('bank_accounts', ['id' => $account->id, 'deleted_at' => null]);
    }

    /** @test */
    public function bank_account_list_cannot_delete_account_with_destination_transfers()
    {
        $bank = $this->createBank();
        $account = $this->createBankAccount($bank);
        $this->createTransfer([
            'source_type' => 'cash',
            'destination_type' => 'bank',
            'destination_bank_account_id' => $account->id,
        ]);

        Livewire::test(BankAccountList::class)
            ->call('deleteAccount', $account->id)
            ->assertDispatched('alert', type: 'error');

        $this->assertDatabaseHas('bank_accounts', ['id' => $account->id, 'deleted_at' => null]);
    }

    // ==================== BANK ACCOUNT FORM TESTS ====================

    /** @test */
    public function bank_account_form_renders_successfully()
    {
        Livewire::test(BankAccountForm::class)
            ->assertStatus(200);
    }

    /** @test */
    public function bank_account_form_can_open_modal()
    {
        Livewire::test(BankAccountForm::class)
            ->call('openBankAccountModal')
            ->assertSet('showModal', true);
    }

    /** @test */
    public function bank_account_form_can_create_account()
    {
        $bank = $this->createBank();

        Livewire::test(BankAccountForm::class)
            ->call('openBankAccountModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('bank_id', $bank->id)
            ->set('account_number', '9876543210')
            ->set('account_name', 'PT New Account')
            ->set('initial_balance', 2000000)
            ->call('save')
            ->assertDispatched('alert', type: 'success')
            ->assertDispatched('refreshBankAccountList')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('bank_accounts', [
            'bank_id' => $bank->id,
            'account_number' => '9876543210',
            'account_name' => 'PT New Account',
            'initial_balance' => 2000000,
            'current_balance' => 2000000,
        ]);
    }

    /** @test */
    public function bank_account_form_can_edit_account()
    {
        $bank = $this->createBank();
        $account = $this->createBankAccount($bank, [
            'account_name' => 'Old Name',
            'initial_balance' => 1000000,
            'current_balance' => 1500000,
        ]);

        Livewire::test(BankAccountForm::class)
            ->call('editBankAccount', $account->id)
            ->assertSet('isEditing', true)
            ->set('account_name', 'New Name')
            ->set('initial_balance', 2000000)
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $updated = $account->fresh();
        $this->assertEquals('New Name', $updated->account_name);
        $this->assertEquals(2000000, $updated->initial_balance);
        // current_balance should adjust: 1500000 + (2000000 - 1000000) = 2500000
        $this->assertEquals(2500000, $updated->current_balance);
    }

    /** @test */
    public function bank_account_form_validates_required_fields()
    {
        Livewire::test(BankAccountForm::class)
            ->call('openBankAccountModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('bank_id', '')
            ->set('account_number', '')
            ->set('account_name', '')
            ->call('save')
            ->assertHasErrors(['bank_id', 'account_number', 'account_name']);
    }

    /** @test */
    public function bank_account_form_validates_unique_account_number_per_bank_and_unit()
    {
        $bank = $this->createBank();
        $this->createBankAccount($bank, ['account_number' => '1234567890']);

        Livewire::test(BankAccountForm::class)
            ->call('openBankAccountModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('bank_id', $bank->id)
            ->set('account_number', '1234567890')
            ->set('account_name', 'Duplicate')
            ->set('initial_balance', 0)
            ->call('save')
            ->assertHasErrors(['account_number']);
    }

    /** @test */
    public function bank_account_form_can_close_modal()
    {
        Livewire::test(BankAccountForm::class)
            ->call('openBankAccountModal')
            ->assertSet('showModal', true)
            ->call('closeModal')
            ->assertSet('showModal', false);
    }

    // ─── Cash Account Form Tests ───

    /** @test */
    public function bank_account_form_can_open_cash_edit()
    {
        $cash = $this->createCashAccount();

        Livewire::test(BankAccountForm::class)
            ->call('editCashAccount', $cash->id)
            ->assertSet('showCashModal', true)
            ->assertSet('cash_name', 'Kas Utama');
    }

    /** @test */
    public function bank_account_form_can_save_cash_account()
    {
        $cash = $this->createCashAccount([
            'name' => 'Kas Utama',
            'initial_balance' => 5000000,
            'current_balance' => 7000000,
        ]);

        Livewire::test(BankAccountForm::class)
            ->call('editCashAccount', $cash->id)
            ->set('cash_name', 'Kas Toko')
            ->set('cash_initial_balance', 6000000)
            ->call('saveCashAccount')
            ->assertDispatched('alert', type: 'success')
            ->assertDispatched('refreshBankAccountList')
            ->assertSet('showCashModal', false);

        $updated = $cash->fresh();
        $this->assertEquals('Kas Toko', $updated->name);
        $this->assertEquals(6000000, $updated->initial_balance);
        // current_balance: 7000000 + (6000000 - 5000000) = 8000000
        $this->assertEquals(8000000, $updated->current_balance);
    }

    /** @test */
    public function bank_account_form_validates_cash_name_required()
    {
        $cash = $this->createCashAccount();

        Livewire::test(BankAccountForm::class)
            ->call('editCashAccount', $cash->id)
            ->set('cash_name', '')
            ->call('saveCashAccount')
            ->assertHasErrors(['cash_name']);
    }

    /** @test */
    public function bank_account_form_can_close_cash_modal()
    {
        $cash = $this->createCashAccount();

        Livewire::test(BankAccountForm::class)
            ->call('editCashAccount', $cash->id)
            ->assertSet('showCashModal', true)
            ->call('closeCashModal')
            ->assertSet('showCashModal', false);
    }

    // ==================== FUND TRANSFER LIST TESTS ====================

    /** @test */
    public function fund_transfer_list_renders_successfully()
    {
        Livewire::test(FundTransferList::class)
            ->assertStatus(200);
    }

    /** @test */
    public function fund_transfer_list_shows_transfers()
    {
        $this->createCashAccount();
        $bank = $this->createBank();
        $account = $this->createBankAccount($bank);
        $this->createTransfer([
            'source_type' => 'cash',
            'destination_type' => 'bank',
            'destination_bank_account_id' => $account->id,
            'reference_no' => 'REF-TEST-123',
        ]);

        Livewire::test(FundTransferList::class)
            ->assertSee('REF-TEST-123');
    }

    /** @test */
    public function fund_transfer_list_can_search_by_reference()
    {
        $this->createTransfer(['reference_no' => 'REF-001']);
        $this->createTransfer(['reference_no' => 'REF-002']);

        Livewire::test(FundTransferList::class)
            ->set('search', 'REF-001')
            ->assertSee('REF-001')
            ->assertDontSee('REF-002');
    }

    /** @test */
    public function fund_transfer_list_can_filter_by_source_type()
    {
        $this->createTransfer(['source_type' => 'cash', 'reference_no' => 'CASH-SRC']);
        $bank = $this->createBank();
        $account = $this->createBankAccount($bank);
        $this->createTransfer([
            'source_type' => 'bank',
            'source_bank_account_id' => $account->id,
            'reference_no' => 'BANK-SRC',
        ]);

        Livewire::test(FundTransferList::class)
            ->set('filterSourceType', 'cash')
            ->assertSee('CASH-SRC')
            ->assertDontSee('BANK-SRC');
    }

    /** @test */
    public function fund_transfer_list_can_filter_by_destination_type()
    {
        $this->createTransfer(['destination_type' => 'cash', 'reference_no' => 'CASH-DST']);
        $bank = $this->createBank();
        $account = $this->createBankAccount($bank);
        $this->createTransfer([
            'destination_type' => 'bank',
            'destination_bank_account_id' => $account->id,
            'reference_no' => 'BANK-DST',
        ]);

        Livewire::test(FundTransferList::class)
            ->set('filterDestType', 'bank')
            ->assertDontSee('CASH-DST')
            ->assertSee('BANK-DST');
    }

    /** @test */
    public function fund_transfer_list_can_sort()
    {
        Livewire::test(FundTransferList::class)
            ->call('sortBy', 'amount')
            ->assertSet('sortField', 'amount');
    }

    /** @test */
    public function fund_transfer_list_can_delete_transfer()
    {
        $cash = $this->createCashAccount(['current_balance' => 5000000]);
        $bank = $this->createBank();
        $destAccount = $this->createBankAccount($bank, ['current_balance' => 1000000]);

        $service = new BankService();
        $transfer = $service->createTransfer([
            'business_unit_id' => $this->unit->id,
            'source_type' => 'cash',
            'source_bank_account_id' => null,
            'destination_type' => 'bank',
            'destination_bank_account_id' => $destAccount->id,
            'amount' => 200000,
            'admin_fee' => 0,
            'transfer_date' => now()->format('Y-m-d'),
            'reference_no' => 'DEL-TEST',
        ]);

        Livewire::test(FundTransferList::class)
            ->call('deleteTransfer', $transfer->id)
            ->assertDispatched('alert');

        $this->assertSoftDeleted('fund_transfers', ['id' => $transfer->id]);

        // Balances should be restored
        $this->assertEquals(5000000, $cash->fresh()->current_balance);
        $this->assertEquals(1000000, $destAccount->fresh()->current_balance);
    }

    // ==================== FUND TRANSFER FORM TESTS ====================

    /** @test */
    public function fund_transfer_form_renders_successfully()
    {
        Livewire::test(FundTransferForm::class)
            ->assertStatus(200);
    }

    /** @test */
    public function fund_transfer_form_can_open_modal()
    {
        Livewire::test(FundTransferForm::class)
            ->call('openFundTransferModal')
            ->assertSet('showModal', true);
    }

    /** @test */
    public function fund_transfer_form_can_create_cash_to_bank_transfer()
    {
        $cash = $this->createCashAccount(['current_balance' => 5000000]);
        $bank = $this->createBank();
        $destAccount = $this->createBankAccount($bank, ['current_balance' => 1000000]);

        Livewire::test(FundTransferForm::class)
            ->call('openFundTransferModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('source_type', 'cash')
            ->set('destination_type', 'bank')
            ->set('destination_bank_account_id', $destAccount->id)
            ->set('amount', 500000)
            ->set('admin_fee', 0)
            ->set('transfer_date', now()->format('Y-m-d'))
            ->set('reference_no', 'TRF-NEW')
            ->call('save')
            ->assertDispatched('alert', type: 'success')
            ->assertDispatched('refreshFundTransferList')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('fund_transfers', [
            'source_type' => 'cash',
            'destination_type' => 'bank',
            'amount' => 500000,
            'reference_no' => 'TRF-NEW',
        ]);

        // Verify balances
        $this->assertEquals(4500000, $cash->fresh()->current_balance);
        $this->assertEquals(1500000, $destAccount->fresh()->current_balance);
    }

    /** @test */
    public function fund_transfer_form_can_create_bank_to_bank_transfer()
    {
        $bca = $this->createBank(['code' => 'BCA', 'name' => 'BCA']);
        $bni = $this->createBank(['code' => 'BNI', 'name' => 'BNI']);
        $srcAccount = $this->createBankAccount($bca, ['account_number' => '111', 'current_balance' => 3000000]);
        $dstAccount = $this->createBankAccount($bni, ['account_number' => '222', 'current_balance' => 1000000]);

        Livewire::test(FundTransferForm::class)
            ->call('openFundTransferModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('source_type', 'bank')
            ->set('source_bank_account_id', $srcAccount->id)
            ->set('destination_type', 'bank')
            ->set('destination_bank_account_id', $dstAccount->id)
            ->set('amount', 1000000)
            ->set('admin_fee', 6500)
            ->set('transfer_date', now()->format('Y-m-d'))
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        // Source: 3000000 - (1000000 + 6500) = 1993500
        $this->assertEquals(1993500, $srcAccount->fresh()->current_balance);
        // Dest: 1000000 + 1000000 = 2000000
        $this->assertEquals(2000000, $dstAccount->fresh()->current_balance);
    }

    /** @test */
    public function fund_transfer_form_can_create_bank_to_cash_transfer()
    {
        $cash = $this->createCashAccount(['current_balance' => 1000000]);
        $bank = $this->createBank();
        $srcAccount = $this->createBankAccount($bank, ['current_balance' => 5000000]);

        Livewire::test(FundTransferForm::class)
            ->call('openFundTransferModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('source_type', 'bank')
            ->set('source_bank_account_id', $srcAccount->id)
            ->set('destination_type', 'cash')
            ->set('amount', 300000)
            ->set('admin_fee', 0)
            ->set('transfer_date', now()->format('Y-m-d'))
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        // Source bank: 5000000 - 300000 = 4700000
        $this->assertEquals(4700000, $srcAccount->fresh()->current_balance);
        // Cash: 1000000 + 300000 = 1300000
        $this->assertEquals(1300000, $cash->fresh()->current_balance);
    }

    /** @test */
    public function fund_transfer_form_can_edit_transfer()
    {
        $cash = $this->createCashAccount(['current_balance' => 5000000]);
        $bank = $this->createBank();
        $destAccount = $this->createBankAccount($bank, ['current_balance' => 1000000]);

        $service = new BankService();
        $transfer = $service->createTransfer([
            'business_unit_id' => $this->unit->id,
            'source_type' => 'cash',
            'source_bank_account_id' => null,
            'destination_type' => 'bank',
            'destination_bank_account_id' => $destAccount->id,
            'amount' => 200000,
            'admin_fee' => 0,
            'transfer_date' => now()->format('Y-m-d'),
            'reference_no' => 'EDIT-TEST',
        ]);

        // After initial: cash=4800000, bank=1200000

        Livewire::test(FundTransferForm::class)
            ->call('editFundTransfer', $transfer->id)
            ->assertSet('isEditing', true)
            ->set('amount', 300000)
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        // Edit = delete old (reverse: cash=5000000, bank=1000000) + create new (cash=4700000, bank=1300000)
        $this->assertEquals(4700000, $cash->fresh()->current_balance);
        $this->assertEquals(1300000, $destAccount->fresh()->current_balance);
    }

    /** @test */
    public function fund_transfer_form_validates_required_fields()
    {
        Livewire::test(FundTransferForm::class)
            ->call('openFundTransferModal')
            ->set('business_unit_id', '')
            ->set('amount', 0)
            ->set('transfer_date', '')
            ->call('save')
            ->assertHasErrors(['business_unit_id', 'amount', 'transfer_date']);
    }

    /** @test */
    public function fund_transfer_form_validates_source_bank_account_when_bank_type()
    {
        Livewire::test(FundTransferForm::class)
            ->call('openFundTransferModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('source_type', 'bank')
            ->set('source_bank_account_id', '')
            ->set('destination_type', 'cash')
            ->set('amount', 100000)
            ->set('transfer_date', now()->format('Y-m-d'))
            ->call('save')
            ->assertHasErrors(['source_bank_account_id']);
    }

    /** @test */
    public function fund_transfer_form_validates_destination_bank_account_when_bank_type()
    {
        Livewire::test(FundTransferForm::class)
            ->call('openFundTransferModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('source_type', 'cash')
            ->set('destination_type', 'bank')
            ->set('destination_bank_account_id', '')
            ->set('amount', 100000)
            ->set('transfer_date', now()->format('Y-m-d'))
            ->call('save')
            ->assertHasErrors(['destination_bank_account_id']);
    }

    /** @test */
    public function fund_transfer_form_can_close_modal()
    {
        Livewire::test(FundTransferForm::class)
            ->call('openFundTransferModal')
            ->assertSet('showModal', true)
            ->call('closeModal')
            ->assertSet('showModal', false);
    }

    // ==================== BANK SERVICE TESTS ====================

    /** @test */
    public function service_can_create_cash_to_bank_transfer()
    {
        $cash = $this->createCashAccount(['current_balance' => 5000000]);
        $bank = $this->createBank();
        $dest = $this->createBankAccount($bank, ['current_balance' => 1000000]);

        $service = new BankService();
        $transfer = $service->createTransfer([
            'business_unit_id' => $this->unit->id,
            'source_type' => 'cash',
            'source_bank_account_id' => null,
            'destination_type' => 'bank',
            'destination_bank_account_id' => $dest->id,
            'amount' => 500000,
            'admin_fee' => 0,
            'transfer_date' => now()->format('Y-m-d'),
        ]);

        $this->assertDatabaseHas('fund_transfers', ['id' => $transfer->id]);
        $this->assertEquals(4500000, $cash->fresh()->current_balance);
        $this->assertEquals(1500000, $dest->fresh()->current_balance);
    }

    /** @test */
    public function service_can_create_bank_to_cash_transfer()
    {
        $cash = $this->createCashAccount(['current_balance' => 1000000]);
        $bank = $this->createBank();
        $src = $this->createBankAccount($bank, ['current_balance' => 3000000]);

        $service = new BankService();
        $service->createTransfer([
            'business_unit_id' => $this->unit->id,
            'source_type' => 'bank',
            'source_bank_account_id' => $src->id,
            'destination_type' => 'cash',
            'destination_bank_account_id' => null,
            'amount' => 200000,
            'admin_fee' => 2500,
            'transfer_date' => now()->format('Y-m-d'),
        ]);

        // Source: 3000000 - (200000 + 2500) = 2797500
        $this->assertEquals(2797500, $src->fresh()->current_balance);
        // Cash: 1000000 + 200000 = 1200000
        $this->assertEquals(1200000, $cash->fresh()->current_balance);
    }

    /** @test */
    public function service_can_create_bank_to_bank_transfer_with_admin_fee()
    {
        $bca = $this->createBank(['code' => 'BCA']);
        $bni = $this->createBank(['code' => 'BNI']);
        $src = $this->createBankAccount($bca, ['account_number' => '111', 'current_balance' => 2000000]);
        $dest = $this->createBankAccount($bni, ['account_number' => '222', 'current_balance' => 500000]);

        $service = new BankService();
        $service->createTransfer([
            'business_unit_id' => $this->unit->id,
            'source_type' => 'bank',
            'source_bank_account_id' => $src->id,
            'destination_type' => 'bank',
            'destination_bank_account_id' => $dest->id,
            'amount' => 1000000,
            'admin_fee' => 6500,
            'transfer_date' => now()->format('Y-m-d'),
        ]);

        // Source: 2000000 - (1000000 + 6500) = 993500
        $this->assertEquals(993500, $src->fresh()->current_balance);
        // Dest: 500000 + 1000000 = 1500000
        $this->assertEquals(1500000, $dest->fresh()->current_balance);
    }

    /** @test */
    public function service_can_delete_transfer_and_reverse_balance()
    {
        $cash = $this->createCashAccount(['current_balance' => 5000000]);
        $bank = $this->createBank();
        $dest = $this->createBankAccount($bank, ['current_balance' => 1000000]);

        $service = new BankService();
        $transfer = $service->createTransfer([
            'business_unit_id' => $this->unit->id,
            'source_type' => 'cash',
            'source_bank_account_id' => null,
            'destination_type' => 'bank',
            'destination_bank_account_id' => $dest->id,
            'amount' => 500000,
            'admin_fee' => 2500,
            'transfer_date' => now()->format('Y-m-d'),
        ]);

        // After create: cash=4497500, dest=1500000
        $this->assertEquals(4497500, $cash->fresh()->current_balance);
        $this->assertEquals(1500000, $dest->fresh()->current_balance);

        $service->deleteTransfer($transfer);

        // After delete: balances restored
        $this->assertEquals(5000000, $cash->fresh()->current_balance);
        $this->assertEquals(1000000, $dest->fresh()->current_balance);
        $this->assertSoftDeleted('fund_transfers', ['id' => $transfer->id]);
    }

    /** @test */
    public function service_get_balance_summary_returns_correct_data()
    {
        $cash = $this->createCashAccount(['current_balance' => 3000000]);
        $bank1 = $this->createBank(['code' => 'BCA']);
        $bank2 = $this->createBank(['code' => 'BNI']);
        $this->createBankAccount($bank1, ['account_number' => '111', 'current_balance' => 2000000]);
        $this->createBankAccount($bank2, ['account_number' => '222', 'current_balance' => 1000000]);

        $service = new BankService();
        $summary = $service->getBalanceSummary($this->unit->id);

        $this->assertCount(1, $summary['cash_accounts']);
        $this->assertCount(2, $summary['bank_accounts']);
        $this->assertEquals(3000000, $summary['total_cash']);
        $this->assertEquals(3000000, $summary['total_bank']);
        $this->assertEquals(6000000, $summary['total_all']);
    }

    /** @test */
    public function service_get_balance_summary_without_unit_returns_all()
    {
        $unit2 = BusinessUnit::withoutEvents(function () {
            return BusinessUnit::create([
                'code' => 'UNT-002',
                'name' => 'Unit Two',
                'is_active' => true,
            ]);
        });

        $this->createCashAccount(['current_balance' => 1000000]);
        CashAccount::withoutEvents(function () use ($unit2) {
            return CashAccount::create([
                'business_unit_id' => $unit2->id,
                'name' => 'Kas Unit 2',
                'initial_balance' => 2000000,
                'current_balance' => 2000000,
                'is_active' => true,
            ]);
        });

        $service = new BankService();
        $summary = $service->getBalanceSummary();

        $this->assertCount(2, $summary['cash_accounts']);
        $this->assertEquals(3000000, $summary['total_cash']);
    }

    // ==================== MODEL TESTS ====================

    /** @test */
    public function bank_model_has_correct_fillable()
    {
        $bank = $this->createBank();
        $this->assertNotNull($bank->id);
        $this->assertEquals('BCA', $bank->code);
        $this->assertEquals('Bank Central Asia', $bank->name);
    }

    /** @test */
    public function bank_model_has_bank_accounts_relationship()
    {
        $bank = $this->createBank();
        $this->createBankAccount($bank);

        $this->assertCount(1, $bank->bankAccounts);
    }

    /** @test */
    public function bank_model_has_active_scope()
    {
        $this->createBank(['code' => 'ACT', 'is_active' => true]);
        $this->createBank(['code' => 'INA', 'is_active' => false]);

        $this->assertCount(1, Bank::active()->get());
    }

    /** @test */
    public function bank_model_has_soft_delete()
    {
        $bank = $this->createBank();
        $bank->delete();

        $this->assertSoftDeleted('banks', ['id' => $bank->id]);
        $this->assertCount(0, Bank::all());
        $this->assertCount(1, Bank::withTrashed()->get());
    }

    /** @test */
    public function bank_model_has_fee_matrix_relationships()
    {
        $bca = $this->createBank(['code' => 'BCA']);
        $bni = $this->createBank(['code' => 'BNI']);
        $this->createFeeMatrix($bca, $bni);

        $this->assertCount(1, $bca->sourceFeeMatrix);
        $this->assertCount(1, $bni->destinationFeeMatrix);
    }

    /** @test */
    public function cash_account_model_has_correct_fillable()
    {
        $cash = $this->createCashAccount();
        $this->assertNotNull($cash->id);
        $this->assertEquals('Kas Utama', $cash->name);
        $this->assertEquals($this->unit->id, $cash->business_unit_id);
    }

    /** @test */
    public function cash_account_get_or_create_default_creates_if_not_exists()
    {
        $this->assertDatabaseCount('cash_accounts', 0);

        $cash = CashAccount::getOrCreateDefault($this->unit->id);

        $this->assertDatabaseCount('cash_accounts', 1);
        $this->assertEquals('Kas Utama', $cash->name);
        $this->assertEquals(0, $cash->initial_balance);
    }

    /** @test */
    public function cash_account_get_or_create_default_returns_existing()
    {
        $existing = $this->createCashAccount(['name' => 'My Kas']);

        $cash = CashAccount::getOrCreateDefault($this->unit->id);

        $this->assertDatabaseCount('cash_accounts', 1);
        $this->assertEquals($existing->id, $cash->id);
    }

    /** @test */
    public function cash_account_has_active_scope()
    {
        $this->createCashAccount(['is_active' => true]);

        $this->assertCount(1, CashAccount::active()->get());
    }

    /** @test */
    public function cash_account_has_by_business_unit_scope()
    {
        $this->createCashAccount();

        $unit2 = BusinessUnit::withoutEvents(function () {
            return BusinessUnit::create([
                'code' => 'UNT-002',
                'name' => 'Unit Two',
                'is_active' => true,
            ]);
        });

        CashAccount::withoutEvents(function () use ($unit2) {
            return CashAccount::create([
                'business_unit_id' => $unit2->id,
                'name' => 'Kas Unit 2',
                'initial_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
            ]);
        });

        $this->assertCount(1, CashAccount::byBusinessUnit($this->unit->id)->get());
    }

    /** @test */
    public function bank_account_model_has_display_label_accessor()
    {
        $bank = $this->createBank(['name' => 'BCA']);
        $account = $this->createBankAccount($bank, [
            'account_number' => '1234567890',
            'account_name' => 'PT Test',
        ]);

        $this->assertEquals('BCA - 1234567890 (PT Test)', $account->display_label);
    }

    /** @test */
    public function bank_account_model_has_relationships()
    {
        $bank = $this->createBank();
        $account = $this->createBankAccount($bank);

        $this->assertNotNull($account->bank);
        $this->assertNotNull($account->businessUnit);
        $this->assertEquals($bank->id, $account->bank->id);
    }

    /** @test */
    public function bank_account_model_has_scopes()
    {
        $bank = $this->createBank();
        $this->createBankAccount($bank, ['is_active' => true, 'account_number' => '111']);
        $this->createBankAccount($bank, ['is_active' => false, 'account_number' => '222']);

        $this->assertCount(1, BankAccount::active()->get());
        $this->assertCount(2, BankAccount::byBusinessUnit($this->unit->id)->get());
        $this->assertCount(2, BankAccount::byBank($bank->id)->get());
    }

    /** @test */
    public function bank_account_model_has_soft_delete()
    {
        $bank = $this->createBank();
        $account = $this->createBankAccount($bank);
        $account->delete();

        $this->assertSoftDeleted('bank_accounts', ['id' => $account->id]);
    }

    /** @test */
    public function bank_fee_matrix_model_has_correct_table()
    {
        $bca = $this->createBank(['code' => 'BCA']);
        $bni = $this->createBank(['code' => 'BNI']);
        $matrix = $this->createFeeMatrix($bca, $bni, ['fee' => 6500]);

        $this->assertEquals('bank_fee_matrix', $matrix->getTable());
        $this->assertEquals(6500, $matrix->fee);
    }

    /** @test */
    public function bank_fee_matrix_find_fee_returns_fee()
    {
        $bca = $this->createBank(['code' => 'BCA']);
        $bni = $this->createBank(['code' => 'BNI']);
        $this->createFeeMatrix($bca, $bni, ['fee' => 6500, 'transfer_type' => 'online']);

        $fee = BankFeeMatrix::findFee($bca->id, $bni->id, 'online');
        $this->assertEquals(6500, $fee);
    }

    /** @test */
    public function bank_fee_matrix_find_fee_returns_null_if_not_found()
    {
        $fee = BankFeeMatrix::findFee(999, 998, 'online');
        $this->assertNull($fee);
    }

    /** @test */
    public function bank_fee_matrix_get_transfer_types_returns_array()
    {
        $types = BankFeeMatrix::getTransferTypes();
        $this->assertIsArray($types);
        $this->assertArrayHasKey('online', $types);
        $this->assertArrayHasKey('bi-fast', $types);
        $this->assertArrayHasKey('rtgs', $types);
    }

    /** @test */
    public function bank_fee_matrix_has_active_scope()
    {
        $bca = $this->createBank(['code' => 'BCA']);
        $bni = $this->createBank(['code' => 'BNI']);
        $bri = $this->createBank(['code' => 'BRI']);
        $this->createFeeMatrix($bca, $bni, ['is_active' => true]);
        $this->createFeeMatrix($bca, $bri, ['is_active' => false]);

        $this->assertCount(1, BankFeeMatrix::active()->get());
    }

    /** @test */
    public function fund_transfer_model_has_correct_casts()
    {
        $cash = $this->createCashAccount();
        $service = new BankService();
        $transfer = $service->createTransfer([
            'business_unit_id' => $this->unit->id,
            'source_type' => 'cash',
            'source_bank_account_id' => null,
            'destination_type' => 'cash',
            'destination_bank_account_id' => null,
            'amount' => 100000,
            'admin_fee' => 2500,
            'transfer_date' => '2025-01-15',
        ]);

        $transfer = FundTransfer::find($transfer->id);
        $this->assertInstanceOf(\Carbon\Carbon::class, $transfer->transfer_date);
    }

    /** @test */
    public function fund_transfer_model_has_total_deducted_accessor()
    {
        $transfer = $this->createTransfer(['amount' => 100000, 'admin_fee' => 2500]);
        $this->assertEquals(102500, $transfer->total_deducted);
    }

    /** @test */
    public function fund_transfer_model_has_source_label_accessor()
    {
        $transfer = $this->createTransfer(['source_type' => 'cash']);
        $this->assertEquals('Kas', $transfer->source_label);

        $bank = $this->createBank(['name' => 'BCA']);
        $account = $this->createBankAccount($bank, ['account_number' => '111']);
        $transfer2 = $this->createTransfer([
            'source_type' => 'bank',
            'source_bank_account_id' => $account->id,
            'reference_no' => 'SRC-LBL',
        ]);
        $this->assertStringContains('BCA', $transfer2->source_label);
    }

    /** @test */
    public function fund_transfer_model_has_destination_label_accessor()
    {
        $transfer = $this->createTransfer(['destination_type' => 'cash']);
        $this->assertEquals('Kas', $transfer->destination_label);

        $bank = $this->createBank(['name' => 'BNI']);
        $account = $this->createBankAccount($bank, ['account_number' => '222']);
        $transfer2 = $this->createTransfer([
            'destination_type' => 'bank',
            'destination_bank_account_id' => $account->id,
            'reference_no' => 'DST-LBL',
        ]);
        $this->assertStringContains('BNI', $transfer2->destination_label);
    }

    /** @test */
    public function fund_transfer_model_has_by_business_unit_scope()
    {
        $this->createTransfer(['reference_no' => 'U1']);

        $unit2 = BusinessUnit::withoutEvents(function () {
            return BusinessUnit::create([
                'code' => 'UNT-002',
                'name' => 'Unit Two',
                'is_active' => true,
            ]);
        });
        FundTransfer::withoutEvents(function () use ($unit2) {
            return FundTransfer::create([
                'business_unit_id' => $unit2->id,
                'source_type' => 'cash',
                'destination_type' => 'cash',
                'amount' => 10000,
                'admin_fee' => 0,
                'transfer_date' => now()->format('Y-m-d'),
                'reference_no' => 'U2',
            ]);
        });

        $this->assertCount(1, FundTransfer::byBusinessUnit($this->unit->id)->get());
    }

    /** @test */
    public function fund_transfer_model_has_by_date_range_scope()
    {
        $this->createTransfer(['transfer_date' => '2025-01-15', 'reference_no' => 'JAN']);
        $this->createTransfer(['transfer_date' => '2025-03-20', 'reference_no' => 'MAR']);

        $result = FundTransfer::byDateRange('2025-01-01', '2025-01-31')->get();
        $this->assertCount(1, $result);
        $this->assertEquals('JAN', $result->first()->reference_no);
    }

    /** @test */
    public function fund_transfer_model_has_soft_delete()
    {
        $transfer = $this->createTransfer();
        $transfer->delete();

        $this->assertSoftDeleted('fund_transfers', ['id' => $transfer->id]);
    }

    // ==================== BUSINESS UNIT SCOPING TESTS ====================

    /** @test */
    public function bank_account_list_filters_by_business_unit()
    {
        $unit2 = BusinessUnit::withoutEvents(function () {
            return BusinessUnit::create([
                'code' => 'UNT-002',
                'name' => 'Unit Two',
                'is_active' => true,
            ]);
        });

        $bank = $this->createBank();
        $this->createBankAccount($bank, ['account_name' => 'Unit 1 Account']);
        BankAccount::withoutEvents(function () use ($bank, $unit2) {
            return BankAccount::create([
                'business_unit_id' => $unit2->id,
                'bank_id' => $bank->id,
                'account_number' => '9999999',
                'account_name' => 'Unit 2 Account',
                'initial_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
            ]);
        });

        Livewire::test(BankAccountList::class)
            ->set('filterUnit', $this->unit->id)
            ->assertSee('Unit 1 Account')
            ->assertDontSee('Unit 2 Account');
    }

    /** @test */
    public function fund_transfer_list_filters_by_business_unit()
    {
        $unit2 = BusinessUnit::withoutEvents(function () {
            return BusinessUnit::create([
                'code' => 'UNT-002',
                'name' => 'Unit Two',
                'is_active' => true,
            ]);
        });

        $this->createTransfer(['reference_no' => 'UNIT-1-TRF']);
        FundTransfer::withoutEvents(function () use ($unit2) {
            return FundTransfer::create([
                'business_unit_id' => $unit2->id,
                'source_type' => 'cash',
                'destination_type' => 'cash',
                'amount' => 10000,
                'admin_fee' => 0,
                'transfer_date' => now()->format('Y-m-d'),
                'reference_no' => 'UNIT-2-TRF',
            ]);
        });

        Livewire::test(FundTransferList::class)
            ->set('filterUnit', $this->unit->id)
            ->assertSee('UNIT-1-TRF')
            ->assertDontSee('UNIT-2-TRF');
    }

    // ==================== INTEGRATION FLOW TESTS ====================

    /** @test */
    public function full_flow_create_bank_create_account_transfer_and_verify_balances()
    {
        // Step 1: Create bank
        Livewire::test(BankForm::class)
            ->call('openBankModal')
            ->set('code', 'BCA')
            ->set('name', 'BCA')
            ->call('saveBank')
            ->assertDispatched('alert', type: 'success');

        $bank = Bank::where('code', 'BCA')->first();
        $this->assertNotNull($bank);

        // Step 2: Create cash account
        $cash = $this->createCashAccount(['current_balance' => 10000000]);

        // Step 3: Create bank account
        Livewire::test(BankAccountForm::class)
            ->call('openBankAccountModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('bank_id', $bank->id)
            ->set('account_number', '1234567890')
            ->set('account_name', 'PT Company')
            ->set('initial_balance', 5000000)
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        $account = BankAccount::where('account_number', '1234567890')->first();
        $this->assertNotNull($account);
        $this->assertEquals(5000000, $account->current_balance);

        // Step 4: Transfer from cash to bank
        Livewire::test(FundTransferForm::class)
            ->call('openFundTransferModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('source_type', 'cash')
            ->set('destination_type', 'bank')
            ->set('destination_bank_account_id', $account->id)
            ->set('amount', 2000000)
            ->set('admin_fee', 0)
            ->set('transfer_date', now()->format('Y-m-d'))
            ->call('save')
            ->assertDispatched('alert', type: 'success');

        // Verify balances
        $this->assertEquals(8000000, $cash->fresh()->current_balance);
        $this->assertEquals(7000000, $account->fresh()->current_balance);

        // Step 5: Verify transfer is shown in list
        $transfer = FundTransfer::first();
        $this->assertNotNull($transfer);
        $this->assertEquals(2000000, $transfer->amount);
    }

    /** @test */
    public function full_flow_fee_matrix_auto_fill_on_transfer()
    {
        $bca = $this->createBank(['code' => 'BCA', 'name' => 'BCA']);
        $bni = $this->createBank(['code' => 'BNI', 'name' => 'BNI']);
        $this->createFeeMatrix($bca, $bni, ['fee' => 6500, 'transfer_type' => 'online']);

        $srcAccount = $this->createBankAccount($bca, ['account_number' => '111', 'current_balance' => 5000000]);
        $dstAccount = $this->createBankAccount($bni, ['account_number' => '222', 'current_balance' => 1000000]);

        // The form should auto-fill admin_fee when both source and dest accounts are selected
        $component = Livewire::test(FundTransferForm::class)
            ->call('openFundTransferModal')
            ->set('business_unit_id', $this->unit->id)
            ->set('source_type', 'bank')
            ->set('destination_type', 'bank')
            ->set('source_bank_account_id', $srcAccount->id)
            ->set('destination_bank_account_id', $dstAccount->id);

        $this->assertEquals(6500, $component->get('admin_fee'));
    }

    /** @test */
    public function full_flow_multiple_transfers_balance_tracking()
    {
        $cash = $this->createCashAccount(['current_balance' => 10000000]);
        $bank = $this->createBank();
        $account = $this->createBankAccount($bank, ['current_balance' => 0]);

        $service = new BankService();

        // Transfer 1: Cash → Bank 3M
        $t1 = $service->createTransfer([
            'business_unit_id' => $this->unit->id,
            'source_type' => 'cash',
            'destination_type' => 'bank',
            'destination_bank_account_id' => $account->id,
            'amount' => 3000000,
            'admin_fee' => 0,
            'transfer_date' => now()->format('Y-m-d'),
        ]);

        $this->assertEquals(7000000, $cash->fresh()->current_balance);
        $this->assertEquals(3000000, $account->fresh()->current_balance);

        // Transfer 2: Bank → Cash 1M with 2500 fee
        $t2 = $service->createTransfer([
            'business_unit_id' => $this->unit->id,
            'source_type' => 'bank',
            'source_bank_account_id' => $account->id,
            'destination_type' => 'cash',
            'amount' => 1000000,
            'admin_fee' => 2500,
            'transfer_date' => now()->format('Y-m-d'),
        ]);

        // Bank: 3000000 - (1000000 + 2500) = 1997500
        $this->assertEquals(1997500, $account->fresh()->current_balance);
        // Cash: 7000000 + 1000000 = 8000000
        $this->assertEquals(8000000, $cash->fresh()->current_balance);

        // Delete transfer 2 → balances should revert
        $service->deleteTransfer($t2);
        $this->assertEquals(3000000, $account->fresh()->current_balance);
        $this->assertEquals(7000000, $cash->fresh()->current_balance);

        // Get summary
        $summary = $service->getBalanceSummary($this->unit->id);
        $this->assertEquals(7000000, $summary['total_cash']);
        $this->assertEquals(3000000, $summary['total_bank']);
        $this->assertEquals(10000000, $summary['total_all']);
    }

    // ─── Helper assertion ───

    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'."
        );
    }
}
