<?php

use App\Models\Database as SiteDatabase;
use App\Models\Server;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

it('renders all sidebar and profile destination pages for authenticated users', function () {
    $user = User::factory()->create();

    $server = Server::factory()->create([
        'name' => 'Main Server',
        'status' => 'active',
        'web_server' => 'nginx',
        'setup_completed_at' => now(),
    ]);

    $site = Site::query()->create([
        'server_id' => $server->id,
        'domain' => 'example.test',
        'document_root' => '/home/example/public_html',
        'system_user' => 'example',
        'php_version' => '8.4',
        'app_type' => 'laravel',
        'web_server' => 'nginx',
        'status' => 'active',
    ]);

    SiteDatabase::query()->create([
        'site_id' => $site->id,
        'server_id' => $server->id,
        'name' => 'db_example',
        'username' => 'example',
        'password' => 'secret-password',
        'type' => 'mariadb',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->get(route('system.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('System/Index')
            ->has('systemStats')
            ->has('services')
            ->has('security')
        );

    $this->actingAs($user)
        ->get(route('sites.catalog'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Sites/Catalog')
            ->has('sites', 1)
            ->has('servers', 1)
        );

    $this->actingAs($user)
        ->get(route('databases.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Databases/Index')
            ->has('databases', 1)
        );

    $this->actingAs($user)
        ->get(route('profile.show'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profile')
            ->where('user.email', $user->email)
        );
});

it('redirects guests to login for new navigation destinations', function () {
    $this->get(route('system.index'))->assertRedirect(route('login'));
    $this->get(route('sites.catalog'))->assertRedirect(route('login'));
    $this->get(route('databases.index'))->assertRedirect(route('login'));
    $this->get(route('profile.show'))->assertRedirect(route('login'));
});
