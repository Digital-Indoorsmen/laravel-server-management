# Status: [x] Complete
# Title: AlmaLinux & Rocky Linux Base Setup Script - Core & Hardening

## Description
Develop the core `setup.sh` script to automate the initial configuration and security hardening of AlmaLinux 8/9 and Rocky Linux 8/9 nodes. This script is the entry point for all managed servers.

## Requirements
- [x] Implement shebang and strict error handling (`set -euo pipefail`).
- [x] Implement OS detection: must be AlmaLinux 8/9 or Rocky Linux 8/9.
- [x] **Idempotent Package Management**:
  - The script must detect if a package is already installed and skip it to save time and prevent redundant logs.
  - Implement a helper function `is_installed()` to check for binaries or packages.
- [x] Configure repositories:
  - `dnf install -y epel-release`
  - `dnf config-manager --set-enabled powertools` (EL 8) or `dnf config-manager --set-enabled crb` (EL 9).
- [x] Implement system hardening:
  - **SSH**: Enable root login with password authentication.
  - **Kernel (Sysctl)**:
    - `net.ipv4.conf.all.rp_filter = 1`
    - `net.ipv4.conf.default.rp_filter = 1`
    - `net.ipv4.icmp_echo_ignore_broadcasts = 1`
    - `fs.file-max = 2097152`
- [x] Implement comprehensive logging to `/var/log/panel-setup.log`.
- [x] Create the `panel` deployment user and group.
- [x] Install base utilities: `curl`, `wget`, `git`, `vim`, `unzip`, `tar`, `nano`, `mlocate`.

## Implementation Details
### Example OS Detection:
```bash
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=$ID
    VER=$VERSION_ID
fi

if [[ "$OS" != "almalinux" && "$OS" != "rocky" ]]; then
    echo "Unsupported OS: $OS"
    exit 1
fi
```
```bash
is_installed() {
    rpm -q "$1" &> /dev/null
}

# Usage:
if ! is_installed "git"; then
    dnf install -y git
fi
```

### Example sysctl application:
```bash
cat <<EOF > /etc/sysctl.d/99-panel-hardening.conf
net.ipv4.conf.all.rp_filter = 1
net.ipv4.conf.default.rp_filter = 1
# ... more settings ...
EOF
sysctl --system
```

## Configuration
- AlmaLinux 8/9, RockyLinux 8/9
- Bash

## Audit & Logging
- Log every step to `/var/log/panel-setup.log`.
- Log OS and version detection results.

## Testing
- Run script on a fresh AlmaLinux VM (Vagrant/Proxmox).
- Run script a second time on the same VM and verify it skips installation steps.
- Verify `sysctl` parameters are active.
- Verify `panel` user exists.

## Completion Criteria
- [x] Idempotent core script completed (skips installed packages)
- [x] Security baseline (SSH/Kernel) achieved
- [x] Repositories and base users configured
- [x] Log file contains no errors
