<?php

namespace App\Services;

class PanelHealthService
{
    /**
     * @return array<int, array{name: string, value: int, unit: string}>
     */
    public function systemStats(): array
    {
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
            $this->serviceStatus('php-fpm', 'PHP-FPM', 'php-fpm', 'php-fpm -v | head -n 1'),
        ];

        foreach (['mariadb' => 'MariaDB', 'mysql' => 'MySQL', 'postgresql' => 'PostgreSQL'] as $key => $name) {
            $status = $this->serviceStatus($key, $name, $key, "{$key} --version 2>&1 | head -n 1");
            if ($status['status'] !== 'not-installed') {
                $services[] = $status;
            }
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
                default => $key
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
}
