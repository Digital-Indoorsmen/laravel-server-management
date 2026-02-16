# Status: [ ] Not Complete
# Title: Commands Execution and History

## Parent
- Depends on: `029a-site-workspace-foundation.md`

## Goal
Implement Forge-like command runner for site-scoped operational commands.

## Scope
- Execute ad-hoc commands as site system user.
- Persist command history, status, duration, actor, output.
- Add safety controls: timeout, output limit, restricted patterns.

## Deliverables
- Command execution endpoint + queue/process handler.
- Commands tab with run form + recent history table.
- Command detail output view.

## Tests
- Command run success/failure.
- Timeout handling.
- Forbidden command pattern rejection.

## Completion Criteria
- [ ] Commands can be executed and reviewed safely
- [ ] History is persistent and queryable
- [ ] Security guardrails enforced
