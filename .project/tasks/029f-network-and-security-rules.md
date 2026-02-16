# Status: [ ] Not Complete
# Title: Network (Redirects + Site Security Rules)

## Parent
- Depends on: `029a-site-workspace-foundation.md`

## Goal
Implement Forge-like network controls with practical redirect and security-rule management.

## Scope
- Redirect rules CRUD (from/to, code, enabled state).
- Site-level security rule toggles where supported.
- Web-server compatibility handling (nginx vs caddy differences).

## Deliverables
- Redirect model + provisioning integration.
- Network tab with rule list/editor and validation.

## Tests
- Redirect create/update/delete.
- Validation and conflict cases.
- Rendered config assertions for supported server types.

## Completion Criteria
- [ ] Redirect rules are manageable and applied correctly
- [ ] Security controls are explicit and server-compatible
- [ ] Changes are audited and reversible
