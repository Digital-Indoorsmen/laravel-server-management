# Laravel Server Management Panel

Laravel + Inertia control panel for provisioning and managing self-hosted Linux web servers (currently AlmaLinux/Rocky Linux focused).

Repository: <https://github.com/Digital-Indoorsmen/laravel-server-management>

This README is a production-oriented, start-to-finish install guide for a fresh AlmaLinux or Rocky Linux 8, 9, or 10 server where you can SSH as `root`.

If you want the fastest path, use the installer in [Bootstrap Installer](#bootstrap-installer), then use the manual sections as reference.

## What You Are Installing

- A Laravel 12 app (this repository) running behind Nginx + PHP-FPM
- SQLite as the default panel database
- A queue worker managed by systemd
- HTTPS via Certbot
- SELinux + firewalld adjustments needed for Laravel runtime

## Important Notes Before You Start

- This branch currently has incomplete production hardening in-app (for example, auth flow is noted as in progress).
- Do not expose this panel publicly without your own access controls (VPN, IP allow-list, basic auth, reverse proxy auth, etc.).
- The panel generates setup commands for other servers. Those managed servers must be able to reach this panel's `APP_URL`.

## Assumptions

- Fresh AlmaLinux or Rocky Linux 8/9/10 minimal install
- You can SSH as `root`
- You have a domain (example: `panel.example.com`) pointing to this server's public IP
- Server has outbound internet access

## Bootstrap Installer

This repository now includes `resources/scripts/install-panel.sh`, which performs core AlmaLinux/Rocky Linux 8/9/10 prep and app install automatically.

Required on target server:

- `curl` or `wget`
- root SSH access

### Recommended (interactive with prompts)

This is the default recommended path. It gives you the full installer prompts (including choosing `nginx` vs `caddy`).

```bash
curl -fsSL "https://raw.githubusercontent.com/Digital-Indoorsmen/laravel-server-management/main/resources/scripts/install-panel.sh?ts=$(date +%s)" -o /tmp/install-panel.sh
chmod +x /tmp/install-panel.sh
PANEL_PROMPTS=1 /tmp/install-panel.sh
```

If you are connected remotely, make sure you have a real TTY (for example `ssh -t`), otherwise prompts cannot render.

### Non-interactive (automation/CI)

Use this only when you explicitly want no prompts. `PANEL_WEB_SERVER` is required.

```bash
PANEL_WEB_SERVER=nginx PANEL_DOMAIN=panel.example.com PANEL_EMAIL=you@example.com bash -c "$(curl -fsSL "https://raw.githubusercontent.com/Digital-Indoorsmen/laravel-server-management/main/resources/scripts/install-panel.sh?ts=$(date +%s)")"
```

Important installer environment variables:

- `PANEL_PROMPTS`: `1` for prompt mode (recommended default)
- `PANEL_WEB_SERVER`: required for non-interactive mode, must be `nginx` or `caddy`
- `PANEL_DOMAIN`: public domain for the panel (optional, but recommended)
- `PANEL_EMAIL`: email used by certbot for HTTPS (required for automatic SSL)
- `PANEL_USE_SSL`: `1` (default) or `0`
- `PANEL_INSTALL_CERTBOT`: `1` (default) or `0`
- `PANEL_APP_DIR`: app path (default `/var/www/laravel-server-management`)
- `PANEL_BRANCH`: git branch to install (default `main`)

After bootstrap completes, continue with [First Use Workflow (Inside the Panel)](#15-first-use-workflow-inside-the-panel).

## 1. Initial OS Prep (as root)

```bash
dnf -y update
dnf -y install epel-release dnf-plugins-core
dnf -y install git curl wget unzip tar nano vim which policycoreutils-python-utils ca-certificates
timedatectl set-timezone UTC
```

Optional but recommended:

```bash
reboot
```

## 2. Install PHP 8.4, Nginx, SQLite, Supervisor, and Common Extensions

Enable Remi and install PHP 8.4:

```bash
dnf -y install https://rpms.remirepo.net/enterprise/remi-release-$(rpm -E %rhel).rpm
dnf -y module reset php
dnf -y module enable php:remi-8.4
dnf -y install php php-cli php-fpm php-common php-mbstring php-xml php-gd php-intl php-zip php-bcmath php-curl php-pdo php-sqlite3 php-mysqlnd php-pgsql php-opcache php-process
```

Install web/runtime packages:

```bash
dnf -y install nginx sqlite supervisor firewalld
```

Enable core services:

```bash
systemctl enable --now php-fpm nginx firewalld
```

## 3. Install Composer

```bash
cd /tmp
curl -fsSLo composer-setup.php https://getcomposer.org/installer
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm -f composer-setup.php
composer --version
```

## 4. Create a Deployment User and App Directory

```bash
id -u panel >/dev/null 2>&1 || useradd -m -s /bin/bash panel
usermod -aG nginx panel
mkdir -p /var/www
cd /var/www
git clone https://github.com/Digital-Indoorsmen/laravel-server-management.git
chown -R panel:nginx /var/www/laravel-server-management
```

## 5. Install Bun (for frontend build) as `panel`

```bash
runuser -u panel -- bash -lc 'curl -fsSL https://bun.sh/install | bash'
runuser -u panel -- bash -lc 'export BUN_INSTALL="$HOME/.bun"; export PATH="$BUN_INSTALL/bin:$PATH"; bun --version'
```

## 6. Install PHP and JS Dependencies

```bash
runuser -u panel -- bash -lc 'cd /var/www/laravel-server-management && composer install --no-dev --optimize-autoloader'
runuser -u panel -- bash -lc 'export BUN_INSTALL="$HOME/.bun"; export PATH="$BUN_INSTALL/bin:$PATH"; cd /var/www/laravel-server-management && bun install --frozen-lockfile'
```

If `bun install --frozen-lockfile` fails due lock drift, run:

```bash
runuser -u panel -- bash -lc 'export BUN_INSTALL="$HOME/.bun"; export PATH="$BUN_INSTALL/bin:$PATH"; cd /var/www/laravel-server-management && bun install'
```

## 7. Configure Environment

```bash
cd /var/www/laravel-server-management
cp .env.example .env
```

Edit `.env`:

```bash
nano /var/www/laravel-server-management/.env
```

Minimum required production values:

```dotenv
APP_NAME="Laravel Server Management"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://panel.example.com

LOG_CHANNEL=stack
LOG_LEVEL=info

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/laravel-server-management/database/database.sqlite

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Create the SQLite DB file:

```bash
touch /var/www/laravel-server-management/database/database.sqlite
chown panel:nginx /var/www/laravel-server-management/database/database.sqlite
chmod 664 /var/www/laravel-server-management/database/database.sqlite
```

## 8. Laravel App Bootstrap

```bash
runuser -u panel -- bash -lc 'cd /var/www/laravel-server-management && php artisan key:generate --force'
runuser -u panel -- bash -lc 'cd /var/www/laravel-server-management && php artisan migrate --force'
runuser -u panel -- bash -lc 'cd /var/www/laravel-server-management && php artisan storage:link'
runuser -u panel -- bash -lc 'cd /var/www/laravel-server-management && php artisan config:cache'
```

Build frontend assets:

```bash
runuser -u panel -- bash -lc 'export BUN_INSTALL="$HOME/.bun"; export PATH="$BUN_INSTALL/bin:$PATH"; cd /var/www/laravel-server-management && bun run build'
```

Set runtime permissions:

```bash
chown -R panel:nginx /var/www/laravel-server-management
chmod -R 775 /var/www/laravel-server-management/storage /var/www/laravel-server-management/bootstrap/cache
```

## 9. Configure PHP-FPM for Nginx

Adjust default pool so Nginx can use the socket:

```bash
sed -i 's/^user = .*/user = nginx/' /etc/php-fpm.d/www.conf
sed -i 's/^group = .*/group = nginx/' /etc/php-fpm.d/www.conf
sed -i 's|^listen = .*|listen = /run/php-fpm/www.sock|' /etc/php-fpm.d/www.conf
sed -i 's/^;listen.owner = .*/listen.owner = nginx/' /etc/php-fpm.d/www.conf
sed -i 's/^;listen.group = .*/listen.group = nginx/' /etc/php-fpm.d/www.conf
sed -i 's/^;listen.mode = .*/listen.mode = 0660/' /etc/php-fpm.d/www.conf
systemctl restart php-fpm
```

## 10. Configure Nginx Virtual Host

Create `/etc/nginx/conf.d/laravel-server-management.conf`:

```nginx
server {
    listen 80;
    server_name panel.example.com;
    root /var/www/laravel-server-management/public;
    index index.php index.html;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php-fpm/www.sock;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Apply config:

```bash
nginx -t
systemctl reload nginx
```

## 11. SELinux + Firewalld

Allow Laravel writable paths and outbound network from web context:

```bash
semanage fcontext -a -t httpd_sys_rw_content_t '/var/www/laravel-server-management/storage(/.*)?'
semanage fcontext -a -t httpd_sys_rw_content_t '/var/www/laravel-server-management/bootstrap/cache(/.*)?'
restorecon -Rv /var/www/laravel-server-management
setsebool -P httpd_can_network_connect 1
```

Open firewall:

```bash
firewall-cmd --permanent --add-service=http
firewall-cmd --permanent --add-service=https
firewall-cmd --reload
```

## 12. HTTPS with Certbot

```bash
dnf -y install certbot python3-certbot-nginx
certbot --nginx -d panel.example.com --agree-tos --no-eff-email -m you@example.com --redirect
```

Confirm renewal timer:

```bash
systemctl status certbot.timer --no-pager
```

## 13. Run Queue Worker with systemd

Create `/etc/systemd/system/laravel-queue.service`:

```ini
[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
User=panel
Group=nginx
Restart=always
RestartSec=5
WorkingDirectory=/var/www/laravel-server-management
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --timeout=120

[Install]
WantedBy=multi-user.target
```

Enable it:

```bash
systemctl daemon-reload
systemctl enable --now laravel-queue
```

Optional scheduler (future-proof):

```bash
cat >/etc/cron.d/laravel-scheduler <<'EOF'
* * * * * panel cd /var/www/laravel-server-management && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
EOF
chmod 644 /etc/cron.d/laravel-scheduler
```

## 14. Verify the Deployment

```bash
curl -I https://panel.example.com/up
curl -I https://panel.example.com
systemctl status nginx php-fpm laravel-queue --no-pager
```

Tail logs:

```bash
tail -f /var/www/laravel-server-management/storage/logs/laravel.log
journalctl -u nginx -u php-fpm -u laravel-queue -f
```

## 15. First Use Workflow (Inside the Panel)

1. Go to `https://panel.example.com`.
2. Create or import an SSH key in `/ssh-keys`.
3. Add a server record and attach that SSH key.
4. Copy the generated bootstrap command from dashboard (format: `curl -sSL https://.../setup/{token} | sudo bash`) and run it on the target server as root.
5. If target server has no `sudo`, run the command as `root` without `sudo`:
   `curl -sSL https://.../setup/{token} | bash`
6. Ensure the selected SSH public key is present in `/home/panel/.ssh/authorized_keys` on the managed server.
7. Run "Test Connection" in the panel.
8. Create sites and optional databases from the panel UI.

## 16. Common Failure Points and Fixes

- Setup script status stays pending:
  - Confirm `APP_URL` is correct and publicly reachable from the managed server.
  - Confirm inbound 443/80 is open on the panel host.
  - Check callback endpoint manually: `POST /setup/{token}/callback`.
- 502 Bad Gateway:
  - Check `php-fpm` is running and socket path matches Nginx config.
- Permission denied writing logs/cache:
  - Re-run ownership/permission commands for `storage` and `bootstrap/cache`.
- SELinux denials:
  - Check `audit.log` and run `restorecon -Rv /var/www/laravel-server-management`.
- JS/CSS not updating after deploy:
  - Re-run `bun install` and `bun run build`, then reload Nginx.

## 17. Updating the App

```bash
cd /var/www/laravel-server-management
git pull origin main
runuser -u panel -- bash -lc 'cd /var/www/laravel-server-management && composer install --no-dev --optimize-autoloader'
runuser -u panel -- bash -lc 'export BUN_INSTALL="$HOME/.bun"; export PATH="$BUN_INSTALL/bin:$PATH"; cd /var/www/laravel-server-management && bun install --frozen-lockfile && bun run build'
runuser -u panel -- bash -lc 'cd /var/www/laravel-server-management && php artisan migrate --force && php artisan config:cache'
systemctl restart laravel-queue php-fpm nginx
```

## Local Development (Quick Reference)

```bash
composer run setup
composer run dev
php artisan test --compact
```

`verification/` contains VM-based setup verification assets (Vagrant + pytest) for provisioning script validation.
