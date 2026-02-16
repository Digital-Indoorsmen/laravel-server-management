# Status: [ ] Not Complete
# Title: Domains and Certificates

## Parent
- Depends on: `029a-site-workspace-foundation.md`

## Goal
Deliver Forge-like domain and certificate management per site.

## Scope
- Add/remove domains and aliases.
- Set primary domain and wildcard option.
- Request/renew/revoke certificates and display status.
- Keep nginx/caddy config and DNS expectations explicit.

## Deliverables
- Domains data model + service operations.
- Certificates workflow integration.
- Domains tab with domain list and cert status/actions.

## Tests
- Domain add/remove/primary switch.
- Certificate lifecycle success/failure.
- Validation for duplicate/invalid domains.

## Completion Criteria
- [ ] Domains can be managed end-to-end
- [ ] Certificate lifecycle actions work and are visible
- [ ] Config updates are safe and audited
