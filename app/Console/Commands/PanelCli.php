<?php

namespace App\Console\Commands;

use App\Jobs\InstallDatabaseEngine;
use App\Jobs\RunSiteDeployment;
use App\Models\DatabaseEngineInstallation;
use App\Models\Server;
use App\Models\Site;
use App\Services\DatabaseProvisioningService;
use App\Services\PanelHealthService;
use App\Services\SiteDeploymentService;
use App\Services\SiteProvisioningService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class PanelCli extends Command
{
    protected $signature = 'panel:cli
        {action? : status, update, new:site, site:deploy, database:install, or help}
        {target? : Site id for site:deploy or database type for database:install}
        {--dry-run : Preview update commands without executing them}
        {--branch=main : Branch to deploy for site:deploy}
        {--server= : Target server id for new:site or database:install}
        {--domain= : Domain for new:site}
        {--system-user= : Linux system user for new:site}
        {--php-version= : PHP version for new:site}
        {--app-type= : App type for new:site}
        {--web-server= : Web server for new:site}
        {--create-database=0 : Whether to create a database (0/1)}
        {--database-type= : Database type for new:site}
        {--provision=1 : Run provisioning after creating site (0/1)}';

    protected $description = 'Panel CLI entrypoint for status, updates, site creation, deployments, and database installation';

    public function __construct(
        protected PanelHealthService $panelHealth,
        protected SiteProvisioningService $siteProvisioning,
        protected SiteDeploymentService $siteDeploymentService,
        protected DatabaseProvisioningService $databaseProvisioning
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $action = strtolower((string) ($this->argument('action') ?? ''));

        if ($action === '') {
            return $this->showAvailableCommands();
        }

        if (! in_array($action, ['status', 'update', 'new:site', 'site:deploy', 'database:install', 'help'], true)) {
            $this->components->error("Unknown action [{$action}].");

            return $this->showAvailableCommands(self::FAILURE);
        }

        return match ($action) {
            'help' => $this->showAvailableCommands(),
            'status' => $this->runStatus(),
            'update' => $this->runUpdate(),
            'new:site' => $this->runNewSite(),
            'site:deploy' => $this->runSiteDeploy(),
            'database:install' => $this->runDatabaseInstall(),
            default => self::FAILURE,
        };
    }

    protected function showAvailableCommands(int $exitCode = self::SUCCESS): int
    {
        intro('Laravel Server Manager CLI');

        $this->table(['Command', 'Description'], [
            ['larapanel status', 'Show host, panel, and service status'],
            ['larapanel update', 'Update panel software and rebuild assets'],
            ['larapanel new:site', 'Create and optionally provision a new site'],
            ['larapanel site:deploy {site}', 'Queue a deployment for a specific site'],
            ['larapanel database:install {type}', 'Install a database engine (mariadb, postgresql)'],
            ['larapanel help', 'Show this command list'],
        ]);

        outro('Run one of the commands above to continue.');

        return $exitCode;
    }

    protected function runDatabaseInstall(): int
    {
        $type = strtolower((string) ($this->argument('target') ?? ''));

        if ($type === '' && $this->input->isInteractive()) {
            $type = select(
                label: 'Which database engine should be installed?',
                options: ['mariadb' => 'MariaDB', 'postgresql' => 'PostgreSQL'],
            );
        }

        if (! in_array($type, ['mariadb', 'postgresql'], true)) {
            $this->components->error("Invalid database type [{$type}]. Supported: mariadb, postgresql.");

            return self::FAILURE;
        }

        $servers = Server::query()->latest()->get(['id', 'name', 'ip_address']);

        if ($servers->isEmpty()) {
            $this->components->error('No servers found.');

            return self::FAILURE;
        }

        $serverId = (string) ($this->option('server') ?? '');

        if ($serverId === '' && $this->input->isInteractive()) {
            $serverId = select(
                label: 'Which server should the engine be installed on?',
                options: $servers->mapWithKeys(fn (Server $server): array => [
                    (string) $server->id => "{$server->name} ({$server->ip_address})",
                ])->all(),
                default: (string) $servers->first()->id,
            );
        }

        $server = $servers->firstWhere('id', $serverId);

        if (! $server) {
            $this->components->error('Invalid server id.');

            return self::FAILURE;
        }

        // Check if already installed
        if (isset($server->database_engines[$type])) {
            $this->components->warn("{$type} is already recorded as installed on this server.");
            if (! confirm('Do you want to proceed with installation anyway?', false)) {
                return self::SUCCESS;
            }
        }

        $installation = DatabaseEngineInstallation::query()->create([
            'server_id' => $server->id,
            'type' => $type,
            'status' => 'queued',
        ]);

        InstallDatabaseEngine::dispatch($installation->id);

        $this->components->info("Installation of {$type} queued for server {$server->name}.");
        $this->line("You can monitor progress in the web panel or via 'larapanel status'.");

        return self::SUCCESS;
    }

    protected function runStatus(): int
    {
        $this->components->info('Panel status');

        $stats = collect($this->panelHealth->systemStats())
            ->map(fn (array $row): array => [
                'Metric' => $row['name'],
                'Value' => (string) $row['value'].$row['unit'],
            ])
            ->all();

        $services = collect($this->panelHealth->services())
            ->map(fn (array $row): array => [
                'Service' => $row['name'],
                'Status' => $row['status'],
                'Version' => $row['version'],
            ])
            ->all();

        $security = $this->panelHealth->security();
        $resources = $this->panelHealth->resourceCounts();

        $this->line('');
        $this->line('Uptime: '.$this->panelHealth->uptime());
        $this->line('SELinux: '.$security['selinux_mode']);
        $this->line('Firewall: '.($security['firewall_active'] ? 'active' : 'inactive'));
        $this->line('Firewall services: '.(count($security['firewall_services']) > 0 ? implode(', ', $security['firewall_services']) : 'none'));
        $this->line('Managed: '.$resources['Sites'].' sites, '.$resources['Databases'].' databases');
        $this->line('');

        $this->table(['Metric', 'Value'], $stats);
        $this->line('');
        $this->table(['Service', 'Status', 'Version'], $services);

        return self::SUCCESS;
    }

    protected function runUpdate(): int
    {
        $this->components->info('Updating panel software');

        $commands = [
            ['git', 'fetch', '--all', '--prune'],
            ['git', 'pull', '--ff-only'],
            ['composer', 'install', '--no-dev', '--optimize-autoloader', '--no-scripts'],
            ['php', 'artisan', 'package:discover', '--ansi'],
            ['bun', 'install', '--frozen-lockfile'],
            ['bun', 'run', 'build'],
            ['php', 'artisan', 'migrate', '--force'],
            ['php', 'artisan', 'config:cache'],
        ];

        if ($this->option('dry-run')) {
            $this->components->info('Dry run enabled. No commands will be executed.');

            foreach ($commands as $command) {
                $this->line('> '.implode(' ', $command));
            }

            return self::SUCCESS;
        }

        $repoOwner = $this->repositoryOwner();

        if ($repoOwner !== null && $this->isRunningAsRoot() && $repoOwner !== 'root') {
            $this->line("Using repository owner [{$repoOwner}] for update commands.");
        }

        foreach ($commands as $command) {
            $this->line('> '.implode(' ', $command));

            $process = $this->runProcess($this->commandForExecution($command, $repoOwner), base_path());

            if (! $process->isSuccessful() && $this->isDubiousOwnershipGitError($command, $process)) {
                $safeDirectoryProcess = $this->runProcess(
                    ['git', 'config', '--global', '--add', 'safe.directory', base_path()],
                    base_path()
                );

                if ($safeDirectoryProcess->isSuccessful()) {
                    $process = $this->runProcess($this->commandForExecution($command, $repoOwner), base_path());
                }
            }

            if (! $process->isSuccessful()) {
                $this->output->write($process->getOutput());
                $this->output->write($process->getErrorOutput());
                $this->components->error('Update failed while running: '.implode(' ', $command));

                return self::FAILURE;
            }
        }

        $this->components->info('Panel update complete.');

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $command
     * @return array<int, string>
     */
    protected function commandForExecution(array $command, ?string $repoOwner): array
    {
        if (
            $repoOwner !== null &&
            $repoOwner !== '' &&
            $repoOwner !== 'root' &&
            $this->isRunningAsRoot()
        ) {
            return ['runuser', '-u', $repoOwner, '--', 'bash', '-lc', $this->commandForOwnerShell($command)];
        }

        return $command;
    }

    /**
     * @param  array<int, string>  $command
     */
    protected function commandForOwnerShell(array $command): string
    {
        $escapedCommand = implode(' ', array_map(static fn (string $part): string => escapeshellarg($part), $command));

        return 'export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"; '.
            'export BUN_INSTALL="$HOME/.bun"; '.
            'export PATH="$BUN_INSTALL/bin:$PATH"; '.
            $escapedCommand;
    }

    protected function isRunningAsRoot(): bool
    {
        return function_exists('posix_geteuid') && posix_geteuid() === 0;
    }

    protected function repositoryOwner(): ?string
    {
        $path = base_path('.git');

        if (! file_exists($path)) {
            $path = base_path();
        }

        $ownerId = @fileowner($path);
        if ($ownerId === false || ! function_exists('posix_getpwuid')) {
            return null;
        }

        $owner = posix_getpwuid($ownerId);
        if (! is_array($owner)) {
            return null;
        }

        return $owner['name'] ?? null;
    }

    /**
     * @param  array<int, string>  $command
     */
    protected function isDubiousOwnershipGitError(array $command, Process $process): bool
    {
        if (($command[0] ?? '') !== 'git') {
            return false;
        }

        $errorOutput = $process->getErrorOutput().$process->getOutput();

        return str_contains($errorOutput, 'detected dubious ownership');
    }

    /**
     * @param  array<int, string>  $command
     */
    protected function runProcess(array $command, string $workingDirectory): Process
    {
        $process = new Process($command, $workingDirectory, null, null, null);
        $process->run(function (string $type, string $output): void {
            $this->output->write($output);
        });

        return $process;
    }

    protected function runNewSite(): int
    {
        $servers = Server::query()->latest()->get(['id', 'name', 'ip_address', 'web_server']);

        if ($servers->isEmpty()) {
            $this->components->error('No servers found. Add a server in the panel first.');

            return self::FAILURE;
        }

        $serverId = (string) ($this->option('server') ?? '');

        if ($serverId === '' && $this->input->isInteractive() && ! $this->option('no-interaction')) {
            $serverOptions = $servers->mapWithKeys(fn (Server $server): array => [
                (string) $server->id => "{$server->name} ({$server->ip_address})",
            ])->all();

            $serverId = select(
                label: 'Which server should host this site?',
                options: $serverOptions,
                default: (string) $servers->first()->id,
            );
        }

        $server = $servers->firstWhere('id', $serverId);

        if (! $server) {
            $this->components->error('Invalid server id. Provide --server with a valid value.');

            return self::FAILURE;
        }

        try {
            $domain = $this->resolveValue(
                value: (string) ($this->option('domain') ?? ''),
                promptLabel: 'Site domain',
                promptDefault: '',
                required: true,
            );

            $systemUser = $this->resolveValue(
                value: (string) ($this->option('system-user') ?? ''),
                promptLabel: 'System user',
                promptDefault: Str::slug(Str::before($domain, '.'), separator: '_'),
                required: true,
            );

            $phpVersion = $this->resolveChoice(
                value: (string) ($this->option('php-version') ?? ''),
                promptLabel: 'PHP version',
                options: ['7.4', '8.1', '8.2', '8.3', '8.4', '8.5'],
                default: '8.4',
            );

            $appType = $this->resolveChoice(
                value: (string) ($this->option('app-type') ?? ''),
                promptLabel: 'Application type',
                options: ['wordpress', 'laravel', 'generic'],
                default: 'laravel',
            );

            $webServer = $this->resolveChoice(
                value: (string) ($this->option('web-server') ?? ''),
                promptLabel: 'Web server',
                options: ['nginx', 'caddy'],
                default: (string) ($server->web_server ?: 'nginx'),
            );

            $createDatabase = $this->option('create-database') === '1';

            if (! $this->option('no-interaction') && $this->input->isInteractive() && ! $this->hasOptionValue('create-database')) {
                $createDatabase = confirm('Create a database for this site?', false);
            }

            $databaseType = null;

            if ($createDatabase) {
                $databaseType = $this->resolveChoice(
                    value: (string) ($this->option('database-type') ?? ''),
                    promptLabel: 'Database type',
                    options: ['mariadb', 'postgresql'],
                    default: 'mariadb',
                );
            }
        } catch (\RuntimeException $runtimeException) {
            $this->components->error($runtimeException->getMessage());

            return self::FAILURE;
        }

        $documentRoot = "/home/{$systemUser}/public_html";

        if ($appType === 'laravel') {
            $documentRoot .= '/public';
        }

        $provision = $this->option('provision') === '1';

        DB::beginTransaction();

        try {
            $site = Site::query()->create([
                'server_id' => $server->id,
                'domain' => $domain,
                'system_user' => $systemUser,
                'php_version' => $phpVersion,
                'app_type' => $appType,
                'web_server' => $webServer,
                'document_root' => $documentRoot,
                'status' => 'creating',
            ]);

            if ($createDatabase && $databaseType !== null) {
                $cleanName = str_replace(['.', '-'], '_', $domain);
                $site->databases()->create([
                    'server_id' => $server->id,
                    'name' => 'db_'.substr($cleanName, 0, 50),
                    'username' => $systemUser,
                    'password' => Str::password(24),
                    'type' => $databaseType,
                    'status' => 'creating',
                ]);
            }

            if ($provision) {
                $this->siteProvisioning->provision($site);
            }

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();
            $this->components->error('Failed to create site: '.$throwable->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Site {$domain} created successfully.");

        if (! $provision) {
            $this->line('Provisioning skipped (--provision=0).');
        }

        return self::SUCCESS;
    }

    protected function resolveValue(string $value, string $promptLabel, string $promptDefault, bool $required): string
    {
        if ($value !== '') {
            return $value;
        }

        if ($this->option('no-interaction') || ! $this->input->isInteractive()) {
            if ($required) {
                throw new \RuntimeException("Missing required value: {$promptLabel}");
            }

            return $promptDefault;
        }

        return trim(text(
            label: $promptLabel,
            default: $promptDefault,
            required: $required,
        ));
    }

    /**
     * @param  array<int, string>  $options
     */
    protected function resolveChoice(string $value, string $promptLabel, array $options, string $default): string
    {
        if ($value !== '') {
            if (! in_array($value, $options, true)) {
                throw new \RuntimeException("Invalid value '{$value}' for {$promptLabel}");
            }

            return $value;
        }

        if ($this->option('no-interaction') || ! $this->input->isInteractive()) {
            return $default;
        }

        return select(
            label: $promptLabel,
            options: array_combine($options, $options),
            default: $default,
        );
    }

    protected function hasOptionValue(string $option): bool
    {
        $raw = $this->input->getParameterOption("--{$option}");

        return $raw !== false;
    }

    protected function runSiteDeploy(): int
    {
        $siteId = (string) ($this->argument('target') ?? '');

        if ($siteId === '') {
            $this->components->error('Site id is required. Usage: larapanel site:deploy {site} [--branch=main].');

            return self::FAILURE;
        }

        $site = Site::query()->find($siteId);

        if (! $site) {
            $this->components->error("Site [{$siteId}] was not found.");

            return self::FAILURE;
        }

        $branch = (string) ($this->option('branch') ?? 'main');

        if (! preg_match('/^[A-Za-z0-9._\/-]+$/', $branch)) {
            $this->components->error('Invalid branch format.');

            return self::FAILURE;
        }

        $deployment = $this->siteDeploymentService->queue(
            site: $site,
            actorId: null,
            triggeredVia: 'cli',
            branch: $branch,
        );

        RunSiteDeployment::dispatch($deployment->id)->onQueue('deployments');

        $this->components->info("Deployment {$deployment->id} queued for {$site->domain} on branch {$branch}.");

        return self::SUCCESS;
    }
}
