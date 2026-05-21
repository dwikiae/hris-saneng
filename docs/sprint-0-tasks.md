# SPRINT 0 — Task Breakdown
## HRIS PT Saneng — Project Skeleton & Local Dev Infrastructure
### Status: READY FOR EXECUTION

> **Untuk AI Coder (Claude Code):**
> Baca AGENTS.md + docs/02_ARCHITECTURE.md sebelum mengeksekusi task apapun.
> Kerjakan task secara berurutan — setiap task bergantung pada task sebelumnya.
> Setiap task selesai: jalankan verification command, laporkan dengan completion report format dari AGENTS.md.

---

## Sprint Goal

Menghasilkan monorepo yang bisa di-clone, di-`docker compose up`, dan langsung berjalan
secara lokal di Linux — dengan struktur folder final, semua service ready, database ter-seed
dengan data awal PT Saneng, dan CI pipeline terkonfigurasi.

**Exit gate Sprint 0:**
- `docker compose up -d` → semua service running tanpa error
- `php artisan migrate` → semua migration berhasil
- `php artisan db:seed` → data awal PT Saneng terseed
- `php artisan test` → baseline test lulus (0 failures)
- `GET /health` → return semua service status OK
- `npm run build` di kedua frontend → berhasil tanpa error
- GitHub Actions CI → lint + test lulus saat push ke branch

---

## Urutan Eksekusi Task

```
S0-T01 → S0-T02 → S0-T03 → S0-T04 → S0-T05 → S0-T06 → S0-T07 → S0-T08
```

---

## S0-T01 — Monorepo Scaffold & Git Setup

**Objective:**
Buat struktur folder monorepo final dan konfigurasi Git dasar.

**In scope:**
- Buat struktur folder root: `/backend`, `/frontend-hris`, `/frontend-web`, `/docs`, `/docs/adr`
- Buat file-file root: `AGENTS.md`, `CLAUDE.md`, `README.md`, `CHANGELOG.md`, `.gitignore` (root)
- Inisialisasi Laravel 11 baru di `/backend` via `composer create-project`
- Inisialisasi Next.js 14 baru di `/frontend-hris` via `npx create-next-app`
- Inisialisasi Next.js 14 baru di `/frontend-web` via `npx create-next-app`
- Setup `.gitignore` yang tepat untuk masing-masing (Laravel + Next.js)
- Copy 4 dokumen fondasi ke posisi yang benar di `/docs`

**Next.js options untuk kedua frontend:**
```
✓ TypeScript
✓ ESLint
✓ Tailwind CSS
✓ `src/` directory
✗ App Router (gunakan Pages Router — lebih stabil untuk project ini)
✗ import alias (skip)
```

**Out of scope:**
- Konfigurasi Docker (S0-T02)
- Install dependency tambahan apapun
- Konfigurasi database

**Acceptance criteria:**
1. Struktur folder sesuai dengan `docs/02_ARCHITECTURE.md` section 2
2. `composer install` di `/backend` → sukses
3. `npm install` di `/frontend-hris` dan `/frontend-web` → sukses
4. `.gitignore` root meng-exclude: `node_modules/`, `vendor/`, `.env`, `*.log`, `storage/logs/*`
5. `/backend/.env.example` ada (Laravel default, belum dimodifikasi)
6. `AGENTS.md` sudah di posisi root dengan konten yang benar

**Verification:**
```bash
ls -la                          # cek struktur root
ls backend/ frontend-hris/ frontend-web/ docs/
cd backend && php artisan --version
cd ../frontend-hris && npm run build --dry-run 2>/dev/null || echo "ok"
```

---

## S0-T02 — Docker Compose (Local Dev)

**Objective:**
Buat konfigurasi Docker Compose untuk semua service local dev di Linux.

**Relevant context:**
- `docs/02_ARCHITECTURE.md` section 11 (environments)
- Stack: PostgreSQL, Redis, MinIO, Soketi

