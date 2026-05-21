# AGENTS.md
## HRIS PT Saneng — Konstitusi untuk AI Coder
### Version: 1.0 | Status: ACTIVE | Last updated: Sprint 0

> **BACA DULU SEBELUM MENGEKSEKUSI APAPUN.**
> File ini adalah hukum tertinggi dalam repository ini.
> Semua task packet, semua instruksi eksekusi, semua request AI — tunduk pada aturan di sini.
> Jika ada konflik antara task packet dan AGENTS.md, AGENTS.md menang.

---

## Project Purpose

Repository ini berisi production codebase HRIS (Human Resource Information System) milik PT Saneng.
Sistem ini mengelola data karyawan, rekrutmen, aset, dan compliance UU PDP untuk ~700 karyawan
dengan ~3 operator HR aktif. Deployment: single-tenant, internal perusahaan, VPS Linux.

**Arsitektur:** Monorepo (Laravel 11 backend + dua frontend Next.js 14 terpisah).
**Maintainer:** Solo developer (Principal = owner sistem).

---

## Repository Map

```
/backend          → Laravel 11 (PHP) — API + business logic
  /app
    /Domain       → Business rules, validations — TIDAK boleh bergantung pada framework
    /Application  → Use cases, services (EvaluateApproval, CreateEmployee, dll)
    /Infrastructure → Adapter: MinIO, SMTP, fingerprint, AI — semua external API di sini
    /Http
      /Controllers  → Hanya terima request, panggil Application layer, return response
      /Middleware   → Auth, IPWhitelist, RateLimit, FieldPermission
      /Requests     → Form request + validation
    /Models         → Eloquent models + casts + scopes
    /Repositories   → Akses DB terpusat — dipanggil dari Application layer
  /database
    /migrations   → Semua migration — tidak pernah destructive tanpa backup step
    /seeders      → Default data seeder
  /tests
    /Unit         → Test fungsi/class terisolasi
    /Feature      → HTTP integration test
/frontend-hris    → Next.js 14 (internal portal — hris.saneng.co.id)
  /src
    /components   → UI components + shadcn/ui
    /pages        → Next.js pages
    /hooks        → Custom React hooks
    /services     → API call layer (axios/fetch)
    /locales      → i18n namespace per modul
/frontend-web     → Next.js 14 (website publik — saneng.co.id)
/docs             → Dokumen fondasi (PROJECT_BRIEF, ARCHITECTURE, dll)
AGENTS.md         → File ini
CLAUDE.md         → Instruksi spesifik Claude Code
README.md         → Setup operasional
CHANGELOG.md      → Catatan perubahan per sprint
```

---

## Core Architectural Rules

### A. Data Isolation & Multi-Company Guard

```
RULE-A1: Semua query ke tabel utama WAJIB filter company_id.
         Tidak ada query yang mengembalikan data lintas company.
         Default company_id = ID PT Saneng (dari config/dari seeder).

RULE-A2: Semua tabel utama WAJIB punya kolom berikut (tanpa pengecualian):
         - company_id          (FK ke tabel companies, NOT NULL)
         - archived_at         (nullable timestamp)
         - archived_by         (nullable FK ke users.id)
         - created_by          (nullable FK ke users.id)
         - updated_by          (nullable FK ke users.id)
         - created_at          (Laravel default)
         - updated_at          (Laravel default)
```

### B. Archive Policy — ZERO Hard Delete

```
RULE-B1: TIDAK ADA ->delete() di production code.
         Satu-satunya yang boleh memanggil delete/forceDelete adalah ArchiveService.
         Jika temukan ->delete() di luar ArchiveService → STOP, laporkan sebagai violation.

RULE-B2: Semua model utama WAJIB pakai Global Scope yang filter whereNull('archived_at').
         Sehingga semua query otomatis exclude archived records.

RULE-B3: Semua migration destruktif (drop column, rename, drop table) WAJIB diawali dengan
         backup step yang terdokumentasi. Tidak pernah langsung destructive.
```

### C. Security — Permission & Auth

```
RULE-C1: Permission check SELALU di backend (Laravel Gate/Policy).
         Frontend PermissionGate hanya untuk UX (sembunyikan tombol).
         Frontend TIDAK pernah menjadi satu-satunya security layer.

RULE-C2: Field-level permission via tabel field_permissions.
         Field sensitif (salary, NIK, rekening, NPWP) tidak boleh dikembalikan
         di API response jika user tidak punya field permission yang sesuai.

RULE-C3: IP whitelist enforcement via middleware IPWhitelist.
         Akses ke frontend-hris dan semua /api/v1/* private endpoint
         hanya boleh dari IP range kantor atau IP WireGuard VPN.
         Jika akses dari luar → 403 "Akses hanya dari jaringan perusahaan".

RULE-C4: Rate limiting wajib di:
         - Login endpoint: 5 request/menit per IP
         - Public form lamaran: 5 submission/10 menit per IP
         - Semua /api/v1/public/* endpoint
```

