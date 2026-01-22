# Status: [ ] Not Complete
# Title: AlmaLinux Base Setup Script - Core & Hardening

## Description
Develop the core `setup.sh` script to automate the initial configuration and security hardening of AlmaLinux nodes.

## Requirements
- Implement shebang and strict error handling (`set -euo pipefail`).
- Implement OS detection (AlmaLinux 8/9).
- Implement system hardening:
  - Enable root password login (SSH keys as well).
  - Configure kernel security parameters in `sysctl.conf`.
- Configure repositories (EPEL, PowerTools/CRB).
- Implement comprehensive logging to `/var/log/panel-setup.log`.
- Create the `panel` deployment user and group.

## Configuration
- AlmaLinux 8/9
- Bash

## Audit & Logging
- Setup process logging.
- Checksum verification for external tools.
- Comprehensive logging in `/var/log/panel-setup.log`.
- Installation summary output.

## Testing
- Run script on a fresh VM and verify hardening status (`ssh -v`, `sysctl -a`).
- Verify log file creation and content.

## Completion Criteria
- [ ] Idempotent core script completed
- [ ] Security baseline achieved
- [ ] Repositories and base users configured
