<?php

namespace App\Services;

use App\Models\Database;
use App\Models\DatabaseEngineInstallation;
use App\Models\Server;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DatabaseProvisioningService
{
    public function __construct(
        protected ServerConnectionService $connection
    ) {}

    public function provision(Database $database): void
    {
        try {
            $database->update(['status' => 'creating']);

            if ($database->type === 'mariadb') {
                $this->provisionMariaDB($database);
            } elseif ($database->type === 'postgresql') {
                $this->provisionPostgreSQL($database);
            }

            $database->update(['status' => 'active']);
        } catch (\Exception $e) {
            $database->update(['status' => 'error']);
            throw $e;
        }
    }

    public function installEngine(DatabaseEngineInstallation $installation): void
    {
        $server = $installation->server;
        $type = $installation->type;

        try {
            $installation->update([
                'status' => 'installing',
                'started_at' => now(),
            ]);

            $rootPassword = Str::password(32);
            $script = match ($type) {
                'mariadb' => $this->getMariaDbInstallScript($rootPassword),
                'postgresql' => $this->getPostgreSqlInstallScript($rootPassword),
                default => throw new \RuntimeException("Unsupported database type: {$type}"),
            };

            $output = $this->connection->runCommand($server, $script);

            $installation->update([
                'status' => 'active',
                'finished_at' => now(),
                'log' => ($installation->log ?? '')."\n".$output,
            ]);

            // Update server's installed engines
            $engines = $server->database_engines ?? [];
            $engines[$type] = [
                'status' => 'active',
                'installed_at' => now()->toDateTimeString(),
                'root_password' => $rootPassword,
            ];
            $server->update(['database_engines' => $engines]);

        } catch (\Exception $e) {
            $installation->update([
                'status' => 'error',
                'log' => ($installation->log ?? '')."\nError: ".$e->getMessage(),
            ]);
            Log::error("Failed to install {$type} on server {$server->id}: ".$e->getMessage());
            throw $e;
        }
    }

    protected function getMariaDbInstallScript(string $rootPassword): string
    {
        $escapedPassword = addslashes($rootPassword);

        return <<<BASH
set -e
echo "Installing MariaDB..."
sudo dnf install -y mariadb-server mariadb
sudo systemctl enable --now mariadb

echo "Securing MariaDB..."
# Set root password
sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '{$escapedPassword}'; FLUSH PRIVILEGES;"

# Remove anonymous users and test database
sudo mysql -u root -p'{$escapedPassword}' -e "DELETE FROM mysql.user WHERE User=''; DELETE FROM mysql.db WHERE Db='test' OR Db='test_%'; FLUSH PRIVILEGES;"

echo "MariaDB installation complete."
BASH;
    }

    protected function getPostgreSqlInstallScript(string $rootPassword): string
    {
        $escapedPassword = addslashes($rootPassword);

        return <<<BASH
set -e
echo "Installing PostgreSQL..."
sudo dnf install -y postgresql-server postgresql-contrib
sudo postgresql-setup --initdb
sudo systemctl enable --now postgresql

echo "Setting postgres user password..."
sudo -u postgres psql -c "ALTER USER postgres WITH PASSWORD '{$escapedPassword}';"

echo "PostgreSQL installation complete."
BASH;
    }

    protected function provisionMariaDB(Database $database): void
    {
        $server = $database->server;
        $password = $database->password;

        $rootPassword = $server->database_engines['mariadb']['root_password'] ?? null;
        $auth = $rootPassword ? "-p'".addslashes($rootPassword)."'" : '';

        $sql = [
            "CREATE DATABASE IF NOT EXISTS `{$database->name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;",
            "CREATE USER IF NOT EXISTS '{$database->username}'@'localhost' IDENTIFIED BY '{$password}';",
            "GRANT ALL PRIVILEGES ON `{$database->name}`.* TO '{$database->username}'@'localhost';",
            'FLUSH PRIVILEGES;',
        ];

        foreach ($sql as $query) {
            $escapedQuery = addslashes($query);
            $cmd = "sudo mysql -u root {$auth} -e \"{$escapedQuery}\"";
            $this->connection->runCommand($server, $cmd);
        }
    }

    protected function provisionPostgreSQL(Database $database): void
    {
        $server = $database->server;
        $password = $database->password;

        $checkUser = "sudo -u postgres psql -tAc \"SELECT 1 FROM pg_roles WHERE rolname='{$database->username}'\" ";
        $userExists = $this->connection->runCommand($server, $checkUser);

        if (trim($userExists) !== '1') {
            $createCmd = "sudo -u postgres psql -c \"CREATE USER {$database->username} WITH PASSWORD '{$password}';\" ";
            $this->connection->runCommand($server, $createCmd);
        } else {
            $updateCmd = "sudo -u postgres psql -c \"ALTER USER {$database->username} WITH PASSWORD '{$password}';\" ";
            $this->connection->runCommand($server, $updateCmd);
        }

        $checkDb = "sudo -u postgres psql -tAc \"SELECT 1 FROM pg_database WHERE datname='{$database->name}'\" ";
        $dbExists = $this->connection->runCommand($server, $checkDb);

        if (trim($dbExists) !== '1') {
            $createDbCmd = "sudo -u postgres psql -c \"CREATE DATABASE {$database->name} OWNER {$database->username};\" ";
            $this->connection->runCommand($server, $createDbCmd);
        }
    }
}