**In scope:**
- Buat `docker-compose.yml` di root
- Buat `docker-compose.override.yml` untuk local dev overrides
- Service yang harus ada:
  - `postgres` — PostgreSQL 16, port 5432, volume persistent
  - `redis` — Redis 7 Alpine, port 6379
  - `minio` — MinIO latest, port 9000 (API) + 9001 (console)
  - `soketi` — Soketi latest (quay.io/soketi/soketi), port 6001 + 9601
  - `mailpit` — Mailpit (untuk catch email di local, tidak perlu SMTP asli), port 1025 (SMTP) + 8025 (UI)
- Buat `Makefile` di root dengan shortcuts:
  ```
  make up       → docker compose up -d
  make down     → docker compose down
  make logs     → docker compose logs -f
  make reset-db → docker compose down -v && docker compose up -d postgres
  make shell-be → docker compose exec backend sh (jika pakai containerized backend)
  ```
- Buat `.env.example` di root (untuk docker compose variables)

**Catatan penting:**
- Backend Laravel dan frontend Next.js **TIDAK** dicontainerkan untuk local dev
  (jalankan langsung di host untuk kemudahan development + hot reload)
- Docker hanya untuk service dependencies: PostgreSQL, Redis, MinIO, Soketi, Mailpit

**Out of scope:**
- Konfigurasi Nginx (untuk production nanti)
- SSL di local
- Backend Laravel config (S0-T03)

**Acceptance criteria:**
1. `docker compose up -d` → semua 5 service running (`docker compose ps` menunjukkan status Up)
2. PostgreSQL bisa diakses: `psql -h localhost -U hris_user -d hris_local`
3. Redis bisa diakses: `redis-cli -h localhost ping` → PONG
4. MinIO console bisa diakses di `http://localhost:9001`
5. Mailpit UI bisa diakses di `http://localhost:8025`
6. Soketi berjalan di port 6001
7. Data PostgreSQL persistent: restart container tidak hilangkan data
8. `Makefile` shortcuts berfungsi

**Verification:**
```bash
docker compose up -d
docker compose ps
docker compose logs postgres | tail -5
docker compose logs redis | tail -5
redis-cli -h localhost ping
curl http://localhost:9001  # MinIO console
curl http://localhost:8025  # Mailpit UI
```

---

## S0-T03 — Backend Laravel Configuration

**Objective:**
Konfigurasi Laravel 11 backend lengkap: environment, database connection, package dasar,
dan struktur folder application layer.

**Relevant context:**
- `AGENTS.md` — semua rules berlaku
- `docs/02_ARCHITECTURE.md` section 3 (layered architecture)
- Stack: Laravel 11, PostgreSQL, Redis, Sanctum, Spatie packages

**In scope:**
- Buat `/backend/.env` dari `.env.example` dengan nilai local dev
- Konfigurasi koneksi PostgreSQL (host: localhost, db: hris_local)
- Konfigurasi Redis (cache + queue driver)
- Konfigurasi MinIO sebagai filesystem disk custom (`documents`)
- Konfigurasi Soketi (broadcasting)
- Konfigurasi Mailpit sebagai SMTP local (host: localhost, port: 1025)
- Install packages:
  ```
  composer require laravel/sanctum
  composer require spatie/laravel-permission
  composer require spatie/laravel-activitylog
  composer require league/flysystem-aws-s3-v3  # untuk MinIO (S3-compatible)
  ```
- Buat struktur folder Application layer:
  ```
  app/Domain/
  app/Application/
  app/Infrastructure/Storage/
  app/Infrastructure/Notification/
  app/Infrastructure/Biometric/
  app/Repositories/Contracts/
  app/Repositories/Eloquent/
  ```
- Buat `app/Infrastructure/Biometric/FingerprintAdapterInterface.php` (placeholder interface)
- Publish config Sanctum, permission, activitylog
- Konfigurasi `config/filesystems.php` — tambah disk `documents` (MinIO)
- Konfigurasi `config/broadcasting.php` — tambah Soketi channel
- Update `/backend/.env.example` dengan semua variable yang dibutuhkan (tanpa nilai sensitif)

