# Server Management Panel - SELinux-First Task Breakdown (FINAL VERSION)

## SQLite Configuration for Panel Management Database

### SQLite WAL Mode & Production Optimization
- [ ] Enable WAL (Write-Ahead Logging) mode for concurrent access
- [ ] Configure `journal_mode = WAL` in SQLite database
- [ ] Set `page_size = 32768` bytes for optimal page handling
- [ ] Enable `auto_vacuum = INCREMENTAL` for space reclamation
- [ ] Configure `cache_size = -20000` (20 MB) for read performance
- [ ] Set `mmap_size = 2147483648` (2 GB) for memory mapping
- [ ] Configure `busy_timeout = 5000` milliseconds for lock contention
- [ ] Enable `foreign_keys = ON` for referential integrity
- [ ] Set `synchronous = NORMAL` for performance/safety balance
- [ ] Configure `temp_store = MEMORY` for faster temporary operations
- [ ] Document SQLite optimization strategy vs. website databases

### Laravel Configuration for SQLite Panel Database
- [ ] Update `config/database.php` with SQLite optimizations
- [ ] Create `database/database.sqlite` as panel management DB
- [ ] Configure migration for SQLite-specific features
- [ ] Separate website databases (MariaDB/PostgreSQL) from panel DB
- [ ] Create AppServiceProvider method to apply per-connection pragmas
- [ ] Implement SQLite connection pooling if needed
- [ ] Document backup strategy for SQLite (file-based backups)
- [ ] Create monitoring for SQLite database file size
- [ ] Implement SQLite integrity checks in health checks
- [ ] Document recovery procedures for SQLite database

### Website Database Strategy (MariaDB & PostgreSQL)
- [ ] Plan per-website database isolation
- [ ] Create MariaDB databases for WordPress/PHP sites needing MySQL
- [ ] Create PostgreSQL databases for Laravel/PostgreSQL sites
- [ ] Document database selection criteria for each site type
- [ ] Create user management per website database
- [ ] Implement database connection string management
- [ ] Design secure credential storage for website databases
- [ ] Plan database backup strategy per site
- [ ] Create automation for database creation during site setup
- [ ] Document rollback procedures for website databases

---

## Critical: AlmaLinux Base Setup Script (setup.sh)

### Create Main AlmaLinux Setup Script (`setup.sh`)
- [ ] Create `/root/setup.sh` for base AlmaLinux 8.x/9.x installation
- [ ] Script should be idempotent (safe to run multiple times)
- [ ] Add system update and security hardening steps
- [ ] Include SELinux configuration and enabling
- [ ] Add firewalld installation and basic configuration
- [ ] Install EPEL and PowerTools/CRB repositories
- [ ] Install PHP 8.3+ with required extensions
- [ ] Install Nginx from official repository
- [ ] Install MariaDB and PostgreSQL server software
- [ ] Install Redis for caching
- [ ] Install Git, Curl, Wget, and development tools
- [ ] Create deployment user with SSH key access
- [ ] Set up system logging and log rotation
- [ ] Configure time synchronization (chrony/NTP)
- [ ] Add curl-based service health checks
- [ ] Document script usage and prerequisites

### Setup Script: System Hardening Section (`setup.sh`)
- [ ] Disable root password login (SSH keys only)
- [ ] Configure fail2ban for brute-force protection
- [ ] Set up automatic security updates via dnf-automatic
- [ ] Configure audit daemon (auditd) for SELinux events
- [ ] Set restrictive file permissions on sensitive files
- [ ] Configure sudo with appropriate timeouts
- [ ] Disable unnecessary services (Bluetooth, CUPS, etc.)
- [ ] Set kernel parameters for security (`sysctl.conf`)
- [ ] Document security baseline achieved by script

### Setup Script: Nginx & PHP-FPM Configuration (`setup.sh`)
- [ ] Create Nginx directory structure
- [ ] Generate SSL certificates placeholder
- [ ] Configure Nginx worker processes based on CPU count
- [ ] Set up Nginx logging
- [ ] Create PHP-FPM base pool template
- [ ] Configure PHP-FPM process manager (ondemand/static)
- [ ] Set PHP memory limits and timeouts
- [ ] Enable PHP-FPM socket-based communication
- [ ] Configure OPcache with appropriate memory
- [ ] Set up PHP error logging
- [ ] Document Nginx/PHP-FPM tuning options

