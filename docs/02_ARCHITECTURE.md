# 02_ARCHITECTURE.md
## HRIS PT Saneng — System Architecture
### Version: 1.0 | Status: FINAL | Last updated: Sprint 0

> Dokumen ini menjelaskan arsitektur sistem secara menyeluruh.
> Semua keputusan di sini sudah final dan terkunci di Master Decision Log.
> Perubahan arsitektur harus melalui ADR baru dan persetujuan Principal.

---

## 1. Gambaran Sistem

HRIS PT Saneng adalah sistem internal single-tenant yang dibangun dengan arsitektur
**modular monolith** — satu backend Laravel 11 melayani dua frontend Next.js 14 terpisah
via REST API. Tidak ada microservices. Semua berjalan di satu VPS Linux.

```
┌─────────────────────────────────────────────────────────────────────┐
│                         VPS Linux (Single Server)                   │
│                                                                     │
│  ┌──────────────┐    ┌──────────────────────────────────────────┐  │
│  │  Nginx       │    │  Application Layer                       │  │
│  │  (Reverse    │    │                                          │  │
│  │   Proxy +    │    │  ┌─────────────┐  ┌─────────────────┐   │  │
│  │   SSL)       │───▶│  │ frontend-   │  │  frontend-web   │   │  │
│  │              │    │  │ hris        │  │  (Next.js 14)   │   │  │
│  │              │    │  │ (Next.js 14)│  │  saneng.co.id   │   │  │
│  │              │    │  │ hris.saneng │  │  [PUBLIC]       │   │  │
│  │              │    │  │ .co.id      │  │                 │   │  │
│  │              │    │  │ [IP LOCKED] │  └────────┬────────┘   │  │
│  └──────────────┘    │  └──────┬──────┘           │            │  │
│                      │         │                  │            │  │
│                      │         ▼                  ▼            │  │
│                      │  ┌─────────────────────────────────┐    │  │
│                      │  │      Laravel 11 (Backend API)   │    │  │
│                      │  │      PHP-FPM                    │    │  │
│                      │  │  /api/v1/*       (Sanctum auth) │    │  │
│                      │  │  /api/v1/public/* (rate limited)│    │  │
│                      │  └──────┬──────────────────────────┘    │  │
│                      │         │                               │  │
│                      │    ┌────┴──────────────────┐           │  │
│                      │    │                       │           │  │
│                      │    ▼                       ▼           │  │
│                      │  ┌──────────┐  ┌────────┐ ┌────────┐  │  │
│                      │  │PostgreSQL│  │ Redis  │ │ MinIO  │  │  │
│                      │  │          │  │(Cache+ │ │(File   │  │  │
│                      │  │          │  │ Queue) │ │Storage)│  │  │
│                      │  └──────────┘  └────────┘ └────────┘  │  │
│                      │                                        │  │
│                      │  ┌────────┐  ┌────────┐               │  │
│                      │  │ Soketi │  │WireGrd │               │  │
│                      │  │(WS)    │  │(VPN)   │               │  │
│                      │  └────────┘  └────────┘               │  │
│                      └──────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 2. Repository Structure (Monorepo)

```
/ (root)
├── backend/                    → Laravel 11
│   ├── app/
│   │   ├── Domain/             → Business rules, domain logic (NO framework dependency)
│   │   │   ├── Employee/
│   │   │   ├── Recruitment/
│   │   │   ├── Asset/
│   │   │   └── Shared/
│   │   ├── Application/        → Use cases, services
│   │   │   ├── Employee/
│   │   │   │   ├── CreateEmployeeService.php
│   │   │   │   ├── ArchiveEmployeeService.php
│   │   │   │   └── ApproveEmployeeService.php
│   │   │   ├── Recruitment/
│   │   │   ├── Asset/
│   │   │   └── Shared/
│   │   ├── Infrastructure/     → External adapters (MinIO, SMTP, Fingerprint, AI)
│   │   │   ├── Storage/
│   │   │   │   └── MinIOStorageAdapter.php
│   │   │   ├── Notification/
│   │   │   │   └── SmtpMailAdapter.php
│   │   │   └── Biometric/      → Placeholder untuk fingerprint (future)
│   │   │       └── FingerprintAdapterInterface.php
│   │   ├── Http/
│   │   │   ├── Controllers/    → Thin controllers — terima request, panggil service, return response
│   │   │   │   ├── Api/V1/
│   │   │   │   │   ├── Employee/
│   │   │   │   │   ├── Recruitment/
│   │   │   │   │   ├── Asset/
│   │   │   │   │   ├── Auth/
│   │   │   │   │   └── Public/
│   │   │   ├── Middleware/
│   │   │   │   ├── IPWhitelist.php
│   │   │   │   ├── FieldPermission.php
│   │   │   │   └── ForcePasswordReset.php
│   │   │   └── Requests/       → FormRequest validation
│   │   ├── Models/             → Eloquent models + casts + global scopes
│   │   ├── Repositories/       → DB access layer (dipanggil dari Application)
│   │   │   ├── Contracts/      → Repository interfaces
│   │   │   └── Eloquent/       → Eloquent implementations
│   │   ├── Jobs/               → Queue jobs (email, notification, export)
│   │   ├── Notifications/      → Laravel Notifications (in-app + email)
│   │   ├── Policies/           → Laravel Policies (Gate authorization)
│   │   └── Providers/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── lang/
│   │   ├── id/                 → Bahasa Indonesia (namespace per modul)
│   │   └── en/                 → English
│   ├── tests/
│   │   ├── Unit/               → Test fungsi/class terisolasi
│   │   └── Feature/            → HTTP integration test
│   └── config/
│
├── frontend-hris/              → Next.js 14 (internal portal)
│   └── src/
│       ├── components/         → UI components + shadcn/ui
│       │   ├── ui/             → shadcn/ui base components
│       │   ├── shared/         → Shared business components
│       │   └── [module]/       → Module-specific components
│       ├── pages/              → Next.js pages
│       ├── hooks/              → Custom React hooks
│       ├── services/           → API call layer
│       ├── stores/             → State management (jika pakai Zustand/Context)
│       ├── types/              → TypeScript interfaces
│       └── locales/            → i18n namespace per modul
│           ├── id/
│           └── en/
│
├── frontend-web/               → Next.js 14 (website publik)
│   └── src/
│       ├── components/
│       ├── pages/
│       │   ├── index.tsx       → Home
│       │   ├── about.tsx
│       │   ├── services.tsx
│       │   ├── contact.tsx
│       │   └── karir/
│       │       └── index.tsx   → Fetch dari /api/v1/public/jobs
│       └── locales/
│
├── docs/
│   ├── 00_PROJECT_BRIEF.md
│   ├── 02_ARCHITECTURE.md      ← file ini
│   └── adr/
│       └── ALL_ADR.md
│
├── AGENTS.md
├── CLAUDE.md
├── README.md
└── CHANGELOG.md
```

---

## 3. Backend Layered Architecture

### 3.1 Layer Overview

```
┌──────────────────────────────────────────────────────────┐
│                    Interface Layer                        │
│  HTTP Controllers · FormRequests · Middleware · Routes   │
│  TIDAK boleh memuat business logic                       │
└──────────────────────────┬───────────────────────────────┘
                           │ memanggil
