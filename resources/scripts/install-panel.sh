#!/usr/bin/env bash

set -euo pipefail

LOG_FILE="/var/log/panel-install.log"
exec > >(tee -a "$LOG_FILE") 2>&1

log() {
    local message="$1"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] ${message}"
}

if [[ "${EUID}" -ne 0 ]]; then
    log "This installer must be run as root."
    exit 1
fi

if [[ -f /etc/os-release ]]; then
    source /etc/os-release
else
    log "Unable to detect OS. /etc/os-release is missing."
    exit 1
fi

os_id="$(echo "${ID:-}" | tr '[:upper:]' '[:lower:]')"
major_version="$(echo "${VERSION_ID:-}" | cut -d'.' -f1)"

if [[ "${os_id}" != "rocky" && "${os_id}" != "almalinux" ]]; then
    log "This installer supports AlmaLinux and Rocky Linux only."
    exit 1
fi

if [[ "${major_version}" != "9" && "${major_version}" != "10" ]]; then
    log "This installer supports AlmaLinux/Rocky Linux versions 9 and 10 only."
    exit 1
fi

PANEL_DOMAIN="${PANEL_DOMAIN:-}"
PANEL_EMAIL="${PANEL_EMAIL:-}"
PANEL_REPO="${PANEL_REPO:-https://github.com/Digital-Indoorsmen/laravel-server-management.git}"
PANEL_BRANCH="${PANEL_BRANCH:-main}"
PANEL_APP_USER="${PANEL_APP_USER:-panel}"
PANEL_WEB_SERVER="${PANEL_WEB_SERVER:-}"
PANEL_WEB_SERVER="$(echo "${PANEL_WEB_SERVER}" | tr '[:upper:]' '[:lower:]')"
if [[ -n "${PANEL_WEB_SERVER}" && "${PANEL_WEB_SERVER}" != "nginx" && "${PANEL_WEB_SERVER}" != "caddy" ]]; then
    log "PANEL_WEB_SERVER must be either 'nginx' or 'caddy'."
    exit 1
fi
PANEL_APP_GROUP="${PANEL_APP_GROUP:-}"
PANEL_APP_GROUP_IS_DEFAULT="0"
if [[ -z "${PANEL_APP_GROUP}" ]]; then
    PANEL_APP_GROUP="${PANEL_WEB_SERVER}"
    PANEL_APP_GROUP_IS_DEFAULT="1"
fi
PANEL_APP_DIR="${PANEL_APP_DIR:-/var/www/laravel-server-management}"
PANEL_USE_SSL="${PANEL_USE_SSL:-1}"
PANEL_INSTALL_CERTBOT="${PANEL_INSTALL_CERTBOT:-1}"
PANEL_PROMPTS="${PANEL_PROMPTS:-1}"
PANEL_ADMIN_NAME="${PANEL_ADMIN_NAME:-Panel Admin}"
PANEL_ADMIN_EMAIL="${PANEL_ADMIN_EMAIL:-${PANEL_EMAIL}}"
PANEL_ADMIN_PASSWORD="${PANEL_ADMIN_PASSWORD:-}"

php_bin="/usr/bin/php"

install_packages() {
    dnf -y install "$@"
}

set_env_value() {
    local file="$1"
    local key="$2"
    local value="$3"
    local escaped_value
    escaped_value="$(printf '%s' "${value}" | sed 's/[\/&]/\\&/g')"

    if grep -qE "^#?${key}=" "${file}"; then
        sed -i -E "s|^#?${key}=.*|${key}=${escaped_value}|" "${file}"
    else
        echo "${key}=${value}" >> "${file}"
    fi
}

run_as_panel() {
    local command="$1"
    runuser -u "${PANEL_APP_USER}" -- bash -lc "${command}"
}

repair_sqlite_runtime_access() {
    local db_dir="${PANEL_APP_DIR}/database"
    local db_file="${db_dir}/database.sqlite"

    mkdir -p "${db_dir}"
    touch "${db_file}"

    # SQLite needs directory write access for -wal/-shm files at runtime.
    chown -R "${PANEL_APP_USER}:${PANEL_WEB_SERVER}" "${db_dir}"
    chmod 775 "${db_dir}"
    find "${db_dir}" -maxdepth 1 -type f -name 'database.sqlite*' -exec chmod 664 {} \;

    if command -v semanage >/dev/null 2>&1; then
        semanage fcontext -a -t httpd_sys_rw_content_t "${db_dir}(/.*)?" || semanage fcontext -m -t httpd_sys_rw_content_t "${db_dir}(/.*)?"
    fi

    if command -v restorecon >/dev/null 2>&1; then
        restorecon -Rv "${db_dir}" || true
    fi
}