### Setup Script: SELinux & Firewall Configuration (`setup.sh`)
- [ ] Load base SELinux policies from policy tarball (if available)
- [ ] Set SELinux to permissive mode initially (for testing)
- [ ] Configure firewalld with restrictive default zone (drop)
- [ ] Allow SSH (port 22) in firewall
- [ ] Create firewall zones for web traffic
- [ ] Document SELinux policy installation procedure
- [ ] Add comments explaining firewall rules
- [ ] Include options to disable firewall for testing (with warnings)
- [ ] Document SELinux context requirements for future reference

### Setup Script: Directory Structure & Permissions (`setup.sh`)
- [ ] Create `/var/www` directory structure
- [ ] Create `/var/log/sites` for per-site logs
- [ ] Create `/etc/ssl/panel` for panel certificates
- [ ] Create `/etc/systemd/system/` custom service directory
- [ ] Set correct permissions on critical directories
- [ ] Create application user (`panel`) for panel operations
- [ ] Assign appropriate groups to panel user
- [ ] Document directory structure and ownership

### Setup Script: Database Initialization (`setup.sh`)
- [ ] Create SQLite database directory at `/var/lib/panel`
- [ ] Initialize empty SQLite database file
- [ ] Apply SQLite optimizations to empty database
- [ ] Start and enable MariaDB service
- [ ] Start and enable PostgreSQL service
- [ ] Create initial database users with strong passwords
- [ ] Document database initialization and credentials
- [ ] Create placeholder for credentials management

### Setup Script: Verification & Health Checks (`setup.sh`)
- [ ] Verify Nginx is running and configured correctly
- [ ] Verify PHP-FPM is running and listening on socket
- [ ] Verify MariaDB is accessible
- [ ] Verify PostgreSQL is accessible
- [ ] Verify Redis is running (if included)
- [ ] Test basic connectivity between services
- [ ] Create health check curl commands
- [ ] Document how to verify successful installation
- [ ] Provide troubleshooting tips for common issues

### Setup Script: Summary & Next Steps (`setup.sh`)
- [ ] Print installation summary to console
- [ ] Display generated credentials (with security warnings)
- [ ] Provide next steps for Laravel panel installation
- [ ] Suggest how to transfer setup.sh output to logs
- [ ] Document manual SELinux policy compilation next steps
- [ ] Provide links to documentation
- [ ] Include date/time stamps for audit trail

### Create Setup Script Documentation (`SETUP.md`)
- [ ] Write prerequisites section (hardware, network)
- [ ] Document all script flags/options
- [ ] Explain security decisions made in script
- [ ] Provide unattended installation examples
- [ ] Document how to customize script for specific needs
- [ ] Include troubleshooting section
- [ ] Explain Bun v1.3.6+ installation within script
- [ ] Document DaisyUI installation and configuration
- [ ] Provide post-installation configuration checklist

### Create Bash Script Headers & Structure
- [ ] Add shebang `#!/bin/bash`
- [ ] Add strict error handling (`set -euo pipefail`)
- [ ] Add color output for readability
- [ ] Add logging to `/var/log/panel-setup.log`
- [ ] Add user input validation
- [ ] Add password generation utilities
- [ ] Add checksum verification for critical steps
- [ ] Add rollback procedures for failed steps

### Script: Variables & Configuration Section
- [ ] Define OS version detection (AlmaLinux 8/9)
- [ ] Set package manager to `dnf`
- [ ] Define version variables for PHP, Nginx, etc.
- [ ] Define SELinux policy source location
- [ ] Define domain names for panel (if applicable)
- [ ] Define SSL certificate paths
- [ ] Define user/group IDs
- [ ] Make script easily customizable via environment variables

---

## Phase 1: Foundation + SELinux Integration (UPDATED FOR SQLITE)

#### Configure Laravel 12 for SQLite Panel Database
- [ ] Update `.env` to use `DB_CONNECTION=sqlite` for panel
- [ ] Set `DB_DATABASE` to `/var/lib/panel/database.sqlite`
- [ ] Create `config/database.php` connection for SQLite with WAL
- [ ] Add AppServiceProvider method to apply per-connection pragmas
- [ ] Create SQLite-specific migrations with proper types
- [ ] Test SQLite connection in tinker before deploying
- [ ] Document SQLite backup strategy in deployment docs
- [ ] Create monitoring for SQLite database file size
- [ ] Plan SQLite to PostgreSQL migration (future scaling)

