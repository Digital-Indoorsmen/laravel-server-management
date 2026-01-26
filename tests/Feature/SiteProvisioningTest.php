<?php

use App\Models\Server;
use App\Models\Site;
use App\Services\ServerConnectionService;
use App\Services\SiteProvisioningService;
use Mockery\MockInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