validate_js_lockfiles() {
    local lockfile_count
    lockfile_count="$(find "${PANEL_APP_DIR}" -maxdepth 1 -type f \( -name 'bun.lock' -o -name 'bun.lockb' -o -name 'package-lock.json' -o -name 'yarn.lock' -o -name 'pnpm-lock.yaml' \) | wc -l | tr -d ' ')"

    if [[ "${lockfile_count}" -eq 0 ]]; then
        log "No JS lockfile found. Expected bun.lock for Bun-managed installs."
        exit 1
    fi

    if [[ "${lockfile_count}" -gt 1 ]]; then
        log "Multiple JS lockfiles detected. Keep only bun.lock for Bun-only installs."
        find "${PANEL_APP_DIR}" -maxdepth 1 -type f \( -name 'bun.lock' -o -name 'bun.lockb' -o -name 'package-lock.json' -o -name 'yarn.lock' -o -name 'pnpm-lock.yaml' \) -print
        exit 1
    fi

    if [[ ! -f "${PANEL_APP_DIR}/bun.lock" ]]; then
        log "Unsupported JS lockfile detected. Commit bun.lock and remove other lockfiles."
        exit 1
    fi
}

install_js_dependencies_with_retries() {
    local bun_shell='export BUN_INSTALL="$HOME/.bun"; export PATH="$BUN_INSTALL/bin:$PATH";'

    validate_js_lockfiles
    run_as_panel "${bun_shell} cd '${PANEL_APP_DIR}' && rm -rf node_modules"
    if run_as_panel "${bun_shell} cd '${PANEL_APP_DIR}' && bun install --frozen-lockfile"; then
        return 0
    fi

    log "bun install --frozen-lockfile failed; clearing Bun cache and node_modules, then retrying..."
    run_as_panel "${bun_shell} cd \"\$HOME\" && bun pm cache rm || true; rm -rf \"\$HOME/.bun/install/cache\" \"\$HOME/.cache/bun\""
    run_as_panel "${bun_shell} cd '${PANEL_APP_DIR}' && rm -rf node_modules"

    if run_as_panel "${bun_shell} cd '${PANEL_APP_DIR}' && bun install --frozen-lockfile --force --no-cache"; then
        return 0
    fi

    log "bun install retry failed; clearing Bun cache and node_modules, then trying one final time with --no-verify..."
    run_as_panel "${bun_shell} cd \"\$HOME\" && bun pm cache rm || true; rm -rf \"\$HOME/.bun/install/cache\" \"\$HOME/.cache/bun\""
    run_as_panel "${bun_shell} cd '${PANEL_APP_DIR}' && rm -rf node_modules && bun install --frozen-lockfile --force --no-cache --no-verify"
}

build_js_assets_with_retry() {
    local bun_shell='export BUN_INSTALL="$HOME/.bun"; export PATH="$BUN_INSTALL/bin:$PATH";'

    if run_as_panel "${bun_shell} cd '${PANEL_APP_DIR}' && bun run build"; then
        return 0
    fi

    log "bun run build failed; retrying once..."
    run_as_panel "${bun_shell} cd '${PANEL_APP_DIR}' && bun run build"
}

ensure_service_running() {
    local name="$1"
    systemctl enable --now "${name}"
}

disable_conflicting_web_server() {
    local active_server="$1"
    local conflicting_server="nginx"

    if [[ "${active_server}" == "nginx" ]]; then
        conflicting_server="caddy"
    fi

    if systemctl list-unit-files "${conflicting_server}.service" >/dev/null 2>&1; then
        systemctl disable --now "${conflicting_server}" >/dev/null 2>&1 || true
    fi
}

ensure_group_exists() {
    local name="$1"
    if ! getent group "${name}" >/dev/null 2>&1; then
        groupadd "${name}"
    fi
}

enable_optional_repositories() {
    if [[ "${major_version}" == "8" ]]; then
        dnf config-manager --set-enabled powertools >/dev/null 2>&1 || true
        dnf config-manager --set-enabled PowerTools >/dev/null 2>&1 || true
    fi

    if [[ "${major_version}" == "9" || "${major_version}" == "10" ]]; then
        dnf config-manager --set-enabled crb >/dev/null 2>&1 || true
    fi
}

