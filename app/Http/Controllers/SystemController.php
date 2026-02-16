<?php

namespace App\Http\Controllers;

use App\Services\PanelHealthService;
use Inertia\Inertia;
use Inertia\Response;

class SystemController extends Controller
{
    public function __invoke(PanelHealthService $panelHealth): Response
    {
        return Inertia::render('System/Index', [
            'systemStats' => $panelHealth->systemStats(),
            'services' => $panelHealth->services(),
            'security' => $panelHealth->security(),
            'uptime' => $panelHealth->uptime(),
        ]);
    }
}
