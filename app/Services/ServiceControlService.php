<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class ServiceControlService
{
    /**
     * @return array<int, string>
     */
    public static function allowedServices(): array
    {
        return ['nginx', 'caddy', 'php-fpm', 'firewalld', 'supervisord'];
    }

    /**
     * @return array<int, string>
     */
    public static function allowedActions(): array
    {
        return ['start', 'restart', 'stop'];
    }

    public function canManageServices(): bool
    {
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            return true;
        }

        $process = new Process(['sudo', '-n', 'true']);
        $process->run();

        return $process->isSuccessful();
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function control(string $service, string $action): array
    {
        if (! in_array($service, self::allowedServices(), true)) {
            return [
                'ok' => false,
                'message' => "Unsupported service [{$service}].",
            ];
        }

        if (! in_array($action, self::allowedActions(), true)) {
            return [
                'ok' => false,
                'message' => "Unsupported action [{$action}].",
            ];
        }

        $command = ['systemctl', $action, $service];

        if (! (function_exists('posix_geteuid') && posix_geteuid() === 0)) {
            $command = ['sudo', '-n', ...$command];
        }

        $process = new Process($command);
        $process->run();

        if (! $process->isSuccessful()) {
            $error = trim($process->getErrorOutput() ?: $process->getOutput());

            return [
                'ok' => false,
                'message' => $error !== '' ? $error : "Failed to {$action} {$service}.",
            ];
        }

        return [
            'ok' => true,
            'message' => "Service {$service} {$action} completed.",
        ];
    }
}
