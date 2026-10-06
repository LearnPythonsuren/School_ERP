# Scholar ERP — Backend (Laravel 13, multi-tenant)

This folder holds the **product code only**. `setup.sh` (repo root) creates
a fresh Laravel app with the tested package versions and copies these files
over it:

| Package | Version | Used for |
|---|---|---|
| laravel/laravel | ^13.0 | framework |
| laravel/sanctum | ^4.0 | API tokens |
| spatie/laravel-permission | ^8.0 | roles |
| stancl/tenancy | ^3.10 | one database per school |

```
app/Http/Middleware/      IdentifySchool, EnsureLicenseActive, EnsureRole, EnsureCentralUser
app/Http/Controllers/Api  school modules        app/Http/Controllers/Central  owner console
app/Models/               Tenant, License, CentralUser, Student, FeeInvoice, …
database/migrations/      central tables          database/migrations/tenant/   per-school tables
database/seeders/         CentralAdminSeeder, RoleSeeder, DemoDataSeeder
routes/api.php            every endpoint + the role matrix
routes/web.php            `/` → the web app      routes/tenant.php  (intentionally empty)
```

## How tenancy works

- Every school request carries `X-Tenant: <school-id>`. `IdentifySchool`
  looks the school up, switches the database connection to that school's
  database, and applies the school's time zone. No query needs a
  `school_id` column: isolation is at the database level.
- `IdentifySchool` extends the package's `InitializeTenancyByRequestData`
  on purpose. That gives it tenancy's top middleware priority, so it runs
  **before** `auth:sanctum` and tokens are looked up in the school's
  database. Don't swap it for a plain middleware.
- `EnsureLicenseActive` runs on every school request: a suspended, revoked
  or expired license returns `403` with `"code": "license_inactive"`.
- The central database holds `tenants`, `domains`, `licenses`,
  `central_users` and the owner's own `personal_access_tokens`.

## Conventions

- JSON in, JSON out. Send `Accept: application/json`.
- Lists are paginated: `?page=`, `?per_page=` (max 200). The response has
  `data`, `current_page`, `last_page`, `total` (students nest these under
  `meta`).
- Validation errors are `422` with `{ message, errors: { field: [..] } }`.
- Login is rate-limited to 10 tries a minute per e-mail + IP (`429`).
- Tokens last 30 days (set `sanctum.expiration` in minutes to change).

## API reference

`H` = needs `X-Tenant`. `A` = needs `Authorization: Bearer <token>`.
Roles in brackets; `super-admin` passes everything.

### Public
```
GET  /api/health                                   uptime probe
```

### Owner console (central — no X-Tenant)
```
POST /api/central/login            { email, password } → { token, user{…, must_change_password}, vendor }
GET  /api/central/me | POST /api/central/logout | POST /api/central/change-password
GET  /api/central/overview         school & license counts, plan mix
GET  /api/central/licenses         ?status=&search=&unassigned=1
POST /api/central/licenses         { licensee, plan?, expires_at? } → { key: "CHEN-XXXX-XXXX-XXXX", … }
GET|PUT /api/central/licenses/{id} PUT { licensee?, plan?, expires_at? } renews / changes plan (mirrors to the school)
POST /api/central/licenses/{id}/suspend | /activate | /revoke      (revoked is permanent)
GET  /api/central/schools
POST /api/central/schools          { id, name, license_key, admin_name, admin_email, admin_password, domain? }
GET  /api/central/schools/{id}
POST /api/central/schools/{id}/suspend | /activate                 (keeps the license in step)
```

### School: account (H; A except login)
```
POST /api/login                    { email, password } → { token, user{ id, name, email, phone, roles[] } }
GET  /api/me
POST /api/logout | /api/logout-all
POST /api/change-password          { current_password, new_password, new_password_confirmation }
```