**Values untuk .env local:**
```
APP_NAME="HRIS PT Saneng"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=hris_local
DB_USERNAME=hris_user
DB_PASSWORD=hris_password

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

MINIO_ENDPOINT=http://localhost:9000
MINIO_KEY=hris_minio_key
MINIO_SECRET=hris_minio_secret
MINIO_REGION=us-east-1
MINIO_BUCKET=hris-local

BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=hris-local
PUSHER_APP_KEY=hris-local-key
PUSHER_APP_SECRET=hris-local-secret
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http

MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_FROM_ADDRESS=noreply@saneng.co.id
MAIL_FROM_NAME="HRIS PT Saneng"
```

**Out of scope:**
- Migration dan model (S0-T04)
- Sanctum route setup (S0-T05)
- Test setup (S0-T06)

**Acceptance criteria:**
1. `php artisan config:clear && php artisan config:show database` → menunjukkan PostgreSQL config benar
2. `php artisan tinker` → `DB::connection()->getPdo()` → tidak error
3. `php artisan tinker` → `Cache::put('test', 1)` → tidak error (Redis)
4. Folder `app/Domain`, `app/Application`, `app/Infrastructure`, `app/Repositories` sudah ada
5. `FingerprintAdapterInterface.php` sudah ada dengan struktur interface yang benar
6. Disk `documents` terkonfigurasi di `config/filesystems.php`
7. `.env.example` sudah diupdate dengan semua variable (tanpa nilai sensitif)
8. Spatie packages ter-publish confignya

**Verification:**
```bash
cd backend
php artisan config:clear
php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB OK';"
php artisan tinker --execute="Cache::put('test', 1, 10); echo Cache::get('test');"
ls app/Domain app/Application app/Infrastructure app/Repositories
cat config/filesystems.php | grep documents
```

---

## S0-T04 — Database Foundation: Migrations & Models

**Objective:**
Buat migration dan model untuk tabel-tabel fondasi sistem: companies, company_settings,
dan struktur wajib yang dibutuhkan sebelum semua modul lain bisa dibuat.

**Relevant context:**
- `AGENTS.md` RULE-A1, RULE-A2 (company_id wajib, kolom wajib)
- `AGENTS.md` RULE-B1, RULE-B2 (archive policy)
- `AGENTS.md` RULE-D1 (encrypted cast)
- `docs/02_ARCHITECTURE.md` section 4

**In scope:**

Migration (urutan eksekusi harus benar):
```
1. create_companies_table
   - id, name, legal_name, npwp (nullable), address, city, phone, email
   - logo_path (nullable), website (nullable)
   - timezone (default: 'Asia/Jakarta'), date_format (default: 'DD/MM/YYYY')
   - language_default (default: 'id')
   - created_at, updated_at

2. create_company_settings_table
   - id, company_id (FK companies)
   - key (string), value (text nullable)
   - created_at, updated_at
   - UNIQUE(company_id, key)

3. create_users_table  (replace Laravel default users migration)
   - id, company_id (FK companies)
   - name, email (unique), password
   - employee_id (nullable, unique) — FK ke employees ditambah di sprint 6
   - language_preference (default: 'id')
   - force_password_reset (boolean, default: false)
   - last_login_at (nullable timestamp)
   - login_attempts (int, default: 0)
   - locked_until (nullable timestamp)
   - archived_at (nullable), archived_by (nullable FK users — self reference)
   - created_by (nullable FK users), updated_by (nullable FK users)
   - created_at, updated_at

4. create_password_reset_tokens_table  (Laravel default, keep)
5. create_sessions_table               (Laravel default, keep as-is)
6. create_cache_table                  (untuk Redis cache — opsional tapi good practice)
7. create_jobs_table                   (Laravel queue jobs)
8. create_failed_jobs_table            (Laravel failed jobs — WAJIB)
```

Models yang dibuat:
- `Company` — fillable, timestamps
- `CompanySetting` — fillable, belongsTo Company
- `User` — update dari Laravel default:
  - Tambah fillable fields baru
  - Tambah `archived` scope: `whereNull('archived_at')`
  - Tambah Global Scope untuk filter archived
  - Tambah method `isLocked(): bool`
  - Tambah method `archive(int $userId): void`
  - Tambah cast `locked_until` → datetime