┌──────────────────────────▼───────────────────────────────┐
│                   Application Layer                       │
│  Use Cases · Services · Orchestration                    │
│  EvaluateApproval · CreateEmployee · ProcessApplication  │
└──────────────────────────┬───────────────────────────────┘
                           │ memanggil
┌──────────────────────────▼───────────────────────────────┐
│                     Domain Layer                          │
│  Business Rules · Validations · State Machines           │
│  TIDAK bergantung pada framework atau external service   │
└──────────────────────────┬───────────────────────────────┘
                           │ interface (dependency inversion)
┌──────────────────────────▼───────────────────────────────┐
│                 Infrastructure Layer                      │
│  Adapters: MinIO · SMTP · Fingerprint (future) · AI      │
│  Repositories: PostgreSQL via Eloquent                   │
└──────────────────────────────────────────────────────────┘
```

### 3.2 Dependency Direction (Hukum Keras)

```
Interface → Application → Domain
                              ↑
Infrastructure ───────────────┘ (via interface/contract)
```

- **Domain** tidak boleh import class dari Laravel, Eloquent, atau package eksternal apapun.
- **Infrastructure** mengimplementasikan interface yang didefinisikan di Domain/Application.
- **Controller** hanya boleh memanggil satu Application Service per endpoint.

### 3.3 Contoh Alur Request

```
POST /api/v1/employees

