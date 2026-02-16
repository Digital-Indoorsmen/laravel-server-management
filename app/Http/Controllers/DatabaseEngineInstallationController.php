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
                ['id' => 'mariadb', 'name' => 'MariaDB'],
                ['id' => 'postgresql', 'name' => 'PostgreSQL'],
            ],
            'installedEngines' => $server->database_engines ?? [],
        ]);
    }

    public function store(Request $request, Server $server)
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:mariadb,postgresql'],
        ]);

        $installation = DatabaseEngineInstallation::create([
            'server_id' => $server->id,
            'type' => $validated['type'],
            'status' => 'queued',
        ]);

        InstallDatabaseEngine::dispatch($installation->id);

        return back()->with('success', "Installation of {$validated['type']} queued.");
    }

    public function show(DatabaseEngineInstallation $installation)
    {
        return Inertia::render('DatabaseEngines/Show', [
            'installation' => $installation->load('server'),
        ]);
    }
}
