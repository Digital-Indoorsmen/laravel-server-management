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
        $version = $installation->version ?: $this->getDefaultVersion($type);

        try {
            $installation->update([
                'status' => 'installing',
                'started_at' => now(),
            ]);

            $rootPassword = Str::password(32);
            $script = match ($type) {
                'mariadb' => $this->getMariaDbInstallScript($rootPassword, $version),
                'mysql' => $this->getMysqlInstallScript($rootPassword, $version),
                'postgresql' => $this->getPostgreSqlInstallScript($rootPassword, $version),
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
                'version' => $version,
                'installed_at' => now()->toDateTimeString(),
                'root_password' => $rootPassword,
            ];
            $server->update(['database_engines' => $engines]);

        } catch (\Exception $e) {
            $installation->update([
                'status' => 'error',
                'log' => ($installation->log ?? '')."\nError: ".$e->getMessage(),
            ]);
            Log::error("Failed to install {$type} v{$version} on server {$server->id}: ".$e->getMessage());
            throw $e;
        }
    }

    public function upgradeEngine(DatabaseEngineInstallation $installation): void
    {
        $server = $installation->server;
        $type = $installation->type;

        try {
            $installation->update([
                'status' => 'installing',
                'started_at' => now(),
            ]);

            $script = match ($type) {
                'mariadb' => $this->getMariaDbUpgradeScript(),
                'mysql' => $this->getMysqlUpgradeScript(),
                'postgresql' => $this->getPostgreSqlUpgradeScript(),
                default => throw new \RuntimeException("Unsupported database type for upgrade: {$type}"),
            };

            $output = $this->connection->runCommand($server, $script);

            $installation->update([
                'status' => 'active',
                'finished_at' => now(),
                'log' => ($installation->log ?? '')."\n".$output,
            ]);
        } catch (\Exception $e) {
            $installation->update([
                'status' => 'error',
                'log' => ($installation->log ?? '')."\nError: ".$e->getMessage(),
            ]);
            Log::error("Failed to upgrade {$type} on server {$server->id}: ".$e->getMessage());
            throw $e;
        }
    }

    protected function getMariaDbUpgradeScript(): string
    {
        return <<<'BASH'
set -e
echo "Upgrading MariaDB packages..."
sudo dnf upgrade -y mariadb-server mariadb
echo "Running mysql_upgrade..."
sudo mysql_upgrade -u root || echo "mysql_upgrade might not be needed or already completed."
echo "Restarting MariaDB..."
sudo systemctl restart mariadb
echo "MariaDB upgrade complete."
BASH;
    }

    protected function getMysqlUpgradeScript(): string
    {
        return <<<'BASH'
set -e
echo "Upgrading MySQL packages..."
sudo dnf upgrade -y mysql-server mysql
echo "Restarting MySQL..."
sudo systemctl restart mysqld
echo "MySQL upgrade complete."
BASH;
    }

    protected function getPostgreSqlUpgradeScript(): string
    {
        return <<<'BASH'
set -e
echo "Upgrading PostgreSQL packages..."
sudo dnf upgrade -y postgresql-server postgresql-contrib
echo "PostgreSQL minor upgrade complete. Major upgrades require manual orchestration (pg_upgrade) and are not handled automatically yet."
sudo systemctl restart postgresql
BASH;
    }

    protected function getDefaultVersion(string $type): string
    {
        return match ($type) {
            'mariadb' => '10.11',
            'mysql' => '8.0',
            'postgresql' => '16',
            default => 'latest',
        };
    }

    protected function getMariaDbInstallScript(string $rootPassword, string $version): string
    {
        $escapedPassword = addslashes($rootPassword);

        return <<<BASH
set -e
echo "Configuring MariaDB module for version {$version}..."
sudo dnf module reset -y mariadb || true
sudo dnf module enable -y mariadb:{$version}

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

    protected function getMysqlInstallScript(string $rootPassword, string $version): string
    {
        $escapedPassword = addslashes($rootPassword);

        return <<<BASH
set -e
echo "Configuring MySQL module for version {$version}..."
sudo dnf module reset -y mysql || true
sudo dnf module enable -y mysql:{$version}

echo "Installing MySQL..."
sudo dnf install -y mysql-server mysql
sudo systemctl enable --now mysqld

echo "Securing MySQL..."
# Set root password
sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '{$escapedPassword}'; FLUSH PRIVILEGES;"

# Remove anonymous users and test database
sudo mysql -u root -p'{$escapedPassword}' -e "DELETE FROM mysql.user WHERE User=''; DELETE FROM mysql.db WHERE Db='test' OR Db='test_%'; FLUSH PRIVILEGES;"

echo "MySQL installation complete."
BASH;
    }

    protected function getPostgreSqlInstallScript(string $rootPassword, string $version): string
    {
        $escapedPassword = addslashes($rootPassword);

        return <<<BASH
set -e
echo "Configuring PostgreSQL module for version {$version}..."
sudo dnf module reset -y postgresql || true
sudo dnf module enable -y postgresql:{$version}

echo "Installing PostgreSQL..."
sudo dnf install -y postgresql-server postgresql-contrib
sudo postgresql-setup --initdb || echo "Database already initialized."
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

        $rootPassword = $server->database_engines['mariadb']['root_password']
            ?? $server->database_engines['mysql']['root_password']
            ?? null;

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
