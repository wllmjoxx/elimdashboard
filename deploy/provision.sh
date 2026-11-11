#!/usr/bin/env bash
#
# One-time setup of a fresh Ubuntu 24.04 server for GGP Elim: Oracle Cloud
# Always Free (staging) or any VPS (production). Run it from the cloned
# repository with sudo, as the user that owns the code:
#
#   sudo DOMAIN=152-70-1-2.sslip.io EMAIL=you@example.org bash deploy/provision.sh
#
# Optional: APP_ENV (default staging). Safe to run again.
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
APP_USER="${SUDO_USER:-ubuntu}"
DOMAIN="${DOMAIN:?Set DOMAIN, for example DOMAIN=152-70-1-2.sslip.io}"
EMAIL="${EMAIL:?Set EMAIL for HTTPS certificate expiry notices}"
APP_ENV="${APP_ENV:-staging}"
PHP=8.3

if [ "$(id -u)" -ne 0 ]; then
    echo "Run with sudo." >&2
    exit 1
fi

echo "==> Packages"
export DEBIAN_FRONTEND=noninteractive
apt-get update -q
apt-get install -y -q nginx "php$PHP-fpm" "php$PHP-cli" "php$PHP-sqlite3" "php$PHP-mbstring" \
    "php$PHP-xml" "php$PHP-curl" "php$PHP-zip" "php$PHP-intl" "php$PHP-bcmath" "php$PHP-gd" \
    composer unzip git sqlite3 supervisor certbot python3-certbot-nginx \
    iptables-persistent netfilter-persistent ca-certificates curl gnupg

if ! command -v node >/dev/null || [ "$(node -v | cut -d. -f1 | tr -d v)" -lt 22 ]; then
    echo "==> Node.js 22 (NodeSource)"
    curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
    apt-get install -y -q nodejs
fi

# Small machines (1 GB RAM) need swap to build the front end.
if [ "$(awk '/MemTotal/ {print $2}' /proc/meminfo)" -lt 2000000 ] && ! swapon --show | grep -q .; then
    echo "==> 2 GB swap"
    fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile
    grep -q '/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi

echo "==> Firewall: allow HTTP and HTTPS (Oracle images block them)"
for port in 80 443; do
    iptables -C INPUT -p tcp --dport "$port" -m state --state NEW -j ACCEPT 2>/dev/null \
        || iptables -I INPUT -p tcp --dport "$port" -m state --state NEW -j ACCEPT
done
netfilter-persistent save

echo "==> PHP-FPM runs as $APP_USER; uploads up to 64 MB"
sed -i "s/^user = .*/user = $APP_USER/; s/^group = .*/group = $APP_USER/" "/etc/php/$PHP/fpm/pool.d/www.conf"
cat > "/etc/php/$PHP/fpm/conf.d/99-elim.ini" <<EOF
upload_max_filesize = 64M
post_max_size = 64M
memory_limit = 256M
expose_php = Off
EOF
systemctl restart "php$PHP-fpm"

echo "==> App files"
chown -R "$APP_USER:$APP_USER" "$APP_DIR"
sudo -u "$APP_USER" mkdir -p "$APP_DIR/storage/logs" "$APP_DIR/bootstrap/cache"
sudo -u "$APP_USER" touch "$APP_DIR/database/database.sqlite"
if [ ! -f "$APP_DIR/.env" ]; then
    sudo -u "$APP_USER" cp "$APP_DIR/deploy/env.example" "$APP_DIR/.env"
    sed -i "s#^APP_URL=.*#APP_URL=https://$DOMAIN#; s#^APP_ENV=.*#APP_ENV=$APP_ENV#" "$APP_DIR/.env"
    echo "    .env created from deploy/env.example; fill in the mail settings later."
fi

echo "==> Nginx"
sed -e "s#__DOMAIN__#$DOMAIN#g" -e "s#__APP_DIR__#$APP_DIR#g" -e "s#__PHP__#$PHP#g" \
    "$APP_DIR/deploy/nginx.conf" > /etc/nginx/sites-available/elim
ln -sf /etc/nginx/sites-available/elim /etc/nginx/sites-enabled/elim
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

echo "==> First build"
sudo -u "$APP_USER" bash "$APP_DIR/deploy/deploy.sh" "$(sudo -u "$APP_USER" git -C "$APP_DIR" rev-parse --abbrev-ref HEAD)"
sudo -u "$APP_USER" php "$APP_DIR/artisan" db:seed --force

echo "==> Queue worker, SSR server and scheduler"
sed -e "s#__APP_DIR__#$APP_DIR#g" -e "s#__APP_USER__#$APP_USER#g" \
    "$APP_DIR/deploy/supervisor.conf" > /etc/supervisor/conf.d/elim.conf
supervisorctl reread && supervisorctl update
echo "* * * * * $APP_USER cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/elim

echo "==> HTTPS certificate (Let's Encrypt)"
certbot --nginx -d "$DOMAIN" -m "$EMAIL" --agree-tos --non-interactive --redirect

echo
echo "Done: https://$DOMAIN"
echo "Next: php artisan core:create-superadmin you@example.org   (in $APP_DIR)"
