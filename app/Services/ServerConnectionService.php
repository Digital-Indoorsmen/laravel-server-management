<?php

namespace App\Services;

use App\Models\Server;
use App\Models\ServerLog;
use Illuminate\Support\Facades\Log;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;

class ServerConnectionService
{
    /**
     * Test connection to a server and retrieve metadata.
     */
    public function testConnection(Server $server): array
    {
        try {
            $ssh = $this->getSshConnection($server);

            $os = $ssh->exec("cat /etc/os-release | grep 'PRETTY_NAME' | cut -d'\"' -f2");
            $ram = $ssh->exec("free -m | awk '/Mem:/ { print $2 }'");
            $cpu = $ssh->exec('nproc');
            $uptime = $ssh->exec('uptime -p');

            $metadata = [
                'os_version' => trim($os),
                'ram_mb' => (int) trim($ram),
                'cpu_cores' => (int) trim($cpu),
                'uptime' => trim($uptime),
                'last_check_at' => now()->toDateTimeString(),
            ];

            $server->update([
                'status' => 'active',
                'os_version' => $metadata['os_version'],
            ]);

            $this->log($server, 'info', 'Connection test successful', $metadata);

            return [
                'success' => true,
                'metadata' => $metadata,
            ];

        } catch (\Exception $e) {
            $this->log($server, 'error', 'Connection test failed: '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Run a command on a server.
     */
    public function runCommand(Server $server, string $command): string
    {
        $ssh = $this->getSshConnection($server);

        return $ssh->exec($command);
    }

    /**
     * Get an established SSH connection.
     */
    protected function getSshConnection(Server $server): SSH2
    {
        if (! $server->sshKey) {
            throw new \Exception('Server has no associated SSH key.');
        }

        $ssh = new SSH2($server->ip_address);

        $key = PublicKeyLoader::load($server->sshKey->private_key);

        if (! $ssh->login('panel', $key)) {
            throw new \Exception("SSH login failed for user 'panel'.");
        }

        return $ssh;
    }

    /**
     * Log an event for a server.
     */
    protected function log(Server $server, string $level, string $message, array $metadata = []): void
    {
        ServerLog::create([
            'server_id' => $server->id,
            'level' => $level,
            'message' => $message,
            'metadata' => $metadata,
        ]);

        Log::channel('stack')->info("Server [{$server->id}]: {$message}", $metadata);
    }
}