**Trait yang dibuat:**
```
app/Models/Concerns/HasArchive.php
  → method archive(int $userId): void
  → Global Scope 'not_archived'
  → method withArchived() scope

app/Models/Concerns/HasCompany.php
  → Global Scope filter company_id otomatis dari config
```

**Out of scope:**
- Tabel employees, roles, permissions (sprint berikutnya)
- Seeder (S0-T05)
- Test (S0-T06)

**Acceptance criteria:**
1. `php artisan migrate` → semua migration lulus tanpa error
2. `php artisan migrate:status` → semua migration status Ran
3. `Company` model bisa di-instantiate
4. `User` model punya method `isLocked()` dan `archive()`
5. Trait `HasArchive` dan `HasCompany` ada dan bisa di-use
6. Global Scope di User model berfungsi: `User::all()` hanya return non-archived users
7. Tidak ada `->delete()` di model manapun — hanya `archive()`
8. Failed_jobs table ada

**Verification:**
```bash
cd backend
php artisan migrate:fresh
php artisan migrate:status
php artisan tinker --execute="echo Company::count();"
php artisan tinker --execute="echo 'User scope: ' . (new \ReflectionClass(App\Models\User::class))->hasMethod('archive');"
```

---

## S0-T05 — Seeders: Data Awal PT Saneng

**Objective:**
Buat seeders untuk data awal yang wajib ada sebelum sistem bisa digunakan:
record PT Saneng sebagai company, default company settings, dan satu user System Admin.

**Relevant context:**
- `AGENTS.md` RULE-A1 (semua query filter company_id — seeder harus buat record ini dulu)
- `docs/02_ARCHITECTURE.md` section 4.2
- Master Decision Log section 11 (company settings yang bisa dikonfigurasi)

**In scope:**

```
database/seeders/
  DatabaseSeeder.php          → orchestrator, panggil semua seeder berurutan
  CompanySeeder.php           → insert PT Saneng
  CompanySettingsSeeder.php   → insert default settings
  AdminUserSeeder.php         → insert System Admin user
```

**CompanySeeder — data PT Saneng:**
```php
Company::create([
    'name'             => 'PT Saneng',
    'legal_name'       => 'PT Saneng',  // update nanti dari Settings UI
    'timezone'         => 'Asia/Jakarta',
    'date_format'      => 'DD/MM/YYYY',
    'language_default' => 'id',
]);
```

**CompanySettingsSeeder — default values:**
```
smtp_host             → ''
smtp_port             → '587'
smtp_username         → ''
smtp_encryption       → 'tls'
smtp_from_name        → 'HRIS PT Saneng'
password_min_length   → '8'
password_require_number → 'true'
password_require_uppercase → 'false'
session_timeout_minutes → '60'
max_login_attempts    → '5'
lockout_minutes       → '15'
retention_employee_financial_years → '10'
retention_employee_nonfinancial_years → '5'
retention_candidate_years → '1'
retention_audit_log_years → '2'
retention_app_log_days → '90'
ip_whitelist          → '127.0.0.1'   ← local dev, update saat production
```

**AdminUserSeeder:**
```php
User::create([
    'company_id'           => $company->id,
    'name'                 => 'System Administrator',
    'email'                => 'admin@saneng.co.id',
    'password'             => Hash::make('Admin@12345'),  // force_password_reset = true
    'force_password_reset' => true,
    'language_preference'  => 'id',
]);
```

**Out of scope:**
- Seeder untuk roles, permissions (Sprint 4)
- Seeder untuk master data (Sprint 2)

**Acceptance criteria:**
1. `php artisan db:seed` → selesai tanpa error
2. `Company::first()->name` → "PT Saneng"
3. `CompanySetting::where('key', 'session_timeout_minutes')->first()->value` → "60"
4. `User::where('email', 'admin@saneng.co.id')->first()->force_password_reset` → true
5. `php artisan migrate:fresh --seed` → bisa dijalankan berulang tanpa error
6. Admin user password bisa di-verify: `Hash::check('Admin@12345', $user->password)` → true

**Verification:**
```bash
cd backend
php artisan migrate:fresh --seed
php artisan tinker --execute="echo Company::first()->name;"
php artisan tinker --execute="echo CompanySetting::where('key','session_timeout_minutes')->value('value');"
php artisan tinker --execute="echo User::where('email','admin@saneng.co.id')->first()->force_password_reset ? 'force reset: YES' : 'NO';"
```

