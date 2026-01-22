<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

Route::get('/settings', [App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
Route::patch('/settings', [App\Http\Controllers\SettingsController::class, 'update'])->name('settings.update');
