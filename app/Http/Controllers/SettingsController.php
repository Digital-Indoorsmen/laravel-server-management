<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Settings', [
            'preferences' => auth()->user()->preference ?? [
                'package_manager' => 'bun',
            ],
            'sshKeys' => \App\Models\SshKey::latest()->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'package_manager' => 'required|string|in:bun,npm',
        ]);

        auth()->user()->preference()->updateOrCreate(
            ['user_id' => auth()->id()],
            $validated
        );

        return back();
    }
}