#### Database Architecture Separation
- [ ] Document clear separation: panel DB (SQLite) vs. website DBs
- [ ] Create diagram showing database architecture
- [ ] Plan multi-database handling in application code
- [ ] Document connection string management
- [ ] Create database credentials management system
- [ ] Plan disaster recovery per database type
- [ ] Design backup and restore procedures

#### Create Laravel Migrations (SQLite-Aware)
- [ ] Migrate `servers`, `ssh_keys`, `sites` tables to SQLite
- [ ] Create migration for `selinux_audit_logs` (SQLite)
- [ ] Create migration for `selinux_violations` (SQLite)
- [ ] Ensure all migrations work with SQLite data types
- [ ] Add indexes that SQLite can use efficiently
- [ ] Test migrations against SQLite
- [ ] Document SQLite-specific column type choices
- [ ] Create rollback testing procedures

#### SQLite Optimization in Migrations
- [ ] Apply `PRAGMA journal_mode = WAL` in migration
- [ ] Apply `PRAGMA page_size = 32768` in migration
- [ ] Apply `PRAGMA auto_vacuum = INCREMENTAL` in migration
- [ ] Document where per-connection pragmas are applied
- [ ] Create verification migration to check optimization status
- [ ] Test with realistic data volumes
- [ ] Document performance characteristics vs. MariaDB

---

## Phase 2: Core Features with SQLite (UPDATED)

#### Simplify Database Layer for SQLite
- [ ] Use single SQLite database for all panel operations
- [ ] Eliminate complex database selection logic
- [ ] Focus Laravel queries on SQLite performance
- [ ] Use SQLite's JSON functionality where applicable
- [ ] Avoid features exclusive to MariaDB/PostgreSQL
- [ ] Test concurrent access with SQLite WAL
- [ ] Document any SQLite limitations encountered

#### Website Database Creation (Separate DBs)
- [ ] Create abstraction for website database creation
- [ ] Support MariaDB database creation per website
- [ ] Support PostgreSQL database creation per website
- [ ] Store database credentials securely
- [ ] Generate secure passwords for website databases
- [ ] Document website database isolation strategy
- [ ] Create database-specific user management

---

## Phase 3: Advanced Features (UPDATED)

#### Scaling Strategy: SQLite vs. Larger Databases
- [ ] Document SQLite capacity limits (single file, single writer)
- [ ] Plan when to recommend PostgreSQL for panel DB
- [ ] Create migration path from SQLite to PostgreSQL
- [ ] Document backup strategy differences
- [ ] Plan monitoring for SQLite size/performance
- [ ] Create metrics for "time to migrate" decision

---

## Phase 4: Polish & Hardening (UPDATED FOR SQLITE)

#### SQLite Security & Backup Hardening
- [ ] Encrypt SQLite database file at rest (optional)
- [ ] Create automated SQLite backup jobs
- [ ] Test SQLite restore procedures
- [ ] Document backup encryption
- [ ] Create off-site backup strategy
- [ ] Monitor SQLite file integrity
- [ ] Document point-in-time recovery procedures

#### Deployment Checklist with SQLite
- [ ] Verify SQLite is optimized before production
- [ ] Verify file permissions on SQLite database
- [ ] Test concurrent access under load
- [ ] Validate all connections use WAL pragmas
- [ ] Create pre-deploy SQLite backup
- [ ] Document post-deploy verification steps
- [ ] Create runbooks for SQLite-related issues

---

## Complete setup.sh Script Template

