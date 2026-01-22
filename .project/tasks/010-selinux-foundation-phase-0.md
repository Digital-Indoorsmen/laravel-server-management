# Status: [ ] Not Complete
# Title: SELinux Foundation - Phase 0

## Description
Establish the foundational SELinux policy architecture required for a secure, multi-tenant server management panel. This task focuses on defining core types and the compilation pipeline.

## Requirements
- Map custom SELinux domain types:
  - `laravel_app_t`: Main application processes.
  - `laravel_php_fpm_t`: PHP-FPM pool workers.
  - `laravel_nginx_t`: Nginx web server.
  - `site_content_t`: Per-site web content.
  - `laravel_ssh_key_t`: SSH key management daemon.
- Create initial Type Enforcement (.te), File Context (.fc), and Interface (.if) files.
- Set up a build pipeline (Makefile) for compiling and installing policy modules:
  - `checkmodule -M -m -o panel.mod panel.te`
  - `semodule_package -o panel.pp -m panel.mod -f panel.fc`
  - `semodule -i panel.pp`
- Create context mappings for the Laravel directory structure:
  - `storage/(/.*)?  -- gen_context(system_u:object_r:laravel_app_rw_t,s0)`
  - `bootstrap/cache(/.*)? -- gen_context(system_u:object_r:laravel_app_rw_t,s0)`
- Implement basic Nginx and PHP-FPM isolation policies in permissive mode.

## Implementation Details
### Example Makefile:
```make
# Simple SELinux Policy Makefile
panel.pp: panel.mod panel.fc
	semodule_package -o panel.pp -m panel.mod -f panel.fc

panel.mod: panel.te
	checkmodule -M -m -o panel.mod panel.te

install: panel.pp
	semodule -i panel.pp

clean:
	rm -f panel.mod panel.pp
```

## Configuration
- SELinux (Targeted mode)
- checkpolicy / semodule-utils package

## Audit & Logging
- Set up `auditd` with aggressive logging for policy development.
- Monitor `/var/log/audit/audit.log` for AVC denials using `ausearch -m avc -ts recent`.

## Testing
- Verify policy compilation with `make`.
- Run `restorecon -Rv /var/www/panel` and verify file contexts match the new policy using `ls -Z`.

## Completion Criteria
- [ ] SELinux policy module structure created
- [ ] Initial domains defined and compiled
- [ ] Makefile operational
- [ ] Laravel directory contexts mapped and tested
