# VivaahFlow — Wedding Services Management System

A complete wedding-services platform built with **PHP 8.2 + MySQL + JSON REST API + Tailwind/vanilla JS**: public marketing website, customer self-service portal (`/account`), and full admin back office (`/manage`).

## Quick start (XAMPP, ~5 minutes)

1. Install **XAMPP** (Apache + MySQL + PHP 8.2+) and start **Apache** + **MySQL**.
2. Place the project at `C:\xampp\htdocs\VivaahFlow`.
3. Open `http://localhost/VivaahFlow/` — on a fresh machine you are routed to the **Setup Wizard** at `/setup` automatically.
4. Complete the wizard: business profile → admin account → optional demo data → install.
5. Sign in:
   - Admin: `http://localhost/VivaahFlow/manage/login`
   - Customer: `Sign In` on the website → `/account`

No manual SQL is required. The installer creates the `vivaahflow` database, installs the schema, writes default settings/roles, creates your admin, and optionally loads demo data. Re-running setup is safe (idempotent) and locked after success.

> Internet is required on first load (Tailwind, Flowbite, Lucide, fonts via CDN).

## What you get

- **Public site** (`/`, `/services`, `/packages`, `/gallery`, `/offers`, `/reviews`, `/contact`, `/login`, `/register`): catalogue search + filters, detail pages, offers, reviews, enquiry capture with reference numbers.
- **Customer portal** (`/account…`): dashboard, enquiries, quotations (accept/decline), bookings, payments, invoices (print), reviews, profile.
- **Admin panel** (`/manage…`): dashboard + reports (ApexCharts), customers, enquiries → quotations → bookings workflow, events + staff assignments, staff, payments (over-payment protected), invoices, reviews moderation, offers, leads + follow-ups, notifications, settings, users/roles, activity log, profile, help.
- **JSON API** (`/api/v1/index.php/...`): versioned, `{success,message,data}` envelope, session + CSRF, role middleware.

## Requirements

- PHP 8.2+ with `pdo_mysql`, `mbstring`, `fileinfo`, `json` (`bcmath` recommended).
- MySQL 5.7+ / MariaDB 10.4+.
- Apache with `mod_rewrite` (recommended) or PHP built-in server.
- Modern browser.

## First-run setup wizard

`GET /setup` (public only while installation is incomplete):

1. Database check → auto-create `vivaahflow` when possible.
2. Schema install → `database/schema.sql` + `schema_migrations` version marker.
3. Business configuration (name, tagline, email, phone, address, city, currency, timezone, footer, registration open/closed).
4. Admin account (name, email, password + confirm, bcrypt-hashed).
5. Optional demo data (`Install sample/demo data` yes/no).
6. Confirmation summary → install → redirect to admin login.

Status API: `GET /api/v1/index.php/setup/status` returns `server_unavailable | database_missing | schema_incomplete | not_installed | installed`. The app redirects to `/setup` whenever the database is not `installed`; after success, `POST /setup/install` returns `409` and the wizard shows “already installed”.

CLI alternative (idempotent):

```powershell
& "C:\xampp\php\php.exe" "C:\xampp\htdocs\VivaahFlow\database\seed.php" --with-demo
```

## Database

- **Name:** `vivaahflow` (`utf8mb4`, InnoDB).
- **Schema:** `database/schema.sql` — 30 tables (structural only, no demo rows).
- **Demo seed:** `database/demo_seed.sql` (manual import) + `database/seeds/demo.php` (idempotent PHP seeder used by the wizard and `database/seed.php`). Demo data is never mixed into the schema.
- **Versioning:** `schema_migrations(version, app_version, applied_at)`; `Connection::getStatus()` distinguishes missing DB, incomplete schema, not-installed, and installed.
- **Money:** `DECIMAL(12,2)` + integer-cent `Money` helper; references like `ENQ-YYYY-NNN`, `QTN-YYYY-NNN`, `BKG-YYYY-NNN`, `INV-YYYY-NNN`.

Verify seeded image paths all exist under `uploads/services/`, `uploads/packages/`, `uploads/settings/`.

