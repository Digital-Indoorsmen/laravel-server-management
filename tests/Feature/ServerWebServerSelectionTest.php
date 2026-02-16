<?php

use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('server web server can be updated from dashboard endpoint', function () {
    $server = Server::factory()->create([
        'web_server' => 'nginx',
    ]);
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->patch(route('servers.web-server.update', $server), [
            'web_server' => 'caddy',
        ]);

    $response->assertRedirect();
    expect($server->fresh()->web_server)->toBe('caddy');
});

test('server web server update validates allowed values', function () {
    $server = Server::factory()->create([
        'web_server' => 'nginx',
    ]);
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->patch(route('servers.web-server.update', $server), [
            'web_server' => 'apache',
        ]);

    $response->assertSessionHasErrors(['web_server']);
    expect($server->fresh()->web_server)->toBe('nginx');
});
