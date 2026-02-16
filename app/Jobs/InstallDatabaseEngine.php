<?php

namespace App\Jobs;

use App\Models\DatabaseEngineInstallation;
use App\Services\DatabaseProvisioningService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class InstallDatabaseEngine implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $installationId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(DatabaseProvisioningService $service): void
    {
        $installation = DatabaseEngineInstallation::findOrFail($this->installationId);

        if ($installation->action === 'upgrade') {
            $service->upgradeEngine($installation);

            return;
        }

        $service->installEngine($installation);
    }
}
