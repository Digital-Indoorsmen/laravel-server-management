<?php

use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('renders real dashboard props for authenticated users', function () {
    $user = User::factory()->create();

    Server::factory()->create([
        'setup_completed_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->has('servers', 1)
        ->has('systemStats', 4)
        ->where('systemStats.0.name', 'CPU Load')
        ->has('services')
        ->has('security')
        ->has('uptime')
    );
});
