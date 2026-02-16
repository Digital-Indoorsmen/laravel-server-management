# Status: [ ] Not Complete
# Title: Dual Web Server Support (Nginx + Caddy)

## Description
Add first-class support for selecting `nginx` or `caddy` for server/site provisioning, while keeping existing Nginx behavior stable. Incorporate the Caddy architecture from `.project/using-caddy.md` (dynamic per-site config, multi-PHP compatibility, SELinux-safe defaults).

## Requirements
- **Web Server Selection Model**:
  - Add a `web_server` selection (`nginx`, `caddy`) at minimum on `servers`.
  - Optionally support site-level override (recommended) with fallback to server default.
  - Default existing and new records to `nginx` for backward compatibility.
- **Provisioning Orchestration**:
  - Provisioning must branch by selected web server:
    - Nginx path: keep current implementation.
    - Caddy path: generate Caddy site config and reload Caddy.
  - Keep PHP-FPM per-site pool generation working for both choices.
- **Setup Script Integration**:
  - Update setup script template to install/configure Caddy when selected.
  - Keep Nginx installation path intact.
  - Ensure the selected service is enabled and healthy after bootstrap.
- **Caddy Configuration Strategy**:
  - Maintain a base Caddyfile that imports generated per-site configs.
  - Generate one site config per domain with:
    - `root`, `encode gzip zstd`, `php_fastcgi`, `file_server`
    - baseline security headers from `using-caddy.md`
  - Validate config before reload (`caddy validate`) and fail safely.
- **SELinux Compatibility for Caddy**:
  - Apply required contexts for web roots, PHP socket paths, Caddy logs, and generated config storage.
  - Enable required booleans (`httpd_can_network_connect`, `httpd_can_network_connect_db`, `httpd_can_sendmail`) where needed.
  - Keep existing SELinux policy flow compatible with both web servers.
- **UI/UX Updates**:
  - Add web server selector in relevant forms (server create/edit, and site create if override is supported).
  - Show selected web server in dashboard/site details so behavior is explicit.

## Implementation Details
- **Database**
  - Add migration(s) for `web_server` fields with defaults and safe rollback.
  - Update model casts/validation rules to enforce allowed values.
- **Services**
  - Refactor current web server config generation into strategy classes, e.g.:
    - `NginxProvisioner`
    - `CaddyProvisioner`
  - Keep shared concerns (system user creation, directories, env writing, database provisioning) centralized.
  - Add Caddy-specific writer/validator/reloader methods:
    - write site config
    - validate full Caddy config
    - reload service only after successful validation
- **Templates / Config Files**
  - Add Caddy site template(s) in `resources/views/provisioning/`.
  - Update setup script template to conditionally configure Caddy or Nginx.
  - Preserve existing Nginx templates and behavior.
- **Operational Safety**
  - On invalid Caddy config, do not reload and set site/server status to `error` with actionable log message.
  - Ensure no destructive changes to existing Nginx-managed sites during rollout.

## Configuration
- Caddy service and base config (`/etc/caddy/Caddyfile`)
- Generated Caddy site configs (imported path, one file per site)
- PHP-FPM pool configs per PHP version/site
- SELinux contexts + booleans for Caddy/PHP integration

## Audit & Logging
- Log selected `web_server` for provisioning jobs and setup callbacks.
- Persist config validation/reload outcomes for Caddy and Nginx in `server_logs`.
- Include command failure output in logs (sanitized for secrets).

## Testing
- **Feature Tests (Pest)**:
  - creating/provisioning site with `nginx` still passes existing behavior.
  - creating/provisioning site with `caddy` issues Caddy-specific commands and config writes.
  - setup script endpoint includes Caddy installation/config when selected.
  - validation fails gracefully when Caddy config is invalid (status + logs asserted).
- **Regression Tests**:
  - existing setup/provisioning/SSH key tests remain green.
- **Manual Verification**:
  - `curl -I` against a Caddy-managed site returns expected headers.
  - `sudo caddy validate --config /etc/caddy/Caddyfile` passes.
  - SELinux checks confirm no AVC denials on normal request path.

## Completion Criteria
- [ ] Web server selection persisted and validated (`nginx`/`caddy`)
- [ ] Provisioning works end-to-end for both Nginx and Caddy
- [ ] Setup script installs/configures selected web server correctly
- [ ] Caddy config validation + safe reload flow implemented
- [ ] SELinux contexts/booleans documented and applied for Caddy
- [ ] UI exposes and displays selected web server clearly
- [ ] New/updated Pest tests pass for both server paths
