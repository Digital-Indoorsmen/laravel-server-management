<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSiteDeploymentsRequest;
use App\Http\Requests\UpdateSiteEnvironmentRequest;
use App\Http\Requests\UpdateSiteGeneralRequest;
use App\Models\Site;
use App\Services\SiteProvisioningService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SiteSettingsController extends Controller
{
    public function __construct(
        protected SiteProvisioningService $provisioner
    ) {}

    public function show(Site $site, string $section = 'general'): Response
    {
        $validSections = ['general', 'deployments', 'environment', 'composer', 'notifications', 'integrations'];
        if (! in_array($section, $validSections)) {
            $section = 'general';
        }

        $data = [
            'site' => $site->load('server'),
            'workspace' => [
                'activeTab' => 'settings',
                'title' => 'Settings',
                'description' => 'Site metadata and safe reconfiguration defaults.',
            ],
            'activeSection' => $section,
            'phpVersions' => ['7.4', '8.1', '8.2', '8.3', '8.4', '8.5'],
            'appTypes' => ['wordpress', 'laravel', 'generic'],
        ];

        if ($section === 'environment') {
            $data['envContent'] = Inertia::lazy(fn () => $this->provisioner->getEnvContent($site));
        }

        return Inertia::render('Sites/Workspace/Settings', $data);
    }

    public function updateGeneral(UpdateSiteGeneralRequest $request, Site $site): RedirectResponse
    {
        $site->update($request->validated());

        return back()->with('success', 'General settings updated.');
    }

    public function updateDeployments(UpdateSiteDeploymentsRequest $request, Site $site): RedirectResponse
    {
        $site->update($request->validated());

        return back()->with('success', 'Deployment settings updated.');
    }

    public function updateEnvironment(UpdateSiteEnvironmentRequest $request, Site $site): RedirectResponse
    {
        if ($request->has('content')) {
            $this->provisioner->updateEnvContent($site, $request->validated('content'));
        }

        $site->update($request->safe()->only(['auto_cache_config', 'auto_restart_queue']));

        return back()->with('success', 'Environment settings updated.');
    }
}
