# Status: [ ] Not Complete
# Title: Website Management & Process Isolation

## Description
Implement the core functionality for creating, configuring, and isolating websites on managed servers.

## Requirements
- UI for Website creation (Domain, PHP version, App Type selection).
- Backend logic for site-specific setup:
  - Document root creation at `/home/{username}/public_html` with correct ownership (`{username}:{username}`).
  - Implementation of site-specific Nginx & PHP templates:
    - Support for multi-domain mappings for all of them.
    - **WordPress**: Support for multi-domain mappings.
    - **Laravel**: Optimized for `/public` sub-directory.
    - **Generic PHP**: Serving directly from `public_html`.
  - PHP-FPM pool isolation (custom sockets, user-bound workers).
  - Nginx virtual host configuration generation from templates.
- Support for WordPress (Multi-domain), Laravel, and Generic PHP site types.
- Integration with SELinux contexts for per-site file isolation.
- MariaDB/PostgreSQL database provisioning per site.
- **Secure Environment Variable Management**:
  - Implementation of a `.env` editor in the UI with server-side validation.
  - Storage of sensitive environment variables in the panel database (encrypted at rest).
  - Logic to push/persist `.env` files to `/home/{username}/.env` (outside the deploy-target directory to survive git updates).
  - Automated symlinking or configuration logic to ensure apps (especially Laravel) read from the persisted `.env` location.

## Configuration
- Nginx & PHP-FPM Templates
- SELinux per-site context rules

## Audit & Logging
- Log site creation milestones.
- Capture configuration validation errors.

## Testing
- Create a test site and verify accessibility.
- Verify cross-site file access is blocked by SELinux.

## Completion Criteria
- [ ] Site creation workflow fully automated
- [ ] Configuration templates functional and secure
- [ ] Per-site isolation verified
- [ ] Environment variable management and persistence verified
