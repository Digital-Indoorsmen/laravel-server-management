<?php

namespace App\Jobs;

use App\Models\SoftwareInstallation;
use App\Services\SoftwareProvisioningService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class InstallSoftware implements ShouldQueue
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
    public function handle(SoftwareProvisioningService $service): void
    {
        $installation = SoftwareInstallation::findOrFail($this->installationId);

        if ($installation->action === 'upgrade') {
            $service->upgradeSoftware($installation);

            return;
        }

        $service->installSoftware($installation);
    }
}
