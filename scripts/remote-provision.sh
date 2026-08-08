#!/usr/bin/env bash
#
# Runs ON the Ubuntu 24.04 server (as root) via deploy.sh over SSH.
# Provisions Apache2 + PHP-FPM + MySQL + Certbot, then deploys the app
# and wires the queue worker into the server's existing Supervisor.
#
# Required env vars (exported by deploy.sh before invoking this script):
#   APP_NAME, DOMAIN, EMAIL, REPO_URL, BRANCH, APP_DIR,
#   DB_NAME, DB_USER, DB_PASS

set -euo pipefail

: "${APP_NAME:?}" "${DOMAIN:?}" "${EMAIL:?}" "${REPO_URL:?}" "${BRANCH:?}" "${APP_DIR:?}"
: "${DB_NAME:?}" "${DB_USER:?}" "${DB_PASS:?}"

PHP_VERSION="8.3"

echo "==> Installing packages"
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y \
    apache2 \
    mysql-server \
    "php${PHP_VERSION}" "php${PHP_VERSION}-fpm" "php${PHP_VERSION}-cli" "php${PHP_VERSION}-common" \
    "php${PHP_VERSION}-mysql" "php${PHP_VERSION}-mbstring" "php${PHP_VERSION}-xml" \
    "php${PHP_VERSION}-curl" "php${PHP_VERSION}-zip" "php${PHP_VERSION}-bcmath" \
    "php${PHP_VERSION}-gd" "php${PHP_VERSION}-intl" "php${PHP_VERSION}-sqlite3" \
    certbot python3-certbot-apache \
    git unzip curl

if ! command -v composer >/dev/null 2>&1; then
    echo "==> Installing Composer"
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

if ! command -v node >/dev/null 2>&1; then
    echo "==> Installing Node.js 22.x"
    curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
    apt-get install -y nodejs
fi

echo "==> Configuring Apache modules"
a2enmod proxy proxy_fcgi setenvif rewrite ssl >/dev/null
a2enconf "php${PHP_VERSION}-fpm" >/dev/null

# Make sure requests are handled by php8.3-fpm even if another PHP-FPM
# version is already enabled on this box (e.g. from a prior app install).
for conf in /etc/apache2/conf-enabled/php*-fpm.conf; do
    [ -e "$conf" ] || continue
    base="$(basename "$conf" .conf)"
    if [ "$base" != "php${PHP_VERSION}-fpm" ]; then
        a2disconf "$base" >/dev/null
    fi
done

systemctl enable --now "php${PHP_VERSION}-fpm"

echo "==> Configuring MySQL user/grants (database already exists)"
mysql -uroot <<SQL
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

echo "==> Fetching application code"
mkdir -p "$(dirname "$APP_DIR")"
if [ -d "${APP_DIR}/.git" ]; then
    git -C "$APP_DIR" fetch origin "$BRANCH"
    git -C "$APP_DIR" reset --hard "origin/${BRANCH}"
else
    git clone --branch "$BRANCH" "$REPO_URL" "$APP_DIR"
fi

cd "$APP_DIR"

echo "==> Installing dependencies"
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build

echo "==> Configuring environment"
if [ ! -f .env ]; then
    cp .env.example .env
fi
sed -i \
    -e "s|^APP_ENV=.*|APP_ENV=production|" \
    -e "s|^APP_DEBUG=.*|APP_DEBUG=false|" \
    -e "s|^APP_URL=.*|APP_URL=https://${DOMAIN}|" \
    -e "s|^DB_CONNECTION=.*|DB_CONNECTION=mysql|" \
    -e "s|^# DB_HOST=.*|DB_HOST=127.0.0.1|" \
    -e "s|^# DB_PORT=.*|DB_PORT=3306|" \
    -e "s|^# DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|" \
    -e "s|^# DB_USERNAME=.*|DB_USERNAME=${DB_USER}|" \
    -e "s|^# DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|" \
    .env

if ! grep -q '^APP_KEY=base64' .env; then
    php artisan key:generate --force
fi

php artisan migrate --force
php artisan storage:link || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Setting permissions"
chown -R www-data:www-data "$APP_DIR"
find "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" -type d -exec chmod 775 {} \;
find "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" -type f -exec chmod 664 {} \;

echo "==> Restarting php${PHP_VERSION}-fpm to clear opcache"
systemctl restart "php${PHP_VERSION}-fpm"

echo "==> Configuring Apache vhost"
cat > "/etc/apache2/sites-available/${APP_NAME}.conf" <<VHOST
<VirtualHost *:80>
    ServerName ${DOMAIN}
    DocumentRoot ${APP_DIR}/public

    <Directory ${APP_DIR}/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/${APP_NAME}-error.log
    CustomLog \${APACHE_LOG_DIR}/${APP_NAME}-access.log combined
</VirtualHost>
VHOST

a2ensite "${APP_NAME}.conf" >/dev/null
a2dissite 000-default.conf >/dev/null 2>&1 || true
apache2ctl configtest
systemctl reload apache2

echo "==> Requesting TLS certificate"
certbot --apache -d "$DOMAIN" --non-interactive --agree-tos -m "$EMAIL" --redirect

echo "==> Wiring queue worker into Supervisor"
cat > "/etc/supervisor/conf.d/${APP_NAME}-worker.conf" <<SUPERVISOR
[program:${APP_NAME}-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${APP_DIR}/artisan queue:work --sleep=3 --tries=3 --max-time=3600
directory=${APP_DIR}
numprocs=2
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/worker.log
stopwaitsecs=3600
SUPERVISOR

supervisorctl reread
supervisorctl update
supervisorctl restart "${APP_NAME}-worker:*"

echo "==> Done. https://${DOMAIN} is live."
