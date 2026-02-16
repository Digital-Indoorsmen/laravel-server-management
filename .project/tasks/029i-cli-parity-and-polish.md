# Status: [ ] Not Complete
# Title: CLI Parity and Site-Management Polish

## Parent
- Depends on: `029b-deployments-and-release-history.md`
- Depends on: `029c-processes-supervisor-management.md`
- Depends on: `029d-commands-execution-and-history.md`
- Depends on: `029e-domains-and-certificates.md`

## Goal
Complete `larapanel` parity for site operations and finalize UX polish for the full module.

## Scope
- Add/finish site-scoped CLI commands:
  - `larapanel site:deploy {site}`
  - `larapanel site:processes {site}`
  - `larapanel site:command {site} -- <cmd>`
  - `larapanel site:domains {site}`
- Improve consistency across UI messaging, statuses, and errors.
- Remove remaining UX friction from cross-tab workflows.

## Deliverables
- CLI command suite with help output and validations.
- End-to-end docs/help text in command outputs.
- Final UX polish pass across site workspace tabs.

## Tests
- Feature tests for each new CLI command.
- Regression pass for all site workspace tabs.

## Completion Criteria
- [ ] CLI parity commands exist and are usable
- [ ] UI/CLI language and statuses are consistent
- [ ] End-to-end site management workflow is production-ready
