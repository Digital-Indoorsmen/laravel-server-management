<?php

namespace App\Services;

use App\Models\Site;
use Illuminate\Support\Facades\Log;

class SiteProvisioningService
{
    public function __construct(
        protected ServerConnectionService $connection,
        protected DatabaseProvisioningService $dbProvisioner
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

            // 4. Configure web server
            $this->configureWebServer($site);

            // 5. Provision Databases
            if ($site->databases()->exists()) {
                foreach ($site->databases as $database) {
                    $this->dbProvisioner->provision($database);
                }
                $this->configureEnvWithDatabase($site);
            }

            // 6. Reload Services
            $this->reloadServices($site);

            $site->update(['status' => 'active']);

        } catch (\Exception $e) {
            Log::error("Failed to provision site {$site->domain}: ".$e->getMessage());
            $site->update(['status' => 'error']);
            throw $e;
        }
    }

    protected function createSystemUser(Site $site): void
    {
        $cmd = "id -u {$site->system_user} &>/dev/null || sudo useradd -m -s /bin/bash {$site->system_user}";
        $this->connection->runCommand($site->server, $cmd);

        // Ensure home dir permissions
        $this->connection->runCommand($site->server, "sudo chmod 711 /home/{$site->system_user}");

        // SELinux User Mapping (MCS Isolation)
        if ($site->mcs_id) {
            $category = 's0:c'.$site->mcs_id;
            // Apply MCS category to the user
            $semanageCmd = "sudo semanage login -a -s user_u -r {$category} {$site->system_user} || sudo semanage login -m -s user_u -r {$category} {$site->system_user}";
            $this->connection->runCommand($site->server, "if command -v semanage &> /dev/null; then {$semanageCmd}; fi");
        }
    }

    protected function createDirectories(Site $site): void
    {
        $publicHtml = "/home/{$site->system_user}/public_html";
        $this->connection->runCommand($site->server, "sudo mkdir -p {$publicHtml}");
        $this->connection->runCommand($site->server, "sudo chown -R {$site->system_user}:{$site->system_user} /home/{$site->system_user}/public_html");
        $this->connection->runCommand($site->server, "sudo chmod 755 /home/{$site->system_user}/public_html");

        // Create a default index.php if empty
        $indexPhp = "<?php echo 'Hello from ' . \$_SERVER['SERVER_NAME'];";
        $cmd = "test -f {$publicHtml}/index.php || echo \"{$indexPhp}\" > {$publicHtml}/index.php";
        // running as site user, so simple sudo -u is fine.
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

    protected function configureCaddy(Site $site): void
    {
        $config = view('provisioning.caddy', ['site' => $site])->render();
        $this->connection->runCommand($site->server, 'sudo mkdir -p /etc/caddy/sites-enabled');

        $path = "/etc/caddy/sites-enabled/{$site->domain}.caddy";
        $this->writeRemoteFile($site->server, $path, $config);
    }

    protected function configureWebServer(Site $site): void
    {
        if ($this->getWebServer($site) === 'caddy') {
            $this->configureCaddy($site);

            return;
        }

        $this->configureNginx($site);
    }

    protected function reloadServices(Site $site): void
    {
        $phpService = $this->getPhpServiceName($site);
        if ($this->getWebServer($site) === 'caddy') {
            $this->validateCaddyConfig($site);
            $this->connection->runCommand($site->server, 'sudo systemctl reload caddy');
        } else {
            $this->connection->runCommand($site->server, 'sudo systemctl reload nginx');
        }

        $this->connection->runCommand($site->server, "sudo systemctl reload {$phpService}");
    }

    protected function writeRemoteFile(\App\Models\Server $server, string $path, string $content): void
    {
        $base64 = base64_encode($content);
        // Pipe to sudo tee to write to privileged locations
        $cmd = "echo '{$base64}' | base64 -d | sudo tee {$path} > /dev/null";
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

    protected function getWebServer(Site $site): string
    {
        return $site->web_server ?? $site->server->web_server ?? 'nginx';
    }

    protected function validateCaddyConfig(Site $site): void
    {
        $output = $this->connection->runCommand(
            $site->server,
            "if sudo caddy validate --config /etc/caddy/Caddyfile >/tmp/panel-caddy-validate.log 2>&1; then echo '__PANEL_CADDY_VALID__'; else cat /tmp/panel-caddy-validate.log; echo '__PANEL_CADDY_INVALID__'; fi"
        );

        if (str_contains($output, '__PANEL_CADDY_INVALID__')) {
            $sanitizedOutput = str_replace(['__PANEL_CADDY_INVALID__', '__PANEL_CADDY_VALID__'], '', $output);

            throw new \RuntimeException('Caddy configuration validation failed: '.trim($sanitizedOutput));
        }
    }

    protected function configureEnvWithDatabase(Site $site): void
    {
        $database = $site->databases()->first();
        if (! $database) {
            return;
        }

        $connection = $database->type === 'postgresql' ? 'pgsql' : 'mysql';
        $port = $database->type === 'postgresql' ? 5432 : 3306;
        $password = $database->password; // Decrypted by model cast

        $envContent = <<<EOT
APP_NAME="{$site->domain}"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://{$site->domain}

DB_CONNECTION={$connection}
DB_HOST=127.0.0.1
DB_PORT={$port}
DB_DATABASE={$database->name}
DB_USERNAME={$database->username}
DB_PASSWORD={$password}

EOT;

        $exists = false;
        try {
            $this->connection->runCommand($site->server, "sudo test -f /home/{$site->system_user}/.env");
            $exists = true;
        } catch (\Exception $e) {
            $exists = false;
        }

        if (! $exists) {
            $this->updateEnvContent($site, $envContent);
        }
    }

    public function getEnvContent(Site $site): string
    {
        try {
            return $this->connection->runCommand($site->server, "sudo cat /home/{$site->system_user}/.env");
        } catch (\Exception $e) {
            return '';
        }
    }

    public function updateEnvContent(Site $site, string $content): void
    {
        $path = "/home/{$site->system_user}/.env";
        $this->writeRemoteFile($site->server, $path, $content);

        // Ensure permissions
        $this->connection->runCommand($site->server, "sudo chown {$site->system_user}:{$site->system_user} {$path}");
        $this->connection->runCommand($site->server, "sudo chmod 600 {$path}");

        $this->reloadServices($site);
    }
}
