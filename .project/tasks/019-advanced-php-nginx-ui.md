# Status: [ ] Not Complete
# Title: Advanced PHP & Nginx UI Configuration

## Description
Provide a high-level UI for managing common PHP-FPM and Nginx settings per site, reducing the need for manual `.env` or config file editing.

## Requirements
- **PHP Configuration UI**:
  - Manage: `memory_limit`, `upload_max_filesize`, `post_max_size`, `max_execution_time`.
  - Toggle for `display_errors` and `OPcache`.
- **Nginx Configuration UI**:
  - Manage: `client_max_body_size`, `proxy_read_timeout`.
  - Support for basic basic-auth (directory protection).
  - Toggle for FastCGI caching and Gzip compression.
- **Configuration Persistence**:
  - Changes must be persistent and survive panel updates.
  - Automated Nginx/PHP-FPM reload after saving.

## Implementation Details
- Generate a site-specific `.user.ini` or custom PHP-FPM include file for PHP settings.
- Generate a site-specific `nginx_advanced.conf` included in the main vhost.

## Configuration
- PHP-FPM Pool Config (`pool.d`)
- Nginx vhost includes

## Audit & Logging
- Log all configuration value changes.
- Record service reload statuses.

## Testing
- Verify that UI changes are reflected in `phpinfo()` output.
- Verify that `client_max_body_size` changes affect file upload limits.

## Completion Criteria
- [ ] PHP common settings UI functional
- [ ] Nginx common settings UI functional
- [ ] Automated service reload integrated