### School: modules (H, A)
```
GET  /api/settings | /api/license | /api/timetable?class= | /api/announcements      [any role]
PUT  /api/settings                 { settings: { school_name, address, phone, email, currency, timezone, academic_year, … } }   [admin]

GET  /api/dashboard                                                                  [admin, teacher, accountant]
GET  /api/students?search=&class=&fee_status=      GET /api/students/{id}           [admin, teacher, accountant]
POST|PUT|DELETE /api/students[/{id}]                                                 [admin]

GET  /api/admissions?status=&search=   GET /api/admissions/{id}                     [admin, teacher]
POST|PUT|DELETE /api/admissions[/{id}]                                               [admin]
POST /api/admissions/{id}/approve | /reject | /enroll                                [admin]

GET  /api/staff?search=&department=    GET /api/staff/{id}                           [admin, teacher]
POST|PUT|DELETE /api/staff[/{id}]                                                    [admin]

GET  /api/attendance?date=&class=  → { date, marks{studentId:status}, students[], classes[], summary }   [admin, teacher]
POST /api/attendance               { date, marks: { "<studentId>": "present|absent|leave" } }             [admin, teacher]

GET|POST /api/exams                ?exam=&class=   { student_id, exam_name, maths, science, english, grade? }  [admin, teacher]
GET|PUT|DELETE /api/exams/{id}                                                                             [admin, teacher]

POST|PUT|DELETE /api/timetable[/{id}]   { class_name, day: Mon…Sat, period_no, subject, teacher?, start_time?, end_time? }  [admin]

GET|POST /api/fees                 ?status=&class=&search=&student_id=   { student_id?, amount, description?, due_on? }   [admin, accountant]
POST /api/fees/bulk                { class_name, description, amount, due_on? }   one invoice per student                [admin, accountant]
GET|PUT|DELETE /api/fees/{id}                                                                                         [admin, accountant]
POST /api/fees/{id}/collect        { amount? (default: full balance), mode?: cash|upi|card|bank|cheque|online }        [admin, accountant]
GET  /api/fees/{id}/receipt        → { school{…}, invoice{…} }                                                         [admin, accountant]

GET|POST /api/books                ?search=&category=        GET|PUT|DELETE /api/books/{id}       [admin, teacher]
POST /api/books/{id}/issue         { borrower, due_on? }      GET /api/book-issues?status=issued|overdue|returned
POST /api/book-issues/{id}/return

GET  /api/vehicles | /api/vehicles/{id}                                              [admin, teacher, driver]
POST /api/vehicles/{id}/location   { lat, lng, speed_kph? }                          [admin, driver]
POST|PUT|DELETE /api/vehicles[/{id}]                                                 [admin]

GET  /api/rooms | /api/rooms/{id}                                                    [admin, teacher]
POST|PUT|DELETE /api/rooms[/{id}]  POST /api/rooms/{id}/allot | /vacate              [admin]

POST /api/announcements            draft  { title, body, channel: sms|email|push, audience: all|parents|staff|class }  [admin, teacher]
POST /api/announcements/send       create + send (same body)                                                          [admin, teacher]
PUT|DELETE /api/announcements/{id}                                                                                    [admin]

GET  /api/reports/summary                                                            [admin, accountant]
GET  /api/reports/export/{students|fees|staff|attendance|results}   CSV (attendance: ?from=&to=)   [admin, accountant]

GET  /api/roles                    roles this admin may assign                       [admin]
GET|POST /api/users  GET|PUT|DELETE /api/users/{id}   { name, email, password, phone?, role }        [admin]
```

### Example

```bash
TOKEN=$(curl -s -X POST http://127.0.0.1:8000/api/login \
  -H "Accept: application/json" -H "Content-Type: application/json" -H "X-Tenant: greenfield" \
  -d '{"email":"admin@greenfield.test","password":"password123"}' | php -r 'echo json_decode(stream_get_contents(STDIN))->token;')

curl http://127.0.0.1:8000/api/dashboard \
  -H "Accept: application/json" -H "X-Tenant: greenfield" -H "Authorization: Bearer $TOKEN"
```

## Extending

- **New module:** model + tenant migration (`database/migrations/tenant/`),
  controller in `Api/`, then add routes inside the right role group in
  `routes/api.php`. Run `bash setup.sh --update`.
- **New role:** add it to `RoleSeeder::ROLES`, use it in an `EnsureRole`
  group, and run `bash setup.sh --update` (it re-runs `RoleSeeder` for every
  school).
- **SMS / e-mail delivery:** dispatch a job from `CommunicationController::send`.
- **Online payments:** after the gateway confirms, call the same logic as
  `FeeController::collect` with `mode: online`.
