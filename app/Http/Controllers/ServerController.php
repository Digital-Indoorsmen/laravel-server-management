<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Services\ServerConnectionService;
use Illuminate\Http\Request;

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

    public function updateWebServer(Request $request, Server $server)
    {
        $validated = $request->validate([
            'web_server' => ['required', 'string', 'in:nginx,caddy'],
        ]);

        $server->update($validated);

        return back()->with('success', 'Web server updated.');
    }
}
