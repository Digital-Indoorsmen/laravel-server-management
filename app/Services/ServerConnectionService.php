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
            if ($this->isLocalServer($server)) {
                $os = $this->runLocalCommand("cat /etc/os-release | grep 'PRETTY_NAME' | cut -d'\"' -f2");
                $ram = $this->runLocalCommand("free -m | awk '/Mem:/ { print $2 }'");
                $cpu = $this->runLocalCommand('nproc');
                $uptime = $this->runLocalCommand('uptime -p');
            } else {
                $ssh = $this->getSshConnection($server);

                $os = $ssh->exec("cat /etc/os-release | grep 'PRETTY_NAME' | cut -d'\"' -f2");
                $ram = $ssh->exec("free -m | awk '/Mem:/ { print $2 }'");
                $cpu = $ssh->exec('nproc');
                $uptime = $ssh->exec('uptime -p');
            }

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
        if ($this->isLocalServer($server)) {
            return $this->runLocalCommand($command);
        }

        $ssh = $this->getSshConnection($server);

        return $ssh->exec($command);
    }

    /**
     * Check if the server is the local host.
     */
    protected function isLocalServer(Server $server): bool
    {
        $ip = $server->ip_address;

        if ($ip === '127.0.0.1' || $ip === 'localhost' || $ip === '::1') {
            return true;
        }

        // Check if the IP matches any local interface
        // We cache this for the request to avoid repeated shell calls
        static $localIps = null;
        if ($localIps === null) {
            $output = shell_exec('hostname -I 2>/dev/null') ?? '';
            $localIps = array_filter(explode(' ', trim($output)));
        }

        if (in_array($ip, $localIps, true)) {
            return true;
        }

        // Check if hostname matches
        if ($server->hostname === gethostname()) {
            return true;
        }

        return false;
    }

    /**
     * Run a command locally on the panel host.
     */
    protected function runLocalCommand(string $command): string
    {
        // We use proc_open or shell_exec for local commands.
        // Since we need to capture output and handle potentially long running scripts,
        // we'll use a simple shell_exec for now, but in a production environment
        // we might want something more robust like Symfony Process.
        $output = shell_exec($command.' 2>&1');

        if ($output === null) {
            throw new \Exception("Local command execution failed: {$command}");
        }

        return $output;
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
