# Status: [ ] Not Complete
# Title: Frontend Foundation & UI Components

## Description
Scaffold the panel's frontend using modern technologies and the DaisyUI component library. This includes setting up the core architecture with Inertia.js, Vue.js 3, and Tailwind CSS v4.

## Requirements
- Set up Vue 3 with Inertia.js.
- Install and configure Tailwind CSS v4 and DaisyUI v5+.
- Use DaisyUI Blueprint MCP
- Configure `tailwind.config.js` with the following DaisyUI themes:
  - `light`, `dark`, `corporate`, `business`.
- Establish the base layout (`AppLayout.vue`):
  - **Sidebar**: Persistent navigation (Dashboard, Servers, Sites, Databases, SSH Keys).
  - **Navbar**: Search bar, User profile dropdown, Theme switcher.
  - **Main Content**: Responsive container with glassmorphism styling.
- Implement a package manager selection UI (Bun vs bun) in user settings.
- Create reusable UI components for:
  - Service status indicators (`stat`, `badge`).
  - SELinux violation alerts (`alert-error`).
  - Resource usage visualizations (`progress`).
- Configure light/dark mode support via DaisyUI's `theme-controller`.

## Implementation Details
### Layout Structure:
- `resources/js/Layouts/AppLayout.vue`
- `resources/js/Components/Sidebar.vue`
- `resources/js/Components/Navbar.vue`

## Configuration
- Vue 3, Inertia.js
- Tailwind CSS v4, DaisyUI v5
- Bun v1.3.6+

## Audit & Logging
- Browser log monitoring for JS errors.
- Bundle size monitoring.

## Testing
- Verify HMR (Hot Module Replacement) works with Bun.
- Accessibility audit of DaisyUI components.
- Verify theme switching persists correctly.

## Completion Criteria
- [ ] Frontend scaffolding complete
- [ ] DaisyUI theme and base layout operational
- [ ] Responsive design verified across breakpoints
- [ ] Package manager preference system implemented in UI
