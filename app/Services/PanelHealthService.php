<?php

namespace App\Services;

use App\Models\Server;

class PanelHealthService
{
    /**
     * @return array<int, array{name: string, value: int, unit: string}>
     */
    public function systemStats(?Server $server = null): array
    {
        $this->syncLocalServerState($server);

        $stats = [
            [
                'name' => 'CPU Load',
                'value' => $this->cpuLoadPercent(),
                'unit' => '%',
            ],
            [
                'name' => 'RAM Usage',
                'value' => $this->memoryUsagePercent(),
                'unit' => '% '.$this->memoryUsageLabel(),
            ],
            [
                'name' => 'Disk Space (/)',
                'value' => $this->diskUsagePercent('/'),
                'unit' => '%',
            ],
        ];

        if ($this->isSeparateMount('/home')) {
            $stats[] = [
                'name' => 'Disk Space (/home)',
                'value' => $this->diskUsagePercent('/home'),
                'unit' => '%',
            ];
        }

        $stats[] = [
            'name' => 'Swap Usage',
            'value' => $this->swapUsagePercent(),
            'unit' => '%',
        ];

        return $stats;
    }

    private function syncLocalServerState(?Server $server = null): void
    {
        $server = $server ?? \App\Models\Server::query()->first();
        if (! $server || ($server->ip_address !== '127.0.0.1' && $server->ip_address !== 'localhost')) {
            return;
        }

        $services = $this->services();
        $software = $server->software ?? [];
        $engines = $server->database_engines ?? [];
        $changed = false;

        foreach ($services as $svc) {
            // Store software if it's running OR if we detected a version (installed but not running as service)
            if ($svc['status'] === 'running' || ($svc['status'] === 'stopped' && $svc['version'] !== 'Unknown' && $svc['version'] !== 'Not installed')) {
                $type = $svc['key'];

                // Special handling for php-fpm to generic php key
                if ($type === 'php-fpm') {
                    $type = 'php';
                }

                // Extract version number with improved logic for MariaDB
                $version = $this->extractVersion($svc['version'], $type);

                // Skip if we couldn't extract a valid version
                if (! $version) {
                    continue;
                }

                // For databases (mariadb, mysql, postgresql), only store ONE version
                if (in_array($type, ['mariadb', 'mysql', 'postgresql'])) {
                    // Replace any existing versions with the current one
                    $software[$type] = [
                        $version => [
                            'status' => 'active',
                            'installed_at' => $software[$type][$version]['installed_at'] ?? now()->toDateTimeString(),
                            'method' => 'discovery',
                        ],
                    ];
                    $changed = true;

                    // Update legacy database_engines structure
                    $engines[$type] = [
                        'status' => 'active',
                        'version' => $version,
                        'installed_at' => $engines[$type]['installed_at'] ?? now()->toDateTimeString(),
                        'method' => 'discovery',
                    ];
                } else {
                    // For PHP and other software, allow multiple versions
                    if (! isset($software[$type][$version])) {
                        $software[$type][$version] = [
                            'status' => 'active',
                            'installed_at' => now()->toDateTimeString(),
                            'method' => 'discovery',
                        ];
                        $changed = true;
                    }
                }
            }
        }

        // Sync web server preference if not set or different
        $activeWebServer = null;
        if (shell_exec('systemctl is-active caddy 2>/dev/null') === "active\n") {
            $activeWebServer = 'caddy';
        } elseif (shell_exec('systemctl is-active nginx 2>/dev/null') === "active\n") {
            $activeWebServer = 'nginx';
        }

        if ($activeWebServer && $server->web_server !== $activeWebServer) {
            $server->web_server = $activeWebServer;
            $changed = true;
        }

        if ($changed) {
            $server->update([
                'software' => $software,
                'database_engines' => $engines,
                'web_server' => $server->web_server,
            ]);
        }
    }

    /**
     * @return array<string, int>
     */
    public function resourceCounts(): array
    {
        return [
            'Sites' => \App\Models\Site::count(),
            'Databases' => \App\Models\Database::count(),
        ];
    }

