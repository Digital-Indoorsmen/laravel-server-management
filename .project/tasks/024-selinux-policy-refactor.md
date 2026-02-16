# Status: [ ] Not Complete
# Title: SELinux Policy Refactor - Extending httpd_t

## Description
Refactor the SELinux policy implementation to align with RHEL/CentOS standards. Instead of defining entirely new domains (`laravel_nginx_t`, `laravel_php_fpm_t`) which requires complex binary relabeling, we will extend the standard system domains (`httpd_t`) to support our custom file types and ports.

## Requirements
- **Remove Custom Domains**:
  - Remove `laravel_nginx_t` and `laravel_php_fpm_t` type definitions.
  - Revert to using the standard `httpd_t` for Nginx and PHP-FPM processes.
- **Extend httpd_t Policy**:
  - Update `panel.te` to allow `httpd_t` to read/write `site_content_t`.
  - Allow `httpd_t` to connect to our custom database and Redis ports if not already allowed.
  - Allow `httpd_t` to access `laravel_app_rw_t` (for the panel itself).
- **Port Labeling**:
  - Ensure port 8095 (Panel) is labeled as `http_port_t` so the standard Nginx can bind to it.
- **Boolean Management**:
  - Ensure standard booleans like `httpd_can_network_connect_db` are managed/checked by the setup script.

## Implementation Details
### Revised `panel.te` Logic:
```bash
require {
    type httpd_t;
    type site_content_t;
}

# Allow standard web server process to access our custom site content
allow httpd_t site_content_t:dir list_dir_perms;
allow httpd_t site_content_t:file read_file_perms;
```

## Configuration
- SELinux Policy Management

## Audit & Logging
- Monitor audit logs for `httpd_t` denials after refactoring.

## Testing
- **Verification**: Run `nginx` and `php-fpm` in Enforcing mode.
- **Test**: Access the panel (Port 8095) and a provisioned site (Port 80).
- **Test**: Verify PHP can write to `storage/` directories (mapped as `laravel_app_rw_t` or `httpd_sys_rw_content_t`).

## Completion Criteria
- [ ] Custom domain definitions removed
- [ ] `httpd_t` allowed access to site content types
- [ ] Port 8095 correctly labeled
- [ ] Full Enforcing mode operational without denials for basic web traffic
