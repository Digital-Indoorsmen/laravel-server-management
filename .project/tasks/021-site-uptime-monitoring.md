# Status: [ ] Not Complete
# Title: Site Uptime & SSL Monitoring

## Description
Implement a dedicated monitoring system for individual sites, focusing on external uptime checks and SSL certificate health.

## Requirements
- **External Uptime Checks**:
  - Periodic (e.g., every 5 mins) HTTP/HTTPS checks for all hosted domains.
  - Support for checking specific strings in the response to ensure the app is healthy.
- **SSL Monitoring**:
  - Alerts for certificate expiration or misconfiguration (e.g., broken chain).
  - Logic to automatically trigger a renewal if a certificate is near expiration.
- **Incident Tracking**:
  - Dashboard view showing uptime history and response times.
  - Logging of downtime incidents (Start, End, Reason).
- **Alerting**:
  - Email/Slack notifications for site downtime or SSL issues.

## Implementation Details
- Use a background job (Laravel Scheduler) or a specialized Go/Rust helper for efficient probing.
- Store results in a time-series or optimized SQLite table for historical reporting.

## Configuration
- Laravel Scheduler / Jobs
- Notification Channels

## Audit & Logging
- Log all downtime events.
- Audit changes to frequency or alert thresholds.

## Testing
- Simulate site failure (stop Nginx) and verify alert triggering.
- Verify SSL expiration warning notifications.

## Completion Criteria
- [ ] External probing system functional
- [ ] Uptime/SSL dashboard operational
- [ ] Notification system for incidents verified
