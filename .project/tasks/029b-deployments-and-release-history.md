# Status: [ ] Not Complete
# Title: Deployments and Release History

## Parent
- Depends on: `029a-site-workspace-foundation.md`

## Goal
Implement deployment execution and historical visibility similar to Forge Deployments.

## Scope
- Add deployments data model/state machine (`queued`, `running`, `succeeded`, `failed`).
- Manual deploy trigger from UI and CLI.
- Persist branch/commit/actor/timestamps/output logs.
- Deployment list with pagination + detail view.

## Deliverables
- Deployment trigger endpoint + queued execution.
- Deployment log storage and retrieval.
- Deployments tab UI with status badges and history table.

## Tests
- Successful deployment path.
- Failed deployment path with captured stderr.
- Authorization and validation tests.

## Completion Criteria
- [ ] Deployments can be triggered and tracked end-to-end
- [ ] Deployment history and logs are visible in UI
- [ ] CLI deploy command works for a site
