# Status: [ ] Not Complete
# Title: Site Settings and Safe Reconfiguration

## Parent
- Depends on: `029a-site-workspace-foundation.md`

## Goal
Implement Forge-style site settings management with safe downstream reconfiguration.

## Scope
- Editable settings: framework/app type, PHP version, tags, notes, root/public directories, deploy defaults.
- Apply changes safely to runtime configs and processes.
- Show impact warnings when changes require process/config restart.

## Deliverables
- Settings update endpoints + validation.
- Settings tab with grouped forms and confirmation UX.
- Reconfiguration job/service orchestration.

## Tests
- Settings update happy/failure paths.
- Reconfiguration side-effect assertions.
- Rollback/error recovery behavior.

## Completion Criteria
- [ ] Site settings are editable with robust validation
- [ ] Runtime reconfiguration is safe and observable
- [ ] High-risk changes include explicit confirmation UX
