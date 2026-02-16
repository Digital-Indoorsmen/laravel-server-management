<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('collect install options command outputs shell assignments', function () {
    putenv('PANEL_WEB_SERVER=caddy');
    putenv('PANEL_DOMAIN=panel.example.test');
    putenv('PANEL_EMAIL=owner@example.test');
    putenv('PANEL_ADMIN_NAME=Owner');
    putenv('PANEL_ADMIN_EMAIL=admin@example.test');
    putenv('PANEL_USE_SSL=1');
    putenv('PANEL_INSTALL_CERTBOT=1');

    Artisan::call('panel:collect-install-options', [
        '--shell' => true,
        '--no-prompts' => true,
    ]);

    $output = Artisan::output();

    expect($output)->toContain("PANEL_WEB_SERVER='caddy'");
    expect($output)->toContain("PANEL_DOMAIN='panel.example.test'");
    expect($output)->toContain("PANEL_ADMIN_NAME='Owner'");
    expect($output)->toContain("PANEL_ADMIN_EMAIL='admin@example.test'");
    expect($output)->toContain("PANEL_INSTALL_CERTBOT='0'");
    expect($output)->toContain("PANEL_EMAIL=''");
    expect($output)->toContain("PANEL_ADMIN_PASSWORD_MODE='regenerate'");

    putenv('PANEL_WEB_SERVER');
    putenv('PANEL_DOMAIN');
    putenv('PANEL_EMAIL');
    putenv('PANEL_ADMIN_NAME');
    putenv('PANEL_ADMIN_EMAIL');
    putenv('PANEL_USE_SSL');
    putenv('PANEL_INSTALL_CERTBOT');
    putenv('PANEL_ADMIN_PASSWORD_MODE');
});

test('collect install options command can write shell assignments to a file', function () {
    $outputFile = storage_path('framework/testing/install-options-shell.txt');

    @unlink($outputFile);
    putenv('PANEL_WEB_SERVER=nginx');
    putenv('PANEL_DOMAIN=panel.example.test');

    Artisan::call('panel:collect-install-options', [
        '--shell-file' => $outputFile,
        '--no-prompts' => true,
    ]);

    expect(file_exists($outputFile))->toBeTrue();

    $contents = file_get_contents($outputFile);

    expect($contents)->toContain("PANEL_WEB_SERVER='nginx'");
    expect($contents)->toContain("PANEL_DOMAIN='panel.example.test'");

    @unlink($outputFile);
    putenv('PANEL_WEB_SERVER');
    putenv('PANEL_DOMAIN');
});

test('collect install options defaults to existing admin values on reinstall', function () {
    User::factory()->create([
        'name' => 'Existing Admin',
        'email' => 'existing@example.test',
    ]);

    Artisan::call('panel:collect-install-options', [
        '--shell' => true,
        '--no-prompts' => true,
    ]);

    $output = Artisan::output();

    expect($output)->toContain("PANEL_ADMIN_NAME='Existing Admin'");
    expect($output)->toContain("PANEL_ADMIN_EMAIL='existing@example.test'");
    expect($output)->toContain("PANEL_ADMIN_PASSWORD_MODE='keep'");
    expect($output)->toContain("PANEL_EXISTING_ADMIN_FOUND='1'");
});