1. Middleware: IPWhitelist → cek IP
2. Middleware: Auth (Sanctum) → validasi token
3. FormRequest: CreateEmployeeRequest → validasi input
4. Controller: EmployeeController@store → panggil CreateEmployeeService
5. Application: CreateEmployeeService
   ├── cek permission via Gate
   ├── validasi business rule via Domain
   ├── panggil EmployeeRepository untuk simpan data
   ├── panggil StorageAdapter untuk upload file (jika ada)
   ├── dispatch NotifyApproverJob ke Queue
   └── return Employee resource
6. Controller: return EmployeeResource (JSON)
7. ActivityLog: otomatis tercatat via model observer
```

---

## 4. Database Architecture

### 4.1 Konvensi Kolom Wajib (Semua Tabel Utama)

```sql
-- Setiap tabel utama WAJIB punya kolom berikut:
company_id      BIGINT NOT NULL REFERENCES companies(id)
archived_at     TIMESTAMP NULL DEFAULT NULL
archived_by     BIGINT NULL REFERENCES users(id)
created_by      BIGINT NULL REFERENCES users(id)
updated_by      BIGINT NULL REFERENCES users(id)
created_at      TIMESTAMP NOT NULL DEFAULT NOW()
updated_at      TIMESTAMP NOT NULL DEFAULT NOW()
```

### 4.2 Core Tables Overview

```
companies                   → Single record untuk PT Saneng (future-proof multi-company)
company_settings            → Settings per company (SMTP, password policy, retention, dll)

users                       → Login credentials + role assignment
  └── employee_id (nullable, unique) → link ke employees jika ada

employees                   → Data karyawan (profil, posisi, kontrak)
  ├── consent_at / consent_by  → UU PDP compliance
  ├── approver_id (FK users)   → Siapa yang approve perubahan data karyawan ini
  └── status / approved_by     → Approval workflow guard

employee_documents          → Dokumen karyawan (path MinIO + metadata)
employee_photos             → Foto karyawan (path MinIO)

roles                       → Dynamic RBAC — roles sebagai master data
permissions                 → Dynamic RBAC — permissions sebagai master data (format: module.action)
role_permissions            → Many-to-many roles ↔ permissions
user_roles                  → Many-to-many users ↔ roles
field_permissions           → Field-level permission untuk field sensitif

departments                 → Struktur organisasi level 1
divisions                   → Struktur organisasi level 2 (sub Department)
units                       → Struktur organisasi level 3 (sub Division, optional)

job_positions               → Jabatan (dengan code stabil)
job_levels                  → Level (Staff, Supervisor, Manager, GM, Director)
employee_types              → Jenis karyawan (Tetap, PKWT, Outsourcing, Magang, Freelance)
contract_types              → Jenis kontrak
work_locations              → Lokasi kerja

ref_types                   → Generic lookup: type registry (bank_list, education_level, dll)
ref_values                  → Generic lookup: values per type

notifications               → In-app notification (Soketi)
activity_log                → Audit trail (via spatie/laravel-activitylog)
incident_logs               → Kebocoran data (Pasal 46 UU PDP)
failed_jobs                 → Laravel default queue failure table

job_postings                → Lowongan kerja (published → muncul di website)
applicants                  → Pelamar (dari form publik website)

assets                      → Inventaris aset perusahaan
asset_assignments           → Assign aset ke karyawan
```

### 4.3 Archive Policy Implementation

```php
// Semua model utama WAJIB pakai:

// 1. Global Scope — exclude archived records dari semua query
protected static function booted(): void
{
    static::addGlobalScope('not_archived', function (Builder $query) {
        $query->whereNull('archived_at');
    });
}

