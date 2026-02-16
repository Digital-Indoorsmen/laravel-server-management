<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Services\PanelHealthService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(PanelHealthService $health): Response
    {
        return Inertia::render('Dashboard', [
            'servers' => Server::query()->with('sshKey')->latest()->get(),
            'systemStats' => $health->systemStats(),
            'services' => $health->services(),
            'security' => $health->security(),
            'uptime' => $health->uptime(),
        ]);
    }
}
