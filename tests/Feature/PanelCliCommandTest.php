<?php

use App\Models\Server;
use App\Services\PanelHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows available commands when run without arguments', function () {
    $this->artisan('panel:cli')
        ->expectsOutputToContain('larapanel status')
        ->expectsOutputToContain('larapanel update')
        ->expectsOutputToContain('larapanel new:site')
        ->assertSuccessful();
});

it('shows status output successfully', function () {
    $service = new class extends PanelHealthService
    {
        public function systemStats(): array
        {
            return [
                ['name' => 'CPU Load', 'value' => 10, 'unit' => '%'],
                ['name' => 'RAM Usage', 'value' => 20, 'unit' => '%'],
                ['name' => 'Disk Space', 'value' => 30, 'unit' => '%'],
                ['name' => 'Swap Usage', 'value' => 0, 'unit' => '%'],
            ];
        }

        public function services(): array
        {
            return [
                ['name' => 'Caddy Web Server', 'status' => 'running', 'version' => '2.10.2'],
            ];
        }

        public function security(): array
        {
            return [
                'selinux_mode' => 'Enforcing',
                'firewall_active' => true,
                'firewall_services' => ['http', 'https'],
            ];
        }

        public function uptime(): string
        {
            return '1d 2h 3m';
        }
    };

    app()->instance(PanelHealthService::class, $service);

    $this->artisan('panel:cli status --no-interaction')
        ->expectsOutputToContain('Panel status')
        ->assertSuccessful();
});

it('creates a site from cli options without provisioning', function () {
    $server = Server::factory()->create([
        'name' => 'CLI Server',
        'status' => 'active',
        'web_server' => 'nginx',
    ]);

    $this->artisan('panel:cli new:site --no-interaction --provision=0 --create-database=1 --database-type=mariadb --server='.$server->id.' --domain=cli-example.test --system-user=cli_example --php-version=8.4 --app-type=laravel --web-server=nginx')
        ->assertSuccessful();

    $this->assertDatabaseHas('sites', [
        'server_id' => $server->id,
        'domain' => 'cli-example.test',
        'system_user' => 'cli_example',
        'php_version' => '8.4',
        'app_type' => 'laravel',
        'web_server' => 'nginx',
    ]);

    $this->assertDatabaseHas('databases', [
        'server_id' => $server->id,
        'username' => 'cli_example',
        'type' => 'mariadb',
    ]);
});