// 2. Archive method — satu-satunya cara "delete"
public function archive(int $archivedByUserId): void
{
    $this->update([
        'archived_at' => now(),
        'archived_by' => $archivedByUserId,
    ]);
}
```

### 4.4 Encrypted Fields

Field berikut menggunakan Laravel `encrypted` cast di Model:

| Model | Field |
|---|---|
| Employee | `nik`, `npwp`, `bank_account_number`, `salary`, `allowances`, `deductions` |

```php
protected $casts = [
    'nik'                 => 'encrypted',
    'npwp'                => 'encrypted',
    'bank_account_number' => 'encrypted',
    'salary'              => 'encrypted',
];
```

### 4.5 Status & Approval Guard

Tabel yang punya workflow approval menyimpan:
```sql
status       VARCHAR NOT NULL DEFAULT 'draft'  -- draft | active | pending | approved | rejected
approved_by  BIGINT NULL REFERENCES users(id)
approved_at  TIMESTAMP NULL
```

Transisi status via state machine — tidak boleh langsung mass-assign `status` field.

---

## 5. API Architecture

### 5.1 Endpoint Groups

```
/api/v1/public/*        → Tanpa auth, rate limited, sanitasi ketat
  GET  /jobs            → Daftar lowongan aktif (untuk website)
  POST /applications    → Submit lamaran (rate limit: 5/10mnt per IP)

/api/v1/*               → Sanctum token required + IP whitelist
  /auth/*               → Login, logout, forgot-password, reset-password
  /employees/*          → CRUD karyawan + approval
  /recruitment/*        → Kelola lowongan + proses pelamar
  /assets/*             → CRUD aset + assignment
  /master/*             → Department, position, level, dll
  /settings/*           → Company settings (System Admin only)
  /audit/*              → Activity log viewer
  /archive/*            → Archive management
  /notifications/*      → In-app notifications
  /health               → Health check (no auth)
```

### 5.2 Response Format Standard

```json
// Success
{
  "success": true,
  "data": { ... },
  "message": "employee.created",    // i18n key, bukan string
  "meta": {                          // untuk paginated response
    "current_page": 1,
    "per_page": 20,
    "total": 150
  }
}

// Error
{
  "success": false,
  "message": "error.validation_failed",   // i18n key
  "errors": {
    "field_name": ["error.field.required"]
  }
}

// Forbidden (permission)
{
  "success": false,
  "message": "error.forbidden"
}

// IP Blocked
{
  "success": false,
  "message": "error.ip_not_allowed"    // "Akses hanya dari jaringan perusahaan"
}
```

### 5.3 Versioning

- Prefix: `/api/v1/`
- Jika breaking change di masa depan: `/api/v2/` berjalan paralel
- v1 tidak pernah di-break tanpa migration path

---

## 6. Authentication & Authorization

### 6.1 Auth Flow

```
User → POST /api/v1/auth/login
  → IPWhitelist middleware (cek IP kantor / VPN)
  → Rate limiter: 5 req/mnt per IP
  → Validate credentials
  → Cek lockout (max 5 failed attempts → lockout 15 menit)
  → Issue Sanctum token
  → Return token + user info + permissions list
  → Frontend simpan token (httpOnly cookie via Sanctum SPA mode)
```

### 6.2 Dynamic RBAC

```
User memiliki → Roles
Roles memiliki → Permissions (format: module.action)

Contoh permissions:
  employee.view          employee.create        employee.update
  employee.archive       employee.export        employee.view_salary
  recruitment.view       recruitment.create     recruitment.publish
  asset.view             asset.assign
  settings.view          settings.update
  audit.view             archive.manage

Field-level permission (tabel field_permissions):
  Mengontrol field mana yang dikembalikan di API response
  Contoh: user tanpa `employee.view_salary` tidak dapat field salary di response
```

### 6.3 Gate Check Pattern

```php
// Di Application Service — SELALU cek permission di backend
Gate::authorize('employee.view_salary');

// Atau via Policy
$this->authorize('viewSalary', $employee);

// Frontend hanya sembunyikan UI (bukan security)
// <PermissionGate permission="employee.view_salary">
//   <SalaryField />
// </PermissionGate>
```

---

## 7. File Storage Architecture

### 7.1 MinIO Bucket Structure

```
bucket: hris-saneng/
├── employees/
│   └── {employee_id}/
│       ├── photo/
│       │   ├── original.jpg
│       │   ├── medium.jpg      (auto-resize)
│       │   └── thumbnail.jpg   (auto-resize)
│       └── documents/
│           └── {doc_type}/     (ktp, npwp, ijazah, kontrak, bpjs, dll)
├── recruitment/
│   └── {job_id}/
│       └── applicants/
│           └── {applicant_id}/
├── assets/
│   └── {asset_id}/
├── exports/
│   └── {YYYY-MM-DD}/
└── backups/
    └── {YYYY-MM-DD}/
```

### 7.2 Storage Abstraction

```php
// Semua akses file via Storage facade — tidak pernah langsung ke MinIO
Storage::disk('documents')->put($path, $content);
Storage::disk('documents')->get($path);
Storage::disk('documents')->url($path);    // Signed URL untuk akses
Storage::disk('documents')->delete($path);
```

### 7.3 File Access Security

- Semua file dokumen karyawan: akses via **signed URL** (time-limited, bukan public URL)
- File publik (website assets): disk terpisah yang memang public
- Tidak ada public URL permanen untuk dokumen sensitif

---

## 8. Notification Architecture

```
Event terjadi (misal: approval request dibuat)
  │
  ├── In-App Notification
  │   ├── Tulis ke tabel `notifications`
  │   └── Broadcast via Soketi (WebSocket)
  │       └── Frontend terima event → bell icon update tanpa refresh
  │
  └── Email Notification
      ├── Dispatch NotificationJob ke Redis Queue
      │   └── Queue worker proses di background (tidak blocking)
      ├── Template: HTML branded (logo PT Saneng + tombol aksi)
      ├── Kirim via SMTP @saneng.co.id
      ├── Retry: 3x dengan exponential backoff
      └── Gagal 3x → masuk failed_jobs → alert System Admin
```

---

## 9. Security Architecture

### 9.1 Network Security

```
Internet
  │
  ├── saneng.co.id (publik, OK dari mana saja)
  │   └── Rate limit pada form lamaran: 5/10mnt per IP
  │
  └── hris.saneng.co.id (LOCKED)
      └── IPWhitelist Middleware
          ├── IP range jaringan kantor PT Saneng → ALLOW
          ├── IP WireGuard VPN → ALLOW
          └── Semua IP lain → 403
```

### 9.2 Data Security Layers

| Layer | Implementasi |
|---|---|
| Transport | HTTPS/TLS (Let's Encrypt, force HTTPS) |
| Auth | Laravel Sanctum SPA token |
| Network | IP whitelist + WireGuard VPN |
| Application | Dynamic RBAC + Field-level permission |
| Data at rest | Laravel encrypted cast untuk field sensitif |
| File | Signed URL, bukan public URL permanent |
| Secrets | .env only, tidak pernah di-commit |
| Input | FormRequest validation + XSS sanitasi |
| SQL | Eloquent ORM + Query Builder (raw SQL hanya via binding) |
| Login | Rate limit 5/mnt + lockout 15 menit setelah 5 gagal |

### 9.3 Audit Trail

```
Semua CRUD pada data pribadi → spatie/laravel-activitylog
  ├── Siapa (user_id)
  ├── Kapan (timestamp)
  ├── Apa yang diubah (old_values → new_values)
  ├── Dari mana (IP address)
  └── Field sensitif → [REDACTED] di log untuk user tanpa permission

Export data → tabel export_logs
  ├── Siapa export
  ├── Modul apa
  ├── Kapan
  └── Berapa record

Kebocoran data → tabel incident_logs (Pasal 46 UU PDP)
```

---

## 10. Frontend Architecture

### 10.1 frontend-hris (Internal Portal)

```
Next.js 14 (App Router atau Pages Router — diputuskan di Sprint 0)
  ├── Styling: Tailwind CSS + shadcn/ui
  ├── Auth: Sanctum SPA mode (httpOnly cookie)
  ├── i18n: next-i18next (namespace per modul)
  ├── State: React Context / Zustand (minimal, hanya auth + UI state)
  └── API: services/ layer (axios/fetch) — tidak fetch langsung dari component

Komponen Kunci:
  PermissionGate    → Sembunyikan UI berdasarkan permission (bukan security)
  LanguageSwitcher  → Switch ID/EN, simpan preferensi ke DB
  NotificationBell  → Real-time via Soketi WebSocket
  DataTable         → Reusable table dengan pagination + filter + sort
  FileUploader      → Upload dengan validasi MIME, size, progress
  AuditTrailViewer  → Lihat history perubahan data
```

### 10.2 frontend-web (Website Publik)

```
Next.js 14
  ├── Static pages: Home, About, Services, Contact (hardcoded, tidak perlu CMS)
  ├── Dynamic page: /karir → SSR/ISR fetch dari /api/v1/public/jobs
  ├── Form lamaran: client-side fetch ke /api/v1/public/applications
  └── i18n: next-i18next (ID/EN)

Tidak ada auth di frontend-web.
Tidak ada akses ke data internal — hanya endpoint /api/v1/public/*.
```

### 10.3 API Service Layer Pattern

```typescript
// services/employee.service.ts
export const employeeService = {
  getAll: (params) => api.get('/employees', { params }),
  getById: (id) => api.get(`/employees/${id}`),
  create: (data) => api.post('/employees', data),
  update: (id, data) => api.put(`/employees/${id}`, data),
  archive: (id) => api.post(`/employees/${id}/archive`),
};

// Tidak pernah fetch langsung dari component:
// ❌ const res = await axios.get('/api/v1/employees');
// ✅ const res = await employeeService.getAll();
```

---

## 11. Staging & Deployment

### 11.1 Environments

| Environment | URL | Branch | DB | Port |
|---|---|---|---|---|
| Production | hris.saneng.co.id / saneng.co.id | main | hris_production | 443 |
| Staging | staging.hris.saneng.co.id | staging | hris_staging | custom |
| Local Dev | localhost | feature/* | hris_local (Docker) | 3000/8000 |

### 11.2 CI/CD Flow

```
Developer buat branch → push → GitHub Actions CI
  ├── Lint (PHP + TypeScript)
  ├── Typecheck (TypeScript)
  ├── Tests (Pest PHP)
  └── Pass semua → bisa merge ke staging

Merge ke staging → manual deploy ke staging environment
Manual testing di staging → jika OK → merge ke main
Merge ke main → manual deploy ke production
```

### 11.3 Backup

```
Harian (cron job otomatis):
  ├── pg_dump → enkripsi → simpan di MinIO /backups/{YYYY-MM-DD}/
  └── MinIO snapshot

Retensi backup: 30 hari rolling

Mingguan (manual oleh owner):
  └── rsync VPS → hard disk eksternal owner

Restore drill: owner verifikasi berkala, catat di log sederhana
```

### 11.4 Health Check

```
GET /health → return:
{
  "status": "ok",
  "services": {
    "database":      "ok",
    "redis":         "ok",
    "storage":       "ok",
    "queue_worker":  "ok"
  },
  "timestamp": "2024-01-01T00:00:00Z"
}

UptimeRobot: cek endpoint ini setiap 5 menit → email alert jika down
```

---

## 12. i18n Architecture

```
Backend (Laravel):
  lang/
  ├── id/
  │   ├── employee.php
  │   ├── recruitment.php
  │   ├── auth.php
  │   ├── common.php
  │   └── errors.php
  └── en/
      ├── employee.php
      ├── recruitment.php
      ├── auth.php
      ├── common.php
      └── errors.php

Frontend (next-i18next):
  locales/
  ├── id/
  │   ├── employee.json
  │   ├── recruitment.json
  │   ├── common.json
  │   └── ...
  └── en/
      └── ... (mirror struktur id/)

Konvensi key: {namespace}.{context}.{label}
Contoh: employee.form.name | recruitment.status.pending | common.button.save

ZERO hardcoded string — baik di PHP maupun TypeScript/TSX.
Preferensi bahasa user disimpan di DB (tabel users.language_preference).
```

---

## 13. Queue Architecture

```
Redis sebagai Queue Driver

Jobs yang berjalan via Queue:
  ├── SendApprovalRequestEmail       → notify approver
  ├── SendApprovalResultEmail        → notify HR setelah approve/reject
  ├── SendSystemAlertEmail           → alert admin (failed jobs, retensi data)
  ├── SendRetentionNotificationEmail → notifikasi data melewati batas retensi
  ├── ProcessFileUpload              → resize foto karyawan
  └── GenerateExportFile             → generate Excel/CSV export

Retry policy: 3x dengan exponential backoff
Failed jobs: tabel failed_jobs → visible di dashboard admin
Queue workers: Laravel Horizon atau artisan queue:work dengan supervisor
```

---

## 14. Module Dependencies Map

```
Sprint 0: Companies, CompanySettings                    [FOUNDATION]
Sprint 1: i18n                                          [FOUNDATION]
Sprint 2: MasterData (Dept, Position, Level, RefData)  [FOUNDATION]
Sprint 3: Auth, Users                  depends on → Sprint 0,1,2
Sprint 4: RBAC (Roles, Permissions)    depends on → Sprint 3
Sprint 5: AuditLog, Archive, Settings  depends on → Sprint 3,4
Sprint 6: Employees                    depends on → Sprint 2,3,4,5
Sprint 7: Recruitment, WebsiteIntegration  depends on → Sprint 3,4,5
Sprint 8: Assets                       depends on → Sprint 3,4,5,6
```

---

*Document owner: Principal (owner sistem PT Saneng)*
*Perubahan arsitektur harus melalui ADR baru. Lihat docs/adr/ALL_ADR.md untuk keputusan arsitektur yang sudah terkunci.*
*Update dokumen ini jika ada perubahan komponen, layer, atau flow yang signifikan.*
