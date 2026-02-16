<?php

use App\Models\Database;
use App\Models\Server;
use App\Models\Site;
use App\Models\User;
use App\Services\DatabaseProvisioningService;
use App\Services\ServerConnectionService;
use App\Services\SiteProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

test('database provisioning service provisions mariadb', function () {
    $server = Server::factory()->create();
    $site = Site::create([
        'server_id' => $server->id,
        'domain' => 'dbtest.com',
        'system_user' => 'dbtest',
        'php_version' => '8.3',
        'app_type' => 'generic',
        'document_root' => '/home/dbtest/public_html',
        'status' => 'creating',
    ]);

    $database = Database::create([
        'site_id' => $site->id,
        'server_id' => $server->id,
        'name' => 'db_dbtest',
        'username' => 'dbtest',
        'password' => 'secret',
        'type' => 'mariadb',
    ]);

    $this->mock(ServerConnectionService::class, function (MockInterface $mock) use ($database) {
        // Expect MariaDB commands
        $mock->shouldReceive('runCommand')
            ->withArgs(function ($server, $cmd) use ($database) {
                return str_contains($cmd, 'mysql') &&
                       str_contains($cmd, "CREATE DATABASE IF NOT EXISTS `{$database->name}`");
            })
            ->once();

        $mock->shouldReceive('runCommand')->andReturn(''); // Catch-all for others
    });

    $service = app(DatabaseProvisioningService::class);
    $service->provision($database);

    expect($database->fresh()->status)->toBe('active');
});

test('database provisioning service provisions postgresql', function () {
    $server = Server::factory()->create();
    $site = Site::create([
        'server_id' => $server->id,
        'domain' => 'pgtest.com',
        'system_user' => 'pgtest',
        'php_version' => '8.3',
        'app_type' => 'generic',
        'document_root' => '/home/pgtest/public_html',
        'status' => 'creating',
    ]);

    $database = Database::create([
        'site_id' => $site->id,
        'server_id' => $server->id,
        'name' => 'db_pgtest',
        'username' => 'pgtest',
        'password' => 'secret',
        'type' => 'postgresql',
    ]);

    $this->mock(ServerConnectionService::class, function (MockInterface $mock) {
        // Expect PostgreSQL commands
        $mock->shouldReceive('runCommand')
            ->withArgs(function ($s, $cmd) {
                /** @noinspection SqlNoDataSourceInspection */
                return str_contains($cmd, 'SELECT 1 FROM pg_roles');
            })
            ->andReturn('0'); // User does not exist

        $mock->shouldReceive('runCommand')
            ->withArgs(function ($server, $cmd) {
                return str_contains($cmd, 'CREATE USER');
            })
            ->once();

        $mock->shouldReceive('runCommand')
            ->withArgs(function ($s, $cmd) {
                /** @noinspection SqlNoDataSourceInspection */
                return str_contains($cmd, 'SELECT 1 FROM pg_database');
            })
            ->andReturn('0'); // DB does not exist

        $mock->shouldReceive('runCommand')
            ->withArgs(function ($server, $cmd) {
                return str_contains($cmd, 'CREATE DATABASE');
            })
            ->once();
    });

    $service = app(DatabaseProvisioningService::class);
    $service->provision($database);

    expect($database->fresh()->status)->toBe('active');
});

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

test('site controller creates database model', function () {
    $server = Server::factory()->create();
    $user = User::factory()->create();

    // Mock provisioning so we don't need real connection
    $this->mock(SiteProvisioningService::class, function (MockInterface $mock) {
        $mock->shouldReceive('provision')->once();
    });

    $response = $this->withoutMiddleware([ValidateCsrfToken::class])
        ->actingAs($user)
        ->post(route('servers.sites.store', $server), [
            'domain' => 'withdb.com',
            'system_user' => 'withdb',
            'php_version' => '8.2',
            'app_type' => 'wordpress',
            'create_database' => true,
            'database_type' => 'mariadb',
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $this->assertDatabaseHas('sites', ['domain' => 'withdb.com']);
    $this->assertDatabaseHas('databases', [
        'username' => 'withdb',
        'type' => 'mariadb',
    ]);

    $db = Database::where('username', 'withdb')->first();
    expect($db->name)->toContain('db_withdb');
});
