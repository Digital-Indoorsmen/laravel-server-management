<?php

use App\Models\User;
use App\Services\ServiceControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests to login when controlling services', function () {
    $response = $this->post(route('system.services.control', ['service' => 'nginx', 'action' => 'restart']));

    $response->assertRedirect(route('login'));
});

it('validates service and action values', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('system.index'))
        ->post(route('system.services.control', ['service' => 'invalid', 'action' => 'hack']));

    $response->assertRedirect(route('system.index'));
    $response->assertSessionHasErrors(['service', 'action']);
});

it('runs a service control action and stores success flash', function () {
    $user = User::factory()->create();

    $mock = \Mockery::mock(ServiceControlService::class);
    $mock->shouldReceive('control')
        ->once()
        ->with('nginx', 'restart')
        ->andReturn([
            'ok' => true,
            'message' => 'Service nginx restart completed.',
        ]);

    app()->instance(ServiceControlService::class, $mock);

    $response = $this->actingAs($user)
        ->from(route('system.index'))
        ->post(route('system.services.control', ['service' => 'nginx', 'action' => 'restart']));

    $response->assertRedirect(route('system.index'));
    $response->assertSessionHas('success', 'Service nginx restart completed.');
});

it('stores service control errors in session', function () {
    $user = User::factory()->create();

    $mock = \Mockery::mock(ServiceControlService::class);
    $mock->shouldReceive('control')
        ->once()
        ->with('caddy', 'stop')
        ->andReturn([
            'ok' => false,
            'message' => 'Permission denied.',
        ]);

    app()->instance(ServiceControlService::class, $mock);

    $response = $this->actingAs($user)
        ->from(route('system.index'))
        ->post(route('system.services.control', ['service' => 'caddy', 'action' => 'stop']));

    $response->assertRedirect(route('system.index'));
    $response->assertSessionHasErrors('service_control');
});
