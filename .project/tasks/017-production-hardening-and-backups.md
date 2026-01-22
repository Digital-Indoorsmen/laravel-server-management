# Status: [ ] Not Complete
# Title: Production Hardening & Backup Strategy

## Description
Implement the final security layers and automated backup systems required for a production-ready server management panel.

## Requirements
- **Automated Backups**:
  - Implement a job to backup the management SQLite database daily (locally and to S3-compatible storage).
  - Implement per-site backup logic (Files + DB) triggered via the panel.
- **Service Monitoring**:
  - Implement a basic heartbeat monitor for managed nodes.
  - Alert system for CPU/RAM/Disk thresholds.
- **Production Hardening**:
  - Disable any remaining development/debug endpoints.
  - Configure automated security updates (`dnf-automatic`) on managed nodes.
  - Implement 2FA (Two-Factor Authentication) for the management panel login.

## Configuration
- Laravel Scheduler
- S3 / Rclone for backups
- dnf-automatic

## Testing
- Verify backup restoration to a test environment.
- Simulate high disk usage to verify alert triggering.

## Completion Criteria
- [ ] Automated backup system functional and tested
- [ ] Monitoring dashboard shows node health
- [ ] node-side hardening applied via setup script update
