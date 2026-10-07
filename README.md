# Scholar ERP

A multi-tenant school-management platform by **Chenthur Info Tech**. One
installation serves any number of schools. Each school gets its own isolated
database and runs only while it holds a license key that you issue.

```
 Browser / phone ──► Laravel API ──► one database per school
   (app.html)          (backend/)       (fully isolated)
                           ▲
           Owner console (Chenthur Info Tech):
           issue & renew licenses · provision · suspend schools
```

Current version: **1.2.0** (see [CHANGELOG.md](CHANGELOG.md)).

## What's included

| Module | What schools can do |
|---|---|
| Dashboard | Live KPIs, 7-day attendance trend, pending work, quick actions |
| Students | Records, guardians, class filters, fee & attendance status, CSV export |
| Admissions | Applications → approve / reject → enroll as a student in one click |
| Staff & HR | Staff directory, departments, daily status |
| Attendance | Class register (P / A / L), mark-all, automatic attendance % |
| Exams & Results | Marks entry, automatic grading, per-exam and per-class views |
| Timetable | Weekly grid per class, click a cell to edit |
| Fee Collection | Invoices, whole-class invoicing, partial payments, payment modes, printable receipts |
| Library | Catalogue, issue / return, overdue tracking |
| Transport | Bus fleet, live GPS from the driver's phone, map view |
| Hostel | Rooms, beds, allot / vacate, occupancy |
| Announcements | Drafts, send to parents / staff / everyone, in-app feed |
| Reports | Collection by class, attendance, grades, admissions, Excel-ready CSV downloads |
| Users & Roles | Accounts with role-based access (below) |
| Settings | School profile (shown on receipts), currency, time zone, license status |

## Roles

| Role | Can use |
|---|---|
| **admin** | Everything in their school |
| **accountant** | Fees, receipts, reports, student list |
| **teacher** | Attendance, exams, library, students & staff (read), announcements |
| **driver** | Their bus and live location sharing |
| **parent / student** | Timetable and announcements |

Access is enforced by the API on every request, not just hidden in the UI.

The **owner** (you) signs in to the separate owner console to issue license
keys (`CHEN-XXXX-XXXX-XXXX`), provision schools, renew, suspend or revoke.
Suspending a school or its key locks out its users immediately, including
anyone already signed in.

## Try it locally (about 5 minutes)

Needs PHP 8.2+ (with `pdo_sqlite`, `mbstring`, `openssl`, `curl`) and Composer.

```bash
bash setup.sh
```

```bash
cd ../scholar-app && php artisan serve
```

Open **http://127.0.0.1:8000**. The demo school ID is `greenfield`; every
demo password is `password123`:

| Login | Role |
|---|---|
| `admin@greenfield.test` | admin |
| `accountant@greenfield.test` | accountant |
| `teacher@greenfield.test` | teacher |
| `driver@greenfield.test` | driver |
| `parent@greenfield.test` | parent |
| `owner@chenthur.tech` / `ChangeMe123!` | owner console (switch tab on the login screen) |

When the server is local, the login screen shows one-click demo buttons.

Run the 69 end-to-end API checks (with the server running):

```bash
bash test-api.sh
```

## Repository layout

| Path | What it is |
|---|---|
| `frontend/app.html` | The web app (school portal + owner console). `setup.sh` installs it into Laravel's `public/`. |
| `frontend/index.html` | Offline sales demo: click through every module with no server. |
| `backend/` | Product code overlaid onto a fresh Laravel app (models, controllers, middleware, migrations, seeders, routes). API reference in [backend/README.md](backend/README.md). |
| `setup.sh` | Fresh install (`bash setup.sh`) or in-place upgrade (`bash setup.sh --update`). |
| `test-api.sh` | End-to-end API test suite. |
| `docs/WINDOWS.md` + `windows/` | Hosting on a Windows PC/server with IIS: one-command install, Cloudflare Tunnel for your domain, nightly backups. |
| `docs/DEPLOYMENT.md` | Putting it on a Linux server (VPS, MySQL, HTTPS, backups, updates). |
| `.github/workflows/ci.yml` | CI runs `setup.sh`, the full test suite and the upgrade path on every push. |

## Going live

Windows: [docs/WINDOWS.md](docs/WINDOWS.md). Linux: [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md). Before the first customer:

1. Set `CENTRAL_ADMIN_EMAIL` / `CENTRAL_ADMIN_PASSWORD` **before** seeding,
   or change the owner password from the console straight after.
2. `APP_ENV=production`, `APP_DEBUG=false`, HTTPS only.
3. Remove the `greenfield` demo school, or don't run the demo seeder.
4. Have a lawyer review `LICENSE` (it's a template).

## Upgrading an existing install

```bash
git pull
SCHOLAR_APP_DIR=/path/to/scholar-app bash setup.sh --update
```

This copies the new code, migrates the central database and every school
database, and adds any new roles. Data is kept.

## Known limits (good next steps)

- **SMS / e-mail / push delivery:** announcements are delivered in-app and
  recipients are counted. Plug a gateway (MSG91, Twilio, FCM, SMTP) into
  `CommunicationController::send` to deliver them externally.
- **Online fee payment:** payments are recorded by staff. A Razorpay or
  Stripe checkout can call the existing `POST /api/fees/{id}/collect`.
- **Parent ↔ child linking:** parent accounts see school-wide timetable and
  announcements, not a per-child view yet.
- **Mobile apps:** the web app is mobile-friendly. Native Flutter apps can
  use the same API.

## License

Proprietary © Chenthur Info Tech. See [LICENSE](LICENSE).
