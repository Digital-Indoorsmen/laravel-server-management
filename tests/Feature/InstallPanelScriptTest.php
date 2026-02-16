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
    expect($script)->toContain("Non-interactive mode requires PANEL_WEB_SERVER to be explicitly set to 'nginx' or 'caddy'.");
    expect($script)->toContain('PANEL_WEB_SERVER');
    expect($script)->toContain('PANEL_PROMPTS');
    expect($script)->toContain('PANEL_ADMIN_EMAIL');
    expect($script)->toContain('PANEL_ADMIN_PASSWORD_MODE');
    expect($script)->toContain('artisan panel:collect-install-options --shell-file="${prompt_output_file}" < /dev/tty');
    expect($script)->toContain('artisan panel:ensure-admin-user --shell');
    expect($script)->toContain('Writing Caddy config...');
    expect($script)->toContain('Writing Nginx vhost...');
    expect($script)->toContain('ensure_group_exists');
    expect($script)->toContain('APP_KEY already present; skipping key generation.');
    expect($script)->toContain('composer install --no-dev --optimize-autoloader --no-scripts');
    expect($script)->toContain('artisan package:discover --ansi');
    expect($script)->toContain('repair_sqlite_runtime_access');
    expect($script)->toContain('install_larapanel_cli');
    expect($script)->toContain('configure_service_control_sudoers');
    expect($script)->toContain('install_js_dependencies_with_retries');
    expect($script)->toContain('build_js_assets_with_retry');
    expect($script)->toContain('disable_conflicting_web_server');
    expect($script)->toContain('reset_php_fpm_runtime_selinux_contexts');
    expect($script)->toContain('Multiple JS lockfiles detected. Keep only bun.lock for Bun-only installs.');
    expect($script)->toContain('Unsupported JS lockfile detected. Commit bun.lock and remove other lockfiles.');
    expect($script)->toContain('bun install --frozen-lockfile failed; clearing Bun cache and node_modules, then retrying...');
    expect($script)->toContain('cd \"\$HOME\" && bun pm cache rm || true');
    expect($script)->toContain('Skipping Laravel Prompts wizard because this run is non-interactive (no TTY).');
    expect($script)->toContain('bun install --frozen-lockfile --force --no-cache');
    expect($script)->toContain('bun install --frozen-lockfile --force --no-cache --no-verify');
    expect($script)->toContain('bun run build failed; retrying once...');
    expect($script)->toContain('Panel admin email: ${PANEL_ADMIN_EMAIL}');
    expect($script)->toContain('Panel admin password was kept from existing account.');
    expect($script)->toContain('s/^listen.acl_users =.*/;listen.acl_users =/');
    expect($script)->toContain('s/^listen.acl_groups =.*/;listen.acl_groups =/');
    expect($script)->toContain('SQLite needs directory write access for -wal/-shm files at runtime.');
    expect($script)->toContain('chown -R "${PANEL_APP_USER}:${PANEL_WEB_SERVER}" "${db_dir}"');
    expect($script)->toContain('find "${db_dir}" -maxdepth 1 -type f -name \'database.sqlite*\' -exec chmod 664 {} \\;');
    expect($script)->toContain('semanage fcontext -d "/run/php-fpm(/.*)?" >/dev/null 2>&1 || true');
    expect($script)->toContain('artisan migrate --force');
    expect($script)->toContain('laravel-queue.service');
    expect($script)->toContain('/usr/local/bin/larapanel');
    expect($script)->toContain('artisan panel:cli "\$@"');
    expect($script)->toContain('/etc/sudoers.d/laravel-panel-service-control');
    expect($script)->toContain('visudo -cf');
});
