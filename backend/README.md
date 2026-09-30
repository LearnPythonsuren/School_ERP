# Scholar ERP — Backend (Laravel 12, Multi-Tenant)

A deployable, multi-tenant REST backend for a school ERP. One codebase
serves unlimited schools, each with its **own isolated database**. Token
auth (Sanctum), role-based access (Spatie), and clean JSON APIs that match
the Scholar ERP admin UI and are ready for the Flutter apps.

This folder contains the **product-specific files**. You drop them into a
fresh Laravel 12 app and install three well-known packages. This keeps the
download small and lets you always start from the latest secure Laravel.

---

## 1. Create the base app + install packages

```bash
# Fresh Laravel 12 app
composer create-project laravel/laravel scholar-erp
cd scholar-erp

# API scaffolding + Sanctum token auth
php artisan install:api

# Roles & permissions
composer require spatie/laravel-permission

# Multi-tenancy (database-per-tenant)
composer require stancl/tenancy
php artisan tenancy:install
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

Register the tenancy service provider if the installer didn't:
`bootstrap/providers.php` → add `App\Providers\TenancyServiceProvider::class`.

## 2. Copy these files in

Copy the contents of this folder over the matching paths in your new app
(same directory structure):

```
app/Models/*                      Tenant, User, Student, StaffMember,
                                  FeeInvoice, ExamResult, Attendance
app/Http/Controllers/Api/*        Auth, Dashboard, Student, Fee, Staff,
                                  Exam, Attendance
app/Http/Controllers/Central/*    TenantController (provision a school)
app/Http/Requests/*               StoreStudentRequest
app/Http/Resources/*              StudentResource
routes/api.php                    all API routes
database/migrations/*             central: school columns
database/migrations/tenant/*      per-school tables
database/seeders/*                RoleSeeder, DemoDataSeeder
```

Point the `Tenant` model in `config/tenancy.php` to `App\Models\Tenant::class`.

## 3. Migrate & seed

```bash
# Central DB (tenants, domains, + school columns)
php artisan migrate

# Provision a first school (or call the API in step 5)
php artisan tinker
>>> $t = App\Models\Tenant::create(['id'=>'greenfield','name'=>'Greenfield Public School']);
>>> $t->domains()->create(['domain'=>'greenfield']);

# Run the per-school migrations + seed demo data for every tenant
php artisan tenants:migrate
php artisan tenants:seed --class=RoleSeeder
php artisan tenants:seed --class=DemoDataSeeder
```

## 4. How multi-tenancy works here

- **Central domain** = your control panel (Super Admin). It owns the list of
  schools and provisions new ones.
- **Tenant requests** carry an `X-Tenant: <school-id>` header. The
  `InitializeTenancyByRequestData` middleware swaps the DB connection to
  that school before any controller runs — so every Eloquent query is
  automatically scoped to one school. No `where('school_id', ...)` anywhere;
  isolation is at the database level. (Prefer subdomains? Swap in
  `InitializeTenancyByDomain` and route per subdomain instead.)

## 5. API reference

All tenant routes require the `X-Tenant` header. Protected routes also need
`Authorization: Bearer <token>`.

```
# Provision a school (central)
POST /api/central/schools
  { id, name, plan, admin_name, admin_email, admin_password, domain? }

# Auth (tenant)
POST   /api/login            { email, password }  -> { token, user }
GET    /api/me
POST   /api/logout

# Modules (tenant, auth required)
GET    /api/dashboard
GET    /api/students?search=&class=&per_page=
POST   /api/students
GET|PUT|DELETE /api/students/{id}
GET    /api/fees?status=
POST   /api/fees
POST   /api/fees/{invoice}/collect
GET    /api/staff   POST /api/staff   PUT /api/staff/{id}   DELETE /api/staff/{id}
GET    /api/exams   POST /api/exams
GET    /api/attendance?date=YYYY-MM-DD
POST   /api/attendance   { date, marks: { studentId: "present|absent|leave" } }

GET    /api/admissions?status=pending
POST   /api/admissions              { applicant_name, class_applied, guardian_name?, guardian_phone? }
POST   /api/admissions/{id}/approve
POST   /api/admissions/{id}/reject
POST   /api/admissions/{id}/enroll  # creates a Student, marks application enrolled
DELETE /api/admissions/{id}

GET    /api/timetable?class=Class 10-A   # returns a day->period grid
POST   /api/timetable   { class_name, day, period_no, subject, teacher?, start_time?, end_time? }
DELETE /api/timetable/{id}

# Library
GET    /api/books?search=
POST   /api/books                    { title, author?, category?, isbn?, total_copies }
POST   /api/books/{id}/issue         { borrower, due_on? }
POST   /api/book-issues/{id}/return

# Transport GPS
GET    /api/vehicles
POST   /api/vehicles                 { bus_no, route_name?, driver?, capacity? }
POST   /api/vehicles/{id}/location   { lat, lng, speed_kph? }   # driver app pings this

# Hostel
GET    /api/rooms
POST   /api/rooms                    { block, room_no, capacity, warden? }
POST   /api/rooms/{id}/allot
POST   /api/rooms/{id}/vacate

# Communication
GET    /api/announcements
POST   /api/announcements            { title, body, channel:sms|email|push, audience:all|parents|staff|class }

# Reports & Settings
GET    /api/reports/summary
GET    /api/settings
PUT    /api/settings                 { settings: { key: value, ... } }
```

### Quick test

```bash
curl -X POST http://localhost:8000/api/login \
  -H "X-Tenant: greenfield" -H "Content-Type: application/json" \
  -d '{"email":"admin@greenfield.test","password":"password"}'

curl http://localhost:8000/api/dashboard \
  -H "X-Tenant: greenfield" -H "Authorization: Bearer <TOKEN>"
```

## 6. Connecting the front end

The admin UI you already have talks to a data layer. To point it at this
backend, replace its DB calls with `fetch`:

```js
const API = "https://your-server.com/api";
const headers = {
  "Content-Type": "application/json",
  "X-Tenant": SCHOOL_ID,
  "Authorization": "Bearer " + TOKEN,
};
// list students
const students = await fetch(`${API}/students`, { headers }).then(r => r.json());
// add a student
await fetch(`${API}/students`, { method: "POST", headers, body: JSON.stringify(payload) });
// collect a fee
await fetch(`${API}/fees/${invoiceId}/collect`, { method: "POST", headers });
// save attendance
await fetch(`${API}/attendance`, { method: "POST", headers,
  body: JSON.stringify({ date, marks }) });
```

The same endpoints serve the Flutter parent/student, staff, and driver apps.

---

## 7. Selling it — the two paths

**A) One-time source-code license (like the poster).**
Ship this repo + the front end as a zip. Buyer runs the steps above on their
own server. Add: an install wizard (`/install` route that writes `.env` and
runs migrations), a license-key check, and clear docs. Sell on CodeCanyon,
your own site, or Gumroad.

**B) Hosted SaaS (monthly plans).**
You host one deployment. Signup/checkout calls `POST /api/central/schools`
to provision a tenant automatically. Add billing (Stripe/Razorpay via
Laravel Cashier), gate access on `tenants.is_active` and `plan`, and give
each school a subdomain. Recurring revenue, you control updates.

Both reuse everything here — the plan/is_active columns and the provisioning
endpoint already anticipate the SaaS path.

## 8. What to build next (roadmap to parity)

All core modules are now built: Students, Fees, Staff, Exams, Attendance,
Admissions, Timetable, Library, Transport (GPS location endpoint),
Hostel, Communication, Reports, and Settings. What remains to reach full
commercial parity is depth inside each (e.g. fee receipts/PDF, report
exports, granular permissions), the payment-gateway integration for online
fee collection, the Super Admin subscription/billing panel for the SaaS
model, and the Flutter apps — all of which consume these same APIs.

## Security checklist before you sell

- Protect `/api/central/*` behind a Super Admin guard + IP allow-list.
- Rate-limit `/api/login`.
- Force HTTPS; set Sanctum token expiry.
- Validate the `X-Tenant` header against active, paid tenants.
- Never commit `.env`; ship `.env.example` only.

---

## Complete endpoint reference (full CRUD)

Every module supports the standard REST verbs. All tenant routes require the
`X-Tenant: <school-id>` header; protected routes also require
`Authorization: Bearer <token>`.

### Auth & account
```
POST   /api/login              { email, password } -> { token, user }
GET    /api/me
POST   /api/logout
POST   /api/logout-all         # revoke every token for this user
POST   /api/change-password    { current_password, new_password, new_password_confirmation }
```

### User management (admins/teachers/students/parents)
```
GET    /api/users?search=&role=
POST   /api/users              { name, email, password, phone?, role }
GET    /api/users/{id}
PUT    /api/users/{id}         # any subset incl. password, role
DELETE /api/users/{id}
```

### Modules — each is full CRUD (index / store / show / update / destroy)
```
students     GET|POST /api/students   GET|PUT|DELETE /api/students/{id}
fees         GET|POST /api/fees       GET|PUT|DELETE /api/fees/{invoice}      + POST /api/fees/{invoice}/collect
staff        GET|POST /api/staff      GET|PUT|DELETE /api/staff/{staff}
exams        GET|POST /api/exams      GET|PUT|DELETE /api/exams/{exam}
admissions   GET|POST /api/admissions GET|PUT|DELETE /api/admissions/{id}     + /approve /reject /enroll
timetable    GET|POST /api/timetable  GET|PUT|DELETE /api/timetable/{slot}    (index = class grid)
books        GET|POST /api/books      GET|PUT|DELETE /api/books/{book}        + /books/{book}/issue, /book-issues/{issue}/return
vehicles     GET|POST /api/vehicles   GET|PUT|DELETE /api/vehicles/{vehicle}  + POST /api/vehicles/{vehicle}/location  (GPS)
rooms        GET|POST /api/rooms      GET|PUT|DELETE /api/rooms/{room}        + /allot /vacate
announcements GET|POST /api/announcements  GET|PUT|DELETE /api/announcements/{id}  + POST /api/announcements/send
```

### Attendance, reports, settings
```
GET  /api/attendance?date=   POST /api/attendance   { date, marks:{studentId:status} }
GET  /api/reports/summary
GET|PUT /api/settings
```

**Note:** the `users` table needs a `phone` column — provided by the tenant
migration `add_phone_to_users_table`, which is already in
`database/migrations/tenant/`. All of the above is verified by the CI.

---

## Licensing & the control plane (Chenthur Info Tech)

This product is owned by **Chenthur Info Tech**. Customers can only run a
school while it holds an **active license key** you issue. There are two
levels of access:

**Super Admin (product owner — you).** Logs into the *central* control
plane. Issues license keys, provisions schools, and can suspend/re-activate
any school instantly. Seeded by `CentralAdminSeeder`
(env: `CENTRAL_ADMIN_EMAIL`, `CENTRAL_ADMIN_PASSWORD`).

```
POST /api/central/login            { email, password } -> { token, vendor }
GET  /api/central/me
POST /api/central/logout
GET  /api/central/licenses
POST /api/central/licenses         { licensee, plan?, expires_at? } -> { key: "CHEN-XXXX-XXXX-XXXX" }
POST /api/central/licenses/{id}/suspend | /activate | /revoke
GET  /api/central/schools
POST /api/central/schools          { id, name, license_key, admin_name, admin_email, admin_password, domain? }
POST /api/central/schools/{id}/suspend | /activate
```

Provisioning **requires a valid, unused license key**. Suspending or
revoking a key immediately blocks that school (its users can no longer log
in — they get `403` with a "contact Chenthur Info Tech" message).

**School Admin (per school).** Logs into their own school (`X-Tenant`
header). Manages users and **role-wise access** (admin / teacher / student /
parent). Only admins can reach user management and settings.

```
GET  /api/roles                    # roles the admin can assign
GET|POST /api/users  ...           # full CRUD (admins only) + role on create/update
GET  /api/license                  # this school's license status (for Settings)
```

Roles: `super-admin`, `admin`, `teacher`, `student`, `parent`
(seeded per school by `RoleSeeder`).

**Setup note:** the central connection keeps its own
`personal_access_tokens` table (migration `...000210`); do not move that one
into `tenant/`. The setup script and CI handle this automatically.
