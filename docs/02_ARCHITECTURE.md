# 02_ARCHITECTURE.md
## Dictive-HR — System Architecture
### Version: 1.0 | Status: FINAL | Phase: Foundation

> Dokumen ini menjelaskan arsitektur sistem secara menyeluruh.
> Semua keputusan di sini sudah final dan terkunci di ALL_ADR.md.
> Perubahan arsitektur harus melalui ADR baru.

---

## 1. Gambaran Sistem

Dictive-HR adalah platform HRIS self-hosted dengan arsitektur **modular monolith** — satu backend Laravel 11 melayani satu frontend Next.js 14 via REST API. Platform berjalan dalam satu Docker Compose stack di server customer.

```
┌─────────────────────────────────────────────────────────────────────┐
│                    Server Customer (Self-Hosted)                     │
│                                                                     │
│  ┌──────────────┐    ┌──────────────────────────────────────────┐  │
│  │    Nginx     │    │           Application Layer              │  │
│  │  (Reverse    │    │                                          │  │
│  │   Proxy +    │    │  ┌────────────────────────────────────┐  │  │
│  │    SSL)      │───▶│  │     Next.js 14 (App Router)        │  │  │
│  │              │    │  │                                    │  │  │
│  │              │    │  │  /dashboard/*   → Surface 1        │  │  │
│  │              │    │  │  /[company]/*   → Surface 2 + 3    │  │  │
│  └──────────────┘    │  └─────────────────┬──────────────────┘  │  │
│                      │                    │                      │  │
│                      │                    ▼                      │  │
│                      │  ┌────────────────────────────────────┐  │  │
│                      │  │     Laravel 11 (Backend API)       │  │  │
│                      │  │     PHP-FPM                        │  │  │
│                      │  │  /api/v1/*          (Sanctum auth) │  │  │
│                      │  │  /api/v1/public/*   (rate limited) │  │  │
│                      │  └──────┬─────────────────────────────┘  │  │
│                      │         │                                 │  │
│                      │   ┌─────┴──────────────────┐            │  │
│                      │   │                        │            │  │
│                      │   ▼                        ▼            │  │
│                      │  ┌──────────┐  ┌────────┐ ┌────────┐   │  │
│                      │  │PostgreSQL│  │ Redis  │ │ MinIO  │   │  │
│                      │  │          │  │(Cache+ │ │(File   │   │  │
│                      │  │          │  │ Queue) │ │Storage)│   │  │
│                      │  └──────────┘  └────────┘ └────────┘   │  │
│                      │                                         │  │
│                      │  ┌────────┐  ┌──────────┐              │  │
│                      │  │ Soketi │  │  Mailpit │              │  │
│                      │  │  (WS)  │  │ (dev)/   │              │  │
│                      │  │        │  │  SMTP    │              │  │
│                      │  └────────┘  └──────────┘              │  │
│                      └─────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 2. Frontend Surfaces

Satu aplikasi Next.js 14 dengan App Router menangani tiga surface berbeda:

```
Surface 1: Platform Dashboard   → /dashboard/*
  - Diakses Instance Admin & semua Company User
  - Protected routes, client-side heavy
  - Manage company, modules, users, data HR

Surface 2: Company Website      → /[company-slug]/*
  - Public, SSR, SEO-friendly
  - Dikelola kontennya via modul Website
  - Profil perusahaan, halaman karir

Surface 3: Candidate Portal     → /[company-slug]/kandidat/*
  - Public, bagian dari Surface 2
  - Same theme dengan company website
  - Form lamaran, tes tulis, pemberkasan
  - Akses via token, tidak butuh login
```

**Theming per company:** CSS variables di-load berdasarkan company slug. Setiap company bisa punya warna, logo, dan tampilan berbeda di Surface 2 & 3.

---

## 3. Repository Structure (Monorepo)

```
/ (root)
├── backend/                      → Laravel 11
│   ├── app/
│   │   ├── Core/                 → Core platform logic
│   │   │   ├── Company/
│   │   │   ├── Auth/
│   │   │   ├── Permission/
│   │   │   ├── ModuleRegistry/
│   │   │   ├── Settings/
│   │   │   ├── I18n/
│   │   │   ├── AuditLog/
│   │   │   ├── Notification/
│   │   │   ├── FileStorage/
│   │   │   └── Archive/
│   │   ├── Modules/              → Semua modul (mandatory + optional)
│   │   │   ├── Karyawan/         → MANDATORY
│   │   │   │   ├── module.json
│   │   │   │   ├── Domain/
│   │   │   │   ├── Application/
│   │   │   │   ├── Infrastructure/
│   │   │   │   ├── Http/
│   │   │   │   ├── Models/
│   │   │   │   ├── Repositories/
│   │   │   │   ├── Database/
│   │   │   │   │   ├── migrations/
│   │   │   │   │   └── seeders/
│   │   │   │   └── Tests/
│   │   │   ├── Kalender/         → MANDATORY
│   │   │   │   └── [struktur sama]
│   │   │   ├── Recruitment/      → OPTIONAL
│   │   │   │   └── [struktur sama]
│   │   │   ├── Aset/             → OPTIONAL
│   │   │   │   └── [struktur sama]
│   │   │   └── Website/          → OPTIONAL
│   │   │       └── [struktur sama]
│   │   ├── Domain/               → Shared domain (lintas modul)
│   │   ├── Application/          → Shared use cases
│   │   ├── Infrastructure/       → External adapters
│   │   │   ├── Storage/
│   │   │   │   └── MinIOStorageAdapter.php
│   │   │   └── Notification/
│   │   │       └── SmtpMailAdapter.php
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── Api/V1/
│   │   │   │   │   ├── Core/
│   │   │   │   │   └── Public/
│   │   │   ├── Middleware/
│   │   │   └── Requests/
│   │   ├── Models/
│   │   ├── Repositories/
│   │   ├── Jobs/
│   │   ├── Notifications/
│   │   └── Providers/
│   │       └── ModuleServiceProvider.php  → Register modul aktif
│   ├── database/
│   │   ├── migrations/           → Core migrations only
│   │   └── seeders/              → Core seeders only
│   ├── lang/
│   │   ├── id/
│   │   └── en/
│   └── tests/
│       ├── Unit/
│       └── Feature/
│
├── frontend/                     → Next.js 14
│   └── src/
│       ├── app/
│       │   ├── dashboard/        → Surface 1
│       │   │   ├── layout.tsx
│       │   │   └── [module]/
│       │   └── [company]/        → Surface 2 + 3
│       │       ├── layout.tsx    → Load company theme
│       │       ├── page.tsx      → Company homepage
│       │       ├── karir/
│       │       └── kandidat/     → Candidate portal
│       ├── components/
│       │   ├── ui/               → shadcn/ui base
│       │   ├── core/             → Core shared components
│       │   └── modules/          → Per-module components
│       ├── hooks/
│       ├── services/             → API call layer
│       ├── stores/
│       ├── types/
│       └── locales/
│           ├── id/
│           └── en/
│
├── docker/
│   ├── nginx/
│   ├── php/
│   └── ...
├── docker-compose.yml
├── docker-compose.prod.yml
├── docs/
│   ├── 00_PROJECT_BRIEF.md
│   ├── 02_ARCHITECTURE.md
│   └── ALL_ADR.md
├── AGENTS.md
├── CLAUDE.md
├── README.md
└── CHANGELOG.md
```

---

## 4. Backend Layered Architecture

### 4.1 Layer Overview

```
┌──────────────────────────────────────────────────────────┐
│                    Interface Layer                        │
│  HTTP Controllers · FormRequests · Middleware · Routes   │
│  TIDAK boleh memuat business logic                       │
└──────────────────────────┬───────────────────────────────┘
                           │
┌──────────────────────────▼───────────────────────────────┐
│                   Application Layer                       │
│  Use Cases · Services · Orchestration                    │
└──────────────────────────┬───────────────────────────────┘
                           │
┌──────────────────────────▼───────────────────────────────┐
│                     Domain Layer                          │
│  Business Rules · Validations · State Machines           │
│  TIDAK bergantung pada framework atau external service   │
└──────────────────────────┬───────────────────────────────┘
                           │ interface (dependency inversion)
┌──────────────────────────▼───────────────────────────────┐
│                 Infrastructure Layer                      │
│  Adapters: MinIO · SMTP · AI (future)                    │
│  Repositories: PostgreSQL via Eloquent                   │
└──────────────────────────────────────────────────────────┘
```

### 4.2 Dependency Direction (Hukum Keras)

```
Interface → Application → Domain
                              ↑
Infrastructure ───────────────┘ (via interface/contract)
```

- **Domain** tidak boleh import class dari Laravel, Eloquent, atau package eksternal.
- **Controller** hanya boleh memanggil satu Application Service per endpoint.
- **DB access** hanya melalui Repository.

---

## 5. Module System Architecture

### 5.1 Module Manifest (module.json)

Setiap modul wajib punya file `module.json`:

```json
{
  "code": "recruitment",
  "name": "Modul Recruitment",
  "version": "1.0.0",
  "description": "Pipeline rekrutmen end-to-end",
  "is_mandatory": false,
  "dependencies": ["karyawan"],
  "min_core_version": "1.0.0"
}
```

### 5.2 Module Registry (Database)

```sql
-- Instance level: modul apa saja yang terinstall
module_registry
  id, code, name, version, is_mandatory
  dependencies (JSON)
  is_installed (boolean)
  installed_at, installed_by

-- Company level: modul mana yang aktif per company
company_module_settings
  id, company_id, module_code
  is_enabled (boolean)
  settings (JSON)   → konfigurasi spesifik modul per company
  enabled_at, enabled_by
```

### 5.3 Module Lifecycle

```
INSTALL (Instance Level — oleh Instance Admin):
  Cek dependencies tersedia
  → Jalankan migration modul
  → Jalankan seeder default modul
  → Update module_registry: is_installed = true
  → Modul tersedia untuk di-enable per company

ENABLE (Company Level):
  Modul sudah installed?
  → Update company_module_settings: is_enabled = true
  → Modul muncul di navigasi company

DISABLE (Company Level):
  → Update company_module_settings: is_enabled = false
  → Modul hilang dari navigasi, data tetap ada

UNINSTALL (Instance Level — oleh Instance Admin):
  Sistem generate export data per company
  → Instance Admin konfirmasi + warning UU PDP
  → Hapus semua data modul di semua company
  → Rollback migration modul
  → Update module_registry: is_installed = false
```

### 5.4 Module Service Provider

Core mendaftarkan modul aktif secara dinamis:

```php
// ModuleServiceProvider.php
// Saat boot: baca module_registry yang is_installed = true
// Register routes, services, dan bindings dari setiap modul aktif
// Untuk request tertentu: filter hanya modul yang is_enabled untuk company tersebut
```

---

## 6. Database Architecture

### 6.1 Strategi

Single database PostgreSQL. Semua tabel utama punya `company_id` untuk isolasi data antar company. Isolasi dijaga di application layer — semua query wajib filter `company_id`.

### 6.2 Core Tables

```
-- Instance level (tidak ada company_id)
instance_settings       → konfigurasi platform global
module_registry         → daftar modul terinstall
companies               → daftar company dalam instance
users                   → semua user (Instance Admin tidak punya company_id)
  └── company_id (nullable) → null = Instance Admin

-- Company level (semua punya company_id)
company_settings        → konfigurasi per company (SMTP, password policy, dll)
company_module_settings → modul apa yang aktif per company
roles                   → RBAC roles per company
permissions             → RBAC permissions (format: module.action)
role_permissions        → many-to-many
user_roles              → many-to-many
field_permissions       → field-level permission
notifications           → in-app notifications
activity_log            → audit trail (via spatie/laravel-activitylog)
```

### 6.3 Konvensi Kolom Wajib (Semua Tabel Utama)

```sql
company_id    BIGINT NOT NULL REFERENCES companies(id)
archived_at   TIMESTAMP NULL DEFAULT NULL
archived_by   BIGINT NULL REFERENCES users(id)
created_by    BIGINT NULL REFERENCES users(id)
updated_by    BIGINT NULL REFERENCES users(id)
created_at    TIMESTAMP NOT NULL DEFAULT NOW()
updated_at    TIMESTAMP NOT NULL DEFAULT NOW()
```

**Pengecualian:** Tabel instance-level (`instance_settings`, `module_registry`, `companies`) tidak punya `company_id`.

### 6.4 Archive Policy

```php
// Semua model utama WAJIB:

// 1. Global Scope — exclude archived records
static::addGlobalScope('not_archived', fn($q) => $q->whereNull('archived_at'));

// 2. Archive method — satu-satunya cara "delete"
public function archive(int $userId): void {
    $this->update(['archived_at' => now(), 'archived_by' => $userId]);
}
```

### 6.5 Encrypted Fields

Field berikut wajib pakai Laravel `encrypted` cast:

| Modul | Field |
|---|---|
| Karyawan | `nik`, `npwp`, `bank_account_number`, `salary`, `allowances`, `deductions` |

### 6.6 Approval Guard

Tabel dengan workflow approval wajib punya:

```sql
status       VARCHAR NOT NULL DEFAULT 'draft'
approved_by  BIGINT NULL REFERENCES users(id)
approved_at  TIMESTAMP NULL
```

---

## 7. API Architecture

### 7.1 Endpoint Groups

```
/api/v1/public/*          → Tanpa auth, rate limited
  GET  /[company]/jobs    → Daftar lowongan aktif (untuk website karir)
  POST /[company]/apply   → Submit lamaran (rate limit: 5/10mnt per IP)
  GET  /quiz/{token}      → Ambil soal tes
  POST /quiz/{token}      → Submit jawaban
  GET  /interview/{token} → Konfirmasi jadwal interview
  GET  /pemberkasan/{token} → Portal upload dokumen

/api/v1/*                 → Sanctum token required
  /auth/*                 → Login, logout, forgot-password
  /instance/*             → Instance Admin only
    /companies            → CRUD company
    /modules              → Install/uninstall modul
    /users                → Manage Instance Admin users
  /[company]/*            → Company-scoped endpoints
    /modules              → Enable/disable modul per company
    /settings             → Company settings
    /employees/*          → Modul Karyawan
    /recruitment/*        → Modul Recruitment
    /assets/*             → Modul Aset
    /calendar/*           → Modul Kalender
    /audit/*              → Audit log viewer
    /notifications/*      → In-app notifications
```

### 7.2 Response Format Standard

```json
// Success
{
  "success": true,
  "data": {},
  "message": "module.action.success",
  "meta": { "current_page": 1, "per_page": 20, "total": 150 }
}

// Error
{
  "success": false,
  "message": "error.validation_failed",
  "errors": { "field": ["error.field.required"] }
}
```

### 7.3 Company Context di Request

Setiap request ke `/api/v1/[company]/*` harus resolve company dari slug/subdomain. Middleware `ResolveCompany` menjalankan ini dan inject `company_id` ke semua query berikutnya.

---

## 8. Authentication & Authorization

### 8.1 Auth Flow

```
User → POST /api/v1/auth/login
  → Rate limiter: 5 req/mnt per IP
  → Validate credentials
  → Cek lockout (3 gagal → lockout sesuai setting)
  → Issue Sanctum token
  → Return token + user info + permissions list
```

### 8.2 Dynamic RBAC

```
User → Roles → Permissions (format: module.action)

Contoh permissions:
  karyawan.view          karyawan.create        karyawan.update
  karyawan.archive       karyawan.export
  recruitment.view       recruitment.manage
  aset.view              aset.assign
  settings.view          settings.update
  audit.view

Field-level permission → tabel field_permissions
  Contoh: user tanpa karyawan.view_salary tidak dapat field salary di response
```

### 8.3 Default Roles per Company

Saat company baru dibuat, dua role default otomatis tersedia:

| Role | Default Permissions |
|---|---|
| Manager | Read + approve semua modul aktif |
| Staff | Input data, tidak bisa delete/archive |

Role tambahan (termasuk Super Admin) dibuat manual oleh Instance Admin jika dibutuhkan.

### 8.4 Instance Admin vs Company User

| | Instance Admin | Company User |
|---|---|---|
| company_id | NULL | NOT NULL |
| Akses | Semua company | Hanya company sendiri |
| Manage modules | Ya (install/uninstall) | Ya (enable/disable) |
| Lihat data HR | Ya (semua company) | Ya (company sendiri) |
| Semua akses dicatat | Ya | Ya |

---

## 9. Notification Architecture

```
Event terjadi
  │
  ├── In-App Notification
  │   ├── Tulis ke tabel notifications
  │   └── Broadcast via Soketi (WebSocket)
  │
  └── Email Notification
      ├── Dispatch job ke Redis Queue
      ├── Retry: 3x exponential backoff
      └── Gagal 3x → failed_jobs → alert Instance Admin
```

---

## 10. File Storage Architecture

### 10.1 MinIO Bucket Structure

```
bucket: dictive-hr/
├── companies/
│   └── {company_id}/
│       ├── logo/
│       └── modules/
│           ├── karyawan/
│           │   └── {employee_id}/
│           │       ├── photo/
│           │       └── documents/
│           ├── recruitment/
│           │   └── {job_id}/
│           │       └── applicants/{applicant_id}/
│           └── aset/
│               └── {asset_id}/
├── exports/
│   └── {company_id}/{YYYY-MM-DD}/
└── backups/
    └── {YYYY-MM-DD}/
```

### 10.2 File Access Security

- Dokumen sensitif: akses via **signed URL** (time-limited)
- File publik (logo, website assets): disk public terpisah
- Tidak ada public URL permanen untuk dokumen sensitif

---

## 11. Security Architecture

| Layer | Implementasi |
|---|---|
| Transport | HTTPS/TLS (Let's Encrypt) |
| Auth | Laravel Sanctum SPA token |
| Application | Dynamic RBAC + field-level permission |
| Data at rest | Laravel encrypted cast untuk field sensitif |
| File | Signed URL, bukan public URL permanent |
| Secrets | .env only, tidak pernah di-commit |
| Input | FormRequest validation + XSS sanitasi |
| SQL | Eloquent ORM + Query Builder (raw SQL via binding only) |
| Login | Rate limit + lockout setelah 3 gagal |
| Audit | Semua aksi tercatat, tidak bisa dihapus |

---

## 12. Audit Trail

```
Semua CRUD pada data → spatie/laravel-activitylog
  ├── Siapa (user_id)
  ├── Kapan (timestamp)
  ├── Company mana (company_id)
  ├── Apa yang diubah (old → new values)
  └── Field sensitif → [REDACTED] untuk user tanpa permission

Instance Admin akses data HR company lain → tercatat sama seperti aksi user biasa
```

---

## 13. Deployment Architecture

```
docker-compose.yml services:
  app         → Laravel 11 (PHP-FPM)
  frontend    → Next.js 14
  postgres    → PostgreSQL
  redis       → Cache + Queue
  minio       → File storage
  soketi      → WebSocket server
  nginx       → Reverse proxy + SSL termination
  mailpit     → Email testing (dev only)
```

**Installer wizard** dijalankan saat pertama kali setup — tidak bisa skip.

**Update platform:** Vendor (Dictive-HR) yang push update. Migration harus backward-safe — tidak boleh breaking data existing.

---

## 14. Module Dependency Map

```
Core Platform (Foundation)
  └── Mandatory Modules
      ├── Karyawan    [root dependency semua modul HR]
      └── Kalender    [root dependency absensi, cuti, payroll]
          └── Optional Modules
              ├── Recruitment  depends on → Karyawan
              ├── Aset         depends on → Karyawan
              ├── Website      depends on → (none)
              ├── Absensi      depends on → Karyawan, Kalender  [future]
              ├── Cuti         depends on → Karyawan, Kalender  [future]
              └── Payroll      depends on → Karyawan, Kalender  [future]
```

---

*Document owner: Dictive-HR Vendor*
*Perubahan arsitektur harus melalui ADR baru. Lihat ALL_ADR.md.*
