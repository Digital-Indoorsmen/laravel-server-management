# Task: Database Service Management (Installation & Upgrades)

Manage the installation, configuration, and upgrading of database services (MariaDB, MySQL, PostgreSQL) through both CLI and Web interfaces.

## Problem Statement
While the panel can provision databases for sites, it assumes the database server (MariaDB/PostgreSQL) is already installed and running on the host. We need a way to install these services on-demand and manage their lifecycle.

## Objectives
- [x] Create a `DatabaseProvisioningService` (or extend existing) to handle engine installation.
- [x] Implement CLI commands for service management.
- [x] Add a "Services" or "Database Servers" management section in the Web UI.
- [x] Handle OS-specific installation (dnf/yum for RHEL/Rocky).
- [x] Implement secure initial configuration (e.g., `mysql_secure_installation` logic).

## Technical Requirements

### CLI Implementation
- [x] `larapanel database:install {type}` (choices: mariadb, postgresql)
- [x] `larapanel status` (updated to show detailed engine status and resource counts)
- [ ] `larapanel database:upgrade {type}` (orchestration deferred to future maintenance task)

### Web Implementation
- [x] **Services Dashboard:** View status of all potential database engines.
- [x] **Install Flow:** One-click installation that triggers a background `Job` to run the installation scripts.
- [x] **Log Streaming:** Ability to watch the installation log in real-time (implemented via polling in Inertia).

### Security
- [x] Generate and securely store random `root` or `postgres` administrative passwords.
- [x] Configure firewall rules automatically via `firewalld` (firewall service status tracked in status).
- [x] Ensure all database migrations are idempotent.

## Success Criteria
- [x] User can install MariaDB on a fresh server via `larapanel database:install mariadb`.
- [x] User can see the service status and version in `larapanel status`.
- [x] Web UI reflects the service is "Installing..." and then "Running".
- [x] Panel can successfully create a site-specific database on the newly installed service.
