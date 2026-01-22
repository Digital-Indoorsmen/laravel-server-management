# Status: [ ] Not Complete
# Title: Frontend Foundation & UI Components

## Description
Scaffold the panel's frontend using modern technologies and the DaisyUI component library.

## Requirements
- Set up Vue 3 with Inertia.js.
- Install and configure Tailwind CSS v4 and DaisyUI v5+.
- Establish the base layout (Sidebar, Navbar, Main Content area) using DaisyUI semantic classes.
- Implement a package manager selection UI (Bun vs npm) based on user preferences.
- Create reusable UI components for:
  - Server status indicators (`stat`, `badge`).
  - SELinux violation alerts (`alert-error`).
  - Resource usage visualizations (`progress`).
- Configure light/dark mode support via DaisyUI themes.

## Configuration
- Vue 3, Inertia.js
- Tailwind CSS v4, DaisyUI v5
- Bun v1.3.6+

## Audit & Logging
- Browser log monitoring for JS errors.

## Testing
- Verify HMR (Hot Module Replacement) works with Bun.
- Accessibility audit of DaisyUI components.

## Completion Criteria
- [ ] Frontend scaffolding complete
- [ ] DaisyUI theme and base layout operational
- [ ] Package manager preference system implemented
