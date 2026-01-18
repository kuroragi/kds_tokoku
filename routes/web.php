<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageController;
use App\Livewire\Coa\CoaList;
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

    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
});