<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\AuthController;
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