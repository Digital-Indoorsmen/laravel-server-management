<?php

use App\Models\Server;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
});

dataset('workspace-tabs', [
    'overview' => ['sites.workspace.overview', 'Sites/Workspace/Overview'],
    'deployments' => ['sites.workspace.deployments', 'Sites/Workspace/Deployments'],
    'processes' => ['sites.workspace.processes', 'Sites/Workspace/Processes'],
    'commands' => ['sites.workspace.commands', 'Sites/Workspace/Commands'],
    'network' => ['sites.workspace.network', 'Sites/Workspace/Network'],
    'observe' => ['sites.workspace.observe', 'Sites/Workspace/Observe'],
    'domains' => ['sites.workspace.domains', 'Sites/Workspace/Domains'],
    'settings' => ['sites.workspace.settings', 'Sites/Workspace/Settings'],
]);

it('renders each site workspace tab for authenticated users', function (string $routeName, string $component): void {
    $user = User::factory()->create();

    $server = Server::factory()->create([
        'name' => 'Workspace Server',
        'status' => 'active',
        'web_server' => 'nginx',
        'setup_completed_at' => now(),
    ]);

    $site = Site::factory()->create([
        'server_id' => $server->id,
        'domain' => 'workspace.test',
        'document_root' => '/home/workspace/public_html',
        'system_user' => 'workspace',
        'php_version' => '8.4',
        'app_type' => 'laravel',
        'web_server' => 'nginx',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->get(route($routeName, $site))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component($component)
            ->where('site.id', $site->id)
            ->where('site.server.id', $server->id)
            ->where('workspace.activeTab', str_replace('sites.workspace.', '', $routeName))
        );
})->with('workspace-tabs');

it('redirects guests from each site workspace tab to login', function (string $routeName): void {
    $server = Server::factory()->create();

    $site = Site::factory()->create([
        'server_id' => $server->id,
        'domain' => fake()->unique()->domainName(),
        'document_root' => '/home/guest/public_html',
        'system_user' => fake()->unique()->lexify('guest????'),
        'php_version' => '8.4',
        'app_type' => 'laravel',
        'web_server' => 'nginx',
        'status' => 'active',
    ]);

    $this->get(route($routeName, $site))->assertRedirect(route('login'));
})->with([
    'overview' => ['sites.workspace.overview'],
    'deployments' => ['sites.workspace.deployments'],
    'processes' => ['sites.workspace.processes'],
    'commands' => ['sites.workspace.commands'],
    'network' => ['sites.workspace.network'],
    'observe' => ['sites.workspace.observe'],
    'domains' => ['sites.workspace.domains'],
    'settings' => ['sites.workspace.settings'],
]);
