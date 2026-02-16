<?php

namespace App\Http\Controllers;

use App\Services\PanelHealthService;
use App\Services\ServiceControlService;
use Inertia\Inertia;
use Inertia\Response;

class SystemController extends Controller
{
    public function __invoke(PanelHealthService $panelHealth, ServiceControlService $serviceControl): Response
    {
        return Inertia::render('System/Index', [
            'systemStats' => $panelHealth->systemStats(),
            'services' => $panelHealth->services(),
            'security' => $panelHealth->security(),
            'uptime' => $panelHealth->uptime(),
            'canManageServices' => $serviceControl->canManageServices(),
        ]);
    }
}
