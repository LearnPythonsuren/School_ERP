# Going live: infrastructure for the first stage

You have no customers yet, so spend almost nothing. Run on this Windows PC,
put it on your domain through Cloudflare, and move to a rented server only
when a school trusts you with real student data.

## Stage 0: now (demos + first pilot school)

| Need | Choice | Cost |
|---|---|---|
| Server | **This PC** (Scholar ERP already runs here under IIS) | ₹0 |
| Domain | e.g. `scholarerp.in` or `chenthurinfotech.com` | ≈ ₹600–1,200 / year |
| HTTPS + reaching the PC from the internet | **Cloudflare free plan + Cloudflare Tunnel** (no router changes, no static IP) | ₹0 |
| Business e-mail (`sales@yourdomain`) | Zoho Mail free plan, or Cloudflare Email Routing → your Gmail | ₹0 |
| WhatsApp for sales | WhatsApp Business app on a separate SIM | ≈ ₹200 / month |
| Uptime alerts | UptimeRobot free: check `https://yourdomain/api/health` every 5 min | ₹0 |
| Off-PC backups | Sync `C:\ScholarERP\backups` with OneDrive or Google Drive | ₹0 |
| Power cuts | A small UPS for the PC and router | ≈ ₹3,000–5,000 one-time |

**Total: about ₹1,000 a year plus a UPS.** Free-plan limits and prices
change, so confirm on each provider's site when you sign up.

**Rules for a PC that is now a server**

- Sleep is already **Never** on this PC; keep it that way.
- Windows Update: set active hours (7 AM–7 PM) so it never restarts during school hours.
- Prefer a wired connection.
- Don't use this PC's admin account for daily browsing.

## Put it on your domain (one time, about 30 minutes)

You do steps 1–3 yourself, because they need your accounts and payment.

1. **Buy the domain.** `.com` can be bought directly at Cloudflare
   Registrar (simplest: DNS is set up automatically). For `.in`, buy at any
   Indian registrar, then in step 2 change its nameservers to Cloudflare's.
2. **Cloudflare (free):** create an account → *Add a site* → your domain →
   Free plan. If the domain came from another registrar, paste the two
   Cloudflare nameservers into the registrar's settings. Wait until
   Cloudflare says *Active* (minutes to a few hours).
3. **Create the tunnel:** Cloudflare dashboard → **Zero Trust → Networks →
   Tunnels → Create a tunnel → Cloudflared**, name it `scholar-erp`. Choose
   *Windows* and copy the long token (starts with `eyJ`) from the install
   command shown. **Don't share the token with anyone.** Then add a
   **Public hostname**: your domain (e.g. `scholarerp.in`; add `www` too if
   you like), service **HTTP**, URL `localhost:80`.
4. **On this PC** (PowerShell in the repo folder; click Yes on the admin prompt):
   ```powershell
   powershell -ExecutionPolicy Bypass -File windows\connect-domain.ps1 -Url https://scholarerp.in -TunnelToken eyJ...
   ```
   This installs Cloudflare's connector as a Windows service that starts
   with Windows, and tells the app its public address.
5. **Put your details in the marketing material:** edit
   `marketing\config.json` (phone, WhatsApp, email, `"website":
   "https://scholarerp.in"`), then run `marketing\build.ps1`, then
   `windows\install.ps1` once more to publish the updated home page.
6. **Test from your phone on mobile data (not Wi-Fi):** the home page, then
   **Login** → *Owner console*, then School ID `demo`.
7. Add the UptimeRobot check and the backup-folder sync from the table above.

Result: `https://yourdomain` shows the product website, `https://yourdomain/app.html`
is the app for every school, and the API is on the same address.

## Stage 1: before a school puts real student data in

A home or office PC is fine for demos. For a school's **real** records, a
power cut or internet outage during school hours costs you credibility.
Move to a small rented server (VPS) with servers in India when you sign
your first pilot or paying school:

| Provider (India region) | Size | Approx. cost |
|---|---|---|
| DigitalOcean (Bangalore) | 1–2 GB RAM | ≈ $6–12 / month |
| AWS Lightsail (Mumbai) | 1–2 GB RAM | ≈ $5–10 / month |
| Any Indian VPS host with a Mumbai/Chennai data centre | 2 GB RAM | ≈ ₹500–1,000 / month |

Prices are approximate; check before buying. Setup is in
[DEPLOYMENT.md](DEPLOYMENT.md), and the move from this PC is a file copy
([WINDOWS.md → Moving to Linux](WINDOWS.md#moving-to-linux-later)). Keep the
same Cloudflare Tunnel: install `cloudflared` on the server with the same
token, and the domain follows the server with no DNS change.

Keeping data in India also makes the data-protection conversation (DPDP Act)
with schools easier.

## Stage 2: 10+ schools

- 2–4 GB RAM VPS. Switch to MySQL when you pass about 20 active schools or
  many simultaneous users; each school keeps its own database.
- Off-site backups every night (another region or cloud storage), plus a
  restore test each month.
- A second, small "staging" copy for trying updates before the live one.
- Support line hours published on the website.
