# Status: [ ] Not Complete
# Title: Domain & Alias Management

## Description
Enhance site management to support multiple domains, aliases, and the ability to change the primary domain of a site seamlessly.

## Requirements
- **Primary Domain Management**:
  - UI to update the primary domain of an existing site.
  - Automated update of Nginx configs, `.env` files, and database search-and-replace (for WordPress/Laravel if applicable).
- **Domain Aliases**:
  - Support for adding multiple alias domains (301 redirects or same content).
  - Update Nginx `server_name` directive to include aliases.
- **SSL Management for Aliases**:
  - Automatically request Let's Encrypt certificates for all aliases.
- **DNS Verification Helper**:
  - Provide a UI tool to check if A/CNAME records point to the server before applying changes.

## Implementation Details
- Use `sed` or specialized scripts via SSH to update domain-specific strings in configuration files.
- Integrate with `certbot` for multi-domain certificates (SAN).

## Configuration
- Nginx Templates
- Certbot / Let's Encrypt

## Audit & Logging
- Log all domain changes and alias additions.
- Record SSL certificate renewal events.

## Testing
- Verify that aliases correctly route to the primary site.
- Verify that changing the primary domain updates all necessary files and redirects the old domain.

## Completion Criteria
- [ ] Primary domain swap logic functional
- [ ] Alias management with SSL operational
- [ ] DNS verification tool integrated
