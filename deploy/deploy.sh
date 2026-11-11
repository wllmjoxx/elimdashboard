#!/usr/bin/env bash
#
# Update the app on the server to the latest commit of a branch and rebuild.
# Run as the app user from anywhere (GitHub Actions calls this over SSH):
#
#   bash /var/www/elim/deploy/deploy.sh main
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."
BRANCH="${1:-main}"

# Visitors see a short maintenance page while the build runs (skipped on the
# very first run, before dependencies exist).
[ -d vendor ] && php artisan down --retry=15 --refresh=15 || true
trap 'php artisan up >/dev/null 2>&1 || true' EXIT

echo "==> Code ($BRANCH)"
git fetch --prune origin
git checkout -q "$BRANCH"
git reset -q --hard "origin/$BRANCH"

echo "==> PHP dependencies"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress
grep -q '^APP_KEY=base64:' .env || php artisan key:generate --force

echo "==> Front end (client and SSR)"
npm ci --no-audit --no-fund --loglevel=error
npm run build:ssr

echo "==> Database and caches"
php artisan migrate --force
php artisan storage:link >/dev/null 2>&1 || true
php artisan optimize

echo "==> Restart background processes"
php artisan queue:restart
# Supervisor starts the SSR server again with the new bundle.
php artisan inertia:stop-ssr >/dev/null 2>&1 || true

echo "Deployed $(git rev-parse --short HEAD)."
