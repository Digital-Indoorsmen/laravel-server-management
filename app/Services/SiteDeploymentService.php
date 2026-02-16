<?php

namespace App\Services;

use App\Models\Deployment;
use App\Models\Site;

class SiteDeploymentService
{
    public function run(Deployment $deployment): Deployment
    {
        $deployment->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $site = $deployment->site()->with('server')->firstOrFail();
            $command = $this->buildDeploymentCommand($site, $deployment->branch);

            $output = app(ServerConnectionService::class)->executeCommand($site->server, $command);
            $commitHash = $this->extractCommitHash($output);

            $deployment->update([
                'status' => 'succeeded',
                'stdout' => $output,
                'stderr' => null,
                'commit_hash' => $commitHash,
                'finished_at' => now(),
            ]);
        } catch (\Throwable $throwable) {
            $deployment->update([
                'status' => 'failed',
                'stderr' => $throwable->getMessage(),
                'finished_at' => now(),
            ]);
        }

        return $deployment->fresh(['actor', 'site.server']);
    }

    public function queue(Site $site, ?int $actorId = null, string $triggeredVia = 'ui', string $branch = 'main'): Deployment
    {
        return $site->deployments()->create([
            'actor_id' => $actorId,
            'triggered_via' => $triggeredVia,
            'status' => 'queued',
            'branch' => $branch,
        ]);
    }

    private function buildDeploymentCommand(Site $site, string $branch): string
    {
        if (! preg_match('/^[a-zA-Z0-9_-]+$/', $site->system_user)) {
            throw new \RuntimeException('Invalid site system user for deployment command.');
        }

        if (! preg_match('/^[A-Za-z0-9._\/-]+$/', $branch)) {
            throw new \RuntimeException('Invalid branch format.');
        }

        $baseDirectory = $site->document_root;

        if ($site->app_type === 'laravel' && str_ends_with($baseDirectory, '/public')) {
            $baseDirectory = dirname($baseDirectory);
        }

        if (! preg_match('/^\/[A-Za-z0-9._\/-]+$/', $baseDirectory)) {
            throw new \RuntimeException('Invalid deployment path.');
        }

        $gitCommands = "cd {$baseDirectory}"
            ." && git fetch origin {$branch}"
            ." && git checkout {$branch}"
            ." && git pull --ff-only origin {$branch}"
            .' && git rev-parse --short HEAD';

        return sprintf('runuser -u %s -- bash -lc %s', $site->system_user, escapeshellarg($gitCommands));
    }

    private function extractCommitHash(string $output): ?string
    {
        if (preg_match('/\b([a-f0-9]{7,40})\b/i', trim($output), $matches) === 1) {
            return strtolower($matches[1]);
        }

        return null;
    }
}