### D. Enkripsi — Field Sensitif

```
RULE-D1: Field berikut WAJIB menggunakan Laravel `encrypted` cast di Model:
         - NIK KTP
         - NPWP pribadi
         - Nomor rekening bank
         - Data gaji/kompensasi (salary, allowances, deductions)

RULE-D2: Field terenkripsi TIDAK boleh di-filter langsung via SQL/query builder.
         Jika perlu search, ambil dan decrypt di application layer.

RULE-D3: Data biometrik (sidik jari fingerprint — future) masuk Data Pribadi Spesifik
         per Pasal 4 UU PDP — enkripsi wajib saat diintegrasikan.
```

### E. Layered Architecture — Dependency Direction

```
RULE-E1: Dependency direction: Interface → Application → Domain.
         Domain TIDAK bergantung pada framework, Eloquent, atau external service apapun.
         Infrastructure bergantung pada interface yang didefinisikan Domain.

RULE-E2: Controller hanya boleh:
         1. Menerima request (via FormRequest)
         2. Memanggil satu method Application Service
         3. Return response (resource/JSON)
         Controller TIDAK boleh memuat business logic.

RULE-E3: Akses DB hanya melalui Repository.
         Tidak ada Eloquent query di Controller atau Application Service secara langsung.
         Semua DB interaction: Controller → Service → Repository → Model.

RULE-E4: Semua external API (MinIO, SMTP, fingerprint, AI) wajib melalui Adapter
         di Infrastructure layer. Domain tidak pernah tahu implementasi eksternal.
         Saat vendor diganti: buat adapter baru, tidak ubah domain.
```

### F. File Storage

```
RULE-F1: Semua akses file WAJIB melalui Storage::disk('documents')->...
         atau Storage::disk('public')->...
         TIDAK PERNAH memanggil MinIO/S3 SDK secara langsung dari luar adapter.

RULE-F2: Path file mengikuti konvensi yang sudah ditetapkan:
         - Foto karyawan:   employees/{employee_id}/photo/
         - Dokumen:         employees/{employee_id}/documents/{doc_type}/
         - Recruitment:     recruitment/{job_id}/applicants/{applicant_id}/
         - Asset:           assets/{asset_id}/
         - Export:          exports/{YYYY-MM-DD}/
         - Backup:          backups/{YYYY-MM-DD}/

RULE-F3: Foto karyawan max 2MB, format JPG/PNG, auto-resize saat upload.
         Dokumen karyawan max 10MB, format PDF/JPG/PNG.
```

### G. Internasionalisasi (i18n) — Zero Hardcoded String

```
RULE-G1: TIDAK ADA hardcoded string di UI (frontend maupun backend response).
         Semua string melalui i18n key.
         Backend: lang/id/ dan lang/en/ (namespace per modul).
         Frontend: next-i18next, namespace per modul.

RULE-G2: Format key: {namespace}.{context}.{label}
         Contoh: employee.form.name, recruitment.status.pending, common.button.save

RULE-G3: Jika membuat UI component baru → WAJIB buat translation key di kedua bahasa (id + en).
         Jangan pernah buat key hanya di satu bahasa.
```

### H. Audit Log & Compliance UU PDP

```
RULE-H1: Semua operasi CRUD pada data pribadi karyawan WAJIB dicatat oleh
         spatie/laravel-activitylog. Model yang handle data pribadi WAJIB pakai trait LogsActivity.

RULE-H2: Field sensitif DI-MASK di log (tampil sebagai [REDACTED]) untuk user tanpa permission.
         NIK, salary, rekening, NPWP tidak boleh muncul sebagai plaintext di activity log.

RULE-H3: Setiap export data (Excel/CSV) WAJIB dicatat: siapa, kapan, modul apa, berapa record.
         Log export tidak bisa dinonaktifkan.

RULE-H4: Kolom consent WAJIB di tabel employees:
         - consent_at  (timestamp, kapan HR centang)
         - consent_by  (FK ke users.id, siapa HR yang centang)
         Data karyawan baru TIDAK BISA disimpan tanpa consent_at terisi.
```

### I. Approval & Status Guard

