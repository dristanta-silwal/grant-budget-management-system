# Grant Budget Management System (GBMS)
The **Grant Budget Management System (GBMS)** is a PHP web app that helps researchers and grant managers create budgets, invite collaborators (PI/Co-PI/etc.), and export a clean Excel summary.


## What’s in this repo
- **PHP 8+** app (vanilla PHP)
- **PostgreSQL** via **Supabase**
- **Composer** dependencies (PhpSpreadsheet, Dotenv)
- Front-end: HTML/CSS/JS

> **Note:** This README describes the current workflow using **Supabase (Postgres)**. Previous iterations used MySQL/JawsDB/Heroku also works.


## Quick links
- Documentation: see the `/docs` directory in the repo
- Screenshots are in `/docs/assets`


## Features
- Secure authentication (login/register)
- Grant management (roles: creator, PI, Co‑PI, viewer)
- Budget items by year, with salary and fringe/indirect calculations
- Excel export (PhpSpreadsheet)
- Notifications and collaboration workflow


# Getting Started (with Supabase)

### Prerequisites
- PHP **8.1+** (CLI and extensions)
- Composer
- `psql` command-line client
- A Supabase project (Postgres) – free tier is sufficient

> **PHP extensions required:** `pdo_pgsql`, `pdo`, `openssl`, `mbstring`, `xml`, `zip`, `gd` (for PhpSpreadsheet images).


## 1. Clone the repo
```bash
git clone https://github.com/dristanta-silwal/grant-budget-management-system
cd grant-budget-management-system
```

## 2. Install PHP dependencies
```bash
composer install
```

## 3. Create your `.env`
This app reads one connection string: `DATABASE_URL`.

Create a file named `.env` in the project root:
```ini
# .env (example)
# Use the Session Pooler from Supabase for IPv4 compatibility
DATABASE_URL=postgresql://postgres.<PROJECT_REF>:<PERCENT_ENCODED_PASSWORD>@<SESSION_POOLER_HOST>:6543/postgres?sslmode=require

# Optional (used by some scripts/UI)
APP_ENV=local
```
**Where to get these values (Supabase Dashboard → Connect):**
- **User (Session Pooler):** `postgres.<PROJECT_REF>`
- **Host (Session Pooler):** something like `<cloud>-<region>.pooler.supabase.com`
- **Port:** `6543` (transaction pooler) or `5432` (session pooler). For most PHP apps, `6543` is fine.
- **DB name:** usually `postgres`
- **SSL:** `sslmode=require`

> **Password with symbols?** URL-encode it in the URI (e.g., `@` → `%40`).



## 4. Verify DB connectivity (optional but recommended)
From the terminal, test with `psql` using the pooler host:
```bash
psql "postgresql://postgres.<PROJECT_REF>:<PERCENT_ENCODED_PASSWORD>@<SESSION_POOLER_HOST>:6543/postgres?sslmode=require" \
   -c "select version(), now();"
```
If you prefer parameter form:
```bash
psql -h <SESSION_POOLER_HOST> -p 6543 -d postgres -U postgres.<PROJECT_REF> \
   -c "select version(), now();" --set=sslmode=require
```


## 5. Create the database schema
This project uses PostgreSQL. Run the Postgres schema file:
```bash
psql "${DATABASE_URL}" -f schema/grant_budget_postgres.sql
```
> If you only see a MySQL-style file (backticks, `ENGINE=InnoDB` etc.), use the Postgres version in `schema/grant_budget_postgres.sql`. The MySQL version will not work on Postgres.


## 6. Run the app locally
Use PHP’s built-in server and serve from `/api` (where the entry pages live):
```bash
php -S localhost:8000 -t api
```
Open: `http://localhost:8000/login.php`

> If you serve from the project root, make sure includes use resilient paths (this repo does) and that your document root can reach `/api` pages.


## 7. First login / seed data (optional)
- Create an account via Register.
- The first user can be elevated by setting `$_SESSION['is_admin']=true` during testing (or add an `is_admin` column and set it in the database).
- Use the admin pages to set Salaries and Fringe Rates if your schema doesn’t include seed data.


