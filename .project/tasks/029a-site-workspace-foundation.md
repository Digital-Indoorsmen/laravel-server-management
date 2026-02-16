# Status: [x] Complete
# Title: Site Workspace Foundation (Tabs, Routing, Shared Layout)

## Parent
- Depends on: `029-forge-site-management-parity.md`

## Goal
Create the site-scoped workspace shell and routing foundation so every Forge-style tab has a real destination.

## Scope
- Add site-scoped route group and consistent URL pattern for tabs.
- Build shared Inertia layout/component for site workspace navigation:
  - Overview, Deployments, Processes, Commands, Network, Observe, Domains, Settings.
- Ensure each tab resolves to a real controller/page (even if initially empty-state only).
- Eliminate placeholder links/actions in site workspace nav.

## Deliverables
- Real route + controller + page for each tab.
- Shared site workspace header with site/server context.
- Empty states with actionable next steps.

## Tests
- Feature tests verifying each tab route is reachable for authenticated users.
- Guest access redirects to login.

## Completion Criteria
- [x] All site workspace tabs are routable and render real pages
- [x] No `#` placeholders in site workspace nav
- [x] Baseline tests for route reachability pass