---

## S0-T06 — Health Check Endpoint

**Objective:**
Buat endpoint `GET /health` yang mengecek status semua service dependencies
dan return response terstruktur.

**Relevant context:**
- `AGENTS.md` RULE-E2 (controller thin)
- `docs/02_ARCHITECTURE.md` section 11.4

**In scope:**
- Route: `GET /health` (no auth, tidak kena IP whitelist)
- Controller: `HealthController@check`
- Service: `HealthCheckService` di `app/Application/Health/`
- Cek status: Database (PostgreSQL), Redis, MinIO (Storage disk), Queue Worker
- Response format:
```json
{
  "status": "ok",
  "environment": "local",
  "services": {
    "database": "ok",
    "redis":    "ok",
    "storage":  "ok",
    "queue":    "ok"
  },
  "timestamp": "2024-01-01T07:00:00+07:00"
}
```
- Jika salah satu service down: `"status": "degraded"`, HTTP 503
- Semua service down: `"status": "error"`, HTTP 503
- Semua OK: HTTP 200

**Out of scope:**
- Auth middleware di endpoint ini
- IP whitelist di endpoint ini (health check harus bisa diakses UptimeRobot)

**Acceptance criteria:**
1. `GET /health` → HTTP 200 saat semua service running
2. Response mengandung key: `status`, `environment`, `services`, `timestamp`
3. `services.database` → "ok" saat PostgreSQL running
4. `services.redis` → "ok" saat Redis running
5. `services.storage` → "ok" saat MinIO bisa diakses
6. Timestamp menggunakan timezone WIB (Asia/Jakarta)
7. Controller tidak mengandung logic langsung — delegasi ke HealthCheckService

**Verification:**
```bash
cd backend
php artisan serve &
sleep 2
curl -s http://localhost:8000/health | jq .
# Expected: {"status":"ok","services":{"database":"ok","redis":"ok","storage":"ok","queue":"ok"}}
```

---

## S0-T07 — GitHub Actions CI Pipeline

**Objective:**
Setup CI pipeline yang otomatis jalankan lint + test setiap push ke branch apapun
dan setiap pull request ke `main` atau `staging`.

**Relevant context:**
- `docs/02_ARCHITECTURE.md` section 11.2
- Tools: PHPStan, Pest PHP, ESLint, TypeScript check

**In scope:**
- Buat `.github/workflows/ci.yml`
- Jobs yang harus ada:
  ```
  backend-checks:
    - PHP lint (php -l semua file)
    - PHPStan level 5 (static analysis)
    - Pest PHP tests (dengan database SQLite in-memory untuk CI)

  frontend-hris-checks:
    - ESLint
    - TypeScript check (tsc --noEmit)
    - Build check (npm run build)

  frontend-web-checks:
    - ESLint
    - TypeScript check
    - Build check
  ```
- Trigger: push ke semua branch + PR ke `main` dan `staging`
- Buat `phpstan.neon` di `/backend` (level 5, scan `app/`)
- Buat `backend/phpunit.xml` atau `backend/pest.config.php` dengan SQLite in-memory untuk CI
- Tambah test environment variables di CI (SQLite, tidak perlu PostgreSQL live)

**Catatan untuk CI database:**
```
# CI menggunakan SQLite in-memory — tidak perlu spin up PostgreSQL di CI
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

**Out of scope:**
- Deploy otomatis (manual deploy untuk sekarang)
- Staging/production secrets (disetup saat production siap)

**Acceptance criteria:**
1. File `.github/workflows/ci.yml` ada dengan struktur yang benar
2. `phpstan.neon` ada dengan konfigurasi level 5
3. CI trigger pada push ke branch baru
4. Backend job: PHP lint, PHPStan, Pest → semua pass
5. Frontend jobs: ESLint, tsc --noEmit, build → semua pass
6. Jobs berjalan paralel (backend, frontend-hris, frontend-web berjalan bersamaan)
7. Jika ada test failure: CI merah, tidak bisa merge

**Verification:**
```bash
# Local simulation
cd backend
./vendor/bin/phpstan analyse app/ --level=5
./vendor/bin/pest --no-coverage