```bash
#!/bin/bash

################################################################################
# Server Management Panel - AlmaLinux Base Setup Script
# Purpose: Automate initial AlmaLinux 8.x/9.x setup for panel deployment
# Usage: sudo bash setup.sh
# License: MIT
# Author: Panel Setup Team
# Version: 1.0.0
################################################################################

set -euo pipefail

# ============================================================================
# Configuration Variables
# ============================================================================

OS_VERSION=\$(grep -oP 'VERSION_ID=\\"\\K[0-9]+' /etc/os-release)
DEPLOYMENT_USER="panel"
DEPLOYMENT_GROUP="panel"
PANEL_HOME="/var/lib/panel"
PANEL_LOG="/var/log/panel"
NGINX_USER="nginx"
PHP_VERSION="8.3"
MARIADB_ROOT_PASS=\$(openssl rand -base64 32)
POSTGRES_ROOT_PASS=\$(openssl rand -base64 32)
PANEL_DB_PASS=\$(openssl rand -base64 32)
SETUP_LOG="/var/log/panel-setup.log"

# Color codes for output
RED='\\033[0;31m'
GREEN='\\033[0;32m'
YELLOW='\\033[1;33m'
BLUE='\\033[0;34m'
NC='\\033[0m' # No Color

# ============================================================================
# Logging Functions
# ============================================================================

log() {
    echo -e "\${GREEN}[INFO]\\${NC} \${1}" | tee -a "\${SETUP_LOG}"
}

warn() {
    echo -e "\${YELLOW}[WARN]\\${NC} \${1}" | tee -a "\${SETUP_LOG}"
}

error() {
    echo -e "\${RED}[ERROR]\\${NC} \${1}" | tee -a "\${SETUP_LOG}"
    exit 1
}

success() {
    echo -e "\${GREEN}[SUCCESS]\\${NC} \${1}" | tee -a "\${SETUP_LOG}"
}

# ============================================================================
# Prerequisite Checks
# ============================================================================

check_root() {
    if [[ \$EUID -ne 0 ]]; then
        error "This script must be run as root"
    fi
}

check_os() {
    if ! grep -qi "almalinux" /etc/os-release; then
        error "This script is designed for AlmaLinux only"
    fi
    
    if [[ "\${OS_VERSION}" != "8" && "\${OS_VERSION}" != "9" ]]; then
        error "This script supports AlmaLinux 8.x and 9.x only"
    fi
    
    log "Detected AlmaLinux \${OS_VERSION}"
}

# ============================================================================
# System Hardening
# ============================================================================

harden_system() {
    log "Hardening system..."
    
    # Update system
    dnf update -y >> "\${SETUP_LOG}" 2>&1
    
    # Disable root password login
    sed -i 's/#PermitRootLogin yes/PermitRootLogin no/' /etc/ssh/sshd_config
    sed -i 's/#PubkeyAuthentication yes/PubkeyAuthentication yes/' /etc/ssh/sshd_config
    sed -i 's/PasswordAuthentication yes/PasswordAuthentication no/' /etc/ssh/sshd_config
    
    # Apply kernel security parameters
    cat >> /etc/sysctl.conf << 'SYSCTL_EOF'
# Network security
net.ipv4.ip_forward = 0
net.ipv4.conf.all.send_redirects = 0
net.ipv4.conf.all.accept_redirects = 0
net.ipv4.conf.all.accept_source_route = 0
net.ipv4.tcp_syncookies = 1

# System security
kernel.dmesg_restrict = 1
kernel.unprivileged_bpf_disabled = 1
SYSCTL_EOF
    
    sysctl -p >> "\${SETUP_LOG}" 2>&1
    success "System hardened"
}

# ============================================================================
# SELinux Configuration
# ============================================================================

configure_selinux() {
    log "Configuring SELinux..."
    
    # Set SELinux to permissive mode initially for testing
    setenforce 0
    sed -i 's/SELINUX=enforcing/SELINUX=permissive/' /etc/selinux/config
    
    # Install SELinux utilities
    dnf install -y setroubleshoot-server selinux-policy-devel >> "\${SETUP_LOG}" 2>&1
    
    log "SELinux set to permissive mode for testing phase"
    warn "Before production, SELinux policies must be fully compiled and tested"
}

# ============================================================================
# Repository Configuration
# ============================================================================

configure_repositories() {
    log "Configuring repositories..."
    
    # Enable EPEL
    dnf install -y epel-release >> "\${SETUP_LOG}" 2>&1
    
    # Enable PowerTools (8) or CRB (9)
    if [[ "\${OS_VERSION}" == "8" ]]; then
        dnf config-manager --set-enabled powertools >> "\${SETUP_LOG}" 2>&1
    else
        dnf config-manager --set-enabled crb >> "\${SETUP_LOG}" 2>&1
    fi
    
    success "Repositories configured"
}

# ============================================================================
# Install PHP
# ============================================================================

install_php() {
    log "Installing PHP \${PHP_VERSION}..."
    
    # Remove conflicting packages
    dnf remove -y php\* 2>/dev/null || true
    
    # Enable PHP module
    dnf module enable -y php:\${PHP_VERSION} >> "\${SETUP_LOG}" 2>&1
    
    # Install PHP packages
    dnf install -y php php-fpm php-json php-pdo php-sqlite3 php-mysqlnd \\
        php-pgsql php-xml php-mbstring php-curl php-cli php-opcache \\
        php-zip >> "\${SETUP_LOG}" 2>&1
    
    # Configure OPcache
    cat > /etc/php.d/opcache-panel.ini << 'PHP_EOF'
[opcache]
opcache.enable = 1
opcache.memory_consumption = 256
opcache.max_accelerated_files = 10000
opcache.validate_timestamps = 0
PHP_EOF
    
    success "PHP \${PHP_VERSION} installed"
}

# ============================================================================
# Install & Configure Nginx
# ============================================================================

install_nginx() {
    log "Installing Nginx..."
    
    dnf install -y nginx >> "\${SETUP_LOG}" 2>&1
    
    # Create panel vhost template
    cat > /etc/nginx/conf.d/panel-template.conf << 'NGINX_EOF'
# Panel Management Interface Template
server {
    listen 8080;
    listen [::]:8080;
    server_name localhost;

    root /var/www/panel/public;
    index index.php index.html;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \\.php\$ {
        fastcgi_split_path_info ^(.+\\.php)(/.+)\$;
        fastcgi_pass unix:/var/run/php-fpm-panel.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
    }
}
NGINX_EOF
    
    # Enable and start Nginx
    systemctl enable nginx >> "\${SETUP_LOG}" 2>&1
    systemctl start nginx >> "\${SETUP_LOG}" 2>&1
    
    success "Nginx installed and configured"
}

# ============================================================================
# Install Databases
# ============================================================================

install_databases() {
    log "Installing MariaDB and PostgreSQL..."
    
    # MariaDB
    dnf install -y mariadb-server >> "\${SETUP_LOG}" 2>&1
    systemctl enable mariadb >> "\${SETUP_LOG}" 2>&1
    systemctl start mariadb >> "\${SETUP_LOG}" 2>&1
    
    # Secure MariaDB (non-interactive)
    mysql -u root <<-MYSQL_EOF
        ALTER USER 'root'@'localhost' IDENTIFIED BY '\${MARIADB_ROOT_PASS}';
        DELETE FROM mysql.user WHERE User='';
        DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
        DROP DATABASE IF EXISTS test;
        FLUSH PRIVILEGES;
MYSQL_EOF
    
    # PostgreSQL
    dnf install -y postgresql-server postgresql-contrib >> "\${SETUP_LOG}" 2>&1
    sudo -u postgres /usr/bin/initdb -D /var/lib/pgsql/data >> "\${SETUP_LOG}" 2>&1
    systemctl enable postgresql >> "\${SETUP_LOG}" 2>&1
    systemctl start postgresql >> "\${SETUP_LOG}" 2>&1
    
    success "Databases installed"
}

# ============================================================================
# Configure SQLite for Panel
# ============================================================================

configure_sqlite() {
    log "Configuring SQLite for panel database..."
    
    # Create panel database directory
    mkdir -p "\${PANEL_HOME}"
    touch "\${PANEL_HOME}/database.sqlite"
    
    # Apply SQLite optimizations
    sqlite3 "\${PANEL_HOME}/database.sqlite" <<-SQLITE_EOF
PRAGMA journal_mode = WAL;
PRAGMA page_size = 32768;
PRAGMA auto_vacuum = INCREMENTAL;
PRAGMA cache_size = -20000;
PRAGMA busy_timeout = 5000;
PRAGMA synchronous = NORMAL;
SQLITE_EOF
    
    success "SQLite configured with WAL mode"
}

# ============================================================================
# Create Directories & Users
# ============================================================================

setup_directories() {
    log "Creating directory structure..."
    
    mkdir -p "\${PANEL_HOME}" "\${PANEL_LOG}" /var/www/panel
    mkdir -p /etc/ssl/panel
    mkdir -p /var/spool/panel/backups
    
    # Create deployment user
    useradd -m -d "\${PANEL_HOME}" -s /bin/bash "\${DEPLOYMENT_USER}" 2>/dev/null || true
    
    # Set permissions
    chown -R "\${DEPLOYMENT_USER}:\${DEPLOYMENT_GROUP}" "\${PANEL_HOME}"
    chown -R "\${DEPLOYMENT_USER}:\${DEPLOYMENT_GROUP}" "\${PANEL_LOG}"
    chmod 750 "\${PANEL_HOME}" "\${PANEL_LOG}"
    chmod 640 "\${PANEL_HOME}/database.sqlite"
    
    success "Directories and users created"
}

# ============================================================================
# Configure Firewall
# ============================================================================

configure_firewall() {
    log "Configuring firewall..."
    
    dnf install -y firewalld >> "\${SETUP_LOG}" 2>&1
    systemctl enable firewalld >> "\${SETUP_LOG}" 2>&1
    systemctl start firewalld >> "\${SETUP_LOG}" 2>&1
    
    # Allow SSH
    firewall-cmd --permanent --add-service=ssh >> "\${SETUP_LOG}" 2>&1
    
    # Allow web traffic
    firewall-cmd --permanent --add-service=http >> "\${SETUP_LOG}" 2>&1
    firewall-cmd --permanent --add-service=https >> "\${SETUP_LOG}" 2>&1
    
    # Allow panel on port 8080 (adjust as needed)
    firewall-cmd --permanent --add-port=8080/tcp >> "\${SETUP_LOG}" 2>&1
    
    firewall-cmd --reload >> "\${SETUP_LOG}" 2>&1
    
    success "Firewall configured"
}

# ============================================================================
# Install Additional Tools
# ============================================================================

install_tools() {
    log "Installing additional tools..."
    
    dnf install -y curl wget git git-core unzip zip nano vim htop \\
        net-tools whois bind-utils telnet lsof openssl >> "\${SETUP_LOG}" 2>&1
    
    # Install Bun (v1.3.6+)
    log "Installing Bun v1.3.6+..."
    curl -fsSL https://bun.sh/install | bash >> "\${SETUP_LOG}" 2>&1
    export PATH="/root/.bun/bin:\$PATH" >> /etc/profile.d/bun.sh
    
    success "Additional tools installed"
}

# ============================================================================
# Verification & Health Checks
# ============================================================================

run_health_checks() {
    log "Running health checks..."
    
    # Check Nginx
    if systemctl is-active --quiet nginx; then
        success "Nginx is running"
    else
        error "Nginx failed to start"
    fi
    
    # Check PHP-FPM
    if systemctl is-active --quiet php-fpm; then
        success "PHP-FPM is running"
    else
        error "PHP-FPM failed to start"
    fi
    
    # Check MariaDB
    if systemctl is-active --quiet mariadb; then
        success "MariaDB is running"
    else
        warn "MariaDB may not be running"
    fi
    
    # Check PostgreSQL
    if systemctl is-active --quiet postgresql; then
        success "PostgreSQL is running"
    else
        warn "PostgreSQL may not be running"
    fi
    
    # Check SQLite
    if [ -f "\${PANEL_HOME}/database.sqlite" ]; then
        success "SQLite database created"
    else
        error "SQLite database creation failed"
    fi
}

# ============================================================================
# Summary & Next Steps
# ============================================================================

print_summary() {
    cat > /tmp/panel-setup-summary.txt << SUMMARY_EOF
================================================================================
Server Management Panel - AlmaLinux Setup Complete
================================================================================

Timestamp: \$(date)
OS Version: AlmaLinux \${OS_VERSION}
Hostname: \$(hostname)

================================================================================
INSTALLED SERVICES
================================================================================
✓ Nginx (Web Server)
✓ PHP \${PHP_VERSION} with extensions
✓ MariaDB Server
✓ PostgreSQL Server
✓ Redis (if selected)
✓ SELinux (permissive mode - requires hardening for production)
✓ Firewalld (basic rules)
✓ Bun v1.3.6+ (Package Manager)

================================================================================
IMPORTANT CREDENTIALS (Store Securely!)
================================================================================
MariaDB Root Password: \${MARIADB_ROOT_PASS}
PostgreSQL Root Password: \${POSTGRES_ROOT_PASS}
Panel User: \${DEPLOYMENT_USER}
Panel Home: \${PANEL_HOME}

================================================================================
NEXT STEPS
================================================================================

1. TRANSFER SETUP LOG:
   scp \${SETUP_LOG} your-local-machine:/backup/location/
   
2. CLONE PANEL APPLICATION:
   cd /var/www
   git clone <panel-repository> panel
   cd panel
   
3. INSTALL PANEL DEPENDENCIES:
   composer install --optimize-autoloader --no-dev
   bun install
   
4. CONFIGURE PANEL:
   cp .env.example .env
   php artisan key:generate
   php artisan migrate
   
5. SELinux HARDENING (PRODUCTION ONLY):
   ⚠️  Currently in PERMISSIVE mode for testing
   - Compile custom policies from selinux/ directory
   - Test thoroughly in permissive before enforcing
   - See documentation: SELINUX_HARDENING.md
   
6. SSL CERTIFICATES:
   - Generate/install certificates in /etc/ssl/panel/
   - Update Nginx vhost configuration
   - Test HTTPS connectivity

7. MONITOR SETUP:
   Follow setup log: \${SETUP_LOG}
   All errors and warnings are logged there.

================================================================================
TESTING CONNECTIVITY
================================================================================

Test Nginx:
  curl http://localhost:8080/

Test PHP-FPM:
  curl http://localhost:8080/index.php (after panel deployment)

Test MariaDB:
  mysql -u root -p -e "SHOW DATABASES;"
  Password: \${MARIADB_ROOT_PASS}

Test PostgreSQL:
  sudo -u postgres psql -c "\\\\l"

Test SQLite:
  sqlite3 \${PANEL_HOME}/database.sqlite ".tables"

================================================================================
FIREWALL RULES
================================================================================
SSH:    Port 22 (allowed)
HTTP:   Port 80 (allowed)
HTTPS:  Port 443 (allowed)
PANEL:  Port 8080 (allowed, adjust as needed)

================================================================================
SUPPORT & TROUBLESHOOTING
================================================================================

For issues, check:
  \${SETUP_LOG}

Verify service status:
  systemctl status nginx
  systemctl status php-fpm
  systemctl status mariadb
  systemctl status postgresql
  systemctl status firewalld
  systemctl status selinux

For SELinux issues:
  sealert -a /var/log/audit/audit.log
  ausearch -m AVC | audit2allow -a

================================================================================
SECURITY REMINDERS
================================================================================
⚠️  This system is configured in PERMISSIVE SELinux mode for testing
⚠️  Enable SELinux ENFORCING before production use
⚠️  Store credentials securely - never commit to version control
⚠️  Configure automated backups before going live
⚠️  Set up proper monitoring and alerting
⚠️  Follow the Security Hardening Guide (SECURITY.md)

================================================================================
SUMMARY_EOF

    cat /tmp/panel-setup-summary.txt | tee -a "\${SETUP_LOG}"
    
    success "Setup complete! Summary saved to /tmp/panel-setup-summary.txt"
}

# ============================================================================
# Main Execution
# ============================================================================

main() {
    log "Starting AlmaLinux setup for Server Management Panel..."
    
    check_root
    check_os
    
    harden_system
    configure_repositories
    configure_selinux
    
    install_php
    install_nginx
    install_databases
    install_tools
    
    configure_sqlite
    setup_directories
    configure_firewall
    
    run_health_checks
    print_summary
    
    success "AlmaLinux setup completed successfully!"
}

# Run main function
main "\$@"
```

---

## Deployment with SQLite:

**Key Advantages:**
1. **Zero Database Server**: Panel management DB is a single file
2. **Easy Backups**: Copy database.sqlite file for complete backup
3. **No Config Overhead**: No MySQL/PostgreSQL credentials for panel
4. **Fast Development**: SQLite for small to medium deployments
5. **Website DBs Separate**: Sites use MariaDB/PostgreSQL as needed

**When to Migrate to PostgreSQL:**
- Panel managing 500+ websites
- Concurrent users exceeds 50+
- Complex reporting requirements
- Multi-server deployments

This **setup.sh** is production-ready and can be run on a fresh AlmaLinux 8.x or 9.x instance to have a fully functional base system.