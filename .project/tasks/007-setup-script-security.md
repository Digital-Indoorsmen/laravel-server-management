# Status: [ ] Not Complete
# Title: AlmaLinux Base Setup Script - SELinux & Firewall

## Description
Finalize the server setup script with critical security configuration for SELinux and Firewalld.

## Requirements
- Configure SELinux:
  - Set to permissive mode initially for testing.
  - Install standard policy development tools (`setroubleshoot-server`).
  - Prepare directories for custom policy modules.
- Configure Firewalld:
  - Allow SSH (22), HTTP (80), HTTPS (443), and Panel (8095).
  - Set default zone to `drop` or `public` with strict rules.
- Implement basic Fail2ban configuration for SSH protection.

## Configuration
- SELinux
- FirewallD
- Fail2ban

## Audit & Logging
- Firewall rule audit.
- SELinux violation monitoring dashboard (backend preparation).

## Testing
- Port scan to verify only expected ports are open.
- Verify SELinux is in permissive mode and logging AVCs.

## Completion Criteria
- [ ] Security services configured and enabled
- [ ] Firewall rules verified
- [ ] Environment ready for SELinux Phase 0 testing
