# Status: [ ] Not Complete
# Title: Setup Script Templating & Serving System

## Description
Implement the mechanism within the Laravel panel to generate and serve the `setup.sh` script dynamically for new servers. This allows for per-server customization and secure delivery.

## Requirements
- Create a `SetupScriptController` with a unique, one-time-use URL or token-protected endpoint.
- Store the setup script as a Blade template in `resources/views/scripts/setup.blade.php`.
- Logic to inject variables into the template:
  - Panel IP/Domain (for callback).
  - Initial SSH Public Key.
  - Server-specific UUID.
- Implement a "Copy Setup Command" UI on the Server Creation page.
  - Example: `curl -sSL https://panel.test/setup/TOKEN | bash`
- Log whenever a script is requested and by which IP.

## Implementation Details
### Blade Template Example:
```bash
#!/bin/bash
# Panel Callback URL: {{ $callbackUrl }}
# Server UUID: {{ $serverUuid }}
# ... core setup logic ...
```

## Configuration
- Laravel Blade
- Token-based URL protection

## Audit & Logging
- Log script generation time and requesting IP.
- Record "Setup Started" event in the database when the script begins execution (callback).

## Testing
- Generate a script via the panel and verify content.
- Verify the unique URL expires or is revoked after use.

## Completion Criteria
- [ ] Setup script can be dynamically generated via Blade
- [ ] Token-protected delivery system operational
- [ ] UI for copying the installation command implemented
