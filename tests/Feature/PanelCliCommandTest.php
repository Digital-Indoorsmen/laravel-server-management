<?php

use App\Console\Commands\PanelCli;
use App\Jobs\RunSiteDeployment;
use App\Models\Server;
use App\Models\Site;
use App\Services\PanelHealthService;
use App\Services\SiteDeploymentService;
use App\Services\SiteProvisioningService;
use App\Services\SoftwareProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

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

        public function resourceCounts(): array
        {
            return [
                'Sites' => 1,
                'Databases' => 1,
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

it('can preview update commands without executing them', function () {
    $this->artisan('panel:cli update --dry-run --no-interaction')
        ->expectsOutputToContain('Dry run enabled. No commands will be executed.')
        ->expectsOutputToContain('git fetch --all --prune')
        ->expectsOutputToContain('bun run build')
        ->assertSuccessful();
});

it('wraps root-run update commands in owner shell with bun-aware environment', function () {
    $command = new class(app(PanelHealthService::class), app(SiteProvisioningService::class), app(SiteDeploymentService::class), app(SoftwareProvisioningService::class)) extends PanelCli
    {
        protected function isRunningAsRoot(): bool
        {
            return true;
        }

        /**
         * @param  array<int, string>  $command
         * @return array<int, string>
         */
        public function exposedCommandForExecution(array $command, ?string $repoOwner): array
        {
            return $this->commandForExecution($command, $repoOwner);
        }
    };

    $wrapped = $command->exposedCommandForExecution(['bun', 'install', '--frozen-lockfile'], 'panel');

    expect($wrapped[0])->toBe('runuser')
        ->and($wrapped[4])->toBe('bash')
        ->and($wrapped[5])->toBe('-lc')
        ->and($wrapped[6])->toContain('export BUN_INSTALL="$HOME/.bun";')
        ->and($wrapped[6])->toContain('export PATH="$BUN_INSTALL/bin:$PATH";')
        ->and($wrapped[6])->toContain("'bun' 'install' '--frozen-lockfile'");
});

it('queues a site deployment from cli', function () {
    Queue::fake();

    $server = Server::factory()->create([
        'name' => 'CLI Deploy Server',
        'status' => 'active',
        'web_server' => 'nginx',
    ]);

    $site = Site::query()->create([
        'server_id' => $server->id,
        'domain' => 'cli-deploy.test',
        'document_root' => '/home/cli_deploy/public_html/public',
        'system_user' => 'cli_deploy',
        'php_version' => '8.4',
        'app_type' => 'laravel',
        'web_server' => 'nginx',
        'status' => 'active',
    ]);

    $this->artisan("panel:cli site:deploy {$site->id} --branch=main --no-interaction")
        ->expectsOutputToContain('queued')
        ->assertSuccessful();

    $this->assertDatabaseHas('deployments', [
        'site_id' => $site->id,
        'triggered_via' => 'cli',
        'branch' => 'main',
        'status' => 'queued',
    ]);

    Queue::assertPushed(RunSiteDeployment::class);
});

it('queues a database engine installation from cli', function () {
    Queue::fake();

    $server = Server::factory()->create([
        'name' => 'DB Install Server',
        'status' => 'active',
    ]);

    $this->artisan("panel:cli database:install mariadb --server={$server->id} --no-interaction")
        ->expectsOutputToContain('Installation of mariadb v10.3 queued')
        ->assertSuccessful();

    $this->assertDatabaseHas('software_installations', [
        'server_id' => $server->id,
        'type' => 'mariadb',
        'status' => 'queued',
    ]);

    Queue::assertPushed(\App\Jobs\InstallSoftware::class);
});