# Configuration reference

### Environment variables
- `DATABASE_URL` – required. Example format:
   ```
   postgresql://postgres.<PROJECT_REF>:<PERCENT_ENCODED_PASSWORD>@<SESSION_POOLER_HOST>:6543/postgres?sslmode=require
   ```
- `APP_ENV` – optional flag used by some scripts/UI.

### PHP extension check
Make sure `pdo_pgsql` is enabled:
```bash
php -m | grep -i pdo_pgsql
```
If missing, install it (method varies by OS).


# Troubleshooting
Below are real issues encountered during setup and how to resolve them.

### 1. Hostname/DNS errors when using the direct Supabase host
**Symptom:**
```
psql: could not translate host name "db.<...>.supabase.co" to address
```
**Cause:** The direct database endpoint can be IPv6-only. Some networks or clients expect IPv4.
**Fix:** Use the Session/Transaction Pooler host from the Connect panel (e.g., `<cloud>-<region>.pooler.supabase.com`) with port `6543` and user `postgres.<PROJECT_REF>`.

### 2. “Password authentication failed for user \"postgres\"”
**Symptom:**
```
FATAL: password authentication failed for user "postgres"
```
**Cause:** When using the pooler, the username changes to `postgres.<PROJECT_REF>`.
**Fix:** Update your URI/DSN to use `postgres.<PROJECT_REF>` and make sure your password is percent-encoded in the URL.

### 3. “duplicate SASL authentication request”
**Symptom:**
```
connection failed: duplicate SASL authentication request
```
**Cause:** Mismatch between client and server authentication flow, often triggered by an incorrect DSN or partial SSL settings.
**Fix:** Ensure you connect via the pooler with `sslmode=require` and a correct `DATABASE_URL`. Verify `pdo_pgsql` is installed and you aren’t mixing MySQL drivers.

### 4. `relation "notifications" does not exist`
**Symptom:**
```
SQLSTATE[42P01]: Undefined table: ... notifications
```
**Cause:** Tables weren’t created yet (or you ran the wrong schema file).
**Fix:** Run the Postgres schema: `psql "${DATABASE_URL}" -f schema/grant_budget_postgres.sql`.

### 5. `syntax error at or near "`"`
**Symptom:**
```
ERROR: syntax error at or near "`"
```
**Cause:** Running a MySQL schema against Postgres (backticks, `ENGINE=...`, etc.).
**Fix:** Use the Postgres schema file. Do not run MySQL SQL on Postgres.

### 6. `Undefined column: g.updated_at`
**Symptom:**
```
SQLSTATE[42703]: Undefined column: ... g.updated_at
```
**Cause:** Code referenced a column that doesn’t exist in the table.
**Fix (two options):**
- Remove `updated_at` from the query and order by an existing column (e.g., `start_date` or `id DESC`).
- Or add `created_at/updated_at` columns and a trigger to maintain `updated_at`.

### 7. Session warnings when including headers
**Symptom:**
```
Notice: session_start(): Ignoring session_start() because a session is already active
```
**Cause:** Calling `session_start()` multiple times.
**Fix:** Guard with `if (session_status() === PHP_SESSION_NONE) { session_start(); }` and include header after session/database initialization.

### 8. Header include path errors
**Symptom:**
```
include ../header.php: No such file or directory
```
**Cause:** Relative path from `/api` to project root.
**Fix:** Use a resilient include: try `dirname(__DIR__).'/header.php'` then fall back to `__DIR__.'/header.php'`.


# Project structure (high level)
```
/                        # repo root
├─ api/                  # PHP entry pages (login, register, grants, etc.)
├─ src/                  # core (db connection, helpers)
├─ assets/               # images/screenshots
├─ schema/               # SQL files (Postgres)
├─ vendor/               # composer dependencies (generated)
├─ docs/                 # GitHub Pages documentation
└─ README.md
```


# Contributing
1. Fork the repo
2. Create a feature branch: `git checkout -b feature-name`
3. Commit: `git commit -m "feat: add X"`
4. Push: `git push origin feature-name`
5. Open a pull request