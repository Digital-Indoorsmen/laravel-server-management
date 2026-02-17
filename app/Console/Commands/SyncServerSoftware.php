<?php

namespace App\Console\Commands;

use App\Models\Server;
use App\Services\PanelHealthService;
use Illuminate\Console\Command;

class SyncServerSoftware extends Command
{
    protected $signature = 'server:sync-software {--server-id= : Specific server ID to sync}';

    protected $description = 'Clear and resync server software versions';

    public function handle(PanelHealthService $health): int
    {
        $serverId = $this->option('server-id');

        if ($serverId) {
            $server = Server::find($serverId);
            if (! $server) {
                $this->error("Server {$serverId} not found");

                return 1;
            }
            $servers = collect([$server]);
        } else {
            $servers = Server::all();
        }

        foreach ($servers as $server) {
            $this->info("Clearing software data for server: {$server->name} ({$server->ip_address})");

            // Clear existing software data
            $server->update([
                'software' => [],
                'database_engines' => [],
            ]);

            $this->info('Resyncing...');

            // Trigger resync
            $health->systemStats($server);

            $server->refresh();

            $this->info('Updated software:');
            foreach ($server->software ?? [] as $type => $versions) {
                foreach ($versions as $version => $data) {
                    $this->line("  - {$type}: {$version}");
                }
            }

            $this->newLine();
        }

        $this->info('Sync complete!');

        return 0;
    }
}
