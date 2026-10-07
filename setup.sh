#!/usr/bin/env bash
# Scholar ERP — one-command setup. Run from inside the repo.
#
#   bash setup.sh                 Fresh install into ../scholar-app (SQLite, demo school)
#   bash setup.sh --update        Upgrade an existing install in place: copy the latest
#                                 product code, run new migrations for every school.
#                                 Keeps all data.
#   SCHOLAR_APP_DIR=/path bash setup.sh      Choose the install folder.
#   SCHOLAR_SKIP_INSTALL=1                    Reuse a Laravel app that already has the
#                                             packages installed (used by CI caching).
#   SCHOLAR_PRODUCTION=1                      Production .env (no debug pages, file cache).
#   SCHOLAR_URL=https://erp.example.com       Public address (with SCHOLAR_PRODUCTION).
#   SCHOLAR_DEMO=0                            Skip the greenfield demo school.
#
# Requires PHP 8.2+ and Composer.
set -euo pipefail
DIR="$(cd "$(dirname "$0")" && pwd)"
B="$DIR/backend"
APP="${SCHOLAR_APP_DIR:-$DIR/../scholar-app}"
MODE="${1:-fresh}"

command -v php >/dev/null || { echo "Install PHP 8.2+ first"; exit 1; }

overlay() {
  echo "==> Copy product code"
  cp -r "$B"/app/* app/
  cp -r "$B"/database/migrations/* database/migrations/
  cp -r "$B"/database/seeders/* database/seeders/
  cp "$B"/routes/api.php routes/api.php
  cp "$B"/routes/web.php routes/web.php
  cp "$B"/routes/tenant.php routes/tenant.php
  cp "$DIR"/frontend/app.html public/app.html   # served at /app.html, same origin as the API
  # Behind a local reverse proxy / Cloudflare Tunnel, take the client IP and https
  # scheme from X-Forwarded-* — but only when the request comes from this machine.
  grep -q trustProxies bootstrap/app.php || sed -i.bak '/withMiddleware(function (Middleware $middleware): void {/{n;s#^\([[:space:]]*\)//[[:space:]]*$#\1$middleware->trustProxies(at: ["127.0.0.1", "::1"]);#;}' bootstrap/app.php && rm -f bootstrap/app.php.bak
}

if [ "$MODE" = "--update" ]; then
  [ -f "$APP/artisan" ] || { echo "No install found at $APP (set SCHOLAR_APP_DIR)"; exit 1; }
  cd "$APP"
  overlay
  php artisan migrate --force
  php artisan tenants:migrate --force
  php artisan tenants:seed --class=RoleSeeder --force
  php artisan optimize:clear
  echo "Updated $APP — all schools migrated."
  exit 0
fi

if [ "${SCHOLAR_SKIP_INSTALL:-0}" != "1" ]; then
  command -v composer >/dev/null || { echo "Install Composer first"; exit 1; }
  if [ -e "$APP" ]; then
    echo "$APP already exists. Delete it first, or use: bash setup.sh --update"; exit 1
  fi
  echo "==> Fresh Laravel at $APP"
  # Versions are pinned to the combination this release is tested against.
  composer create-project "laravel/laravel:^13.0" "$APP" --no-interaction --quiet
  cd "$APP"
  php artisan install:api --no-interaction --without-migration-prompt
  # install:api silently skips Sanctum if it can't run Composer itself — make sure.
  composer require "laravel/sanctum:^4.0" "spatie/laravel-permission:^8.0" "stancl/tenancy:^3.10" --no-interaction --quiet
  ls database/migrations/*personal_access_tokens* >/dev/null 2>&1 || php artisan vendor:publish --tag=sanctum-migrations --no-interaction
  php artisan tenancy:install --no-interaction
  php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --no-interaction
else
  cd "$APP"
fi
php -r '$f="bootstrap/providers.php";$c=file_get_contents($f);if(strpos($c,"TenancyServiceProvider")===false){$c=preg_replace("/return \[\s*/","return [\n    App\\\\Providers\\\\TenancyServiceProvider::class,\n    ",$c,1);file_put_contents($f,$c);}'

overlay

echo "==> Configure tenancy + per-school tables (keep central token table central)"
sed -i.bak "s#'tenant_model' => .*#'tenant_model' => \\\\App\\\\Models\\\\Tenant::class,#" config/tenancy.php && rm -f config/tenancy.php.bak
mv database/migrations/0001_01_01_000000_create_users_table.php database/migrations/tenant/
for f in database/migrations/*personal_access_tokens*; do
  [ "$(basename "$f")" = "2024_01_01_000210_create_personal_access_tokens_table.php" ] || mv "$f" database/migrations/tenant/
done
mv database/migrations/*permission* database/migrations/tenant/
SETTINGS=('DB_CONNECTION=sqlite' 'CACHE_STORE=array' 'SESSION_DRIVER=array' 'QUEUE_CONNECTION=sync')
if [ "${SCHOLAR_PRODUCTION:-0}" = "1" ]; then
  # file cache persists between requests, so login rate limits actually hold
  SETTINGS+=('APP_ENV=production' 'APP_DEBUG=false' "APP_URL=${SCHOLAR_URL:-http://localhost}" 'CACHE_STORE=file' 'LOG_LEVEL=warning')
fi
for kv in "${SETTINGS[@]}"; do
  k="${kv%%=*}"; if grep -q "^$k=" .env; then sed -i.bak "s#^$k=.*#$kv#" .env; else echo "$kv" >> .env; fi; done
sed -i.bak '/^DB_HOST=/d;/^DB_PORT=/d;/^DB_DATABASE=/d;/^DB_USERNAME=/d;/^DB_PASSWORD=/d' .env; rm -f .env.bak
# Several PHP workers share each SQLite file: wait for locks instead of failing,
# and use WAL so readers never block writers. School databases inherit this.
sed -i.bak "s/'busy_timeout' => null/'busy_timeout' => 10000/; s/'journal_mode' => null/'journal_mode' => 'wal'/; s/'synchronous' => null/'synchronous' => 'normal'/" config/database.php && rm -f config/database.php.bak
touch database/database.sqlite
php artisan key:generate --force; php artisan config:clear

echo "==> Migrate + seed control plane owner"
php artisan migrate --force
php artisan db:seed --class=CentralAdminSeeder --force

if [ "${SCHOLAR_DEMO:-1}" = "0" ]; then
  echo ""; echo " DONE. Backend ready at: $APP (no demo school — provision schools from the owner console)"
  exit 0
fi
echo "==> Licensed demo school"
# Demo school with a real license key, so the control plane shows it correctly.
php artisan tinker --execute="\$l=App\Models\License::create(['key'=>App\Models\License::generateKey(),'licensee'=>'Greenfield Public School','plan'=>'pro','status'=>'active','tenant_id'=>'greenfield']); \$t=App\Models\Tenant::create(['id'=>'greenfield','name'=>'Greenfield Public School','plan'=>'pro','licensee'=>\$l->licensee,'license_key'=>\$l->key,'license_status'=>'active']); \$t->domains()->create(['domain'=>'greenfield.localhost']);"
php artisan tenants:seed --class=RoleSeeder --force
php artisan tenants:seed --class=DemoDataSeeder --force
php artisan tinker --execute="App\Models\Tenant::find('greenfield')->run(function(){ \$u=App\Models\User::create(['name'=>'Admin','email'=>'admin@greenfield.test','password'=>bcrypt('password123')]); \$u->assignRole('admin'); });"

echo ""
echo "=================================================================="
echo " DONE. Backend ready at: $APP"
echo " Start:  cd \"$APP\" && php artisan serve"
echo " Then open http://127.0.0.1:8000  (the web app is served by Laravel)"
echo ""
echo " School ID: greenfield   (all passwords: password123)"
echo "   admin@greenfield.test       full access"
echo "   accountant@greenfield.test  fees + reports"
echo "   teacher@greenfield.test     attendance, exams, library"
echo "   driver@greenfield.test / parent@greenfield.test"
echo " Owner control plane: owner@chenthur.tech / ChangeMe123!  (change it!)"
echo " Test:   bash \"$DIR/test-api.sh\""
echo "=================================================================="
