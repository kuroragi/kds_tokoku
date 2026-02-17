<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ReportController;
use App\Livewire\Coa\CoaList;
use App\Mail\SendMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('login');
})->name('login')->middleware('guest');

Route::post('authenticate', [AuthController::class, 'authenticate'])->name('authenticate')->middleware('guest');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [PageController::class, 'dashboard'])->name('dashboard');

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