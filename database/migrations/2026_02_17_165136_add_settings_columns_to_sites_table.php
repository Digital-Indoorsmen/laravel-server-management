<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('sites') && ! Schema::hasColumn('sites', 'deploy_script')) {
            Schema::table('sites', function (Blueprint $table) {
                // General settings
                $table->json('tags')->nullable()->after('status');
                $table->text('notes')->nullable()->after('tags');
                $table->string('color')->nullable()->after('notes');
                $table->string('git_repository')->nullable()->after('color');
                $table->string('git_branch')->default('main')->after('git_repository');

                // Deployment settings
                $table->text('deploy_script')->nullable()->after('git_branch');
                $table->boolean('push_to_deploy')->default(false)->after('deploy_script');
                $table->string('deploy_hook_url')->nullable()->unique()->after('push_to_deploy');
                $table->boolean('health_check_enabled')->default(false)->after('deploy_hook_url');
                $table->boolean('github_deployments_enabled')->default(false)->after('health_check_enabled');
                $table->boolean('env_in_deploy_script')->default(false)->after('github_deployments_enabled');

                // Environment settings
                $table->boolean('auto_cache_config')->default(true)->after('env_in_deploy_script');
                $table->boolean('auto_restart_queue')->default(true)->after('auto_cache_config');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sites') && Schema::hasColumn('sites', 'deploy_script')) {
            Schema::table('sites', function (Blueprint $table) {
                $table->dropColumn([
                    'tags',
                    'notes',
                    'color',
                    'git_repository',
                    'git_branch',
                    'deploy_script',
                    'push_to_deploy',
                    'deploy_hook_url',
                    'health_check_enabled',
                    'github_deployments_enabled',
                    'env_in_deploy_script',
                    'auto_cache_config',
                    'auto_restart_queue',
                ]);
            });
        }
    }
};
