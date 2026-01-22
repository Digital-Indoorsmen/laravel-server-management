# Status: [ ] Not Complete
# Title: Website Management & Process Isolation

## Description
Implement the core functionality for creating, configuring, and isolating websites on managed servers. Each site must run under its own system user with isolated PHP-FPM pools and Nginx configs.

## Requirements
- UI for Website creation:
  - Selection: Domain, PHP Version (7.4-8.4), App Type (WordPress, Laravel, Generic PHP).
- Backend logic for site-specific setup (executed via SSH):
  - **User Management**: Create a unique system user per site (e.g., `web1`, `web2`).
  - **Filesystem**: Create `/home/{username}/public_html` with `755` permissions and site-user ownership.
  - **Process Isolation**: Generate a dedicated PHP-FPM pool config in `/etc/php/{version}/fpm/pool.d/{site}.conf`.
    - User/Group must match the site user.
    - Listen on a unique Unix socket `/run/php-fpm/{site}.sock`.
- **Nginx Configuration**: Generate virtual host files using optimized templates.
  - **WordPress**: Support for Multi-Domain/Multisite mapping, FastCGI caching rules.
  - **Laravel**: Root set to `/public`, standard Laravel rewrite rules.
  - **Generic PHP**: Direct execution from `public_html`.
- **Environment Management**:
  - Implement a `.env` editor in the UI.
  - Push `.env` files to `/home/{username}/.env` (symlinked into the app root if needed).
  - Ensure the panel can restart PHP-FPM for specific pools after config changes.

## Implementation Details
### Example PHP-FPM Pool Template:
```ini
[{{ site_user }}]
user = {{ site_user }}
group = {{ site_group }}
listen = /run/php-fpm/{{ site_user }}.sock
pm = ondemand
pm.max_children = 5
```

## Configuration
- Nginx & PHP-FPM Templates (stored in `resources/views/templates`)
- SSH Process Wrappers

## Audit & Logging
- Detailed logs for each provisioning step (User created -> Dir created -> Pool started).
- Record Nginx/PHP-FPM config validation output (`nginx -t`).

## Testing
- Automated test: Provision a "Laravel" site and verify the Nginx config contains `/public`.
- Manual: Verify the PHP process for Site A is running as User A.

## Completion Criteria
- [ ] Site creation workflow fully automated via SSH
- [ ] Individual PHP-FPM pools working correctly
- [ ] Nginx virtual hosts correctly routing based on App Type
- [ ] Environment variable persistence verified