```
RULE-I1: Semua tabel yang punya workflow approval WAJIB punya kolom:
         - status      (enum/string, minimal: draft | active)
         - approved_by (nullable FK ke users.id)
         - approved_at (nullable timestamp)
         Ini mencegah ALTER TABLE saat approval workflow diaktifkan.

RULE-I2: Transisi status TIDAK boleh bebas diubah langsung via mass assignment.
         Status transition harus melalui dedicated method/service yang validate
         bahwa transisi tersebut valid (state machine pattern).

RULE-I3: Approval request mengirim notifikasi ke approver via:
         1. In-app real-time (Soketi WebSocket)
         2. Email (SMTP via Queue, retry 3x dengan exponential backoff)
         Notifikasi TIDAK boleh blocking request cycle.
```

### J. Queue & Notification

```
RULE-J1: Semua email notification dikirim via Laravel Queue (tidak blocking).
         Tidak pernah kirim email synchronous dalam request cycle.

RULE-J2: Queue retry: 3x dengan exponential backoff.
         Setelah 3x gagal: masuk failed_jobs, kirim alert ke System Admin.

RULE-J3: Failed jobs HARUS visible di admin dashboard dan bisa di-retry manual.
```

### K. Master Data

```
RULE-K1: Master data (department, position, employee type, dll) tidak pernah dihapus.
         Gunakan kolom is_active untuk menonaktifkan.
         Foreign key TIDAK cascade delete.

RULE-K2: Setiap master data punya kolom `code` yang stabil untuk referensi di kode.
         Jangan hardcode nama/label — selalu gunakan code.
```

---

## Do-Not-Touch Areas

```
DO-NOT-TOUCH-1: Jangan modifikasi ArchiveService kecuali task secara eksplisit menyebutnya.
                ArchiveService adalah single point of truth untuk semua soft-delete logic.

DO-NOT-TOUCH-2: Jangan modifikasi middleware IPWhitelist kecuali task secara eksplisit menyebutnya.
                Perubahan di sini bisa membuka celah akses keamanan.

DO-NOT-TOUCH-3: Jangan drop atau rename kolom di migration tanpa backup step eksplisit
                yang tertulis di task packet.

DO-NOT-TOUCH-4: Jangan ubah struktur tabel companies dan company_settings
                tanpa diskusi dengan Principal terlebih dahulu.

DO-NOT-TOUCH-5: Jangan ubah format permission string (module.action) — ini adalah kontrak
                yang digunakan di seluruh codebase. Mengubah format = breaking change global.

DO-NOT-TOUCH-6: Jangan tambah dependency (composer package atau npm package) baru
                tanpa menyebutkannya di completion report. Principal harus tahu semua dependency baru.
```

---

## Development Commands

```bash
# Backend (dari /backend)
composer install          # Install dependencies
php artisan migrate       # Jalankan migration
php artisan db:seed       # Jalankan seeders
php artisan test          # Jalankan semua test (PHPUnit/Pest)
./vendor/bin/pest         # Jalankan Pest PHP tests
php artisan test --filter # Jalankan test spesifik
php artisan lint          # (jika dikonfigurasi) Jalankan linter
./vendor/bin/phpstan analyse  # Static analysis

# Frontend HRIS (dari /frontend-hris)
npm install               # Install dependencies
npm run dev               # Dev server
npm run build             # Production build
npm run lint              # ESLint
npx tsc --noEmit          # TypeScript check

# Frontend Web (dari /frontend-web)
npm install
npm run dev
npm run build
npm run lint
npx tsc --noEmit

# Docker Compose (dari root)
docker compose up -d      # Start semua service (dev)
docker compose down       # Stop semua service
docker compose logs -f    # Lihat logs
```

---

## Coding Conventions

### PHP/Laravel
- Prefer small pure functions dengan nama yang mencerminkan business intent.
- Gunakan explicit error handling — tidak pernah swallow exception diam-diam.
- Tidak ada hidden global state.
- Semua FormRequest wajib punya `authorize()` yang benar (tidak selalu return true).
- Gunakan typed properties dan return types (PHP 8.x features).
- Eloquent: gunakan `$fillable` atau `$guarded`, tidak pernah `$guarded = []` secara global.
- Magic numbers dan magic strings → extract ke konstanta atau config.
- Decimal/monetary calculation: gunakan BCMath atau dedicated Money library, TIDAK pernah float.

### TypeScript/React
- Strict TypeScript: tidak ada `any` kecuali ada alasan sangat kuat dan dikomentari.
- Component kecil dan single-responsibility.
- Custom hooks untuk business logic yang reusable.
- Semua API call melalui service layer (`/services/`), tidak langsung dari component.
- PermissionGate component untuk sembunyikan UI — bukan untuk security enforcement.

