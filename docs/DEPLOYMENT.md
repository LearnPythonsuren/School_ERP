# Deployment — from this repo to a live server

This guide puts Scholar ERP on one Linux server (Ubuntu 22.04/24.04 VPS on
DigitalOcean, Hetzner, AWS Lightsail, etc.). One server comfortably runs
dozens of schools; every school still gets its own database.

## 1. Server prerequisites

```bash
sudo apt update
sudo apt install -y nginx mysql-server redis-server unzip git \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-sqlite3 php8.3-mbstring \
  php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
```

(Ubuntu 22.04 needs the `ppa:ondrej/php` repository for PHP 8.3.)

## 2. Install the app

```bash
git clone <your-private-repo-url> /opt/scholar-erp-src
cd /opt/scholar-erp-src
SCHOLAR_APP_DIR=/var/www/scholar CENTRAL_ADMIN_EMAIL=you@yourcompany.com \
  CENTRAL_ADMIN_PASSWORD='a-long-unique-password' bash setup.sh
```

`setup.sh` builds a SQLite demo install. Now switch it to production
settings.

## 3. Production `.env` (`/var/www/scholar/.env`)

```ini
APP_NAME="Scholar ERP"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://erp.yourcompany.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=scholar_central
DB_USERNAME=scholar
DB_PASSWORD=change-me

CACHE_STORE=redis
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
REDIS_HOST=127.0.0.1
```

Notes:

- **The MySQL user must be allowed to create databases**: each new school
  gets its own `tenant<school-id>` database.
  ```sql
  CREATE DATABASE scholar_central CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER 'scholar'@'localhost' IDENTIFIED BY 'change-me';
  GRANT ALL PRIVILEGES ON *.* TO 'scholar'@'localhost';
  ```
  (Limit to `scholar_central.*` and `` `tenant%`.* `` if you prefer.)
- **Use Redis for the cache.** The tenancy package tags cache entries per
  school, which the `file` and `database` stores do not support, and login
  rate-limits need a cache that persists between requests (`array` doesn't).
- `APP_DEBUG=false` is essential: debug pages expose secrets.

Then create the production databases:

```bash
cd /var/www/scholar
php artisan config:clear
php artisan migrate --force
php artisan db:seed --class=CentralAdminSeeder --force
php artisan config:cache && php artisan route:cache
sudo chown -R www-data:www-data storage bootstrap/cache database
```

Don't create the `greenfield` demo school in production. Provision real
schools from the owner console.

## 4. Nginx + HTTPS

`/etc/nginx/sites-available/scholar`:

```nginx
server {
    server_name erp.yourcompany.com;
    root /var/www/scholar/public;
    index index.php;
    client_max_body_size 10m;

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
    location ~ /\.(?!well-known) { deny all; }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/scholar /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d erp.yourcompany.com
```

Open `https://erp.yourcompany.com`. The web app is served from the same
domain as the API, so it needs no configuration. Sign in on the **Owner
console** tab.

## 5. Onboarding a school (your sales flow)

1. Owner console → **Issue license**: enter the customer, plan and expiry.
2. **Provision school**: pick the key, type the school name (the School ID
   is suggested), and enter the admin's name and e-mail. A password is
   generated for you.
3. Copy the **hand-over note** and send it to the school admin.
4. The admin signs in, fills in **Settings** (address and phone appear on
   receipts) and creates staff accounts under **Users & Roles**.

Renewals: **Licenses → Renew / plan**. Non-payment: **Suspend** (reversible).
Contract ended: **Revoke** (permanent).

## 6. Backups

Back up the central database **and every school database**:

```bash
# /etc/cron.daily/scholar-backup
#!/bin/sh
D=/var/backups/scholar/$(date +%F); mkdir -p "$D"
for db in $(mysql -N -e "SHOW DATABASES LIKE 'scholar_central'; SHOW DATABASES LIKE 'tenant%';"); do
  mysqldump --single-transaction "$db" | gzip > "$D/$db.sql.gz"
done
find /var/backups/scholar -maxdepth 1 -mtime +14 -exec rm -rf {} +
```

Copy backups off the server (S3, Backblaze, another VPS). Test a restore
occasionally.

## 7. Updating to a new version

```bash
cd /opt/scholar-erp-src && git pull
SCHOLAR_APP_DIR=/var/www/scholar bash setup.sh --update
cd /var/www/scholar && php artisan config:cache && php artisan route:cache
```

`--update` migrates the central database and every school, and keeps all
data. Take a backup first.

## 8. Monitoring

- Uptime: point UptimeRobot (or similar) at `https://erp.yourcompany.com/api/health`.
- Errors: `storage/logs/laravel.log`.

## Other hosting options

- **Laravel Forge / Laravel Cloud:** connect the generated app folder's
  repository and use the same `.env` as above. The MySQL user still needs
  `CREATE DATABASE` rights.
- **Shared hosting** generally won't work: it rarely allows creating
  databases at runtime.
- **Hosting the web app separately** (Netlify, Vercel, etc.) is optional.
  If you do, run `php artisan config:publish cors` and set `allowed_origins`
  to that domain. Users then enter the API URL under "Server" on the login
  screen.

## Putting the source on GitHub (private)

```bash
gh repo create scholar-erp --private --source=. --remote=origin --push
```

Keep the repository private: it's the product you sell.