    /**
     * @return array<int, array{key: string, name: string, status: string, version: string}>
     */
    public function services(): array
    {
        $services = [
            $this->serviceStatus('nginx', 'Nginx Web Server', 'nginx', 'nginx -v 2>&1 | head -n 1'),
            $this->serviceStatus('caddy', 'Caddy Web Server', 'caddy', 'caddy version | head -n 1'),
            $this->serviceStatus('php-fpm', 'PHP-FPM', 'php', 'php -v | head -n 1'),
        ];

        $foundMariaDb = false;

        $mariadb = $this->serviceStatus('mariadb', 'MariaDB', 'mariadb', 'mariadb --version 2>&1 | head -n 1');
        if ($mariadb['status'] !== 'not-installed') {
            $services[] = $mariadb;
            $foundMariaDb = true;
        }

        $mysql = $this->serviceStatus('mysql', 'MySQL', 'mysqld', 'mysql --version 2>&1 | head -n 1');

        if ($mysql['status'] !== 'not-installed') {
            $isMariaDbSymlink = str_contains(strtolower($mysql['version']), 'mariadb');
            if (! $isMariaDbSymlink || ! $foundMariaDb) {
                $services[] = $mysql;
            }
        }

        $postgresql = $this->serviceStatus('postgresql', 'PostgreSQL', 'postgresql', 'psql --version 2>&1 | head -n 1');
        if ($postgresql['status'] !== 'not-installed') {
            $services[] = $postgresql;
        }

        $services[] = $this->serviceStatus('firewalld', 'Firewalld', 'firewalld', 'firewall-cmd --version');
        $services[] = $this->serviceStatus('supervisord', 'Supervisor', 'supervisord', 'supervisord --version');

        return $services;
    }

    /**
     * @return array{selinux_mode: string, firewall_active: bool, firewall_services: array<int, string>}
     */
    public function security(): array
    {
        $selinuxMode = $this->runCommand('getenforce') ?? 'Unknown';
        $firewallState = $this->runCommand('firewall-cmd --state') ?? 'unknown';
        $firewallServicesOutput = $this->runCommand('firewall-cmd --list-services') ?? '';
        $firewallServices = array_values(array_filter(explode(' ', trim($firewallServicesOutput))));

        return [
            'selinux_mode' => $selinuxMode,
            'firewall_active' => $firewallState === 'running',
            'firewall_services' => $firewallServices,
        ];
    }

    public function uptime(): string
    {
        $uptimeRaw = @file_get_contents('/proc/uptime');
        if ($uptimeRaw === false) {
            return 'unknown';
        }

        $seconds = (int) floor((float) explode(' ', trim($uptimeRaw))[0]);
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return "{$days}d {$hours}h {$minutes}m";
    }

    private function cpuLoadPercent(): int
    {
        $load = sys_getloadavg()[0] ?? 0.0;
        $coreCount = (int) ($this->runCommand('nproc') ?? '1');
        $coreCount = max($coreCount, 1);

        return (int) min(100, max(0, round(($load / $coreCount) * 100)));
    }

    private function memoryUsagePercent(): int
    {
        $mem = $this->getMemoryInfo();
        if ($mem['total'] <= 0) {
            return 0;
        }

        $usedKb = max($mem['total'] - $mem['available'], 0);

        return (int) min(100, round(($usedKb / $mem['total']) * 100));
    }

    private function memoryUsageLabel(): string
    {
        $mem = $this->getMemoryInfo();
        if ($mem['total'] <= 0) {
            return '';
        }

        $usedKb = max($mem['total'] - $mem['available'], 0);

        $format = fn (int $kb): string => $kb > 1048576
            ? round($kb / 1048576, 1).'GB'
            : round($kb / 1024, 0).'MB';

        return '('.$format($usedKb).' / '.$format($mem['total']).')';
    }

