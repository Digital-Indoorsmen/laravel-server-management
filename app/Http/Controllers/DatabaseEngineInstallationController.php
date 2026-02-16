<?php

namespace App\Http\Controllers;

use App\Jobs\InstallDatabaseEngine;
use App\Models\DatabaseEngineInstallation;
use App\Models\Server;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DatabaseEngineInstallationController extends Controller
{
    public function index(Server $server)
    {
        return Inertia::render('DatabaseEngines/Index', [
            'server' => $server,
            'installations' => $server->databaseEngineInstallations()->latest()->get(),
            'availableEngines' => [
                [
                    'id' => 'mariadb',
                    'name' => 'MariaDB',
                    'versions' => ['10.3', '10.5', '10.11'],
                ],
                [
                    'id' => 'mysql',
                    'name' => 'MySQL',
                    'versions' => ['8.0', '8.4'],
                ],
                [
                    'id' => 'postgresql',
                    'name' => 'PostgreSQL',
                    'versions' => ['13', '15', '16'],
                ],
            ],
            'installedEngines' => $server->database_engines ?? [],
        ]);
    }

    public function store(Request $request, Server $server)
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:mariadb,mysql,postgresql'],
            'action' => ['sometimes', 'string', 'in:install,upgrade'],
            'version' => ['required_if:action,install', 'nullable', 'string'],
        ]);

        $action = $validated['action'] ?? 'install';

        $installation = DatabaseEngineInstallation::create([
            'server_id' => $server->id,
            'type' => $validated['type'],
            'version' => $validated['version'] ?? null,
            'action' => $action,
            'status' => 'queued',
        ]);

        InstallDatabaseEngine::dispatch($installation->id);

        $verb = $action === 'upgrade' ? 'Upgrade' : 'Installation';

        return back()->with('success', "{$verb} of {$validated['type']} queued.");
    }

    public function show(DatabaseEngineInstallation $installation)
    {
        return Inertia::render('DatabaseEngines/Show', [
            'installation' => $installation->load('server'),
        ]);
    }
}
