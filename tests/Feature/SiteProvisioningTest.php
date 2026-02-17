<?php

use App\Models\Server;
use App\Models\Site;
use App\Services\ServerConnectionService;
use App\Services\SiteProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

test('site can be provisioned', function () {
    $server = Server::factory()->create();

    // Mock the connection service
    $this->mock(ServerConnectionService::class, function (MockInterface $mock) {
        // Allow any command and return a success output
        $mock->shouldReceive('runCommand')->andReturn('');
    });

    $service = app(SiteProvisioningService::class);

    $site = Site::create([
        'server_id' => $server->id,
        'domain' => 'example.com',
        'system_user' => 'example',
        'php_version' => '8.3',
        'app_type' => 'laravel',
        'document_root' => '/home/example/public_html/public',
        'status' => 'creating',
    ]);

    $service->provision($site);

    $site->refresh();
    expect($site->status)->toBe('active');
});

test('site can be provisioned with caddy', function () {
    $server = Server::factory()->create([
        'web_server' => 'caddy',
    ]);

    $this->mock(ServerConnectionService::class, function (MockInterface $mock) {
        $mock->shouldReceive('runCommand')->byDefault()->andReturn('');

        $mock->shouldReceive('runCommand')
            ->withArgs(function ($server, $command) {
                return str_contains($command, 'mkdir -p /etc/caddy/sites-enabled');
            })
            ->once()
            ->andReturn('');

        $mock->shouldReceive('runCommand')
            ->withArgs(function ($server, $command) {
                return str_contains($command, '/etc/caddy/sites-enabled/caddy-example.com.caddy');
            })
            ->once()
            ->andReturn('');

        $mock->shouldReceive('runCommand')
            ->withArgs(function ($server, $command) {
                return str_contains($command, 'caddy validate --config /etc/caddy/Caddyfile');
            })
            ->once()
            ->andReturn('__PANEL_CADDY_VALID__');

        $mock->shouldReceive('runCommand')
            ->withArgs(function ($server, $command) {
                return str_contains($command, 'systemctl reload caddy');
            })
            ->once()
            ->andReturn('');
    });

    $service = app(SiteProvisioningService::class);

    $site = Site::create([
        'server_id' => $server->id,
        'domain' => 'caddy-example.com',
        'system_user' => 'caddyexample',
        'php_version' => '8.3',
        'app_type' => 'laravel',
        'document_root' => '/home/caddyexample/public_html/public',
        'status' => 'creating',
    ]);

    $service->provision($site);

    expect($site->fresh()->status)->toBe('active');
});

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

test('site creation via controller triggers provisioning', function () {
    $server = Server::factory()->create();
    $user = User::factory()->create();

    $this->mock(SiteProvisioningService::class, function (MockInterface $mock) {
        $mock->shouldReceive('provision')->once();
    });

    $response = $this->withoutMiddleware([ValidateCsrfToken::class])
        ->actingAs($user)
        ->post(route('servers.sites.store', $server), [
            'domain' => 'newsite.com',
            'system_user' => 'newsite',
            'php_version' => '8.2',
            'app_type' => 'wordpress',
        ]);

    $response->assertRedirect(route('servers.sites.index', $server));

    $this->assertDatabaseHas('sites', [
        'domain' => 'newsite.com',
        'system_user' => 'newsite',
        'php_version' => '8.2',
        'status' => 'creating',
    ]);
});

test('site creation uses server web server (auto-detected)', function () {
    $server = Server::factory()->create([
        'web_server' => 'caddy',
    ]);
    $user = User::factory()->create();

    $this->mock(SiteProvisioningService::class, function (MockInterface $mock) {
        $mock->shouldReceive('provision')->once();
    });

    $response = $this->withoutMiddleware([ValidateCsrfToken::class])
        ->actingAs($user)
        ->post(route('servers.sites.store', $server), [
            'domain' => 'auto-detect.com',
            'system_user' => 'autodetect',
            'php_version' => '8.3',
            'app_type' => 'generic',
        ]);

    $response->assertRedirect(route('servers.sites.index', $server));

    $this->assertDatabaseHas('sites', [
        'domain' => 'auto-detect.com',
        'web_server' => 'caddy',
    ]);
});
