<?php

namespace App\Services;

use App\Models\Site;
use Illuminate\Support\Facades\Log;

class SiteProvisioningService
{
    public function __construct(
        protected ServerConnectionService $connection
    ) {}

    public function provision(Site $site): void
    {
        $server = $site->server;

        try {
            $site->update(['status' => 'creating']);

            // 1. Create System User
            $this->createSystemUser($site);

            // 2. Create Directory Structure
            $this->createDirectories($site);

            // 3. Configure PHP-FPM
            $this->configurePhpFpm($site);

            // 4. Configure Nginx
            $this->configureNginx($site);

            // 5. Reload Services
            $this->reloadServices($site);

            $site->update(['status' => 'active']);

        } catch (\Exception $e) {
            Log::error("Failed to provision site {$site->domain}: " . $e->getMessage());
            $site->update(['status' => 'error']);
            throw $e;
        }
    }

    protected function createSystemUser(Site $site): void
    {
        $cmd = "id -u {$site->system_user} &>/dev/null || useradd -m -s /bin/bash {$site->system_user}";
        $this->connection->runCommand($site->server, $cmd);
        
        // Ensure home dir permissions
        $this->connection->runCommand($site->server, "chmod 711 /home/{$site->system_user}");
    }

    protected function createDirectories(Site $site): void
    {
        $publicHtml = "/home/{$site->system_user}/public_html";
        $this->connection->runCommand($site->server, "mkdir -p {$publicHtml}");
        $this->connection->runCommand($site->server, "chown -R {$site->system_user}:{$site->system_user} /home/{$site->system_user}/public_html");
        $this->connection->runCommand($site->server, "chmod 755 /home/{$site->system_user}/public_html");

        // Create a default index.php if empty
        $indexPhp = "<?php echo 'Hello from ' . \$_SERVER['SERVER_NAME'];";
        $cmd = "test -f {$publicHtml}/index.php || echo \"{$indexPhp}\" > {$publicHtml}/index.php";
        $this->connection->runCommand($site->server, "sudo -u {$site->system_user} bash -c '{$cmd}'");
    }

    protected function configurePhpFpm(Site $site): void
    {
        $config = view('provisioning.php-fpm', ['site' => $site])->render();
        $this->writeRemoteFile($site->server, $this->getPhpPoolPath($site), $config);
    }

    protected function configureNginx(Site $site): void
    {
        $config = view('provisioning.nginx', ['site' => $site])->render();
        $path = "/etc/nginx/conf.d/{$site->domain}.conf";
        $this->writeRemoteFile($site->server, $path, $config);
    }

    protected function reloadServices(Site $site): void
    {
        $phpService = $this->getPhpServiceName($site);
        $this->connection->runCommand($site->server, "systemctl reload nginx");
        $this->connection->runCommand($site->server, "systemctl reload {$phpService}");
    }

    protected function writeRemoteFile(\App\Models\Server $server, string $path, string $content): void
    {
        $base64 = base64_encode($content);
        // Use bash to decode and write, ensuring directory exists isn't strictly necessary if we assume structure,
        // but it's good practice. Here we just write the file.
        $cmd = "echo '{$base64}' | base64 -d > {$path}";
        $this->connection->runCommand($server, $cmd);
    }

    protected function getPhpPoolPath(Site $site): string
    {
        $version = str_replace('.', '', $site->php_version); // 8.3 -> 83
        return "/etc/opt/remi/php{$version}/php-fpm.d/{$site->system_user}.conf";
    }

    protected function getPhpServiceName(Site $site): string
    {
        $version = str_replace('.', '', $site->php_version); // 8.3 -> 83
        return "php{$version}-php-fpm";
    }

    public function getEnvContent(Site $site): string
    {
        try {
            return $this->connection->runCommand($site->server, "cat /home/{$site->system_user}/.env");
        } catch (\Exception $e) {
            return '';
        }
    }

    public function updateEnvContent(Site $site, string $content): void
    {
        $path = "/home/{$site->system_user}/.env";
        $this->writeRemoteFile($site->server, $path, $content);
        
        // Ensure permissions
        $this->connection->runCommand($site->server, "chown {$site->system_user}:{$site->system_user} {$path}");
        $this->connection->runCommand($site->server, "chmod 600 {$path}");

        // Reload PHP-FPM to pick up new env vars if using clear_env = no (but we are using .user.ini or pool config?)
        // Usually .env is loaded by the framework (Laravel) at runtime, so no reload needed unless hardcoded in pool.
        // But if using standard Laravel, .env is read on every request.
        // However, if we cache config, we need to clear cache.
        // The requirement says: "Ensure the panel can restart PHP-FPM for specific pools after config changes."
        // We might as well reload PHP just in case.
        $this->reloadServices($site);
    }
}
