# Status: [ ] Not Complete
# Title: Nginx Security Hardening & Headers

## Description
Port the specific security headers and access control rules from the legacy Nginx templates to ensure the new system provides the same level of default security.

## Requirements
- **Default Security Headers**:
  - Automatically inject the following headers into generated Nginx vhosts:
    - `X-XSS-Protection "1; mode=block"`
    - `Content-Security-Policy` (Default to a safe baseline, configurable)
    - `X-Frame-Options "SAMEORIGIN"`
    - `X-Content-Type-Options "nosniff"`
    - `Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"`
    - `Referrer-Policy "no-referrer-when-downgrade"`
    - `Permissions-Policy "geolocation=(), microphone=(), camera=()"`
- **Directory Protection Rules**:
  - Implement the "Blocked Directories" logic found in the legacy template (`api-notify`, `modules`, `system`, `views`).
  - Make this configurable per site (e.g., "Legacy Protection Mode") or apply standard denials (dotfiles) by default.
- **Server Tokens**:
  - Ensure `server_tokens off;` is applied.

## Implementation Details
- Update the Nginx Blade templates (`resources/views/templates/nginx.conf.blade.php` etc.) to include these headers.
- Add a boolean toggle in the Site Settings UI: "Enable Legacy Security Rules" or "Enable Strict Security Headers".

## Configuration
- Nginx Headers

## Audit & Logging
- Monitor logs for 403/404 errors triggered by the protection rules.

## Testing
- Use `curl -I https://site.test` to verify all headers are present.
- Verify that accessing `/modules/` returns 404/403.

## Completion Criteria
- [ ] Default security headers applied to all new sites
- [ ] Directory protection logic implemented
- [ ] `server_tokens off` verified
