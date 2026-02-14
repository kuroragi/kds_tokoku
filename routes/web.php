<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageController;
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