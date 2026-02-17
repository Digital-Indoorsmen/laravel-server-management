<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeploymentRequest;
use App\Jobs\RunSiteDeployment;
use App\Models\Deployment;
use App\Models\Site;
use App\Services\SiteDeploymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class SiteDeploymentController extends Controller
{
    public function __construct(
        protected SiteDeploymentService $deploymentService
    ) {}

    public function index(Site $site): Response
    {
        return Inertia::render('Sites/Workspace/Deployments', [
            'site' => $site->load('server'),
            'workspace' => [
                'activeTab' => 'deployments',
                'title' => 'Deployments',
                'description' => 'Release history and deployment execution for this site.',
            ],
            'deployments' => $site->deployments()
                ->with('actor:id,name,email')
                ->latest()
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function store(StoreDeploymentRequest $request, Site $site): RedirectResponse
    {
        $deployment = $this->deploymentService->queue(
            site: $site,
            actorId: $request->user()?->id,
            triggeredVia: 'ui',
            branch: (string) ($request->validated('branch') ?? 'main'),
        );

        RunSiteDeployment::dispatch($deployment->id)->onQueue('deployments');

        return redirect()
            ->route('sites.workspace.deployments.show', [$site, $deployment])
            ->with('success', 'Deployment queued successfully.');
    }

    public function show(Site $site, Deployment $deployment): Response
    {
        abort_unless($deployment->site_id === $site->id, 404);

        return Inertia::render('Sites/Workspace/DeploymentShow', [
            'site' => $site->load('server'),
            'workspace' => [
                'activeTab' => 'deployments',
                'title' => 'Deployments',
                'description' => 'Release history and deployment execution for this site.',
            ],
            'deployment' => $deployment->load('actor:id,name,email'),
        ]);
    }

    public function webhook(string $token): HttpResponse
    {
        $site = Site::where('deploy_hook_url', $token)->firstOrFail();

        $deployment = $this->deploymentService->queue(
            site: $site,
            actorId: null,
            triggeredVia: 'webhook',
            branch: 'main',
        );

        RunSiteDeployment::dispatch($deployment->id)->onQueue('deployments');

        return response('Deployment queued successfully.', 200);
    }
}
