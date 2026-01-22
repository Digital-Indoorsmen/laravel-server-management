#!/bin/bash

# AlmaLinux & Rocky Linux Base Setup Script - Core & Hardening
# Log file: /var/log/panel-setup.log

set -euo pipefail

LOG_FILE="/var/log/panel-setup.log"

# Function to log messages
log() {
    local message="$1"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $message"
}

# Redirect all output to log file and keep it on stdout
exec > >(tee -a "$LOG_FILE") 2>&1

# Ensure script is run as root
if [[ $EUID -ne 0 ]]; then
   log "Error: This script must be run as root."
   exit 1
fi

log "Starting core setup script..."

# 1. OS Detection
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=$ID
    VER=$VERSION_ID
else
    log "Error: /etc/os-release not found. Unsupported OS."
    exit 1
fi

# Normalize OS ID for comparison
OS_LOWER=$(echo "$OS" | tr '[:upper:]' '[:lower:]')

if [[ "$OS_LOWER" != "almalinux" && "$OS_LOWER" != "rocky" ]]; then
    log "Error: Unsupported OS '$OS'. This script supports AlmaLinux and Rocky Linux only."
    exit 1
fi

log "Detected OS: $OS (Version $VER)"

# 2. Idempotent Helper
is_installed() {
    rpm -q "$1" &> /dev/null
}

# 3. Repository Configuration
log "Configuring repositories..."

if ! is_installed "epel-release"; then
    log "Installing epel-release..."
    dnf install -y epel-release
else
    log "epel-release is already installed."
fi

# Enable PowerTools (EL 8) or CRB (EL 9)
if [[ "${VER%%.*}" == "8" ]]; then
    log "Enabling PowerTools for EL 8..."
    dnf config-manager --set-enabled powertools || true
elif [[ "${VER%%.*}" == "9" ]]; then
    log "Enabling CRB for EL 9..."
    dnf config-manager --set-enabled crb || true
fi

# 4. Security Hardening - Sysctl
log "Applying kernel hardening settings..."
CAT_SYSCTL=$(cat <<EOF
net.ipv4.conf.all.rp_filter = 1
net.ipv4.conf.default.rp_filter = 1
net.ipv4.icmp_echo_ignore_broadcasts = 1
fs.file-max = 2097152
EOF
)

if [[ ! -f /etc/sysctl.d/99-panel-hardening.conf ]] || [[ "$(cat /etc/sysctl.d/99-panel-hardening.conf)" != "$CAT_SYSCTL" ]]; then
    log "Writing /etc/sysctl.d/99-panel-hardening.conf..."
    echo "$CAT_SYSCTL" > /etc/sysctl.d/99-panel-hardening.conf
    sysctl --system
else
    log "Sysctl hardening already applied."
fi

# 5. Security Hardening - SSH
log "Hardening SSH configuration..."
SSH_CONFIG="/etc/ssh/sshd_config"

# Backup original if not already backed up
if [[ ! -f "${SSH_CONFIG}.bak" ]]; then
    cp "$SSH_CONFIG" "${SSH_CONFIG}.bak"
fi

# Function to update sshd_config
update_ssh_config() {
    local key="$1"
    local value="$2"
    if grep -q "^#\?${key}" "$SSH_CONFIG"; then
        sed -i "s|^#\?${key}.*|${key} ${value}|" "$SSH_CONFIG"
    else
        echo "${key} ${value}" >> "$SSH_CONFIG"
    fi
}

update_ssh_config "PermitRootLogin" "yes"
update_ssh_config "PasswordAuthentication" "yes"

if ! sshd -t; then
    log "Error: SSH configuration validation failed. Restoring backup."
    cp "${SSH_CONFIG}.bak" "$SSH_CONFIG"
    exit 1
else
    log "Restarting SSH service..."
    systemctl restart sshd
fi

# 6. User Management
log "Creating 'panel' deployment user and group..."
if ! getent group panel > /dev/null; then
    groupadd panel
    log "Group 'panel' created."
else
    log "Group 'panel' already exists."
fi

if ! id -u panel > /dev/null 2>&1; then
    useradd -g panel -m -s /bin/bash panel
    log "User 'panel' created."
else
    log "User 'panel' already exists."
fi

# 7. Install Base Utilities
log "Installing base utilities..."
BASE_UTILS=("curl" "wget" "git" "vim-enhanced" "unzip" "tar" "nano" "mlocate")

for util in "${BASE_UTILS[@]}"; do
    # Map vim-enhanced to vim check
    check_name="$util"
    if [[ "$util" == "vim-enhanced" ]]; then check_name="vim"; fi
    # Map mlocate to locate check
    if [[ "$util" == "mlocate" ]]; then check_name="locate"; fi
    
    if ! is_installed "$check_name"; then
        log "Installing $util..."
        dnf install -y "$util"
    else
        log "$util is already installed."
    fi
done

# Initialize mlocate database
if is_installed "locate"; then
    log "Updating locate database..."
    updatedb
fi

# 8. Set Default Editor
log "Setting nano as the default editor..."
cat <<EOF > /etc/profile.d/editor.sh
export EDITOR=nano
export VISUAL=nano
EOF
chmod +x /etc/profile.d/editor.sh

log "Core setup completed successfully."
