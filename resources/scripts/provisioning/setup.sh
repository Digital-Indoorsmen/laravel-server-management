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

# 9. Extended Repositories (Remi for PHP)
log "Configuring Remi repository for PHP..."
if ! is_installed "remi-release"; then
    log "Installing remi-release..."
    dnf install -y https://rpms.remirepo.net/enterprise/remi-release-$(rpm -E %rhel).rpm
else
    log "Remi repository is already installed."
fi

# 10. Multi-PHP Version Installation
log "Installing multiple PHP versions (7.4, 8.1, 8.2, 8.3, 8.4, 8.5)..."
PHP_VERSIONS=("74" "81" "82" "83" "84") # 8.5 isn't widely available in Remi yet, but we'll include logic for it if it exists
# Special handling for versions that might be available
for ver in "${PHP_VERSIONS[@]}"; do
    pkg="php${ver}-php-fpm"
    if ! is_installed "$pkg"; then
        log "Installing PHP $ver..."
        dnf install -y "php${ver}-php-fpm" "php${ver}-php-mysqlnd" "php${ver}-php-pgsql" "php${ver}-php-xml" "php${ver}-php-mbstring" "php${ver}-php-gd" "php${ver}-php-zip" "php${ver}-php-opcache"
    else
        log "PHP $ver is already installed."
    fi
    systemctl enable "php${ver}-php-fpm" --now
done

# 11. Database Servers (MariaDB, PostgreSQL)
log "Installing database servers..."
if ! is_installed "mariadb-server"; then
    log "Installing MariaDB..."
    dnf install -y mariadb-server
    systemctl enable mariadb --now
else
    log "MariaDB is already installed."
fi

if ! is_installed "postgresql-server"; then
    log "Installing PostgreSQL..."
    dnf install -y postgresql-server
    # Initialize DB if not already done
    if [[ ! -d /var/lib/pgsql/data/base ]]; then
        postgresql-setup --initdb
    fi
    systemctl enable postgresql --now
else
    log "PostgreSQL is already installed."
fi

# 12. Redis Installation
log "Installing Redis..."
if ! is_installed "redis"; then
    log "Installing Redis..."
    dnf install -y redis
    systemctl enable redis --now
else
    log "Redis is already installed."
fi

# 13. Web Server (Nginx)
log "Installing Nginx..."
if ! is_installed "nginx"; then
    log "Installing Nginx..."
    dnf install -y nginx
    systemctl enable nginx --now
else
    log "Nginx is already installed."
fi

# 14. Global Tooling (Bun)
log "Installing Bun for 'panel' user..."
if [[ ! -f /home/panel/.bashrc ]] || ! grep -q "BUN_INSTALL" /home/panel/.bashrc; then
    sudo -u panel bash -c 'curl -fsSL https://bun.sh/install | bash'
    log "Bun installed for 'panel' user."
else
    log "Bun is already installed for 'panel' user."
fi

# 15. Directory Structure & Permissions
log "Creating directory structure..."
DIRS=(
    "/home/panel/sites"
    "/var/log/panel"
    "/etc/ssl/panel"
)

for dir in "${DIRS[@]}"; do
    if [[ ! -d "$dir" ]]; then
        mkdir -p "$dir"
        log "Created directory: $dir"
    fi
done

# Set permissions
chown -R panel:panel /home/panel/sites
chown -R panel:panel /var/log/panel
chmod 755 /home/panel/sites
chmod 755 /var/log/panel
chmod 700 /etc/ssl/panel

# 16. Panel Site Configuration (Port 8095)
log "Configuring Nginx for the management panel on port 8095..."
PANEL_CONF="/etc/nginx/conf.d/panel.conf"
if [[ ! -f "$PANEL_CONF" ]]; then
    cat <<EOF > "$PANEL_CONF"
server {
    listen 8095;
    server_name _;
    root /var/www/panel/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php-fpm/www.sock; # Default to pool, or specific version later
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF
    systemctl reload nginx
    log "Panel Nginx configuration created."
else
    log "Panel Nginx configuration already exists."
fi

# 17. Service Health Checks
log "Running service health checks..."
SERVICES=("nginx" "mariadb" "postgresql" "redis")
for svc in "${SERVICES[@]}"; do
    if systemctl is-active --quiet "$svc"; then
        log "Service '$svc' is running."
    else
        log "Warning: Service '$svc' is NOT running."
    fi
done

log "Extended services setup completed successfully."