cd ../frontend-hris
npm run lint
npx tsc --noEmit

cd ../frontend-web
npm run lint
npx tsc --noEmit
```

---

## S0-T08 — Baseline Test Suite

**Objective:**
Buat test suite minimal yang memverifikasi fondasi sistem berjalan benar.
Ini adalah "green baseline" — semua test harus lulus sebelum Sprint 1 dimulai.

**Relevant context:**
- `AGENTS.md` verification rule
- `docs/02_ARCHITECTURE.md` section 13 (test strategy)
- Tools: Pest PHP

**In scope:**

```
tests/Unit/
  Models/
    CompanyTest.php          → Company model bisa dibuat, fillable benar
    UserTest.php             → User model: isLocked(), archive(), global scope
    HasArchiveTrait Test     → trait archive berfungsi, global scope exclude archived

tests/Feature/
  HealthCheck/
    HealthCheckTest.php      → GET /health return 200 + struktur response benar
  Database/
    MigrationTest.php        → semua tabel wajib ada setelah migrate
    SeederTest.php           → seeder menghasilkan data yang benar
```

**Test cases wajib:**

```php
// UserTest
it('excludes archived users from default query')
it('can archive a user')
it('returns archived users when using withArchived scope')
it('correctly identifies a locked user')
it('correctly identifies an unlocked user')

// HasArchiveTraitTest
it('sets archived_at and archived_by when archiving')
it('global scope filters out archived records')
it('withArchived scope includes archived records')

// HealthCheckTest
it('returns 200 with ok status when all services are running')
it('response contains required keys: status, environment, services, timestamp')
it('timestamp is in WIB timezone')

// MigrationTest
it('companies table exists with required columns')
it('users table has company_id, archived_at, archived_by columns')
it('failed_jobs table exists')

// SeederTest
it('seeds PT Saneng company record')
it('seeds default company settings with correct values')
it('seeds admin user with force_password_reset true')
it('admin user password is correctly hashed')
```

**Out of scope:**
- Test untuk fitur yang belum dibuat (auth, RBAC, dll)
- E2E test
- Performance test

**Acceptance criteria:**
1. `php artisan test` → 0 failures, 0 errors
2. Semua test cases di atas ada dan pass
3. Test menggunakan RefreshDatabase trait (database fresh per test)
4. Tidak ada test yang depend pada state dari test lain
5. Test bisa dijalankan berulang (idempotent)
6. Coverage minimal: semua method public di model yang dibuat di S0-T04 ada test-nya

**Verification:**
```bash
cd backend
php artisan test --coverage 2>/dev/null || php artisan test
php artisan test --filter HealthCheck
php artisan test --filter UserTest
php artisan test --filter HasArchive
```

---

## Completion Criteria Sprint 0

Sprint 0 dianggap selesai bila semua ini terpenuhi:

```bash
# 1. Semua service running
docker compose up -d && docker compose ps  # semua Up

# 2. Backend fresh install + seed berjalan
cd backend
php artisan migrate:fresh --seed          # 0 errors

# 3. Health check hijau
php artisan serve &
curl -s http://localhost:8000/health | jq '.status'  # "ok"

# 4. Semua test lulus
php artisan test                          # 0 failures

# 5. Frontend build sukses
cd ../frontend-hris && npm run build      # no errors
cd ../frontend-web && npm run build       # no errors

# 6. CI pipeline lulus (setelah push ke GitHub)
# GitHub Actions → semua jobs green
```

---

## Catatan untuk Claude Code

- Kerjakan task **secara berurutan S0-T01 → S0-T08**
- Setelah setiap task: jalankan verification command, buat completion report
- Jika menemukan conflict dengan AGENTS.md rules: **STOP dan laporkan** jangan bypass
- Jangan install package tambahan di luar yang disebutkan tanpa mention di completion report
- Pastikan `.env` tidak pernah di-commit (ada di `.gitignore`)
- Setiap task yang selesai: commit dengan message `feat(sprint-0): S0-T0X [deskripsi singkat]`
