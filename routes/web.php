<?php

use Illuminate\Support\Facades\Route;

Route::get('/setup/{token}', [App\Http\Controllers\SetupScriptController::class, 'show'])->name('setup.script');
Route::post('/setup/{token}/callback', [App\Http\Controllers\SetupScriptController::class, 'callback'])->name('setup.callback');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/dashboard', App\Http\Controllers\DashboardController::class)->name('dashboard');
    Route::get('/system', App\Http\Controllers\SystemController::class)->name('system.index');
    Route::post('/system/services/{service}/{action}', App\Http\Controllers\SystemServiceController::class)->name('system.services.control');
    Route::get('/sites', App\Http\Controllers\SiteCatalogController::class)->name('sites.catalog');
    Route::get('/databases', App\Http\Controllers\DatabaseController::class)->name('databases.index');
    Route::get('/software', function () {
        $server = \App\Models\Server::query()->first();
        if (! $server) {
            $server = \App\Models\Server::query()->create([
                'name' => 'Local Server',
                'ip_address' => '127.0.0.1',
                'hostname' => gethostname() ?: 'localhost',
                'os_version' => 'rocky_9',
                'status' => 'active',
                'web_server' => 'nginx',
            ]);
        }

        return redirect()->route('servers.software.index', $server);
    })->name('software.index');
    Route::get('/profile', App\Http\Controllers\ProfileController::class)->name('profile.show');

    Route::get('/settings', [App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings', [App\Http\Controllers\SettingsController::class, 'update'])->name('settings.update');

    Route::get('/ssh-keys', [App\Http\Controllers\SshKeyController::class, 'index'])->name('ssh-keys.index');
    Route::post('/ssh-keys', [App\Http\Controllers\SshKeyController::class, 'store'])->name('ssh-keys.store');
    Route::post('/ssh-keys/import', [App\Http\Controllers\SshKeyController::class, 'import'])->name('ssh-keys.import');
    Route::get('/ssh-keys/{sshKey}/download', [App\Http\Controllers\SshKeyController::class, 'download'])->name('ssh-keys.download');
    Route::delete('/ssh-keys/{sshKey}', [App\Http\Controllers\SshKeyController::class, 'destroy'])->name('ssh-keys.destroy');

    Route::get('/servers/{server}/test', [App\Http\Controllers\ServerController::class, 'testConnection'])->name('servers.test');
    Route::get('/servers/{server}/logs', [App\Http\Controllers\ServerController::class, 'logs'])->name('servers.logs');
    Route::patch('/servers/{server}/web-server', [App\Http\Controllers\ServerController::class, 'updateWebServer'])->name('servers.web-server.update');

    Route::get('/servers/{server}/software', [App\Http\Controllers\SoftwareInstallationController::class, 'index'])->name('servers.software.index');
    Route::post('/servers/{server}/software', [App\Http\Controllers\SoftwareInstallationController::class, 'store'])->name('servers.software.store');
    Route::get('/software-installations/{installation}', [App\Http\Controllers\SoftwareInstallationController::class, 'show'])->name('software-installations.show');

    Route::resource('servers.sites', App\Http\Controllers\SiteController::class)->shallow();
    Route::get('/sites/{site}/env', [App\Http\Controllers\SiteController::class, 'env'])->name('sites.env');
    Route::put('/sites/{site}/env', [App\Http\Controllers\SiteController::class, 'updateEnv'])->name('sites.env.update');
    Route::prefix('/sites/{site}/workspace')->name('sites.workspace.')->group(function (): void {
        Route::get('/', [App\Http\Controllers\SiteWorkspaceController::class, 'overview'])->name('index');
        Route::get('/overview', [App\Http\Controllers\SiteWorkspaceController::class, 'overview'])->name('overview');
        Route::get('/deployments', [App\Http\Controllers\SiteDeploymentController::class, 'index'])->name('deployments');
        Route::post('/deployments', [App\Http\Controllers\SiteDeploymentController::class, 'store'])->name('deployments.store');
        Route::get('/deployments/{deployment}', [App\Http\Controllers\SiteDeploymentController::class, 'show'])->name('deployments.show');
        Route::get('/processes', [App\Http\Controllers\SiteWorkspaceController::class, 'processes'])->name('processes');
        Route::get('/commands', [App\Http\Controllers\SiteWorkspaceController::class, 'commands'])->name('commands');
        Route::get('/network', [App\Http\Controllers\SiteWorkspaceController::class, 'network'])->name('network');
        Route::get('/observe', [App\Http\Controllers\SiteWorkspaceController::class, 'observe'])->name('observe');
        Route::get('/domains', [App\Http\Controllers\SiteWorkspaceController::class, 'domains'])->name('domains');
        Route::get('/settings', [App\Http\Controllers\SiteWorkspaceController::class, 'settings'])->name('settings');
    });
    Route::post('/logout', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
