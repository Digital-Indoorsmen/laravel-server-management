<?php

use App\Models\Server;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->server = Server::factory()->create();
    $this->site = Site::factory()->create(['server_id' => $this->server->id]);
});

it('renders the general settings section by default', function () {
    $response = $this->actingAs($this->user)
        ->get(route('sites.workspace.settings', $this->site->id));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Sites/Workspace/Settings')
        ->where('activeSection', 'general')
        ->has('site')
    );
});

it('renders each settings section for authenticated users', function (string $section) {
    if ($section === 'environment') {
        // Mock getEnvContent
        $this->mock(\App\Services\SiteProvisioningService::class)
            ->shouldReceive('getEnvContent')
            ->andReturn('SECRET=CONTENT');
    }

    $response = $this->actingAs($this->user)
        ->get(route('sites.workspace.settings', [$this->site->id, $section]));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Sites/Workspace/Settings')
        ->where('activeSection', $section)
    );
})->with(['general', 'deployments', 'environment', 'composer', 'notifications', 'integrations']);

it('redirects guests from settings to login', function () {
    $this->get(route('sites.workspace.settings', $this->site->id))
        ->assertRedirect(route('login'));
});

it('updates general settings successfully', function () {
    $response = $this->actingAs($this->user)
        ->patch(route('sites.workspace.settings.general.update', $this->site->id), [
            'php_version' => '8.4',
            'app_type' => 'laravel',
            'notes' => 'Updated notes',
            'tags' => ['prod', 'api'],
        ]);

    $response->assertRedirect();
    $this->site->refresh();

    expect($this->site->php_version)->toBe('8.4');
    expect($this->site->app_type)->toBe('laravel');
    expect($this->site->notes)->toBe('Updated notes');
    expect($this->site->tags)->toBe(['prod', 'api']);
});

it('updates deployment settings successfully', function () {
    $response = $this->actingAs($this->user)
        ->patch(route('sites.workspace.settings.deployments.update', $this->site->id), [
            'deploy_script' => 'git pull origin production',
            'push_to_deploy' => true,
            'health_check_enabled' => true,
        ]);

    $response->assertRedirect();
    $this->site->refresh();

    expect($this->site->deploy_script)->toBe('git pull origin production');
    expect($this->site->push_to_deploy)->toBeTrue();
    expect($this->site->health_check_enabled)->toBeTrue();
});

it('updates environment settings successfully', function () {
    $this->mock(\App\Services\SiteProvisioningService::class)
        ->shouldReceive('updateEnvContent')
        ->once();

    $response = $this->actingAs($this->user)
        ->patch(route('sites.workspace.settings.environment.update', $this->site->id), [
            'content' => 'APP_DEBUG=true',
            'auto_cache_config' => false,
        ]);

    $response->assertRedirect();
    $this->site->refresh();

    expect($this->site->auto_cache_config)->toBeFalse();
});

it('falls back to general section for invalid section parameter', function () {
    $response = $this->actingAs($this->user)
        ->get(route('sites.workspace.settings', [$this->site->id, 'invalid-section']));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Sites/Workspace/Settings')
        ->where('activeSection', 'general')
    );
});

it('triggers a deployment via webhook', function () {
    \Illuminate\Support\Facades\Bus::fake();

    $response = $this->post(route('sites.deploy.webhook', $this->site->deploy_hook_url));

    $response->assertSuccessful();
    $response->assertSee('Deployment queued successfully.');

    \Illuminate\Support\Facades\Bus::assertDispatched(\App\Jobs\RunSiteDeployment::class);
});