configure_php_stream() {
    if dnf -q module list php >/dev/null 2>&1; then
        dnf -y module reset php || true
        dnf -y module enable php:remi-8.4 || true
    fi
}

log "Updating system packages..."
dnf -y update

log "Installing base repositories and utilities..."
install_packages epel-release dnf-plugins-core
enable_optional_repositories
install_packages git curl wget unzip tar nano vim which ca-certificates

log "Installing Remi and PHP 8.4..."
install_packages "https://rpms.remirepo.net/enterprise/remi-release-${major_version}.rpm"
configure_php_stream
install_packages php php-cli php-fpm php-common php-mbstring php-xml php-gd php-intl php-zip php-bcmath php-curl php-pdo php-sqlite3 php-mysqlnd php-pgsql php-opcache php-process

log "Installing web/runtime packages..."
install_packages sqlite supervisor firewalld policycoreutils-python-utils
ensure_service_running firewalld
ensure_service_running supervisord

if ! command -v composer >/dev/null 2>&1; then
    log "Installing Composer..."
    cd /tmp
    curl -fsSLo composer-setup.php https://getcomposer.org/installer
    "${php_bin}" composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm -f composer-setup.php
fi

if ! id -u "${PANEL_APP_USER}" >/dev/null 2>&1; then
    log "Creating ${PANEL_APP_USER} user..."
    useradd -m -s /bin/bash "${PANEL_APP_USER}"
fi

log "Preparing application directory..."
clone_parent_dir="$(dirname "${PANEL_APP_DIR}")"
mkdir -p "${clone_parent_dir}"
chown "${PANEL_APP_USER}:${PANEL_APP_USER}" "${clone_parent_dir}"
chmod 755 "${clone_parent_dir}"

if [[ -d "${PANEL_APP_DIR}/.git" ]]; then
    log "Repository already exists; updating checkout..."
    run_as_panel "cd '${PANEL_APP_DIR}' && git fetch --all --prune && git checkout '${PANEL_BRANCH}' && git pull --ff-only origin '${PANEL_BRANCH}'"
else
    run_as_panel "git clone --branch '${PANEL_BRANCH}' '${PANEL_REPO}' '${PANEL_APP_DIR}'"
fi

chown -R "${PANEL_APP_USER}:${PANEL_APP_USER}" "${PANEL_APP_DIR}"

if [[ ! -f "${PANEL_APP_DIR}/.env" ]]; then
    cp "${PANEL_APP_DIR}/.env.example" "${PANEL_APP_DIR}/.env"
fi

mkdir -p "${PANEL_APP_DIR}/database"
touch "${PANEL_APP_DIR}/database/database.sqlite"
chown "${PANEL_APP_USER}:${PANEL_APP_USER}" "${PANEL_APP_DIR}/.env" "${PANEL_APP_DIR}/database/database.sqlite"
chmod 664 "${PANEL_APP_DIR}/database/database.sqlite"

if ! run_as_panel "command -v bun >/dev/null 2>&1"; then
    log "Installing Bun for ${PANEL_APP_USER}..."
    run_as_panel "curl -fsSL https://bun.sh/install | bash"
fi

log "Installing PHP dependencies..."
run_as_panel "cd '${PANEL_APP_DIR}' && composer install --no-dev --optimize-autoloader --no-scripts"

if [[ "${PANEL_PROMPTS}" == "1" && -e /dev/tty ]]; then
    if [[ -z "${PANEL_WEB_SERVER}" ]]; then
        PANEL_WEB_SERVER="nginx"
    fi

    log "Launching Laravel Prompts installer wizard..."
    prompt_output_file="$(mktemp)"

    if (
        cd "${PANEL_APP_DIR}"
        PANEL_WEB_SERVER="${PANEL_WEB_SERVER}" \
        PANEL_DOMAIN="${PANEL_DOMAIN}" \
        PANEL_EMAIL="${PANEL_EMAIL}" \
        PANEL_ADMIN_NAME="${PANEL_ADMIN_NAME}" \
        PANEL_ADMIN_EMAIL="${PANEL_ADMIN_EMAIL}" \
        PANEL_ADMIN_PASSWORD="${PANEL_ADMIN_PASSWORD}" \
        PANEL_USE_SSL="${PANEL_USE_SSL}" \
        PANEL_INSTALL_CERTBOT="${PANEL_INSTALL_CERTBOT}" \
        "${php_bin}" artisan panel:collect-install-options --shell-file="${prompt_output_file}" < /dev/tty
    ); then
        # shellcheck disable=SC1090
        source "${prompt_output_file}"
    else
        rm -f "${prompt_output_file}"
        log "Interactive prompt collection failed."
        exit 1
    fi

    rm -f "${prompt_output_file}"
