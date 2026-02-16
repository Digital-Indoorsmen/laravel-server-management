# Laravel Server Management Panel

This project is a Laravel + Inertia control panel for provisioning and managing self-hosted web servers (currently focused on AlmaLinux and Rocky Linux).

It tracks servers, generates a one-time bootstrap script, manages SSH keys, provisions isolated sites (Nginx + PHP-FPM + optional database), and supports remote `.env` editing.

## Current Feature Set

- Server dashboard with pending/active states and provisioning command generation.
- Setup script endpoint (`/setup/{token}`) that installs and configures:
  - Nginx, MariaDB, PostgreSQL, Redis, Fail2ban
  - Multiple PHP-FPM versions (Remi packages)
  - SELinux policy scaffolding and firewall defaults
  - A privileged `panel` user used for remote automation
- Setup callback endpoint (`/setup/{token}/callback`) for status updates (`provisioning` -> `ready`).
- SSH key management:
  - generate Ed25519 or RSA keypairs
  - import public keys
  - download generated private keys
  - private keys encrypted at rest
- Server connection checks over SSH (OS, RAM, CPU cores, uptime) with log entries.
- Site provisioning per server:
  - Linux system user and directory creation
  - PHP-FPM pool config generation
  - Nginx vhost config generation
  - optional MariaDB or PostgreSQL database/user provisioning
  - optional starter `.env` generation with DB credentials
- Site details and remote `.env` editor.
- Audit/event logging for model create/update/delete and site MCS assignment.

## Tech Stack

- PHP 8.4, Laravel 12
- Inertia.js v2 + Vue 3
- Tailwind CSS v4 + DaisyUI
- phpseclib v3 for SSH operations
- SQLite default local database (with performance pragmas)
- Pest v4 for tests

## Local Development

### Prerequisites

- PHP 8.4+
- Composer
- Bun
- SQLite (default) or another configured DB

### Install

```bash
composer run setup
```

This installs dependencies, creates `.env` if missing, generates an app key, runs migrations, installs frontend packages, and builds assets.

### Run

```bash
composer run dev
```

This starts the Laravel server, queue listener, logs (`pail`), and Vite dev server concurrently.

If you use Laravel Herd, the project is also available at the standard Herd project domain for this folder.

## Testing

Run all tests:

```bash
php artisan test --compact
```

Run a targeted suite:

```bash
php artisan test --compact tests/Feature/SiteProvisioningTest.php
```

## Provisioning Workflow

1. Create or import an SSH key in the panel.
2. Create a server record with an attached SSH key and setup token.
3. Copy the generated bootstrap command from the dashboard and run it as root on the target server.
4. Wait for callback status to become `ready`.
5. Create sites on that server (optionally with database provisioning).
6. Manage site configuration via the `.env` editor.

## Important Assumptions

- Remote command execution logs in as Linux user `panel` using the selected SSH private key.
- The setup script is intended for AlmaLinux/Rocky Linux.
- Site provisioning currently runs synchronously in the request cycle.
- Deprovisioning is minimal (site row delete; no full remote teardown yet).

## Current Gaps / In Progress

- Full server CRUD UI/routes are not fully implemented yet.
- Some dashboard metrics and service cards are placeholders.
- Authentication flow is not fully wired for production use in this branch.

## Repository Notes

- `legacy/` is intentionally left out of Git tracking in local Git exclude rules for migration/reference work.
- `verification/` contains VM-based provisioning verification assets (Vagrant + pytest).
