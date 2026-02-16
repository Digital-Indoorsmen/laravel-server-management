<?php

use App\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the setup script with a valid token', function () {
    $server = Server::factory()->create([
        'name' => 'Test Server',
        'setup_token' => 'test-token-123',
        'web_server' => 'nginx',
    ]);

    $response = $this->get(route('setup.script', ['token' => 'test-token-123']));

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    $response->assertSee('Starting core setup script for server '.$server->id);
    $response->assertSee('test-token-123/callback');
    $response->assertSee('Installing Nginx');
});

it('renders caddy setup steps when server uses caddy', function () {
    $server = Server::factory()->create([
        'name' => 'Caddy Server',
        'setup_token' => 'test-token-caddy',
        'web_server' => 'caddy',
    ]);

    $response = $this->get(route('setup.script', ['token' => 'test-token-caddy']));

    $response->assertStatus(200);
    $response->assertSee('Installing Caddy');
    $response->assertSee('Configuring Caddy for the management panel on port 8095');
});

it('returns 404 for an invalid token', function () {
    $response = $this->get(route('setup.script', ['token' => 'invalid-token']));

    $response->assertStatus(404);
});

it('updates server status on setup started callback', function () {
    $server = Server::factory()->create([
        'setup_token' => 'test-token-123',
        'status' => 'pending',
    ]);

    $response = $this->postJson(route('setup.callback', ['token' => 'test-token-123']), [
        'status' => 'provisioning',
    ]);

    $response->assertStatus(200);
    expect($server->fresh()->status)->toBe('provisioning');
});

it('updates server status on setup ready callback', function () {
    $server = Server::factory()->create([
        'setup_token' => 'test-token-123',
        'status' => 'provisioning',
    ]);

    $response = $this->postJson(route('setup.callback', ['token' => 'test-token-123']), [
        'status' => 'ready',
    ]);

    $response->assertStatus(200);
    $server = $server->fresh();
    expect($server->status)->toBe('ready');
    expect($server->setup_completed_at)->not->toBeNull();
});