    /**
     * @return array{total: int, available: int}
     */
    private function getMemoryInfo(): array
    {
        $memoryInfo = @file('/proc/meminfo', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($memoryInfo === false) {
            return ['total' => 0, 'available' => 0];
        }

        $totalKb = 0;
        $availableKb = 0;

        foreach ($memoryInfo as $line) {
            if (str_starts_with($line, 'MemTotal:')) {
                $totalKb = (int) filter_var($line, FILTER_SANITIZE_NUMBER_INT);
            }

            if (str_starts_with($line, 'MemAvailable:')) {
                $availableKb = (int) filter_var($line, FILTER_SANITIZE_NUMBER_INT);
            }
        }

        return ['total' => $totalKb, 'available' => $availableKb];
    }

    private function swapUsagePercent(): int
    {
        $memoryInfo = @file('/proc/meminfo', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($memoryInfo === false) {
            return 0;
        }

        $swapTotalKb = 0;
        $swapFreeKb = 0;

        foreach ($memoryInfo as $line) {
            if (str_starts_with($line, 'SwapTotal:')) {
                $swapTotalKb = (int) filter_var($line, FILTER_SANITIZE_NUMBER_INT);
            }

            if (str_starts_with($line, 'SwapFree:')) {
                $swapFreeKb = (int) filter_var($line, FILTER_SANITIZE_NUMBER_INT);
            }
        }

        if ($swapTotalKb <= 0) {
            return 0;
        }

        $usedKb = max($swapTotalKb - $swapFreeKb, 0);

        return (int) min(100, round(($usedKb / $swapTotalKb) * 100));
    }

    private function diskUsagePercent(string $path): int
    {
        $total = @disk_total_space($path);
        $free = @disk_free_space($path);

        if ($total === false || $free === false || $total <= 0) {
            return 0;
        }

        $used = max($total - $free, 0);

        return (int) min(100, ceil(($used / $total) * 100));
    }

    private function isSeparateMount(string $path): bool
    {
        if (! is_dir($path)) {
            return false;
        }

        $rootDev = @file_get_contents('/proc/self/mountinfo');
        if ($rootDev === false) {
            // Fallback to checking if device IDs are different
            $rootStat = @stat('/');
            $pathStat = @stat($path);

            return $rootStat && $pathStat && $rootStat['dev'] !== $pathStat['dev'];
        }

        // More reliable way to check mount points on Linux
        $mounts = explode("\n", $rootDev);
        foreach ($mounts as $mount) {
            $parts = preg_split('/\s+/', $mount);
            if (isset($parts[4]) && $parts[4] === $path) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{key: string, name: string, status: string, version: string}
     */
    private function serviceStatus(string $key, string $name, string $systemdUnit, string $versionCommand): array
    {
        $version = $this->runCommand($versionCommand);
        $version = preg_replace('/\s+/', ' ', trim($version ?? '')) ?: null;

        $activeState = $this->runCommand("systemctl is-active {$systemdUnit}");

        // Handle RHEL/Rocky versioned PHP-FPM service names (e.g. php84-php-fpm)
        if ($key === 'php-fpm' && ($activeState === null || $activeState === 'unknown')) {
            $versionedUnit = $this->runCommand("systemctl list-units --type=service --all | grep -oE 'php[0-9]+-php-fpm' | head -n 1");
            if ($versionedUnit) {
                $systemdUnit = $versionedUnit;
                $activeState = $this->runCommand("systemctl is-active {$systemdUnit}");
            }
        }

        // Handle MySQL/MariaDB interchangeable service names
        if (($key === 'mariadb' || $key === 'mysql') && ($activeState === null || $activeState === 'unknown')) {
            $altUnit = $key === 'mariadb' ? 'mysqld' : 'mariadb';
            $altState = $this->runCommand("systemctl is-active {$altUnit}");
            if ($altState && $altState !== 'unknown') {
                $systemdUnit = $altUnit;
                $activeState = $altState;
            }
        }

        $status = match ($activeState) {
            'active' => 'running',
            'failed' => 'failed',
            'inactive' => 'stopped',
            default => 'starting',
        };

        if ($activeState === null || $activeState === 'unknown') {
            $status = 'stopped';
        }

        // Fall back to process checks when systemctl output is unavailable or unreliable.
        if ($status !== 'running' && in_array($key, ['caddy', 'nginx', 'php-fpm', 'mariadb', 'mysql'], true)) {
            $processName = match ($key) {
                'php-fpm' => 'php-fpm',
                'mariadb' => 'mariadbd',
                'mysql' => 'mysqld',
                default => $key,
            };

            $hasProcess = $this->runCommand("pgrep -x {$processName}");

            if ($hasProcess !== null) {
                $status = 'running';
            }
        }

        if ($version === null && $status === 'stopped') {
            $status = 'not-installed';
            $version = 'Not installed';
        }

        return [
            'key' => $key,
            'name' => $name,
            'status' => $status,
            'version' => $version ?? 'Unknown',
        ];
    }

    private function runCommand(string $command): ?string
    {
        $output = @shell_exec("{$command} 2>/dev/null");
        if ($output === null) {
            return null;
        }

        $trimmed = trim($output);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * Extract version number from version string with special handling for MariaDB.
     *
     * MariaDB output looks like: "mariadb from 10.11.15-MariaDB, client 15.2..."
     * We want to extract "10.11" not "15.2"
     */
    private function extractVersion(string $versionString, string $type): ?string
    {
        // Special handling for MariaDB - look for the version before "MariaDB" keyword
        if ($type === 'mariadb' || str_contains(strtolower($versionString), 'mariadb')) {
            // Match pattern like "10.11.15-MariaDB" or "from 10.5.29-MariaDB"
            if (preg_match('/(\d+\.\d+)(?:\.\d+)?-MariaDB/i', $versionString, $matches)) {
                return $matches[1]; // Returns "10.11" or "10.5"
            }
        }

        // For all other software, extract first version number found
        if (preg_match('/(\d+\.\d+)(?:\.\d+)?/', $versionString, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
