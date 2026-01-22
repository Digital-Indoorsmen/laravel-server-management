# Status: [ ] Not Complete
# Title: AlmaLinux & Rocky Linux Base Setup Script - SELinux & Firewall

## Description
Finalize the server setup script with critical security configuration for SELinux and Firewalld. This ensures the node is protected while remaining manageable by the panel.

## Requirements
- Configure SELinux:
  - Set to `permissive` mode initially to allow for policy development without blocking functionality.
  - Install `policycoreutils-python-utils` (for `semanage`).
  - Prepare `/usr/share/selinux/packages/panel` for custom policy modules.
- Configure Firewalld:
  - Default zone: `public`.
  - Allowed services: `ssh`, `http`, `https`.
  - Custom ports: `8095` (Panel API), `3306` (MariaDB - optional/restricted), `5432` (Postgres - optional/restricted).
- Implement Fail2ban:
  - Default jail for `sshd`.
  - Max retry: 5, Bantime: 1h.
- Ensure all security settings are applied immediately.

## Implementation Details
### Firewalld configuration:
```bash
firewall-cmd --permanent --add-service=http
firewall-cmd --permanent --add-service=https
firewall-cmd --permanent --add-port=8095/tcp
firewall-cmd --reload
```

## Configuration
- SELinux (Permissive)
- FirewallD
- Fail2ban

## Audit & Logging
- Output of `firewall-cmd --list-all`.
- Verification of `getenforce` status.

## Testing
- Port scan from an external machine to verify port 8095 and 22 are open.
- Attempt multiple failed SSH logins to verify Fail2ban (from a safe source).

## Completion Criteria
- [ ] Security services configured and enabled
- [ ] Firewall rules verified externally
- [ ] Environment ready for SELinux Phase 0 testing (logging active)
