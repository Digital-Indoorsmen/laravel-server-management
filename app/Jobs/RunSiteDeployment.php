<?php

namespace App\Jobs;

use App\Models\Deployment;
use App\Services\SiteDeploymentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunSiteDeployment implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $deploymentId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SiteDeploymentService $deploymentService): void
    {
        $deployment = Deployment::query()->find($this->deploymentId);

        if (! $deployment) {
            return;
        }

        $deploymentService->run($deployment);
    }
}
