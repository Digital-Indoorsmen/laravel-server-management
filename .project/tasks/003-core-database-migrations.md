# Status: [x] Complete
# Title: Core Database Migrations & Audit Schema

## Description
Create the foundational database schema for the server management panel. This includes tables for servers, sites, users, and specialized audit tables for SELinux violation tracking.

## Requirements
- Create migrations for core resource tables with the following specific schemas:

### `servers` table
- `id` (ULID)
- `name` (string)
- `ip_address` (string, unique)
- `hostname` (string)
- `os_version` (string: alma_8, alma_9, rocky_8, rocky_9)
- `ssh_key_id` (unsignedBigInteger, nullable, foreignKey)
- `status` (string: provisioning, active, error)
- `created_at`, `updated_at`

### `ssh_keys` table
- `id` (ULID)
- `name` (string)
- `public_key` (text)
- `private_key` (text, encrypted)
- `fingerprint` (string, unique)
- `created_at`, `updated_at`

### `sites` table
- `id` (ULID)
- `server_id` (unsignedBigInteger, foreignKey)
- `domain` (string, unique)
- `document_root` (string)
- `system_user` (string)
- `php_version` (string)
- `app_type` (string: wordpress, laravel, generic)
- `status` (string: creating, active, suspended)
- `created_at`, `updated_at`

### `selinux_audit_logs` table
- `id` (ULID)
- `log_level` (string)
- `message` (text)
- `context` (json)
- `created_at`

- Ensure all migrations use SQLite-compatible column types.
- Use ULIDs for primary keys where appropriate for better security/portability.
- Implement Model Observers to log record changes into `selinux_audit_logs`.

## Configuration
- Laravel 12 Migrations
- SQLite 3

## Audit & Logging
- Migration history in `migrations` table.
- Verification of foreign key constraints after migration.

## Testing
- Run `php artisan migrate:fresh` on a fresh SQLite database.
- Verify audit tables are correctly indexed for performance.

## Completion Criteria
- [x] All resource tables created according to detailed schema
- [x] Audit schema implemented and tested
- [x] Model observers for basic logging operational
