<?php

it('includes a one-line AlmaLinux and Rocky installer script with critical setup steps', function () {
    $scriptPath = resource_path('scripts/install-panel.sh');

    expect(file_exists($scriptPath))->toBeTrue();

    $script = file_get_contents($scriptPath);

    expect($script)->toContain('#!/usr/bin/env bash');
    expect($script)->toContain('set -euo pipefail');
    expect($script)->toContain('This installer supports AlmaLinux and Rocky Linux only.');
    expect($script)->toContain('This installer supports AlmaLinux/Rocky Linux versions 9 and 10 only.');
    expect($script)->toContain('PANEL_WEB_SERVER must be either');
    expect($script)->toContain('PANEL_WEB_SERVER');
    expect($script)->toContain('PANEL_PROMPTS');
    expect($script)->toContain('PANEL_ADMIN_EMAIL');
    expect($script)->toContain('artisan panel:collect-install-options --shell');
    expect($script)->toContain('artisan panel:ensure-admin-user --shell');
    expect($script)->toContain('Writing Caddy config...');
    expect($script)->toContain('Writing Nginx vhost...');
    expect($script)->toContain('ensure_group_exists');
    expect($script)->toContain('APP_KEY already present; skipping key generation.');
    expect($script)->toContain('composer install --no-dev --optimize-autoloader --no-scripts');
    expect($script)->toContain('artisan package:discover --ansi');
    expect($script)->toContain('install_js_dependencies_with_retries');
    expect($script)->toContain('build_js_assets_with_retry');
    expect($script)->toContain('Multiple JS lockfiles detected. Keep only bun.lock for Bun-only installs.');
    expect($script)->toContain('Unsupported JS lockfile detected. Commit bun.lock and remove other lockfiles.');
    expect($script)->toContain('bun install --frozen-lockfile failed; clearing Bun cache and node_modules, then retrying...');
    expect($script)->toContain('cd \"\$HOME\" && bun pm cache rm || true');
    expect($script)->toContain('Skipping Laravel Prompts wizard because this run is non-interactive (no TTY).');
    expect($script)->toContain('bun install --frozen-lockfile --force --no-cache');
    expect($script)->toContain('bun install --frozen-lockfile --force --no-cache --no-verify');
    expect($script)->toContain('bun run build failed; retrying once...');
    expect($script)->toContain('Panel admin email: ${PANEL_ADMIN_EMAIL}');
    expect($script)->toContain('artisan migrate --force');
    expect($script)->toContain('laravel-queue.service');
});
