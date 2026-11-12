#!/usr/bin/env bash
#
# Install or update GGP Elim on cPanel shared hosting (DomaiNesia). Run it in
# cPanel › Terminal after extracting a release zip into the app folder:
#
#   cd ~/elim && bash deploy/cpanel/install.sh
#
# Safe to run again: the first run prepares .env, later runs migrate and
# refresh the caches. The release zip already holds vendor/ and public/build,
# so the server needs neither Composer nor Node.
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/../.."
APP_DIR="$(pwd)"

# --- PHP 8.3+ ---------------------------------------------------------------
# The terminal's default `php` may be older than the site's PHP (MultiPHP /
# Select PHP Version), so look for a suitable binary.
PHP=""
for candidate in \
    "${PHP_BIN:-}" \
    /opt/cpanel/ea-php84/root/usr/bin/php /opt/cpanel/ea-php83/root/usr/bin/php \
    /opt/alt/php84/usr/bin/php /opt/alt/php83/usr/bin/php \
    php /usr/local/bin/php; do
    [ -n "$candidate" ] || continue
    command -v "$candidate" >/dev/null 2>&1 || continue
    if "$candidate" -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);' 2>/dev/null; then
        PHP="$candidate"
        break
    fi
done

if [ -z "$PHP" ]; then
    echo "PHP 8.3 atau lebih baru tidak ditemukan."
    echo "Pilih PHP 8.3/8.4 di cPanel › Select PHP Version, lalu jalankan lagi."
    exit 1
fi
echo "==> PHP: $PHP ($("$PHP" -r 'echo PHP_VERSION;'))"

missing=$("$PHP" -r '
    $need = ["bcmath", "ctype", "curl", "dom", "exif", "fileinfo", "gd", "mbstring", "openssl", "pdo_mysql", "tokenizer", "xml", "zip"];
    echo implode(" ", array_filter($need, fn ($e) => ! extension_loaded($e)));
')
if [ -n "$missing" ]; then
    echo "Ekstensi PHP belum aktif: $missing"
    echo "Centang di cPanel › Select PHP Version › Extensions, lalu jalankan lagi."
    exit 1
fi

artisan() { "$PHP" "$APP_DIR/artisan" "$@"; }

mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache
# Caches left by the previous release describe the old code. These are
# files; the database cache is left alone (its table may not exist yet).
for cache in config route view event; do artisan "$cache:clear" >/dev/null; done

# --- .env ---------------------------------------------------------------------
if [ ! -f .env ]; then
    cp deploy/cpanel/env.example .env
    chmod 600 .env
    artisan key:generate --force >/dev/null
    echo
    echo ".env dibuat dari deploy/cpanel/env.example dan APP_KEY sudah diisi."
    echo "Isi APP_URL, DB_* dan MAIL_* di .env (File Manager › Edit), lalu jalankan lagi:"
    echo "  cd $APP_DIR && bash deploy/cpanel/install.sh"
    exit 0
fi

for key in APP_URL DB_DATABASE DB_USERNAME DB_PASSWORD; do
    if ! grep -Eq "^${key}=.+" .env; then
        echo "$key di .env masih kosong. Isi dulu, lalu jalankan lagi."
        exit 1
    fi
done
grep -q '^APP_KEY=base64:' .env || artisan key:generate --force >/dev/null

# --- Install / update -----------------------------------------------------------
first_run=false
artisan migrate:status >/dev/null 2>&1 || first_run=true

if [ "$first_run" = false ]; then
    artisan down --retry=15 --refresh=15 >/dev/null 2>&1 || true
    trap 'artisan up >/dev/null 2>&1 || true' EXIT
fi

echo "==> Database"
artisan migrate --force

if [ "$first_run" = true ]; then
    # Roles, the GGP Elim branch and starter content. No dummy accounts
    # outside local/testing.
    artisan db:seed --force
fi

echo "==> File upload dan cache"
[ -e public/storage ] || artisan storage:link >/dev/null
artisan optimize
artisan queue:restart >/dev/null

echo
echo "Selesai. Versi: $(cat RELEASE 2>/dev/null || echo '?')"
if [ "$first_run" = true ]; then
    echo
    echo "Langkah berikutnya:"
    echo "  1. Buat akun Superadmin:"
    echo "     $PHP artisan core:create-superadmin email-anda@ggpelim.com --name=\"Nama Anda\""
    echo "  2. cPanel › Cron Jobs, tambahkan (Once Per Minute):"
    echo "     cd $APP_DIR && $PHP artisan schedule:run >> /dev/null 2>&1"
fi