else
    if [[ -z "${PANEL_WEB_SERVER}" ]]; then
        log "Non-interactive mode requires PANEL_WEB_SERVER to be explicitly set to 'nginx' or 'caddy'."
        exit 1
    fi

    log "Skipping Laravel Prompts wizard because this run is non-interactive (no TTY)."
    log "For interactive setup, download and run the script directly in a terminal with PANEL_PROMPTS=1."
fi

if [[ "${PANEL_APP_GROUP_IS_DEFAULT}" == "1" ]]; then
    PANEL_APP_GROUP="${PANEL_WEB_SERVER}"
fi

ensure_group_exists "${PANEL_APP_GROUP}"

if [[ "${PANEL_WEB_SERVER}" == "caddy" ]]; then
    log "Installing Caddy..."
    install_packages "dnf-command(copr)"
    dnf copr enable -y @caddy/caddy || true
    install_packages caddy
else
    log "Installing Nginx..."
    install_packages nginx
fi

ensure_service_running php-fpm
disable_conflicting_web_server "${PANEL_WEB_SERVER}"
ensure_service_running "${PANEL_WEB_SERVER}"
usermod -aG "${PANEL_APP_GROUP}" "${PANEL_APP_USER}" || true

log "Installing JS dependencies and building assets..."
install_js_dependencies_with_retries
build_js_assets_with_retry

if [[ ! -f "${PANEL_APP_DIR}/.env" ]]; then
    cp "${PANEL_APP_DIR}/.env.example" "${PANEL_APP_DIR}/.env"
    chown "${PANEL_APP_USER}:${PANEL_APP_GROUP}" "${PANEL_APP_DIR}/.env"
fi

app_url="http://$(hostname -I | awk '{print $1}')"

if [[ -n "${PANEL_DOMAIN}" ]]; then
    if [[ "${PANEL_USE_SSL}" == "1" ]]; then
        app_url="https://${PANEL_DOMAIN}"
    else
        app_url="http://${PANEL_DOMAIN}"
    fi
fi

log "Configuring .env..."
set_env_value "${PANEL_APP_DIR}/.env" "APP_NAME" "\"Laravel Server Management\""
set_env_value "${PANEL_APP_DIR}/.env" "APP_ENV" "production"
set_env_value "${PANEL_APP_DIR}/.env" "APP_DEBUG" "false"
set_env_value "${PANEL_APP_DIR}/.env" "APP_URL" "${app_url}"
set_env_value "${PANEL_APP_DIR}/.env" "LOG_CHANNEL" "stack"
set_env_value "${PANEL_APP_DIR}/.env" "LOG_LEVEL" "info"
set_env_value "${PANEL_APP_DIR}/.env" "DB_CONNECTION" "sqlite"
set_env_value "${PANEL_APP_DIR}/.env" "DB_DATABASE" "${PANEL_APP_DIR}/database/database.sqlite"
set_env_value "${PANEL_APP_DIR}/.env" "SESSION_DRIVER" "database"
set_env_value "${PANEL_APP_DIR}/.env" "CACHE_STORE" "database"
set_env_value "${PANEL_APP_DIR}/.env" "QUEUE_CONNECTION" "database"

touch "${PANEL_APP_DIR}/database/database.sqlite"
repair_sqlite_runtime_access

log "Discovering Laravel packages..."
run_as_panel "cd '${PANEL_APP_DIR}' && ${php_bin} artisan package:discover --ansi"

log "Ensuring panel admin user..."
admin_output_file="$(mktemp)"