### Umum
- File tidak boleh melebihi 300 baris. Jika melebihi → pecah menjadi file lebih kecil.
- Nama fungsi/method mencerminkan business intent (bukan teknis). Contoh: `createEmployee()` bukan `insertUserRecord()`.
- Tidak ada TODO pada flow kritis — selesaikan atau dokumentasikan sebagai known limitation.
- Semua perubahan behavior → update test yang relevan.

---

## Verification Rule

Sebelum mengklaim task selesai, AI WAJIB:

1. Jalankan test yang relevan dan laporkan hasilnya.
2. Jalankan lint/typecheck jika berlaku.
3. Verifikasi tidak ada file di luar scope yang tersentuh.
4. Laporkan semua file yang diubah.
5. Laporkan semua test yang dijalankan dan statusnya.
6. Laporkan jika ada unresolved issue atau risiko residual.

**Completion report format:**
```
## Completion Report

### Implementation Summary
[Apa yang diimplementasikan]

### Files Changed
- path/to/file.php — [deskripsi singkat perubahan]
- path/to/test.php — [deskripsi singkat]

### Tests Run
- TestClass::testMethod → PASS
- TestClass::testMethod2 → PASS

### Risks & Follow-up
- [Jika ada]

### Violations Found (jika ada)
- [Jika ditemukan violation dari AGENTS.md rules]
```

---

## Documentation Rule

Update dokumentasi ketika:
- Behavior sistem berubah → update docs/ yang relevan
- Environment variable baru ditambahkan → update .env.example + docs/06_OPERATIONS.md
- API contract berubah → update docs/03_TECH_SPEC.md
- Arsitektur berubah → update docs/02_ARCHITECTURE.md
- Sprint task selesai → update CHANGELOG.md

Dokumen yang tidak diperbarui lebih berbahaya daripada tidak ada dokumen.

---

## Security Rules

```
SEC-1: TIDAK PERNAH hardcode secret, API key, password, atau credential di kode.
       Semua melalui .env — jika tidak ada di .env, buat entry di .env.example (tanpa nilai).

SEC-2: TIDAK PERNAH log credential, token, password, NIK, rekening, atau data pribadi spesifik.
       Field sensitif di log = [REDACTED].

SEC-3: Semua external input WAJIB divalidasi via FormRequest sebelum diproses.
       Ini berlaku untuk endpoint public maupun private.

SEC-4: Public form (form lamaran kandidat di website) WAJIB sanitasi input XSS.

SEC-5: Raw SQL query DILARANG kecuali sangat perlu. Jika terpaksa raw query,
       WAJIB menggunakan parameter binding. Tidak pernah string concatenation di SQL.

SEC-6: Login lockout: 5 percobaan gagal → lockout 15 menit. Ini TIDAK boleh dinonaktifkan.

SEC-7: Semua file upload WAJIB divalidasi: MIME type, ukuran max, extension whitelist.
       Tidak pernah trust client-provided MIME type saja.
```

---

## Compliance Rules (UU PDP No. 27/2022)

```
COMP-1: Data retensi default (configurable via Settings, minimum tidak bisa dikurangi):
        - Data karyawan + dokumen pembukuan: 10 tahun (UU KUP Pasal 28 ayat 11)
        - Data karyawan non-pembukuan:       5 tahun setelah hubungan kerja berakhir
        - Data kandidat tidak lolos:         1 tahun
        - Audit log:                         2 tahun minimum
        - Log teknis aplikasi:               90 hari

COMP-2: Sistem TIDAK auto-delete data yang melewati retensi.
        Sistem kirim notifikasi ke System Admin → Admin yang review dan approve penghapusan.
        Penghapusan yang diapprove tetap dicatat di audit trail.

COMP-3: Incident log (tabel incident_logs) WAJIB tersedia untuk dokumentasi
        kebocoran data jika terjadi (Pasal 46 UU PDP).
```

---

## Hal yang TIDAK Boleh Masuk ke File Ini

- Acceptance criteria per fitur (masuk task packet)
- Catatan debugging satu masalah spesifik
- Transcript diskusi atau keputusan sementara
- PRD atau requirement detail per modul
- Log error spesifik sesi development

---

*AGENTS.md adalah living document — update ketika ada aturan baru yang perlu berlaku lintas sesi.*
*Setiap perubahan di AGENTS.md harus di-commit dengan message yang jelas dan dicatat di CHANGELOG.md.*
