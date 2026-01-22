# Status: [ ] Not Complete
# Title: Production Hardening, Monitoring & Backups

## Description
Finalize the production readiness of the panel with specialized hardening, monitoring, and backup strategies for both the panel and managed sites.

## Requirements
- Implement automated SQLite backup jobs (off-site storage support).
- Set up monitoring for SQLite database file size and integrity.
- Implement production hardening for the panel:
  - Configuration encryption.
  - Secure session management.
- Create a deployment checklist featuring SELinux verification and port 8095 validation.
- Develop point-in-time recovery (PITR) procedures for site databases (MariaDB/PG).
- Implement real-time monitoring dashboard for SELinux violations in production.

## Configuration
- S3 / Rclone for off-site backups
- Laravel System Monitoring utilities

## Audit & Logging
- Backup success/failure logs.
- SELinux production violation alerts.

## Testing
- Test disaster recovery by restoring a panel from an off-site SQLite backup.
- Simulate high-load and verify monitoring alerts trigger correctly.

## Completion Criteria
- [ ] Automated backup system operational
- [ ] Production hardening applied
- [ ] Monitoring dashboard for SELinux and performance finalized
- [ ] Deployment checklist and disaster recovery guide completed
