# Status: [ ] Not Complete
# Title: Forge-Style Site Management Parity

## Description
Implement a cohesive "Site Management" experience that mirrors Laravel Forge’s day-to-day site operations for a single site: Overview, Deployments, Processes, Commands, Network, Observe, Domains, and Settings. This task unifies several existing partial tasks into a single, user-facing parity milestone.

## Requirements
- **Site Workspace Navigation**
  - Add a site-level navigation shell with tabs: `Overview`, `Deployments`, `Processes`, `Commands`, `Network`, `Observe`, `Domains`, `Settings`.
  - Navigation must be scoped to a selected site and preserve context (server, site id, selected tab).
- **Overview (Operational Snapshot)**
  - Show recent deployments with commit hash, branch, timestamp, actor, and status.
  - Show high-value runtime details (server id, site id, app type/framework, PHP version, public IP, created date).
  - Show quick status toggles/indicators for common Laravel runtime concerns (scheduler, queue, maintenance mode, etc.) with clear read-only vs actionable states.
- **Deployments**
  - Persist deployment history and status transitions (`queued`, `running`, `succeeded`, `failed`).
  - Trigger deployment manually from UI and CLI.
  - Store stdout/stderr logs per deployment with pagination/filtering.
- **Processes**
  - Manage background processes per site (create, list, start, restart, stop, delete).
  - Integrate with Supervisor on host and keep DB state in sync.
  - Support Laravel-specific presets (queue worker, reverb, scheduler runner) plus custom commands.
- **Commands**
  - Execute ad-hoc site commands as the site system user.
  - Store command history with status, duration, actor, and output.
  - Include safety guardrails (timeout, output truncation, forbidden patterns policy).
- **Network**
  - Site-level redirect rules management (create/update/delete).
  - Security-level settings exposed in a controlled way (headers, force-https, optional protections) with clear web-server compatibility notes.
- **Observe**
  - Heartbeats (create endpoint, expected frequency, status, alert state).
  - Site-scoped activity and log timeline for deploy/process/command/network/domain events.
- **Domains**
  - Add/remove custom domains and aliases.
  - Set primary domain and optional wildcard subdomain policy.
  - Certificate management workflow (request/renew/revoke/status).
- **Settings**
  - Site general settings: framework/app type, PHP version, tags, notes, directory/root/public paths, deployment branch/script defaults.
  - Settings changes must trigger required downstream reconfiguration safely.

## Data Model & Backend
- Add or complete models/tables for:
  - deployments
  - process definitions + process run state
  - command history
  - redirects
  - heartbeats
  - site activities/events
  - domain + certificate records (if not already complete)
- Ensure all mutations are audited into site activity stream.
- Ensure server execution paths use existing connection/provisioning services and run as least-privileged site user.

## UI/UX
- Build a dedicated site management page group under Inertia/Vue with DaisyUI styling consistent with current app.
- Every visible action must map to a real route and backend behavior (no placeholder UI actions).
- Add empty states that clearly explain next steps for each tab.

## CLI Parity
- Extend `larapanel` with site-scoped commands aligned to this module:
  - `larapanel site:deploy {site}`
  - `larapanel site:processes {site}`
  - `larapanel site:command {site} -- <cmd>`
  - `larapanel site:domains {site}`

## Security
- Enforce auth + authorization for every site action.
- Redact secrets in logs/outputs.
- Protect execution endpoints against command injection and unsafe argument interpolation.

## Testing
- Add Pest feature tests per tab/action with both success and failure paths.
- Add permission tests (guest, authenticated non-owner/non-admin if roles added, admin).
- Add regression tests ensuring no placeholder links/actions remain in site workspace.

## Completion Criteria
- [ ] Site workspace has all Forge-style tabs with real routes and real data
- [ ] Deployments are triggerable and historical logs are viewable
- [ ] Processes can be created/managed and reflect Supervisor state
- [ ] Commands can be executed with persisted history and logs
- [ ] Network redirects and security options are manageable
- [ ] Observe section supports heartbeats + activity timeline
- [ ] Domains and certificates are fully manageable
- [ ] Site settings are editable with safe downstream reconfiguration
- [ ] CLI commands exist for core site-management flows
- [ ] Comprehensive Pest coverage added for all above
