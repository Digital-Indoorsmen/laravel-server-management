<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('ensure admin user command creates a user and returns shell credentials', function () {
    putenv('PANEL_ADMIN_NAME=Primary Admin');
    putenv('PANEL_ADMIN_EMAIL=admin@example.test');
    putenv('PANEL_ADMIN_PASSWORD=');
    putenv('PANEL_ADMIN_PASSWORD_MODE=regenerate');

    Artisan::call('panel:ensure-admin-user', [
        '--shell' => true,
    ]);

    $output = Artisan::output();

    expect($output)->toContain("PANEL_ADMIN_EMAIL='admin@example.test'");
    expect($output)->toContain("PANEL_ADMIN_PASSWORD_GENERATED='1'");

    preg_match("/PANEL_ADMIN_PASSWORD='([^']+)'/", $output, $matches);
    $plainPassword = $matches[1] ?? null;

    expect($plainPassword)->not->toBeNull();

    $user = User::query()->where('email', 'admin@example.test')->first();

    expect($user)->not->toBeNull();
    expect($user?->name)->toBe('Primary Admin');
    expect(Hash::check((string) $plainPassword, (string) $user?->password))->toBeTrue();

    putenv('PANEL_ADMIN_NAME');
    putenv('PANEL_ADMIN_EMAIL');
    putenv('PANEL_ADMIN_PASSWORD');
    putenv('PANEL_ADMIN_PASSWORD_MODE');
});

test('ensure admin user command can keep existing password', function () {
    $existing = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'admin@example.test',
        'password' => 'existing-secret',
    ]);

    $existingPasswordHash = $existing->password;

    putenv('PANEL_ADMIN_NAME=Updated Name');
    putenv('PANEL_ADMIN_EMAIL=admin@example.test');
    putenv('PANEL_ADMIN_PASSWORD=');
    putenv('PANEL_ADMIN_PASSWORD_MODE=keep');

    Artisan::call('panel:ensure-admin-user', [
        '--shell' => true,
    ]);

    $output = Artisan::output();

    expect($output)->toContain("PANEL_ADMIN_PASSWORD_REUSED='1'");
    expect($output)->toContain("PANEL_ADMIN_PASSWORD_AVAILABLE='0'");

    $updated = User::query()->where('email', 'admin@example.test')->first();

    expect($updated)->not->toBeNull();
    expect($updated?->name)->toBe('Updated Name');
    expect($updated?->password)->toBe($existingPasswordHash);

    putenv('PANEL_ADMIN_NAME');
    putenv('PANEL_ADMIN_EMAIL');
    putenv('PANEL_ADMIN_PASSWORD');
    putenv('PANEL_ADMIN_PASSWORD_MODE');
});
