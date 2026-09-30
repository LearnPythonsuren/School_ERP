# Hosting — from Git to a live server

## 1. Put it on GitHub (or GitLab)

You have two ways. Pick one.

### Option A — GitHub CLI (fastest)

Requires the `gh` tool and a one-time `gh auth login`.

```bash
cd scholar-erp
gh repo create scholar-erp --private --source=. --remote=origin --push
```

That creates the repo under your account and pushes `main` in one step.
Use `--public` instead of `--private` only if you want it visible to
everyone (keep it private while it's a product you sell).

### Option B — Web + git (no CLI)

1. On github.com, click **New repository**, name it `scholar-erp`, choose
   **Private**, and **do not** add a README/.gitignore (this repo already
   has them).
2. Copy the repo URL it shows you, then:

```bash
cd scholar-erp
git remote add origin https://github.com/<your-username>/scholar-erp.git
git branch -M main
git push -u origin main
```

If GitHub asks for a password, use a **Personal Access Token** (Settings →
Developer settings → Tokens), not your account password.

### Starting from the bundle we shipped

If you downloaded `scholar-erp.bundle`, you can reconstruct the full repo
with history and then follow Option A or B:

```bash
git clone scholar-erp.bundle scholar-erp
cd scholar-erp
```

## 2. Later: deploy to a hosting platform

**Backend (Laravel).** Good options, easiest first:
- **Laravel Cloud** or **Laravel Forge + a VPS** (DigitalOcean, Hetzner, AWS
  Lightsail) — purpose-built for Laravel; connect the GitHub repo and it
  deploys on push.
- **Railway** / **Render** — connect the repo, add a MySQL database, set the
  `.env` variables, done.
  Build steps: `composer install`, `php artisan migrate`,
  then `php artisan tenants:migrate` for each school.

**Frontend (static).** The `frontend/` folder is a single HTML file, so any
static host works: **Netlify**, **Vercel**, **Cloudflare Pages**, or
**GitHub Pages**. Point it at the `frontend/` directory. When you wire it to
the real API, set the API base URL and it's production-ready.

**Environment.** Never commit `.env`. On the host, set the DB credentials,
`APP_KEY` (`php artisan key:generate`), and your central domain there.