if (
    cd "${PANEL_APP_DIR}"
    PANEL_ADMIN_NAME="${PANEL_ADMIN_NAME}" \
    PANEL_ADMIN_EMAIL="${PANEL_ADMIN_EMAIL}" \
    PANEL_ADMIN_PASSWORD="${PANEL_ADMIN_PASSWORD}" \
    PANEL_EMAIL="${PANEL_EMAIL}" \
    "${php_bin}" artisan panel:ensure-admin-user --shell
) > "${admin_output_file}"; then
    # shellcheck disable=SC1090
    source "${admin_output_file}"
    rm -f "${admin_output_file}"
else
    rm -f "${admin_output_file}"
    log "Unable to create panel admin user. Set PANEL_ADMIN_EMAIL (or PANEL_EMAIL) and rerun."
    exit 1
fi

if [[ "${PANEL_ADMIN_PASSWORD_GENERATED:-0}" == "1" ]]; then
    credentials_file="/root/panel-admin-credentials.txt"
    cat > "${credentials_file}" <<EOF
PANEL_URL=${app_url}
PANEL_ADMIN_EMAIL=${PANEL_ADMIN_EMAIL}
PANEL_ADMIN_PASSWORD=${PANEL_ADMIN_PASSWORD}
EOF
    chmod 600 "${credentials_file}"
fi

log "Running Laravel setup commands..."
current_app_key="$(grep -E '^APP_KEY=' "${PANEL_APP_DIR}/.env" | tail -n1 | cut -d'=' -f2- || true)"
if [[ -z "${current_app_key}" ]]; then
    run_as_panel "cd '${PANEL_APP_DIR}' && ${php_bin} artisan key:generate --force"
else
    log "APP_KEY already present; skipping key generation."
fi

run_as_panel "cd '${PANEL_APP_DIR}' && ${php_bin} artisan migrate --force"
run_as_panel "cd '${PANEL_APP_DIR}' && ${php_bin} artisan storage:link --force"
run_as_panel "cd '${PANEL_APP_DIR}' && ${php_bin} artisan config:cache"

log "Setting runtime permissions..."
chown -R "${PANEL_APP_USER}:${PANEL_APP_GROUP}" "${PANEL_APP_DIR}"
chmod -R 775 "${PANEL_APP_DIR}/storage" "${PANEL_APP_DIR}/bootstrap/cache"
repair_sqlite_runtime_access

log "Configuring PHP-FPM socket for ${PANEL_WEB_SERVER}..."
sed -i "s/^user = .*/user = ${PANEL_WEB_SERVER}/" /etc/php-fpm.d/www.conf
sed -i "s/^group = .*/group = ${PANEL_WEB_SERVER}/" /etc/php-fpm.d/www.conf
sed -i 's|^listen = .*|listen = /run/php-fpm/www.sock|' /etc/php-fpm.d/www.conf
sed -i "s/^;listen.owner = .*/listen.owner = ${PANEL_WEB_SERVER}/" /etc/php-fpm.d/www.conf
sed -i "s/^;listen.group = .*/listen.group = ${PANEL_WEB_SERVER}/" /etc/php-fpm.d/www.conf
sed -i 's/^;listen.mode = .*/listen.mode = 0660/' /etc/php-fpm.d/www.conf

server_name="${PANEL_DOMAIN:-_}"

if [[ "${PANEL_WEB_SERVER}" == "caddy" ]]; then
    caddy_site_label=":80"
    if [[ -n "${PANEL_DOMAIN}" ]]; then
        if [[ "${PANEL_USE_SSL}" == "1" ]]; then
            caddy_site_label="${PANEL_DOMAIN}"
        else
            caddy_site_label="http://${PANEL_DOMAIN}"
        fi
    fi

    caddy_auto_https_line=""
    if [[ "${PANEL_USE_SSL}" != "1" || -z "${PANEL_DOMAIN}" ]]; then
        caddy_auto_https_line="    auto_https off"
    fi

    log "Writing Caddy config..."
    cat > /etc/caddy/Caddyfile <<EOF
{
${caddy_auto_https_line}
}

${caddy_site_label} {
    root * ${PANEL_APP_DIR}/public
    encode gzip zstd
    php_fastcgi unix//run/php-fpm/www.sock
    file_server

    header {
        X-Frame-Options "SAMEORIGIN"
        X-Content-Type-Options "nosniff"
        X-XSS-Protection "1; mode=block"
        Referrer-Policy "strict-origin-when-cross-origin"
    }
}
EOF
else
    log "Writing Nginx vhost..."
    cat > /etc/nginx/conf.d/laravel-server-management.conf <<EOF
