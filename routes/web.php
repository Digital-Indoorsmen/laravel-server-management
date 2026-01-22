<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard', [
        'pendingServers' => \App\Models\Server::whereNull('setup_completed_at')->get(),
    ]);
})->name('dashboard');

Route::get('/settings', [App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
Route::patch('/settings', [App\Http\Controllers\SettingsController::class, 'update'])->name('settings.update');

Route::get('/setup/{token}', [App\Http\Controllers\SetupScriptController::class, 'show'])->name('setup.script');
Route::post('/setup/{token}/callback', [App\Http\Controllers\SetupScriptController::class, 'callback'])->name('setup.callback')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]); // Allow callback from script
