<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Inertia\Inertia;
use Inertia\Response;

class SiteWorkspaceController extends Controller
{
    public function overview(Site $site): Response
    {
        return $this->renderTab(
            $site,
            'overview',
            'Overview',
            'Operational status and quick links for this site.',
            'Sites/Workspace/Overview',
        );
    }

    public function deployments(Site $site): Response
    {
        return $this->renderTab(
            $site,
            'deployments',
            'Deployments',
            'Release history and deployment execution for this site.',
            'Sites/Workspace/Deployments',
        );
    }

    public function processes(Site $site): Response
    {
        return $this->renderTab(
            $site,
            'processes',
            'Processes',
            'Background workers and supervisor-managed process controls.',
            'Sites/Workspace/Processes',
        );
    }

    public function commands(Site $site): Response
    {
        return $this->renderTab(
            $site,
            'commands',
            'Commands',
            'Run and review one-off site commands safely.',
            'Sites/Workspace/Commands',
        );
    }

    public function network(Site $site): Response
    {
        return $this->renderTab(
            $site,
            'network',
            'Network',
            'Redirects and network-level security behavior for this site.',
            'Sites/Workspace/Network',
        );
    }

    public function observe(Site $site): Response
    {
        return $this->renderTab(
            $site,
            'observe',
            'Observe',
            'Heartbeats, events, and operational activity timeline.',
            'Sites/Workspace/Observe',
        );
    }

    public function domains(Site $site): Response
    {
        return $this->renderTab(
            $site,
            'domains',
            'Domains',
            'Manage domains, aliases, and certificate lifecycle.',
            'Sites/Workspace/Domains',
        );
    }

    private function renderTab(Site $site, string $tab, string $title, string $description, string $component): Response
    {
        return Inertia::render($component, [
            'site' => $site->load('server'),
            'workspace' => [
                'activeTab' => $tab,
                'title' => $title,
                'description' => $description,
            ],
        ]);
    }
}
