<?php

use Illuminate\Support\Facades\Artisan;

test('collect install options command outputs shell assignments', function () {
    putenv('PANEL_WEB_SERVER=caddy');
    putenv('PANEL_DOMAIN=panel.example.test');
    putenv('PANEL_EMAIL=owner@example.test');
    putenv('PANEL_USE_SSL=1');
    putenv('PANEL_INSTALL_CERTBOT=1');

    Artisan::call('panel:collect-install-options', [
        '--shell' => true,
        '--no-prompts' => true,
    ]);

    $output = Artisan::output();

    expect($output)->toContain("PANEL_WEB_SERVER='caddy'");
    expect($output)->toContain("PANEL_DOMAIN='panel.example.test'");
    expect($output)->toContain("PANEL_INSTALL_CERTBOT='0'");
    expect($output)->toContain("PANEL_EMAIL=''");

    putenv('PANEL_WEB_SERVER');
    putenv('PANEL_DOMAIN');
    putenv('PANEL_EMAIL');
    putenv('PANEL_USE_SSL');
    putenv('PANEL_INSTALL_CERTBOT');
});
