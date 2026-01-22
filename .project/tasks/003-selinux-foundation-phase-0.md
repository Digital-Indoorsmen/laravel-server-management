# Status: [ ] Not Complete
# Title: SELinux Foundation - Phase 0

## Description
Establish the foundational SELinux policy architecture required for a secure, multi-tenant server management panel.

## Requirements
- Map custom SELinux domain types:
  - `laravel_app_t`: Main application processes.
  - `laravel_php_fpm_t`: PHP-FPM pool workers.
  - `laravel_nginx_t`: Nginx web server.
  - `site_content_t`: Per-site web content.
  - `laravel_ssh_key_t`: SSH key management daemon.
- Create initial Type Enforcement (.te), File Context (.fc), and Interface (.if) files.
- Set up a build pipeline (Makefile) for compiling and installing policy modules (`checkmodule`, `semodule_package`).
- Create context mappings for the Laravel directory structure (`storage/`, `bootstrap/cache/`, `.env`).
- Implement basic Nginx and PHP-FPM isolation policies.

## Configuration
- SELinux (Targeted mode)
- checkpolicy / semodule-utils package

## Audit & Logging
- Set up `auditd` with aggressive logging for policy development.
- Monitor `/var/log/audit/audit.log` for AVC denials.

## Testing
- Verify policy compilation with `checkmodule`.
- run `restorecon` and verify file contexts match the new policy.

## Completion Criteria
- [ ] SELinux policy module structure created
- [ ] Initial domains defined and compiled
- [ ] Makefile operational
- [ ] Laravel directory contexts mapped and tested
