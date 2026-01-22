# Status: [ ] Not Complete
# Title: Core Database Migrations & Audit Schema

## Description
Create the foundational database schema for the server management panel, including specialized audit tables for SELinux violation tracking.

## Requirements
- Create migrations for core resource tables:
  - `servers`, `ssh_keys`, `sites`.
  - `system_users`, `databases`, `database_users`.
  - `php_versions`, `ssl_certificates`.
  - `user_preferences` (package manager preference).
- Create specialized SELinux audit tables:
  - `selinux_audit_logs`.
  - `selinux_violations`.
- Ensure all migrations use SQLite-compatible column types.
- Implement Model Observers to log SELinux context with record changes.

## Configuration
- Laravel 12 Migrations
- SQLite 3

## Audit & Logging
- Migration history.
- Verification of foreign key constraints.

## Testing
- Run all migrations on a fresh SQLite database.
- Verify audit tables are correctly indexed for performance.

## Completion Criteria
- [ ] All resource tables created
- [ ] Audit schema implemented
- [ ] Model observers for context logging operational
