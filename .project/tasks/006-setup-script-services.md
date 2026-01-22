# Status: [ ] Not Complete
# Title: AlmaLinux Base Setup Script - Services & Tools

## Description
Extend the `setup.sh` script to install the full LNMP stack and development tools.

## Requirements
- Install Nginx, MariaDB, and PostgreSQL.
- Install Multiple PHP versions (base 8.3/8.4 + modules).
- Install Redis for caching.
- Install Bun (v1.3.6+) as the primary package manager.
- Initialize the panel SQLite database with optimization pragmas.
- Create directory structure (`/home`, `/var/log/panel`, `/etc/ssl/panel`).
- Set appropriate file permissions and ownership for the `panel` user.
- Ensure main panel can run at port 8095

## Configuration
- LNMP Stack
- Bun v1.3.6+

## Audit & Logging
- Service installation verification.
- Initial health checks for all services.

## Testing
- Verify all services start and run on reboot.
- Verify Bun is available in the user's PATH.

## Completion Criteria
- [ ] All required services installed and operational
- [ ] Directory structure created with correct permissions
- [ ] Management database initialized
