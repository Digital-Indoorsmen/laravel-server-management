<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Prompts\Renderers\InstallerNoteRenderer;
use Illuminate\Console\Command;
use Laravel\Prompts\Note;
use Laravel\Prompts\Prompt;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class CollectInstallOptions extends Command
{
    protected $signature = 'panel:collect-install-options
                            {--shell : Output shell variable assignments}
                            {--shell-file= : Write shell variable assignments to a file}
                            {--no-prompts : Skip interactive prompts}';

    protected $description = 'Collect panel installer options using Laravel Prompts';

    public function handle(): int
    {
        $settings = $this->defaultSettings();

        if ($this->input->isInteractive() && ! $this->option('no-prompts')) {
            $settings = $this->promptForSettings($settings);
        }

        $settings = $this->normalizeSettings($settings);
        $shellAssignments = $this->toShellAssignments($settings);

        $shellFile = $this->option('shell-file');
        if (is_string($shellFile) && $shellFile !== '') {
            file_put_contents($shellFile, $shellAssignments);

            return self::SUCCESS;
        }

        if ($this->option('shell')) {
            $this->line($shellAssignments);

            return self::SUCCESS;
        }

        $this->line(json_encode($settings, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    protected function defaultSettings(): array
    {
        $existingUser = $this->existingAdminUser();
        $existingAppUrl = $this->existingAppUrl();
        $hasExistingInstall = $this->hasExistingInstall();

        $webServer = strtolower((string) (getenv('PANEL_WEB_SERVER') ?: $this->detectInstalledWebServer()));
        if (! in_array($webServer, ['nginx', 'caddy'], true)) {
            $webServer = 'nginx';
        }

        $detectedDomain = $existingAppUrl['host'] ?? '';
        $detectedUseSsl = ($existingAppUrl['scheme'] ?? '') === 'https' ? '1' : '0';

        $passwordMode = strtolower((string) (getenv('PANEL_ADMIN_PASSWORD_MODE') ?: ''));
        if (! in_array($passwordMode, ['keep', 'regenerate', 'provided'], true)) {
            $passwordMode = $existingUser instanceof User ? 'keep' : 'regenerate';
        }

        if ((string) (getenv('PANEL_ADMIN_PASSWORD') ?: '') !== '') {
            $passwordMode = 'provided';
        }

        return [
            'PANEL_WEB_SERVER' => $webServer,
            'PANEL_DOMAIN' => (string) (getenv('PANEL_DOMAIN') ?: $detectedDomain),
            'PANEL_EMAIL' => (string) (getenv('PANEL_EMAIL') ?: ($existingUser?->email ?? '')),
            'PANEL_ADMIN_NAME' => (string) (getenv('PANEL_ADMIN_NAME') ?: ($existingUser?->name ?? 'Panel Admin')),
            'PANEL_ADMIN_EMAIL' => (string) (getenv('PANEL_ADMIN_EMAIL') ?: getenv('PANEL_EMAIL') ?: ($existingUser?->email ?? '')),
            'PANEL_USE_SSL' => $this->toBooleanString(getenv('PANEL_USE_SSL'), $detectedUseSsl === '1' || $detectedUseSsl === ''),
            'PANEL_INSTALL_CERTBOT' => $this->toBooleanString(getenv('PANEL_INSTALL_CERTBOT'), true),
            'PANEL_ADMIN_PASSWORD_MODE' => $passwordMode,
            'PANEL_EXISTING_INSTALL' => $hasExistingInstall ? '1' : '0',
            'PANEL_EXISTING_ADMIN_FOUND' => $existingUser instanceof User ? '1' : '0',
        ];
    }

    /**
     * @param  array<string, string>  $settings
     * @return array<string, string>
     */
    protected function promptForSettings(array $settings): array
    {
        Prompt::addTheme('installer', [
            Note::class => InstallerNoteRenderer::class,
        ]);

        $currentTheme = Prompt::theme();
        Prompt::theme('installer');

        try {
            intro('Laravel Server Manager Installer');

            if ($settings['PANEL_EXISTING_INSTALL'] === '1') {
                note('Existing installation detected. Defaults were prefilled from current panel configuration.');
            }

            $settings['PANEL_WEB_SERVER'] = select(
                label: 'Which web server should host the panel?',
                options: [
                    'nginx' => 'Nginx',
                    'caddy' => 'Caddy',
                ],
                default: $settings['PANEL_WEB_SERVER']
            );

            $settings['PANEL_DOMAIN'] = trim(text(
                label: 'Panel domain (leave blank to use server IP)',
                default: $settings['PANEL_DOMAIN']
            ));

            $settings['PANEL_USE_SSL'] = confirm(
                label: 'Enable HTTPS for the panel?',
                default: $settings['PANEL_USE_SSL'] === '1'
            ) ? '1' : '0';

            if ($settings['PANEL_WEB_SERVER'] === 'nginx' && $settings['PANEL_USE_SSL'] === '1' && $settings['PANEL_DOMAIN'] !== '') {
                $settings['PANEL_EMAIL'] = trim(text(
                    label: 'Email for SSL certificate notices',
                    default: $settings['PANEL_EMAIL'],
                    validate: function (string $value): ?string {
                        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            return null;
                        }

                        return 'Enter a valid email address.';
                    }
                ));

                $settings['PANEL_INSTALL_CERTBOT'] = confirm(
                    label: 'Install and run Certbot automatically?',
                    default: $settings['PANEL_INSTALL_CERTBOT'] === '1'
                ) ? '1' : '0';
            }

            if ($settings['PANEL_WEB_SERVER'] === 'caddy' && $settings['PANEL_USE_SSL'] === '1' && $settings['PANEL_DOMAIN'] !== '') {
                note('Caddy will manage TLS certificates automatically.');
            }

            if ($settings['PANEL_DOMAIN'] === '') {
                warning('Domain left empty. The panel will be served by server IP.');
            }

            $settings['PANEL_ADMIN_NAME'] = trim(text(
                label: 'Panel admin display name',
                default: $settings['PANEL_ADMIN_NAME']
            ));

            $settings['PANEL_ADMIN_EMAIL'] = trim(text(
                label: 'Panel admin login email',
                default: $settings['PANEL_ADMIN_EMAIL'],
                validate: function (string $value): ?string {
                    if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        return null;
                    }

                    return 'Enter a valid admin email address.';
                }
            ));

            if ($settings['PANEL_EXISTING_ADMIN_FOUND'] === '1') {
                $settings['PANEL_ADMIN_PASSWORD_MODE'] = select(
                    label: 'How should installer handle panel admin password?',
                    options: [
                        'keep' => 'Keep existing login password',
                        'regenerate' => 'Generate a new password',
                    ],
                    default: $settings['PANEL_ADMIN_PASSWORD_MODE'] === 'regenerate' ? 'regenerate' : 'keep',
                );
            } else {
                $settings['PANEL_ADMIN_PASSWORD_MODE'] = 'regenerate';
                note('No existing admin user was found. Installer will generate a new panel password.');
            }

            outro('Installer options captured.');
        } finally {
            Prompt::theme($currentTheme);
        }

        return $settings;
    }

    /**
     * @param  array<string, string>  $settings
     * @return array<string, string>
     */
    protected function normalizeSettings(array $settings): array
    {
        if ($settings['PANEL_WEB_SERVER'] === 'caddy') {
            $settings['PANEL_INSTALL_CERTBOT'] = '0';
        }

        if ($settings['PANEL_USE_SSL'] !== '1' || $settings['PANEL_DOMAIN'] === '') {
            $settings['PANEL_INSTALL_CERTBOT'] = '0';
        }

        if ($settings['PANEL_INSTALL_CERTBOT'] === '0') {
            $settings['PANEL_EMAIL'] = '';
        }

        if ($settings['PANEL_ADMIN_PASSWORD_MODE'] !== 'provided') {
            // Password is either regenerated by installer or existing one is kept.
            $settings['PANEL_ADMIN_PASSWORD'] = '';
        }

        return $settings;
    }

    protected function toBooleanString(mixed $value, bool $default): string
    {
        if ($value === false || $value === null || $value === '') {
            return $default ? '1' : '0';
        }

        if (in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true)) {
            return '1';
        }

        return '0';
    }

    /**
     * @param  array<string, string>  $settings
     */
    protected function toShellAssignments(array $settings): string
    {
        $lines = [];

        foreach ($settings as $key => $value) {
            $lines[] = $key.'='.$this->shellQuote($value);
        }

        return implode(PHP_EOL, $lines);
    }

    protected function shellQuote(string $value): string
    {
        return "'".str_replace("'", "'\"'\"'", $value)."'";
    }

    protected function hasExistingInstall(): bool
    {
        $appKey = (string) config('app.key', '');
        if ($appKey === '') {
            return false;
        }

        $databasePath = (string) config('database.connections.sqlite.database', '');

        return $databasePath !== '' && file_exists($databasePath);
    }

    /**
     * @return array{scheme: string, host: string}
     */
    protected function existingAppUrl(): array
    {
        $url = (string) config('app.url', '');
        if ($url === '') {
            return ['scheme' => '', 'host' => ''];
        }

        return [
            'scheme' => (string) (parse_url($url, PHP_URL_SCHEME) ?: ''),
            'host' => (string) (parse_url($url, PHP_URL_HOST) ?: ''),
        ];
    }

    protected function detectInstalledWebServer(): string
    {
        $caddyActive = trim((string) @shell_exec('systemctl is-active caddy 2>/dev/null'));
        if ($caddyActive === 'active') {
            return 'caddy';
        }

        $nginxActive = trim((string) @shell_exec('systemctl is-active nginx 2>/dev/null'));
        if ($nginxActive === 'active') {
            return 'nginx';
        }

        return 'nginx';
    }

    protected function existingAdminUser(): ?User
    {
        try {
            return User::query()->orderBy('created_at')->first();
        } catch (\Throwable) {
            return null;
        }
    }
}