## Demo accounts (seeded, bcrypt-hashed)

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@example.com` | `Admin@123` |
| Manager | `manager@example.com` | `Manager@123` |
| Staff | `staff@example.com` | `Staff@123` |
| Customer | `customer@example.com` | `Customer@123` |

Change all passwords before real use. Demo covers roles, users, settings, categories (6), services (12 + gallery), packages (3), offers (2), enquiries, quotations, bookings, events, staff assignments, payments, invoices, reviews, leads + follow-ups, notifications, and activity logs — a coherent end-to-end story (enquiry → quotation → booking → event → payment → invoice → review).

## Project structure

```
VivaahFlow/
├── frontend/
│   ├── customer/   # public site shells (no SQL) + router
│   ├── portal/     # /account shells + router
│   ├── admin/      # /manage shells + router
│   ├── setup/      # first-run wizard (router + index + js/setup/wizard.js)
│   └── assets/     # css/, js/ (app.*, core/loader.js, customer/, portal/, admin/, setup/), images/brand/, icons/
├── api/v1/index.php
├── backend/
│   ├── bootstrap.php | Calculations/ | Controllers/ (incl. SetupController)
│   ├── Core/ (Router, Request, Response, Container, Session, Routes)
│   ├── Database/Connection.php (server/db connect, databaseExists, createDatabase, getStatus)
│   ├── Helpers/ | Middleware/ (CsrfGuard, EnsurePanelUser, EnsureCustomer)
│   ├── Repositories/ | Services/ (incl. InstallationService) | Validators/
├── config/config.php
├── database/schema.sql | demo_seed.sql | seed.php | seeds/demo.php
├── storage/logs/ | uploads/ (customers, services, packages, settings, temp)
├── index.php | router.php (PHP built-in server) | .htaccess | README.md
├── qa/ (browser QA harness + reports; runtime artifacts ignored)
```

## Main routes

| URL | Implementation |
| --- | --- |
| `/` `/services` `/services/{slug}` `/packages` `/packages/{slug}` `/gallery` `/offers` `/reviews` `/contact` `/login` `/register` | `frontend/customer/` |
| `/setup` | `frontend/setup/` (wizard) |
| `/account` `/account/enquiries` `/account/quotations…` `/account/bookings…` `/account/payments` `/account/invoices…` `/account/reviews` `/account/profile` | `frontend/portal/` |
| `/manage` `/manage/login` `/manage/customers…` `/manage/services` `/manage/packages` `/manage/offers` `/manage/enquiries` `/manage/leads` `/manage/quotations…` `/manage/bookings…` `/manage/events` `/manage/staff` `/manage/payments` `/manage/invoices…` `/manage/reviews` `/manage/reports` `/manage/notifications` `/manage/settings` `/manage/users` `/manage/activity` `/manage/profile` `/manage/help` | `frontend/admin/` |
| `/api/v1/index.php/...` | `backend/Core/Routes.php` (PATH_INFO) |

## Configuration (`config/config.php`)

`app_name` (VivaahFlow), `app_version` (1.0.0), `schema_version`, `timezone` (Asia/Kolkata), `debug`, `database.host/port/name/user/pass/charset` (`127.0.0.1/3306/vivaahflow/root/''/utf8mb4`), `session.name` (`vivaahflow_sess`), `security.*` (throttling 5/15m, uploads 3MB, `jpg/jpeg/png/webp`), `currency.*`, `api_base` (`/api/v1`). Every deployment-sensitive value honors a `DB_*`/`APP_*`/`SESSION_*` env var (see `.env.example`) so production never edits tracked config. Secrets never reach the frontend; DB errors return friendly 503 setup messages (details in `storage/logs/php-error.log`).

Built-in server (requires the bundled router — plain `-t` mode cannot resolve `/assets/*`):

```powershell
& "C:\xampp\php\php.exe" -S 127.0.0.1:8100 "C:\xampp\htdocs\VivaahFlow\router.php"
# then http://127.0.0.1:8100/ , /services , /setup , /account , /manage/login
```

## Security notes

Prepared statements everywhere, bcrypt passwords, session regeneration on login, CSRF on all mutating API routes, login throttling (`login_attempts`), server-side role checks (frontend hiding is cosmetic), upload MIME + extension validation with generated filenames and an `uploads/.htaccess` script-execution guard, global `nosniff`/`SAMEORIGIN`/`Referrer-Policy`/`Permissions-Policy` headers (API + HTML), host-header sanitization, no raw PDO/exception output to users.

## Deployment notes

Set `debug=false`, use HTTPS (`SESSION_SECURE=true` or rely on auto-secure cookies on HTTPS), restrict `storage/logs`, keep `uploads/` writable (0755), back up `vivaahflow` regularly, change demo credentials, and pin CDN versions (Lucide pinned to `0.454.0`; see `AssetManager::LIBRARIES`). Environment overrides available for all deployment-sensitive config — see `.env.example` (`DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASS`, `APP_DEBUG`, `SESSION_SECURE`).

## Development

No build step — plain PHP + vanilla JS, no Composer/Node required:

```powershell
# Lint all PHP files
& "C:\xampp\php\php.exe" -r "$ok=0;$bad=0;Get-ChildItem -Recurse -Filter *.php | ForEach-Object { & C:\xampp\php\php.exe -l $_.FullName >$null 2>&1; if ($LASTEXITCODE -eq 0) { $ok++ } else { $bad++; Write-Output $_.FullName } }; Write-Output \"ok=$ok bad=$bad\""

# Reseed demo data (idempotent)
& "C:\xampp\php\php.exe" "C:\xampp\htdocs\VivaahFlow\database\seed.php" --with-demo
```

Writable at runtime: `storage/logs/`, `uploads/customers/`, `uploads/temp/` (auto-created; `.gitkeep` placeholders keep them in git).

Browser QA harness (`qa/`, Node + Playwright, desktop Chromium): see `qa/reports/VivaahFlow-Deep-Browser-QA-Report.md` for the latest release QA results.

## Troubleshooting

- **Routed to /setup:** database is missing/incomplete — complete the wizard.
- **Database server unavailable:** start MySQL, check host/port/user in `config/config.php` (or `DB_*` env vars).
- **Access denied:** DB user/password wrong or missing CREATE privilege for auto-create.
- **Login 429:** too many failures — wait 15 minutes.
- **Registration closed:** enable `registration_open` in Settings or setup.

## License

MIT — see [LICENSE](LICENSE). VivaahFlow 1.0.0 is the initial stable release.
