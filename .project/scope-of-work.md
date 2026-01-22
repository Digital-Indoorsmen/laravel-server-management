# Server Management Software Development Plan (Updated)
# Laravel-Based Server Management Panel (SpinupWP Alternative)

## Project Overview
Build a self-hosted server management panel using Laravel 12, Inertia.js, and Vue.js that replicates SpinupWP functionality for AlmaLinux and Rocky Linux servers with full WordPress, Laravel, and PHP application support.

## Architecture & Technology Stack

### Core Technologies
- **Backend**: Laravel 12 (PHP 8.3+)
- **Frontend**: Vue.js 3 + Inertia.js + Bun (default) / bun (optional)
- **CSS**: TailwindCSS with DaisyUI
- **Database**: SQLite (primary for server management) + MariaDB (latest) + PostgreSQL (latest)
- **Server Communication**: Laravel CLI Kit (SSH-based)
- **Web Server**: Nginx with custom configurations
- **SELinux**: Full support with proper context management

## Server Requirements
- AlmaLinux 8.x/9.x or Rocky Linux 8.x/9.x (RHEL-based)
- Minimum 2GB RAM, 20GB storage
- SELinux enabled (targeted mode)
- Firewalld configured
- Multiple PHP versions (7.4, 8.0, 8.1, 8.2, 8.3, 8.4, 8.5)

## Core Features Breakdown

### 1. Server Management Module
- **Server Provisioning**
  - Automated AlmaLinux setup (.sh file for initial server setup, installing required packages, setting up SSH keys, etc.)
  - SSH key management (including root access via web interface)
  - Firewall configuration
  - SELinux policy management
  - System updates automation

