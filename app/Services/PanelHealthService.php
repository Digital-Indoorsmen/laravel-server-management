<?php

namespace App\Services;

class PanelHealthService
{
    /**
     * @return array<int, array{name: string, value: int, unit: string}>
     */
    public function systemStats(): array
    {
        return [
            [
                'name' => 'CPU Load',
                'value' => $this->cpuLoadPercent(),
                'unit' => '%',
            ],
            [
                'name' => 'RAM Usage',
                'value' => $this->memoryUsagePercent(),
                'unit' => '%',
            ],
            [
                'name' => 'Disk Space',
                'value' => $this->diskUsagePercent('/'),
                'unit' => '%',
            ],
            [
                'name' => 'Swap Usage',
                'value' => $this->swapUsagePercent(),
                'unit' => '%',
            ],
        ];
    }

    /**
     * @return array<int, array{name: string, status: string, version: string}>
     */
    public function services(): array
    {
        return [
            $this->serviceStatus('Nginx Web Server', 'nginx', 'nginx -v'),
            $this->serviceStatus('Caddy Web Server', 'caddy', 'caddy version'),
            $this->serviceStatus('PHP-FPM', 'php-fpm', 'php-fpm -v | head -n 1'),
            $this->serviceStatus('Firewalld', 'firewalld', 'firewall-cmd --version'),
            $this->serviceStatus('Supervisor', 'supervisord', 'supervisord --version'),
        ];
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
        $memoryInfo = @file('/proc/meminfo', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($memoryInfo === false) {
            return 0;
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

        if ($totalKb <= 0) {
            return 0;
        }

        $usedKb = max($totalKb - $availableKb, 0);

        return (int) min(100, round(($usedKb / $totalKb) * 100));
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

        return (int) min(100, round(($used / $total) * 100));
    }

    /**
     * @return array{name: string, status: string, version: string}
     */
    private function serviceStatus(string $name, string $systemdUnit, string $versionCommand): array
    {
        $activeState = $this->runCommand("systemctl is-active {$systemdUnit}");
        $status = match ($activeState) {
            'active' => 'running',
            'failed' => 'failed',
            'inactive' => 'stopped',
            default => 'starting',
        };

        if ($activeState === null) {
            $status = 'stopped';
        }

        $version = $this->runCommand($versionCommand) ?? 'Not installed';
        $version = preg_replace('/\s+/', ' ', trim($version)) ?: 'Not installed';

        return [
            'name' => $name,
            'status' => $status,
            'version' => $version,
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
