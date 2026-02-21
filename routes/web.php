<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\ApArController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\OpnameController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SaldoController;
use App\Http\Controllers\VoucherController;
use App\Mail\SendMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

// ── Landing & Auth (Guest) ──
Route::get('/', [LandingController::class, 'index'])->name('landing');

Route::middleware('guest')->group(function () {
    Route::get('login', function () {
        return view('login');
    })->name('login');

    Route::post('authenticate', [AuthController::class, 'authenticate'])->name('authenticate');
    Route::get('register', [RegisterController::class, 'show'])->name('register');
    Route::post('register', [RegisterController::class, 'store'])->name('register.store');
});

// ── Google OAuth (accessible for both guest & auth) ──
Route::get('auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [PageController::class, 'dashboard'])->name('dashboard');

    // Voucher Redeem
    Route::post('voucher/redeem', [VoucherController::class, 'redeem'])->name('voucher.redeem');

    // configuration routes
    Route::get('coa', [AccountingController::class, 'coa'])->name('coa');
    Route::get('journal', [AccountingController::class, 'journal'])->name('journal');

    // accounting reports
    Route::get('general-ledger', [AccountingController::class, 'generalLedger'])->name('general-ledger');
    Route::get('general-ledger/{coa}', [AccountingController::class, 'generalLedgerDetail'])->name('general-ledger.detail');
    Route::get('adjustment-journal', [AccountingController::class, 'adjustmentJournal'])->name('adjustment-journal');
    Route::get('trial-balance', [AccountingController::class, 'trialBalance'])->name('trial-balance');
    Route::get('income-statement', [AccountingController::class, 'incomeStatement'])->name('income-statement');
    Route::get('adjusted-trial-balance', [AccountingController::class, 'adjustedTrialBalance'])->name('adjusted-trial-balance');
    Route::get('tax-closing', [AccountingController::class, 'taxClosing'])->name('tax-closing');
    Route::get('tax/report', [AccountingController::class, 'taxReport'])->name('tax-report.index');

    // Report Pages
    Route::get('report/final-balance-sheet', [AccountingController::class, 'finalBalanceSheet'])->name('report.final-balance-sheet');

    // PDF Report Downloads
    Route::prefix('report/pdf')->name('report.pdf.')->group(function () {
        Route::get('trial-balance', [ReportController::class, 'trialBalance'])->name('trial-balance');
        Route::get('balance-sheet', [ReportController::class, 'balanceSheet'])->name('balance-sheet');
        Route::get('income-statement', [ReportController::class, 'incomeStatement'])->name('income-statement');
        Route::get('adjusted-trial-balance', [ReportController::class, 'adjustedTrialBalance'])->name('adjusted-trial-balance');
        Route::get('general-ledger', [ReportController::class, 'generalLedger'])->name('general-ledger');
        Route::get('general-ledger/{coa}', [ReportController::class, 'generalLedgerDetail'])->name('general-ledger.detail');
        Route::get('final-balance-sheet', [ReportController::class, 'finalBalanceSheet'])->name('final-balance-sheet');
    });

    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    // Master Data
    Route::prefix('master')->group(function () {
        // Business Unit
        Route::get('business-unit', [MasterController::class, 'businessUnitIndex'])->name('business-unit.index');
        Route::get('business-unit/create', [MasterController::class, 'businessUnitCreate'])->name('business-unit.create');
        Route::get('business-unit/{unit}/edit', [MasterController::class, 'businessUnitEdit'])->name('business-unit.edit');

        // User Management
        Route::get('user', [MasterController::class, 'userIndex'])->name('user.index');

        // Role Management
        Route::get('role', [MasterController::class, 'roleIndex'])->name('role.index');

        // Permission Management
        Route::get('permission', [MasterController::class, 'permissionIndex'])->name('permission.index');

        // Stock Management
        Route::get('stock-category', [MasterController::class, 'stockCategoryIndex'])->name('stock-category.index');
        Route::get('category-group', [MasterController::class, 'categoryGroupIndex'])->name('category-group.index');
        Route::get('unit-of-measure', [MasterController::class, 'unitOfMeasureIndex'])->name('unit-of-measure.index');
        Route::get('stock', [MasterController::class, 'stockIndex'])->name('stock.index');

        // Jabatan (Position)
        Route::get('position', [MasterController::class, 'positionIndex'])->name('position.index');

        // Kartu Nama
        Route::get('employee', [MasterController::class, 'employeeIndex'])->name('employee.index');
        Route::get('customer', [MasterController::class, 'customerIndex'])->name('customer.index');
        Route::get('vendor', [MasterController::class, 'vendorIndex'])->name('vendor.index');
        Route::get('partner', [MasterController::class, 'partnerIndex'])->name('partner.index');
    });

    // Asset Management
    Route::prefix('asset')->group(function () {
        // Master Data
        Route::get('category', [AssetController::class, 'assetCategoryIndex'])->name('asset-category.index');
        Route::get('/', [AssetController::class, 'assetIndex'])->name('asset.index');

        // Transactions
        Route::get('depreciation', [AssetController::class, 'depreciationIndex'])->name('asset-depreciation.index');
        Route::get('transfer', [AssetController::class, 'transferIndex'])->name('asset-transfer.index');
        Route::get('disposal', [AssetController::class, 'disposalIndex'])->name('asset-disposal.index');
        Route::get('repair', [AssetController::class, 'repairIndex'])->name('asset-repair.index');

        // Reports
        Route::get('report/register', [AssetController::class, 'reportRegister'])->name('asset-report.register');
        Route::get('report/book-value', [AssetController::class, 'reportBookValue'])->name('asset-report.book-value');
        Route::get('report/depreciation', [AssetController::class, 'reportDepreciation'])->name('asset-report.depreciation');
        Route::get('report/history', [AssetController::class, 'reportHistory'])->name('asset-report.history');
    });

    // AP/AR (Hutang / Piutang)
    Route::prefix('apar')->group(function () {
        Route::get('payable', [ApArController::class, 'payableIndex'])->name('payable.index');
        Route::get('receivable', [ApArController::class, 'receivableIndex'])->name('receivable.index');

        // Reports
        Route::get('report/aging', [ApArController::class, 'reportAging'])->name('apar-report.aging');
        Route::get('report/outstanding', [ApArController::class, 'reportOutstanding'])->name('apar-report.outstanding');
        Route::get('report/payment-history', [ApArController::class, 'reportPaymentHistory'])->name('apar-report.payment-history');
    });

    // Payroll
    Route::prefix('payroll')->group(function () {
        Route::get('salary-component', [PayrollController::class, 'salaryComponentIndex'])->name('salary-component.index');
        Route::get('setting', [PayrollController::class, 'payrollSettingIndex'])->name('payroll-setting.index');
        Route::get('/', [PayrollController::class, 'payrollIndex'])->name('payroll.index');

        // Payroll Reports (before wildcard)
        Route::get('report/recap', [PayrollController::class, 'reportRecap'])->name('payroll-report.recap');
        Route::get('report/employee', [PayrollController::class, 'reportEmployee'])->name('payroll-report.employee');
        Route::get('report/bpjs', [PayrollController::class, 'reportBpjs'])->name('payroll-report.bpjs');

        Route::get('{payrollPeriod}', [PayrollController::class, 'payrollDetail'])->name('payroll.detail');
    });

    // Pinjaman Karyawan
    Route::prefix('loan')->group(function () {
        Route::get('/', [LoanController::class, 'loanIndex'])->name('employee-loan.index');
        Route::get('{loan}', [LoanController::class, 'loanDetail'])->name('employee-loan.detail');
    });

    // Saldo Management
    Route::prefix('saldo')->group(function () {
        Route::get('provider', [SaldoController::class, 'providerIndex'])->name('saldo-provider.index');
        Route::get('product', [SaldoController::class, 'productIndex'])->name('saldo-product.index');
        Route::get('topup', [SaldoController::class, 'topupIndex'])->name('saldo-topup.index');
        Route::get('transaction', [SaldoController::class, 'transactionIndex'])->name('saldo-transaction.index');
        Route::get('opening-balance', [SaldoController::class, 'openingBalanceIndex'])->name('opening-balance.index');
    });

    // Bank Management
    Route::prefix('bank')->group(function () {
        Route::get('/', [BankController::class, 'bankIndex'])->name('bank.index');
        Route::get('account', [BankController::class, 'accountIndex'])->name('bank-account.index');
        Route::get('transfer', [BankController::class, 'transferIndex'])->name('fund-transfer.index');
        Route::get('mutation', [BankController::class, 'mutationIndex'])->name('bank-mutation.index');
        Route::get('reconciliation', [BankController::class, 'reconciliationIndex'])->name('bank-reconciliation.index');
    });

    // Purchase Management
    Route::prefix('purchase')->group(function () {
        Route::get('order', [PurchaseController::class, 'purchaseOrderIndex'])->name('purchase-order.index');
        Route::get('/', [PurchaseController::class, 'purchaseIndex'])->name('purchase.index');
    });

    // Opname (Stock & Saldo)
    Route::prefix('opname')->group(function () {
        Route::get('stock', [OpnameController::class, 'stockOpnameIndex'])->name('stock-opname.index');
        Route::get('saldo', [OpnameController::class, 'saldoOpnameIndex'])->name('saldo-opname.index');
    });

    // Sales Management
    Route::prefix('sales')->group(function () {
        Route::get('/', [SalesController::class, 'salesIndex'])->name('sales.index');
    });

    // Warehouse Monitor
    Route::get('warehouse/monitor', function () {
        return view('pages.warehouse.monitor');
    })->name('warehouse.monitor');

    // Project / Job Order
    Route::get('project', [ProjectController::class, 'index'])->name('project.index');
});


Route::get('/mail-testing', function() {
    $details = [
        'subject' => 'Mail from KDS Tokoku',
        'content' => 'This is for testing email using smtp'
    ];
    
    try {
        $mail = Mail::to('uum1612@gmail.com')->queue(new SendMail($details));

        return 'Mail has been sent!';
    } catch (Exception $e) {
        // Log the error message
        return $e->getMessage();
    }
});