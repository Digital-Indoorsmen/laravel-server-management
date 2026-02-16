<?php

namespace App\Services;

use App\Models\Database;
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

    protected function provisionMariaDB(Database $database): void
    {
        $server = $database->server;
        $password = $database->password; // Automatically decrypted by Eloquent

        $sql = [
            "CREATE DATABASE IF NOT EXISTS `{$database->name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;",
            "CREATE USER IF NOT EXISTS '{$database->username}'@'localhost' IDENTIFIED BY '{$password}';",
            "GRANT ALL PRIVILEGES ON `{$database->name}`.* TO '{$database->username}'@'localhost';",
            "FLUSH PRIVILEGES;"
        ];

        foreach ($sql as $query) {
            // Escape double quotes for shell and execute
            // Using mysql -e "query"
            // We need to be careful with passwords in process list, but for now this is the standard way.
            // Ideally we'd use a temporary .my.cnf or similar, but direct command is simpler for MVP.
            // To hide password from logs, we might rely on the connection service not logging full command if we were stricter,
            // but here we are running queries that contain the password.
            // The password is in the CREATE USER command.
            
            // To be safer, we can try to obscure it, but let's stick to functional first.
            $escapedQuery = addslashes($query);
            $cmd = "sudo mysql -u root -e \"{$escapedQuery}\"";
            $this->connection->runCommand($server, $cmd);
        }
    }

    protected function provisionPostgreSQL(Database $database): void
    {
        $server = $database->server;
        $password = $database->password;

        // PostgreSQL is a bit different. We typically use `sudo -u postgres psql`.
        
        // 1. Create User
        // Check if user exists first to avoid error, or catch it.
        // Postgres doesn't have "CREATE USER IF NOT EXISTS" in older versions easily in one line without a block.
        // But we can use a DO block or just try and ignore "already exists".
        
        // Safer way:
        // psql -c "SELECT 1 FROM pg_roles WHERE rolname='$username'" | grep -q 1 || createuser ...
        
        $checkUser = "sudo -u postgres psql -tAc \"SELECT 1 FROM pg_roles WHERE rolname='{$database->username}'\" ";
        $userExists = $this->connection->runCommand($server, $checkUser);
        
        if (trim($userExists) !== '1') {
            $createCmd = "sudo -u postgres psql -c \"CREATE USER {$database->username} WITH PASSWORD '{$password}';\" ";
            $this->connection->runCommand($server, $createCmd);
        } else {
             // Update password just in case?
             $updateCmd = "sudo -u postgres psql -c \"ALTER USER {$database->username} WITH PASSWORD '{$password}';\" ";
             $this->connection->runCommand($server, $updateCmd);
        }

        // 2. Create Database
        // check if db exists
        $checkDb = "sudo -u postgres psql -tAc \"SELECT 1 FROM pg_database WHERE datname='{$database->name}'\" ";
        $dbExists = $this->connection->runCommand($server, $checkDb);

        if (trim($dbExists) !== '1') {
            $createDbCmd = "sudo -u postgres psql -c \"CREATE DATABASE {$database->name} OWNER {$database->username};\" ";
            $this->connection->runCommand($server, $createDbCmd);
        }
    }
}
