<?php

use App\Jobs\RunSiteDeployment;
use App\Models\Deployment;
use App\Models\Server;
use App\Models\Site;
use App\Models\User;
use App\Services\ServerConnectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    config(['queue.default' => 'sync']);
});

function createDeploymentSite(): Site
{
    $server = Server::factory()->create([
        'name' => 'Deploy Server',
        'status' => 'active',
        'web_server' => 'nginx',
    ]);

    return Site::query()->create([
        'server_id' => $server->id,
        'domain' => 'deployments.test',
        'document_root' => '/home/deployments/public_html/public',
        'system_user' => 'deployments',
        'php_version' => '8.4',
        'app_type' => 'laravel',
        'web_server' => 'nginx',
        'status' => 'active',
    ]);
}

it('triggers a successful deployment and stores logs', function (): void {
    $user = User::factory()->create();
    $site = createDeploymentSite();

    $connection = new class extends ServerConnectionService
    {
        public function executeCommand(\App\Models\Server $server, string $command): string
        {
            expect($command)->toContain('git fetch origin main');

            return "Deploy complete\nabc1234\n";
        }
    };

    app()->instance(ServerConnectionService::class, $connection);

    $response = $this->actingAs($user)
        ->post(route('sites.workspace.deployments.store', $site), [
            'branch' => 'main',
        ]);

    $deployment = Deployment::query()->latest('created_at')->firstOrFail();

    $response->assertRedirect(route('sites.workspace.deployments.show', [$site, $deployment]));

    expect($deployment->site_id)->toBe($site->id)
        ->and($deployment->actor_id)->toBe($user->id)
        ->and($deployment->status)->toBe('succeeded')
        ->and($deployment->commit_hash)->toBe('abc1234')
        ->and($deployment->stdout)->toContain('Deploy complete')
        ->and($deployment->stderr)->toBeNull();

    $this->actingAs($user)
        ->get(route('sites.workspace.deployments.show', [$site, $deployment]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Sites/Workspace/DeploymentShow')
            ->where('deployment.id', $deployment->id)
            ->where('deployment.status', 'succeeded')
        );
});

it('marks deployment as failed when command execution throws', function (): void {
    $user = User::factory()->create();
    $site = createDeploymentSite();

    $connection = new class extends ServerConnectionService
    {
        public function executeCommand(\App\Models\Server $server, string $command): string
        {
            throw new RuntimeException('git pull failed with merge conflict');
        }
    };

    app()->instance(ServerConnectionService::class, $connection);

    $this->actingAs($user)
        ->post(route('sites.workspace.deployments.store', $site), [
            'branch' => 'release/2026-02-16',
        ])
        ->assertRedirect();

    $deployment = Deployment::query()->latest('created_at')->firstOrFail();

    expect($deployment->status)->toBe('failed')
        ->and($deployment->stderr)->toContain('merge conflict')
        ->and($deployment->finished_at)->not->toBeNull();
});

it('rejects invalid branch values during deployment trigger', function (): void {
    $user = User::factory()->create();
    $site = createDeploymentSite();

    $this->actingAs($user)
        ->from(route('sites.workspace.deployments', $site))
        ->post(route('sites.workspace.deployments.store', $site), [
            'branch' => 'main; rm -rf /',
        ])
        ->assertRedirect(route('sites.workspace.deployments', $site))
        ->assertSessionHasErrors('branch');

    $this->assertDatabaseCount('deployments', 0);
});

it('requires authentication for deployment trigger and detail routes', function (): void {
    $site = createDeploymentSite();

    $deployment = $site->deployments()->create([
        'status' => 'queued',
        'branch' => 'main',
        'triggered_via' => 'ui',
    ]);

    $this->post(route('sites.workspace.deployments.store', $site), [
        'branch' => 'main',
    ])->assertRedirect(route('login'));

    $this->get(route('sites.workspace.deployments.show', [$site, $deployment]))
        ->assertRedirect(route('login'));
});

it('runs queued deployment job when invoked directly', function (): void {
    $site = createDeploymentSite();

    $deployment = $site->deployments()->create([
        'status' => 'queued',
        'branch' => 'main',
        'triggered_via' => 'ui',
    ]);

    $connection = new class extends ServerConnectionService
    {
        public function executeCommand(\App\Models\Server $server, string $command): string
        {
            return "ok\ndef5678\n";
        }
    };

    app()->instance(ServerConnectionService::class, $connection);

    (new RunSiteDeployment($deployment->id))->handle(app(\App\Services\SiteDeploymentService::class));

    expect($deployment->fresh()->status)->toBe('succeeded')
        ->and($deployment->fresh()->commit_hash)->toBe('def5678');
});
