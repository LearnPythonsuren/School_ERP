# Hosting on Windows (IIS)

Run Scholar ERP as an always-on server on a Windows 10/11 Pro or Windows
Server machine. When you're ready to move to Linux, see
[Moving to Linux](#moving-to-linux-later); it's a file copy.

| Piece | What it is |
|---|---|
| `C:\ScholarERP\php` | Private PHP copy with a production `php.ini` |
| `C:\ScholarERP\app` | The Laravel app (code + `database\` with one SQLite file per school) |
| `C:\ScholarERP\backups` | Nightly zip backups (last 14 days) |
| `C:\ScholarERP\logs` | Install logs and PHP errors (Laravel logs: `app\storage\logs`) |
| IIS site **ScholarERP** | Serves the app on port 80 through PHP FastCGI |
| Task **Scholar ERP backup** | Runs `windows\backup.ps1` every night at 02:00 |

## 1. Prerequisites (one time)

```powershell
winget install PHP.PHP.8.3
```

```powershell
winget install Git.Git
```

IIS is turned on by the installer if it isn't already.

## 2. Install

From the repository folder:

```powershell
powershell -ExecutionPolicy Bypass -File windows\install.ps1
```

Click **Yes** on the Windows admin prompt. The script:

1. copies PHP to `C:\ScholarERP\php` and enables the needed extensions;
2. downloads Composer and builds the app in production mode;
3. installs IIS URL Rewrite (from Microsoft) if missing, registers PHP with
   IIS, and creates the **ScholarERP** site on port 80. It stops IIS's
   "Default Web Site" welcome page, which also uses port 80;
4. gives the site write access only to `storage`, `bootstrap\cache` and
   `database`;
5. opens port 80 for your local network and schedules the nightly backup;
6. checks `http://localhost/api/health`.

Options: `-WithDemo` adds the `greenfield` demo school (don't use it on an
internet-facing server: the demo passwords are public). `-Port 8080` uses
another port. `-InstallDir D:\ScholarERP` installs elsewhere.

**Owner login:** a random owner password is generated and saved to
`C:\ScholarERP\FIRST-LOGIN.txt`, which only Administrators can read. Sign in
on the **Owner console** tab, change the password, then delete that file.

Open `http://localhost` on this PC, or `http://<this-PC-IP>` from another
device on the same network.

## 3. Put it on the internet with your domain

### Recommended: Cloudflare Tunnel

No router changes, no static IP, and HTTPS is automatic. It works on normal
broadband connections, including ones where port forwarding is impossible.

1. Add your domain to Cloudflare (free plan) and switch its nameservers to
   Cloudflare's.
2. Cloudflare dashboard → **Zero Trust → Networks → Tunnels → Create a
   tunnel → Cloudflared**. Name it `scholar-erp`, then copy the token, the
   long `eyJ…` string in the install command shown.
3. In the tunnel's **Public hostname** tab add: subdomain `erp`, your
   domain, service type **HTTP**, URL `localhost:80`.
4. On this PC:
   ```powershell
   powershell -ExecutionPolicy Bypass -File windows\connect-domain.ps1 -Url https://erp.yourdomain.com -TunnelToken eyJ...
   ```
   This installs `cloudflared` as a Windows service (it starts with Windows)
   and records the address in the app.

Keep port 80 closed to the internet: the tunnel makes the connection
outbound, so nothing needs to be opened.

### Alternative: port forwarding

Only if you have a static public IP and router access: forward TCP 80 and
443 to this PC, point an `A` record at your IP, add an HTTPS binding to the
ScholarERP site with a certificate from [win-acme](https://www.win-acme.com/),
then run `connect-domain.ps1 -Url https://erp.yourdomain.com` (without
`-TunnelToken`).

## 4. Keep this PC reliable

It's now a server, so:

- **Power & sleep:** set *Sleep* to **Never** (Settings → System → Power).
- **Windows Update:** set active hours, or schedule restarts for nights.
  IIS, the tunnel and backups all start automatically after a reboot.
- **Wired network** rather than Wi-Fi if possible, and a UPS if power cuts
  are common.
- **Copy backups off the machine** daily: point OneDrive / Google Drive at
  `C:\ScholarERP\backups`, or copy them to a USB drive or NAS.
- **Capacity:** fine for several schools and dozens of simultaneous users.
  Beyond that, move to Linux with MySQL.

## 5. Everyday operations

| Task | How |
|---|---|
| Update to a new version | `git pull`, then run `windows\install.ps1` again (data is kept; a copy of `database\` is saved to `backups\pre-update-…` first) |
| Back up now | `powershell -ExecutionPolicy Bypass -File windows\backup.ps1` |
| Restart the app | `Restart-WebAppPool ScholarERP` (admin PowerShell), or IIS Manager |
| See errors | `C:\ScholarERP\app\storage\logs\laravel.log` and `C:\ScholarERP\logs\php-errors.log` |
| Check it's up | open `/api/health`, or monitor it with UptimeRobot |

### Restoring a backup

1. Stop the site: `Stop-Website ScholarERP`.
2. Unzip the backup. Copy `database.sqlite` and the `tenant…` files back
   into `C:\ScholarERP\app\database\`, and delete any `*-wal` / `*-shm`
   files there.
3. Make sure `APP_KEY` in `app\.env` matches the backup's `env.txt`.
4. `Start-Website ScholarERP`.

## Moving to Linux later

The data is plain SQLite files, so the move is simple:

1. Set up the Linux server with `bash setup.sh` following
   [DEPLOYMENT.md](DEPLOYMENT.md) (SQLite works there too; MySQL is
   optional).
2. Stop the Windows site and take a backup with `backup.ps1`.
3. Copy `database.sqlite` and every `tenant…` file into the Linux app's
   `database/` folder, and copy `APP_KEY` from `env.txt` into the Linux `.env`.
4. Run `bash setup.sh --update` on Linux (applies any newer migrations).
5. Point the domain at the new server: in Cloudflare, install `cloudflared`
   on Linux with the same tunnel token, or change the DNS record.
6. Uninstall the Windows service when you're done:
   `cloudflared service uninstall`.

To switch to MySQL at the same time, ask for a migration script. Each
school's SQLite file is imported into its own `tenant…` MySQL database.
