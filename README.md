# Scholar ERP

A multi-tenant school-management platform by **Chenthur Info Tech**. One
codebase serves unlimited schools, each with its own isolated database, and
each running only on a license key the vendor issues.

```
Web dashboard / Flutter apps  --->  Laravel API  --->  one database per school
      (frontend/)                    (backend/)         (isolated per tenant)
                                         ^
                          Central control plane (you / Chenthur Info Tech):
                          issue license keys - provision & suspend schools
```

## Repository layout

| Path | What it is |
|------|------------|
| `frontend/index.html` | Offline demo dashboard — all 13 modules, click-around, no server needed. |
| `frontend/app.html` | **Live client** — school login **and** owner control-plane login, wired to the API. |
| `backend/`  | Laravel 12 multi-tenant REST API (Sanctum auth, Spatie roles, licensing). Setup in `backend/README.md`. |
| `docs/`     | `HOSTING.md` - push-to-GitHub + deployment guide. |
| `setup.sh`  | One command that builds a runnable backend from `backend/`. |
| `test-api.sh` | Runs the control-plane + CRUD + license checks against a running server. |
| `.github/workflows/ci.yml` | CI: builds and tests the whole thing on every push. |

## Modules (all with full CRUD)

Students - Fees - Staff - Exams - Attendance - Admissions - Timetable -
Library - Transport (GPS) - Hostel - Communication - Reports - Settings,
plus **User management** with role assignment.

## Two levels of access

- **Super Admin (Chenthur Info Tech - the product owner).** Central control
  plane: issue license keys (`CHEN-XXXX-XXXX-XXXX`), provision schools,
  suspend/re-activate any school. Default: `owner@chenthur.tech` /
  `ChangeMe123!` (change via `CENTRAL_ADMIN_EMAIL` / `CENTRAL_ADMIN_PASSWORD`).
- **School Admin (per school).** Manages their own users and role-wise access
  (admin / teacher / student / parent). A school only works while its license
  is active.

## Quick start

**Frontend** (no build):

```bash
open frontend/index.html      # or: npx serve frontend
```

**Backend** (needs PHP 8.2+ and Composer):

```bash
bash setup.sh                 # builds ../scholar-app, migrates, seeds a licensed demo school
cd ../scholar-app && php artisan serve
# in a second terminal, from the repo:
bash test-api.sh
```

Logins after setup:
- School admin: `admin@greenfield.test` / `password123` (header `X-Tenant: greenfield`)
- Owner control plane: `owner@chenthur.tech` / `ChangeMe123!` (no `X-Tenant`)

## Deploy

See `docs/HOSTING.md`. Backend -> Laravel Cloud / Forge / Railway / Render;
frontend -> Netlify / Vercel / Cloudflare Pages / GitHub Pages.

## License

Proprietary (c) Chenthur Info Tech. See `LICENSE`.

## Using the live client (`frontend/app.html`)

Open `frontend/app.html`, enter your API URL (e.g. `http://127.0.0.1:8000`), then:
- **School login** — School ID + email + password (e.g. `greenfield` / `admin@greenfield.test` / `password123`). Gives the school dashboard, students, users & roles, fees, and license status — all live.
- **Owner (control plane)** — `owner@chenthur.tech` / `ChangeMe123!`. Issue license keys, provision schools, and suspend/activate any school.
