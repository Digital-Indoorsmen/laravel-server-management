# Status: [ ] Not Complete
# Title: SELinux Policy Enforcement & Multi-Tenancy

## Description
Transition the SELinux environment from permissive to enforcing for the management panel and ensure strict isolation between different websites (tenants).

## Requirements
- Enable SELinux `enforcing` mode on the test node.
- Implement per-site user context:
  - Sites should run under a specific user category (MCS - Multi-Category Security) like `s0:c1,c2` to prevent cross-site file access even if directory permissions are identical.
- Refine `laravel_php_fpm_t` to allow:
  - Network connect to database ports (3306, 5432).
  - Write access only to specific site-owned directories.
- Refine `laravel_nginx_t` to:
  - Read only from `site_content_t`.
  - Connect only to specific PHP-FPM sockets.
- Implement SELinux boolean toggles for the panel:
  - `panel_can_manage_users`
  - `panel_can_reload_nginx`

## Implementation Details
### MCS Isolation Logic:
Each site should be assigned a unique category pair (e.g., `c100`, `c101`) during creation.

## Configuration
- SELinux (Enforcing mode)
- libsemanage-php (to manage policies from Laravel if needed)

## Audit & Logging
- Log all AVC denials into the `selinux_audit_logs` table via a system daemon or specialized observer.

## Testing
- Attempt to read File A (Site 1) from PHP process B (Site 2) and verify it is blocked by SELinux even if permissions are 777.
- Verify Nginx can still serve static content.

## Completion Criteria
- [ ] SELinux Enforcing mode active
- [ ] Cross-site access blocked via MCS/Categories
- [ ] Audit logging into database functional
