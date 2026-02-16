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
});
