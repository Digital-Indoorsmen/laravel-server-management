# Status: [ ] Not Complete
# Title: Processes (Supervisor-backed Management)

## Parent
- Depends on: `029a-site-workspace-foundation.md`

## Goal
Provide Forge-like background process management for each site.

## Scope
- CRUD process definitions per site.
- Start/restart/stop/delete process actions.
- Sync runtime state from Supervisor.
- Presets for queue worker/scheduler/reverb plus custom commands.

## Deliverables
- Process model + supervisor integration service.
- Processes tab with status and actions.
- Health/status refresh behavior.

## Tests
- Process create/update/delete.
- Runtime action success/failure.
- Unauthorized action coverage.

## Completion Criteria
- [ ] Processes are fully manageable from UI
- [ ] Runtime state reflects Supervisor accurately
- [ ] Preset creation flows work
