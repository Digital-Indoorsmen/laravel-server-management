# Status: [ ] Not Complete
# Title: DNS Provider Integration & Wildcard SSL

## Description
Implement support for DNS-01 challenges to enable Wildcard SSL certificate generation. The legacy system utilized a `dns-multi` plugin (specifically for cPanel); this system should be more generic but initially support common providers to match or exceed legacy capabilities.

## Requirements
- **DNS Provider Management**:
  - UI to store and manage API credentials for DNS providers (e.g., Cloudflare, cPanel/WHM, Route53).
  - Secure storage of API tokens (encrypted at rest).
- **Wildcard SSL Generation**:
  - Extend the Site Creation/Management flow to allow selecting "Wildcard SSL" if a DNS provider is configured.
  - Automate `certbot` execution with the appropriate DNS plugin.
- **Legacy Compatibility**:
  - Specifically implement the `cPanel` DNS integration (via `certbot-dns-cpanel` or similar) to match legacy `dns-multi` functionality.

## Implementation Details
- Install necessary certbot DNS plugins via the setup script (or on-demand).
- Logic to generate the credential INI files required by certbot plugins on the fly (or store them securely).
- Example command: `certbot certonly --dns-cloudflare --dns-cloudflare-credentials ~/.secrets/certbot/cloudflare.ini -d example.com -d *.example.com`

## Configuration
- Certbot DNS Plugins
- Laravel Encryption

## Audit & Logging
- Log DNS API interaction results (success/failure).
- Audit when Wildcard SSL is requested.

## Testing
- Verify that a Wildcard Certificate (`*.domain.com`) is successfully issued.
- Verify automatic renewal works for DNS-01 challenges.

## Completion Criteria
- [ ] DNS Provider credential management UI implemented
- [ ] Wildcard SSL generation logic functional
- [ ] cPanel DNS integration verified (matching legacy capability)
