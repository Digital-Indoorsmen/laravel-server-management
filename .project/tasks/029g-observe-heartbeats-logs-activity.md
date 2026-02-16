# Status: [ ] Not Complete
# Title: Observe (Heartbeats, Logs, Activity)

## Parent
- Depends on: `029a-site-workspace-foundation.md`

## Goal
Provide Forge-like observability surfaces at site scope.

## Scope
- Heartbeats: create/manage heartbeat checks and status.
- Site logs viewer for key operational streams.
- Activity timeline for deploy/process/command/network/domain events.

## Deliverables
- Heartbeat model/endpoints and expected schedule handling.
- Observe tab with Heartbeats, Logs, Activity sub-views.

## Tests
- Heartbeat create/update/status transitions.
- Activity event persistence assertions.
- Access control tests for logs/activity endpoints.

## Completion Criteria
- [ ] Heartbeats are functional with status visibility
- [ ] Activity timeline is complete for major site actions
- [ ] Observe views are usable without placeholder states
