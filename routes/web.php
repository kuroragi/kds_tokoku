<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('login');
})->name('login')->middleware('guest');

Route::post('authenticate', [AuthController::class, 'authenticate'])->name('authenticate')->middleware('guest');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
});