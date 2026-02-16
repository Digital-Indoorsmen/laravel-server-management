<?php

namespace App\Console\Commands;

use App\Models\Server;
use App\Models\Site;
use App\Services\PanelHealthService;
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
        {action? : status, update, new:site, or help}
        {--server= : Target server id for new:site}
        {--domain= : Domain for new:site}
        {--system-user= : Linux system user for new:site}
        {--php-version= : PHP version for new:site}
        {--app-type= : App type for new:site}
        {--web-server= : Web server for new:site}
        {--create-database=0 : Whether to create a database (0/1)}
        {--database-type= : Database type for new:site}
        {--provision=1 : Run provisioning after creating site (0/1)}';

    protected $description = 'Panel CLI entrypoint for status, updates, and site creation';

    public function __construct(
        protected PanelHealthService $panelHealth,
        protected SiteProvisioningService $siteProvisioning
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $action = strtolower((string) ($this->argument('action') ?? ''));

        if ($action === '') {
            return $this->showAvailableCommands();
        }

        if (! in_array($action, ['status', 'update', 'new:site', 'help'], true)) {
            $this->components->error("Unknown action [{$action}].");

            return $this->showAvailableCommands(self::FAILURE);
        }

        return match ($action) {
            'help' => $this->showAvailableCommands(),
            'status' => $this->runStatus(),
            'update' => $this->runUpdate(),
            'new:site' => $this->runNewSite(),
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
            ['larapanel help', 'Show this command list'],
        ]);

        outro('Run one of the commands above to continue.');

        return $exitCode;
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

        $this->line('');
        $this->line('Uptime: '.$this->panelHealth->uptime());
        $this->line('SELinux: '.$security['selinux_mode']);
        $this->line('Firewall: '.($security['firewall_active'] ? 'active' : 'inactive'));
        $this->line('Firewall services: '.(count($security['firewall_services']) > 0 ? implode(', ', $security['firewall_services']) : 'none'));
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

        foreach ($commands as $command) {
            $this->line('> '.implode(' ', $command));

            $process = new Process($command, base_path(), null, null, null);
            $process->run(function (string $type, string $output): void {
                $this->output->write($output);
            });

            if (! $process->isSuccessful()) {
                $this->components->error('Update failed while running: '.implode(' ', $command));

                return self::FAILURE;
            }
        }

        $this->components->info('Panel update complete.');

        return self::SUCCESS;
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
}
