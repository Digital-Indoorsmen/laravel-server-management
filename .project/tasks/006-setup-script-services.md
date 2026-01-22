# Status: [x] Complete
# Title: AlmaLinux & Rocky Linux Base Setup Script - Services & Tools

## Description
Extend the `setup.sh` script to install and configure the full LNMP (Linux, Nginx, MariaDB, PHP) stack and essential development tools.

## Requirements
- **Idempotent Installation**:
  - Check for each service (Nginx, MariaDB, PHP, Redis) before attempting installation.
- Install Web Servers: `nginx`.
- Install Database Servers: `mariadb-server`, `postgresql-server`.
- Install Multiple PHP versions (via Remi repository):
  - Versions: 7.4, 8.1, 8.2, 8.3, 8.4, 8.5.
  - Required modules: `fpm`, `mysqlnd`, `pgsql`, `xml`, `mbstring`, `gd`, `zip`, `opcache`.
- Install Redis: `redis`.
- Install Bun (latest) globally for the `panel` user.
- Initialize the panel SQLite database on the node (if applicable).
- Create directory structure:
  - `/home/panel/sites`: Base for all website roots.
  - `/var/log/panel`: Central log storage.
  - `/etc/ssl/panel`: SSL certificate storage.
- Set appropriate file permissions:
  - World-readable for web roots, but owner-only for sensitive configs.
- Configure Nginx to run the management panel on port 8095.

## Implementation Details
### PHP Installation (Remi Repository) with check:
```bash
if ! is_installed "remi-release"; then
    dnf install -y https://rpms.remirepo.net/enterprise/remi-release-$(rpm -E %rhel).rpm
fi

# Example module check
if ! dnf list installed "php83-php-fpm" &> /dev/null; then
    dnf module install php:remi-8.3 -y
fi
```

## Configuration
- LNMP Stack
- Bun v1.3.6+
- Remi Repository

## Audit & Logging
- Service installation verification (status check).
- Initial health checks for port 80, 443, 3306, 5432, 8095.

## Testing
- Verify all services (`nginx`, `mariadb`, `php-fpm`, `pgsql`, `redis`) start on boot.
- Verify Bun is available in the `panel` user's shell.
- Run script twice and ensure no redundant installation logs appear.

## Completion Criteria
- [x] All required services installed and operational
- [x] Idempotency confirmed for service installation
- [x] Directory structure created with correct permissions
- [x] Multi-PHP version support confirmed
