<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Services\ServerConnectionService;

class ServerController extends Controller
{
    public function testConnection(Server $server, ServerConnectionService $service)
    {
        $result = $service->testConnection($server);

        if ($result['success']) {
            return back()->with('success', 'Connection test successful.');
        }

        return back()->with('error', 'Connection test failed: '.$result['error']);
    }

    public function logs(Server $server)
    {
        return response()->json($server->logs()->latest()->limit(50)->get());
    }
}
