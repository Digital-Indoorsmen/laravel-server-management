# Server Management Panel - Consolidated Technical Ideation

## 1. SQLite Optimization for Panel Management Database

### SQLite WAL Mode & Production Optimization
- Enable WAL (Write-Ahead Logging) mode for concurrent access.
- Configure `journal_mode = WAL` in SQLite database.
- Set `page_size = 32768` bytes for optimal page handling.
- Enable `auto_vacuum = INCREMENTAL` for space reclamation.
- Configure `cache_size = -20000` (20 MB) for read performance.
- Set `mmap_size = 2147483648` (2 GB) for memory mapping.
- Configure `busy_timeout = 5000` milliseconds for lock contention.
- Enable `foreign_keys = ON` for referential integrity.
- Set `synchronous = NORMAL` for performance/safety balance.
- Configure `temp_store = MEMORY` for faster temporary operations.

### Laravel Configuration
- Update `config/database.php` with SQLite optimizations.
- Create `database/database.sqlite` as panel management DB.
- Apply per-connection pragmas via `AppServiceProvider`.

---

## 2. SELinux-First Architecture (Phase 0)

### Policy Development
- **Domain Mapping**:
  - `laravel_app_t`: Main application processes.
  - `laravel_php_fpm_t`: PHP-FPM pool workers.
  - `laravel_nginx_t`: Nginx web server.
  - `site_content_t`: Per-site web content.
  - `laravel_ssh_key_t`: SSH key management daemon.
- **File Contexts (.fc)**: Automatic assignment for `/home/*/public_html`, sockets, and log locations.
- **Type Enforcement (.te)**: Explicit allow rules for process transitions and file access.
- **Interfaces (.if)**: Public interfaces for domain transitions.

---

## 3. AlmaLinux Base Setup Script (setup.sh)

### Core System Setup
- Idempotent script for AlmaLinux 8.x/9.x.
- Security hardening (root pass disable, sysctl tweaks).
- Repository configuration (EPEL, PowerTools/CRB).
- System updates (`dnf update`).

### Service Installation
- Nginx (Official repos).
- Multi-version PHP support (7.4 to 8.4).
- MariaDB & PostgreSQL (latest stable).
- Redis for caching.
- Bun v1.3.6+ for frontend asset management.

### Security Implementation
- SELinux initial configuration (permissive mode).
- Firewalld configuration (SSH, HTTP, HTTPS, 8095).
- Chroot jail for SFTP access.
- Process isolation per site (Users located at `/home/{username}` with site root at `public_html`).
- Site-specific Nginx & PHP templates (WordPress Multi-domain, Laravel, Generic PHP).
- **Persistent Environment Variables**: Managing `.env` files outside the git-managed web root (`/home/{username}/.env`) to prevent overwrites during deployment.

---

## 4. Database Architecture & Migrations

### Core Tables (SQLite-based)
- `servers`: Details about managed servers.
- `ssh_keys`: Key management with fingerprinting.
- `sites`: Domain, PHP version, doc_root mapping.
- `system_users`: Unix users for process isolation.
- `databases`: Tracking MariaDB/PostgreSQL instances per site.
- `selinux_audit_logs`: Violation tracking and context monitoring.

### Website Database Isolation
- Websites use separate MariaDB or PostgreSQL instances.
- Automated database and user creation during site setup.

---

## 5. Frontend & UI (DaisyUI v5 + Inertia.js)

### Technology Stack
- **Framework**: Vue 3 + Inertia.js.
- **Styling**: Tailwind CSS v4 + DaisyUI v5+.
- **Package Manager**: Bun v1.3.6+ (default), bun (fallback).

### UI Components (DaisyUI Patterns)
- **Monitoring**: `stat`, `progress`, and `badge` for server health and SELinux status.
- **Forms**: `form-control`, `input`, `select` for site and server management.
- **Feedback**: `toast` and `alert` for real-time SELinux violations.

---

## 6. SSH Key & Server Management

### Key Management
- RSA/ED25519 key generation via web interface.
- Root access key insertion into remote servers.
- Key rotation and `authorized_keys` management.

### Connectivity
- Laravel CLI Kit integration for SSH-based command execution.
- Domain transition to `laravel_ssh_key_t` context for key operations.

---

## 7. Scaling & Future Proofing
- Monitoring SQLite file size and performance.
- Migration path to PostgreSQL for the panel DB if sites > 500.
- Horizontal scaling preparation for multi-node deployments.