server {
    listen 80;
    server_name ${server_name};
    root ${PANEL_APP_DIR}/public;
    index index.php index.html;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_pass unix:/run/php-fpm/www.sock;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF
fi

log "Configuring SELinux and firewall..."
semanage fcontext -a -t httpd_sys_rw_content_t "${PANEL_APP_DIR}/storage(/.*)?" || semanage fcontext -m -t httpd_sys_rw_content_t "${PANEL_APP_DIR}/storage(/.*)?"
semanage fcontext -a -t httpd_sys_rw_content_t "${PANEL_APP_DIR}/bootstrap/cache(/.*)?" || semanage fcontext -m -t httpd_sys_rw_content_t "${PANEL_APP_DIR}/bootstrap/cache(/.*)?"
semanage fcontext -a -t httpd_sys_rw_content_t "${PANEL_APP_DIR}/database(/.*)?" || semanage fcontext -m -t httpd_sys_rw_content_t "${PANEL_APP_DIR}/database(/.*)?"
if [[ "${PANEL_WEB_SERVER}" == "caddy" ]]; then
    semanage fcontext -a -t httpd_config_t "/etc/caddy(/.*)?" || semanage fcontext -m -t httpd_config_t "/etc/caddy(/.*)?"
    semanage fcontext -d "/run/php-fpm(/.*)?" >/dev/null 2>&1 || true
    semanage fcontext -a -t httpd_sys_rw_content_t "/var/run/php-fpm(/.*)?" || semanage fcontext -m -t httpd_sys_rw_content_t "/var/run/php-fpm(/.*)?"
fi
restorecon -Rv "${PANEL_APP_DIR}"
if [[ "${PANEL_WEB_SERVER}" == "caddy" ]]; then
    restorecon -Rv /etc/caddy /var/run/php-fpm /run/php-fpm || true
fi
setsebool -P httpd_can_network_connect 1
if [[ "${PANEL_WEB_SERVER}" == "caddy" ]]; then
    setsebool -P httpd_can_network_connect_db 1 || true
    setsebool -P httpd_can_sendmail 1 || true
fi

firewall-cmd --permanent --add-service=http
firewall-cmd --permanent --add-service=https
firewall-cmd --reload

if [[ "${PANEL_WEB_SERVER}" == "nginx" && "${PANEL_INSTALL_CERTBOT}" == "1" && "${PANEL_USE_SSL}" == "1" && -n "${PANEL_DOMAIN}" && -n "${PANEL_EMAIL}" ]]; then
    log "Installing SSL certificate for ${PANEL_DOMAIN}..."
    install_packages certbot python3-certbot-nginx
    certbot --nginx -d "${PANEL_DOMAIN}" --agree-tos --no-eff-email -m "${PANEL_EMAIL}" --redirect --non-interactive
elif [[ "${PANEL_WEB_SERVER}" == "caddy" && "${PANEL_USE_SSL}" == "1" && -n "${PANEL_DOMAIN}" ]]; then
    log "Skipping certbot because Caddy manages TLS certificates automatically."
else
    log "Skipping certbot step. Set PANEL_DOMAIN and PANEL_EMAIL to enable automatic SSL."
fi

log "Configuring queue worker service..."
cat > /etc/systemd/system/laravel-queue.service <<EOF
[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
User=${PANEL_APP_USER}
Group=${PANEL_APP_GROUP}
Restart=always
RestartSec=5
WorkingDirectory=${PANEL_APP_DIR}
ExecStart=${php_bin} artisan queue:work --sleep=3 --tries=3 --timeout=120

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable --now laravel-queue

systemctl restart php-fpm

if [[ "${PANEL_WEB_SERVER}" == "caddy" ]]; then
    caddy validate --config /etc/caddy/Caddyfile
    systemctl restart caddy
else
    nginx -t
    systemctl restart nginx
fi

log "Install complete."
log "Web server: ${PANEL_WEB_SERVER}"
log "Panel URL: ${app_url}"
log "Panel admin email: ${PANEL_ADMIN_EMAIL}"
if [[ "${PANEL_ADMIN_PASSWORD_GENERATED:-0}" == "1" ]]; then
    log "Panel admin password was generated and saved to /root/panel-admin-credentials.txt"
else
    log "Panel admin password was provided through PANEL_ADMIN_PASSWORD."
fi
log "Health check endpoint: ${app_url}/up"
log "Install log: ${LOG_FILE}"
