# Status: [x] Complete
# Title: Setup Script Automated Verification Suite

## Description
Create a comprehensive test suite to automatically verify the correctness and idempotency of the `setup.sh` script on fresh AlmaLinux nodes.

## Requirements
- Develop a Python or Bash-based test runner that:
  - Provisions a fresh VM (e.g., via Vagrant, Docker with Systemd, or Cloud API).
  - Executes the setup script.
  - Verifies system state (packages installed, services running, users created).
- Assertions to include:
  - `command -v nginx` returns 0.
  - `systemctl is-active mariadb` returns "active".
  - `/home/panel` has correct ownership.
  - No critical errors in `/var/log/panel-setup.log`.
- Test idempotency:
  - Run the script a second time and verify it completes without errors or redundant modifications.

## Configuration
- Vagrant or Docker-with-systemd
- Python `pytest` or simple Bash assertions

## Audit & Logging
- Log test results (Pass/Fail per assertion).
- Capture VM state snapshots for failed tests if possible.

## Testing
- Run the verification suite against AlmaLinux 8/9.
- Run the verification suite against Rocky Linux 8/9.

## Completion Criteria
- [x] Automated verification script created
- [x] Idempotency confirmed on all supported OS versions (Alma/Rocky)
- [x] Test summary report generated
