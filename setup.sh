#!/usr/bin/env bash
# Scholar ERP — one-command local setup. Run from inside the repo:  bash setup.sh
# Requires PHP 8.2+ and Composer. Uses SQLite (no MySQL needed).
set -e
DIR="$(cd "$(dirname "$0")" && pwd)"
B="$DIR/backend"; APP="$DIR/../scholar-app"
command -v php >/dev/null || { echo "Install PHP 8.2+ first"; exit 1; }
command -v composer >/dev/null || { echo "Install Composer first"; exit 1; }

echo "==> Fresh Laravel at $APP"
rm -rf "$APP"; composer create-project laravel/laravel "$APP" --no-interaction --quiet
cd "$APP"
php artisan install:api --no-interaction
composer require spatie/laravel-permission stancl/tenancy --no-interaction --quiet
php artisan tenancy:install --no-interaction
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --no-interaction
php -r '$f="bootstrap/providers.php";$c=file_get_contents($f);if(strpos($c,"TenancyServiceProvider")===false){$c=preg_replace("/return \[\s*/","return [\n    App\\\\Providers\\\\TenancyServiceProvider::class,\n    ",$c,1);file_put_contents($f,$c);}'

echo "==> Overlay product code"
cp -r "$B"/app/* app/
cp -r "$B"/database/migrations/* database/migrations/
cp -r "$B"/database/seeders/* database/seeders/
cp "$B"/routes/api.php routes/api.php

echo "==> Configure tenancy + per-school tables (keep central token table central)"
sed -i.bak "s#'tenant_model' => .*#'tenant_model' => \\\\App\\\\Models\\\\Tenant::class,#" config/tenancy.php && rm -f config/tenancy.php.bak
mv database/migrations/0001_01_01_000000_create_users_table.php database/migrations/tenant/
for f in database/migrations/*personal_access_tokens*; do
  [ "$(basename "$f")" = "2024_01_01_000210_create_personal_access_tokens_table.php" ] || mv "$f" database/migrations/tenant/
done
mv database/migrations/*permission* database/migrations/tenant/
for kv in 'DB_CONNECTION=sqlite' 'CACHE_STORE=array' 'SESSION_DRIVER=array' 'QUEUE_CONNECTION=sync'; do
  k="${kv%%=*}"; grep -q "^$k=" .env && sed -i.bak "s#^$k=.*#$kv#" .env || echo "$kv" >> .env; done
sed -i.bak '/^DB_HOST=/d;/^DB_PORT=/d;/^DB_DATABASE=/d;/^DB_USERNAME=/d;/^DB_PASSWORD=/d' .env; rm -f .env.bak
touch database/database.sqlite
php artisan key:generate; php artisan config:clear

echo "==> Migrate + seed control plane owner + a licensed demo school"
php artisan migrate --force
php artisan db:seed --class=CentralAdminSeeder --force
# demo school with an active license so you can log straight in
php artisan tinker --execute="\$t=App\Models\Tenant::create(['id'=>'greenfield','name'=>'Greenfield','licensee'=>'Greenfield','license_status'=>'active']); \$t->domains()->create(['domain'=>'greenfield.localhost']);"
php artisan tenants:seed --class=RoleSeeder --force
php artisan tenants:seed --class=DemoDataSeeder --force
php artisan tinker --execute="App\Models\Tenant::find('greenfield')->run(function(){ \$u=App\Models\User::create(['name'=>'Admin','email'=>'admin@greenfield.test','password'=>bcrypt('password123')]); \$u->assignRole('admin'); });"

echo ""
echo "=================================================================="
echo " DONE. Backend ready at: $APP"
echo " Start:  cd $APP && php artisan serve"
echo ""
echo " School admin login : admin@greenfield.test / password123  (X-Tenant: greenfield)"
echo " Owner control plane: owner@chenthur.tech   / ChangeMe123!  (no X-Tenant)"
echo "                      ^ change via CENTRAL_ADMIN_EMAIL / CENTRAL_ADMIN_PASSWORD"
echo " Test:   bash \"$DIR/test-api.sh\""
echo "=================================================================="