- **Service Management**
  - Nginx configuration and virtual hosts
  - PHP-FPM pool management per site
  - Database server management (MariaDB/PostgreSQL)
  - Redis configuration
  - SSL certificate management (Let's Encrypt)

### 2. Website Management Module
- **Site Creation Workflow**
  - Domain management and DNS validation
  - Web root directory setup
  - Linux user creation with proper permissions
  - PHP version selection per site
  - Database creation and user management (MariaDB or PostgreSQL)

- **Application Support**
  - WordPress automated installation
  - Laravel project setup
  - Generic PHP application support
  - Git-based deployments
  - Composer integration

### 3. PHP Management System
- **Multi-Version Support**
  - Install/remove PHP versions dynamically
  - Per-site PHP version selection
  - PHP-FPM pool isolation
  - Extension management per version

- **Configuration Management**
  - Global php.ini management
  - Per-site PHP settings override
  - Memory limit, upload limits, timeout settings
  - OPcache configuration per site

### 4. Database Management
- **Multi-Database Support**
  - MariaDB latest version with user isolation
  - PostgreSQL 14+ support with user isolation
  - Database creation per site (MariaDB or PostgreSQL)
  - User privilege management
  - phpMyAdmin/phpPgAdmin integration

### 5. Security & Isolation
- **User Isolation**
  - Each site runs under dedicated Linux user
  - SFTP access with chroot jail
  - File permission management
  - Process isolation with PHP-FPM pools

- **SELinux Integration**
  - Custom SELinux policies
  - File context management
  - Port labeling for web services
  - Boolean management for web services

### 6. Caching & Performance
- **Nginx Caching**
  - FastCGI cache configuration
  - Cache key customization
  - Cache purging mechanisms
  - Browser cache headers

- **PHP OPcache**
  - Per-site OPcache configuration
  - Memory allocation management
  - Validation frequency settings

### 7. Deployment & Release Management
- **Git Integration**
  - Repository cloning with SSH keys
  - Branch selection and switching
  - Webhook-based deployments
  - Deployment scripts execution
  - Rollback capabilities

- **Staging Environment**
  - One-click staging site creation
  - Database synchronization
  - File synchronization
  - Production deployment workflow

### 8. Monitoring & Analytics
- **Resource Monitoring**
  - Real-time CPU, memory, disk usage
  - Network traffic monitoring
  - Process monitoring
  - Log file analysis

- **Site Health**
  - Uptime monitoring
  - Response time tracking
  - Error log monitoring
  - SSL certificate expiry alerts

## Frontend Package Management

### Bun Integration (Default)
// package.json with bun as default
{
  "name": "server-panel",
  "packageManager": "bun@1.1.0",
  "scripts": {
    "dev": "bun run dev",
    "build": "bun run build",
    "preview": "bun run preview"
  }
}

### User Preference System
-- User preferences table
user_preferences: id, user_id, package_manager, created_at, updated_at

### Build Commands
// Bun commands (default)
bun install
bun run dev
bun run build

// bun commands (optional)
bun install
bun run dev
bun run build

## Database Schema Design

### Core Tables
-- Servers table
servers: id, name, ip_address, hostname, os_version, ssh_key_id, status, created_at

-- SSH Keys table
ssh_keys: id, name, public_key, private_key, fingerprint, created_at

-- Sites table  
sites: id, server_id, domain, document_root, system_user, php_version, status, created_at

-- SystemUsers table
system_users: id, site_id, username, home_directory, shell, status, created_at

-- Databases table
databases: id, site_id, name, type, host, port, status, created_at

-- DatabaseUsers table
database_users: id, database_id, username, host, privileges, created_at

-- PhpVersions table
php_versions: id, version, status, created_at

-- SslCertificates table
ssl_certificates: id, site_id, domain, certificate_type, expiry_date, status, created_at

-- UserPreferences table
user_preferences: id, user_id, package_manager, created_at, updated_at

## API Architecture

### RESTful Endpoints
/api/v1/servers
/api/v1/ssh-keys
/api/v1/sites
/api/v1/databases
/api/v1/php-versions
/api/v1/ssl-certificates
/api/v1/deployments
/api/v1/monitoring
/api/v1/user-preferences

### CLI Commands Structure
php artisan server:create
php artisan server:delete
php artisan ssh-key:generate
php artisan ssh-key:insert
php artisan site:create
php artisan site:deploy
php artisan site:backup
php artisan php:install
php artisan php:switch-version

## Security Implementation

### SSH Key Management
- RSA/ED25519 key generation via web interface
- Root access key insertion (with proper validation)
- Key rotation mechanisms
- Authorized_keys management
- Key-based authentication only

### File System Security
- Proper ownership (site-user:www-data)
- 755 for directories, 644 for files
- SELinux contexts for web content
- Chroot jail for SFTP access

### Network Security
- FirewallD configuration
- Fail2ban integration
- Rate limiting per site
- SSL/TLS enforcement

## Deployment Workflow

### 1. Initial Server Setup
// AlmaLinux preparation
dnf update -y
dnf install -y epel-release
dnf config-manager --set-enabled powertools

// Install web stack
dnf install -y nginx mariadb-server postgresql-server
dnf install -y php-fpm php-mysqlnd php-pgsql php-xml php-mbstring

// Install bun
curl -fsSL https://bun.sh/install | bash

### 2. Panel Installation
// Laravel application setup
composer create-project laravel/laravel server-panel
cd server-panel
composer require grazulex/laravel-cli-kit

// Install frontend dependencies with bun (default)
bun install

// Or with bun (user preference)
bun install

// Database setup
php artisan migrate
php artisan db:seed

### 3. SELinux Configuration
// Set SELinux booleans
setsebool -P httpd_can_network_connect 1
setsebool -P httpd_enable_cgi 1
setsebool -P httpd_enable_homedirs 1

// Set file contexts
semanage fcontext -a -t httpd_sys_content_t "/var/www(/.*)?"
restorecon -Rv /var/www

## Testing Strategy

### Unit Testing
- Laravel feature tests
- SSH command testing
- Configuration validation
- Security vulnerability testing

### Integration Testing
- Full site creation workflow
- PHP version switching
- Database connectivity
- SSL certificate generation

### Load Testing
- Concurrent site creation
- Resource usage monitoring
- Performance benchmarking

## Development Phases

### Phase 1: Foundation (Weeks 1-4)
- Laravel application setup
- Basic authentication system
- Server connection via SSH with key management
- Database schema implementation
- Bun integration for frontend

### Phase 2: Core Features (Weeks 5-12)
- Site creation and management
- PHP version management
- Database provisioning (MariaDB and PostgreSQL)
- Basic monitoring
- User preference system for package managers

### Phase 3: Advanced Features (Weeks 13-20)
- Git deployment system
- SSL certificate automation
- Caching configuration
- SELinux integration
- Advanced SSH key management

### Phase 4: Polish & Optimization (Weeks 21-24)
- UI/UX improvements
- Performance optimization
- Security hardening
- Documentation completion

## Key Implementation Considerations

### SELinux Compatibility
- Custom policy modules for web services
- Proper file context labeling
- Port type definitions
- Boolean management interface

### Multi-PHP Architecture
- PHP-FPM pool isolation per site
- Separate socket files per version
- Individual php.ini per pool
- Extension management per version

### Scalability Design
- Queue-based task processing
- Database connection pooling
- Redis for caching sessions
- Horizontal scaling preparation

### Frontend Build Optimization
- Bun's fast package installation
- Tree shaking for production builds
- Asset optimization
- Hot module replacement

This plan provides a comprehensive foundation for a robust, secure, and feature-rich server management panel that matches SpinupWP capabilities while being self-hosted and optimized for AlmaLinux environments with full PostgreSQL support for websites, MariaDB as the MySQL alternative, SSH key management through the web interface, and flexible frontend package management.