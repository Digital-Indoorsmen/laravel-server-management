<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Site>
 */
class SiteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'server_id' => \App\Models\Server::factory(),
            'domain' => $this->faker->unique()->domainName(),
            'document_root' => '/public',
            'system_user' => 'panel',
            'php_version' => '8.3',
            'app_type' => 'laravel',
            'web_server' => 'caddy',
            'status' => 'available',
            'deploy_script' => "#!/bin/bash\ngit pull origin main",
            'deploy_hook_url' => (string) str()->uuid(),
            'push_to_deploy' => false,
            'health_check_enabled' => false,
            'github_deployments_enabled' => false,
            'env_in_deploy_script' => true,
            'auto_cache_config' => true,
            'auto_restart_queue' => true,
        ];
    }
}
