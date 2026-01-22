<?php

namespace App\Http\Controllers;

use App\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class SetupScriptController extends Controller
{
    public function show(string $token): Response
    {
        $server = Server::where('setup_token', $token)->firstOrFail();

        Log::info("Setup script requested for server: {$server->id}", [
            'ip' => request()->ip(),
            'server_name' => $server->name,
        ]);

        $callbackUrl = route('setup.callback', ['token' => $token]);

        $content = view('scripts.setup', [
            'server' => $server,
            'callbackUrl' => $callbackUrl,
        ])->render();

        return response($content)
            ->header('Content-Type', 'text/plain');
    }

    public function callback(Request $request, string $token): Response
    {
        $server = Server::where('setup_token', $token)->firstOrFail();

        $status = $request->input('status');

        Log::info("Setup callback received for server: {$server->id}", [
            'status' => $status,
            'ip' => $request->ip(),
        ]);

        if ($status === 'ready') {
            $server->update([
                'status' => 'ready',
                'setup_completed_at' => now(),
            ]);
        } elseif ($status === 'provisioning') {
            $server->update(['status' => 'provisioning']);
        }

        return response('OK');
    }
}
