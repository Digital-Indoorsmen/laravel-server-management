<?php

namespace App\Http\Controllers;

use App\Models\SshKey;
use App\Services\SshKeyService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SshKeyController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('SshKeys/Index', [
            'sshKeys' => SshKey::latest()->get(),
        ]);
    }

    public function store(Request $request, SshKeyService $service)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:ed25519,rsa',
        ]);

        $key = $service->generate($request->name, $request->type);

        return back()->with('success', "SSH Key '{$key->name}' generated successfully.");
    }

    public function import(Request $request, SshKeyService $service)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'public_key' => 'required|string',
        ]);

        $key = $service->import($request->name, $request->public_key);

        return back()->with('success', "SSH Key '{$key->name}' imported successfully.");
    }

    public function download(SshKey $sshKey)
    {
        if (! $sshKey->private_key) {
            return back()->with('error', 'This key was imported and does not have a private key available for download.');
        }

        $filename = str($sshKey->name)->slug().'_id_rsa';
        if (str_starts_with($sshKey->public_key, 'ssh-ed25519')) {
            $filename = str($sshKey->name)->slug().'_id_ed25519';
        }

        return response($sshKey->private_key)
            ->header('Content-Type', 'application/octet-stream')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    public function destroy(SshKey $sshKey)
    {
        $sshKey->delete();

        return back()->with('success', 'SSH Key deleted successfully.');
    }
}
