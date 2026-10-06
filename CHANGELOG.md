# Changelog

## 1.1.0 — Production-ready release

Upgrading an existing install: `bash setup.sh --update` (keeps all data,
migrates every school, adds the new roles).

### Security fixes
- **License enforcement is now live.** Suspending, revoking or expiring a
  license used to block only *new* logins; anyone already signed in kept
  working. Every school request is now checked (`EnsureLicenseActive`).
- **Role-wise access is enforced on every endpoint.** Before, any signed-in
  user (including a parent or student account) could delete students,
  collect fees or change records. Routes are now gated per role (see
  README → Roles).
- **Control-plane routes accept only owner accounts** (`EnsureCentralUser`).
- **Login brute-force protection:** 10 attempts per minute per email + IP
  on both login screens.
- **API tokens expire** (30 days by default; `sanctum.expiration` overrides).
- School admins can no longer grant `super-admin`, delete their own account,
  or remove/demote the last admin (lockout protection).
- Per-school permission cache keys, so schools can never share a cached
  role list.
- CSV exports neutralise spreadsheet formula injection.

### Bug fixes
- Saving attendance twice for the same day crashed (HTTP 500) on SQLite: the
  date was stored with a time part, so the upsert never matched. Fixed.
- Attendance accepted unknown student IDs (database error); now a clear 422.
- "Today" was computed in UTC, so an Indian school saw the wrong day for the
  first 5½ hours. Each school now has its own time zone (default
  Asia/Kolkata, set in Settings).
- An unknown or missing School ID returned a server error; now 404 / 400
  with a readable message.
- User search combined with a role filter returned users of other roles
  (`OR` precedence bug). Same fix applied to library and staff search.
- Admission numbers could collide after a deletion (duplicate-key 500).
- A failed school provisioning left a half-created school with its license
  key consumed; it now rolls back and frees the key.
- Suspending a school directly left its license "active" (and vice versa);
  the two now always agree. A revoked key can no longer be re-activated.
- Hostel occupancy could exceed capacity; library available copies could
  exceed total copies; books on loan / occupied rooms could be deleted.
- The domain root (`/`) returned 404 because of the tenancy package's sample
  routes; it now opens the app.
- `setup.sh` silently produced a broken install if Laravel's `install:api`
  could not run Composer (Sanctum missing). It now verifies Sanctum.
- `setup.sh` always installed the newest Laravel, so a future major release
  could break new installs. Versions are now pinned to the tested set
  (Laravel 13, Sanctum 4, spatie/laravel-permission 8, stancl/tenancy 3.10).
- `setup.sh` no longer deletes an existing folder without asking.

### New features
- **Web app covers every module** (previously 5 of 13): dashboard,
  students, admissions, staff, attendance register, exams & results,
  timetable grid, fees, library, transport with live map, hostel,
  announcements, reports, users & roles, settings — plus the full owner
  console (overview, schools, licenses, issue, provision).
- Role-aware navigation; mobile layout with slide-out menu; dark mode.
- **Fees:** partial payments, payment modes, automatic invoice & receipt
  numbers, printable receipts with the school letterhead, "invoice a whole
  class" in one step, due dates, student fee status kept in sync.
- **Reports:** collection by class, attendance averages, grade
  distribution, admissions funnel, and Excel-ready CSV exports (students,
  fees, attendance, results, staff).
- Attendance register per class with "mark all present"; attendance % is
  recalculated automatically.
- Exams grade automatically (A+ … F) unless a grade is entered.
- Dashboard: 7-day attendance trend, pending admissions, books on loan,
  recent announcements, quick actions.
- New roles: **accountant** and **driver** (drivers share their bus's GPS
  location from their phone).
- Owner console: renew/extend licenses, change plans, unused-key picker
  when provisioning, auto-generated admin password and a hand-over note.
- New school provisioning seeds the school name, currency, academic year
  and time zone.
- `GET /api/health` for uptime monitors.
- The web app is served by Laravel at `/app.html`, so one URL serves both
  and no CORS setup is needed.
- `setup.sh --update` upgrades an install in place.

### Testing
- `test-api.sh` grew from 11 to 69 end-to-end checks (every module, roles,
  live license enforcement, partial payments) and is safe to re-run.
- CI now runs the same `setup.sh` customers run, the full test suite, and
  the upgrade path.
