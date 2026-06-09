# AGENTS.md
## Dictive-HR — Konstitusi untuk AI Coder
### Version: 1.0 | Status: ACTIVE | Phase: Foundation

> **BACA DULU SEBELUM MENGEKSEKUSI APAPUN.**
> File ini adalah hukum tertinggi dalam repository ini.
> Jika ada konflik antara task packet dan AGENTS.md, AGENTS.md menang.

---

## Project Purpose

Dictive-HR adalah platform HRIS self-hosted modular untuk pasar Indonesia. Satu instance bisa menampung banyak company. Modul bisa di-install dan di-uninstall di level instance, di-enable dan di-disable di level company.

**Stack:** Laravel 11 (backend API) + Next.js 14 App Router (frontend semua surface).
**Maintainer:** Solo developer (vendor platform).

---

## Repository Map

```
/backend
  /app
    /Core              → Core platform logic (Company, Auth, Permission, ModuleRegistry, dll)
    /Modules           → Semua modul (mandatory + optional), masing-masing self-contained
      /Karyawan        → MANDATORY
      /Kalender        → MANDATORY
      /Recruitment     → OPTIONAL
      /Aset            → OPTIONAL
      /Website         → OPTIONAL
    /Domain            → Shared domain logic lintas modul
    /Application       → Shared use cases
    /Infrastructure    → External adapters (MinIO, SMTP)
    /Http
      /Controllers/Api/V1
        /Core          → Instance-level endpoints
        /Public        → Public endpoints (tanpa auth)
      /Middleware
      /Requests
    /Models
    /Repositories
    /Providers
      /ModuleServiceProvider.php  → Register modul aktif secara dinamis
  /database
    /migrations        → Core migrations only (module migrations ada di dalam modul)
    /seeders           → Core seeders only
  /lang/id, /lang/en

/frontend
  /src
    /app
      /dashboard       → Surface 1: Platform Dashboard (protected)
      /[company]       → Surface 2 + 3: Company Website + Candidate Portal (public)
    /components/ui, /core, /modules
    /services          → Semua API call dari sini, tidak langsung dari component
    /locales/id, /en
```

---

## Core Architectural Rules

### A. Multi-Company Data Isolation

```
RULE-A1: Semua query ke tabel utama WAJIB filter company_id.
         Tidak ada query yang mengembalikan data lintas company.
         Diimplementasikan via Global Scope di semua model.

RULE-A2: Semua tabel utama WAJIB punya kolom:
         company_id, archived_at, archived_by, created_by, updated_by, created_at, updated_at
         Pengecualian: tabel instance-level (instance_settings, module_registry, companies)
         tidak punya company_id.

RULE-A3: Middleware ResolveCompany wajib inject company_id ke setiap request company-scoped.
         Request tanpa company context yang valid → 400 Bad Request.
```

### B. Module System

```
RULE-B1: Setiap modul WAJIB punya module.json dengan field:
         code, name, version, is_mandatory, dependencies, min_core_version

RULE-B2: Module migration ada di dalam folder modul (/Modules/{Name}/Database/migrations/)
         bukan di /database/migrations/ core.

RULE-B3: Modul mandatory (Karyawan, Kalender) tidak boleh muncul di UI toggle install/uninstall.
         is_mandatory: true di module.json mereka.

RULE-B4: Sebelum enable modul di company, sistem WAJIB cek semua dependencies sudah enabled.
         Jika dependency tidak terpenuhi → tolak dengan pesan jelas modul apa yang dibutuhkan.

RULE-B5: Sebelum uninstall modul di instance, sistem WAJIB generate export data per company.
         Uninstall tidak bisa dilanjutkan tanpa konfirmasi Instance Admin + warning UU PDP.

RULE-B6: Format nomor entity (karyawan, dll) menggunakan token engine.
         Developer mendefinisikan token yang tersedia. Admin menyusun format dari
         token tersebut. Tidak ada eval() atau arbitrary code execution untuk
         generate nomor.
```

### C. Archive Policy — ZERO Hard Delete

```
RULE-C1: TIDAK ADA ->delete() di production code.
         Satu-satunya yang boleh memanggil delete adalah proses uninstall modul dan
         proses retensi data yang sudah diapprove Instance Admin.

RULE-C2: Semua model utama WAJIB pakai Global Scope whereNull('archived_at').

RULE-C3: Method archive(int $userId) tersedia di semua model utama.
         Tombol di UI: "Arsipkan" bukan "Hapus".
```

### D. Security — Permission & Auth

```
RULE-D1: Permission check SELALU di backend (Gate/Policy).
         Frontend PermissionGate hanya untuk UX — bukan security layer.

RULE-D2: Field sensitif (NIK, NPWP, rekening, gaji) WAJIB pakai Laravel encrypted cast.
         Field ini tidak boleh di-filter langsung via SQL.

RULE-D3: Rate limiting wajib di:
         - Login: 5 req/menit per IP
         - Public apply lamaran: 5/10 menit per IP
         - Semua /api/v1/public/* endpoint

RULE-D4: Login lockout: 3 kali gagal → lockout sesuai setting company.

RULE-D5: Platform Administrator punya semua permission karena role-nya mencakup
         semua permission — bukan karena bypass logic atau kondisi khusus di kode.
         Flow bisnis proses tetap sama untuk semua role.
```

### E. Layered Architecture

```
RULE-E1: Dependency direction: Interface → Application → Domain.
         Domain TIDAK bergantung pada framework, Eloquent, atau external service.

RULE-E2: Controller hanya boleh:
         1. Terima request (via FormRequest)
         2. Panggil satu Application Service
         3. Return response
         Controller TIDAK boleh memuat business logic.

RULE-E3: Akses DB hanya melalui Repository.
         Controller → Service → Repository → Model.

RULE-E4: Semua external API (MinIO, SMTP) wajib melalui Adapter di Infrastructure layer.
```

### F. i18n — Zero Hardcoded String

```
RULE-F1: TIDAK ADA hardcoded string di UI maupun backend response.
         Backend: __('namespace.context.label')
         Frontend: t('namespace:context.label')

RULE-F2: Format key: {namespace}.{context}.{label}
         Contoh: karyawan.form.nama, recruitment.status.pending, common.button.simpan

RULE-F3: Setiap komponen UI baru WAJIB tambah translation key di KEDUA bahasa (id + en).
```

### G. Audit Log

```
RULE-G1: Semua CRUD pada data utama WAJIB dicatat via spatie/laravel-activitylog.
         Model yang handle data utama WAJIB pakai trait LogsActivity.

RULE-G2: Field sensitif di log tampil sebagai [REDACTED] untuk user tanpa permission.

RULE-G3: Audit log adalah append-only. Tidak ada delete atau update pada audit log,
         termasuk oleh Instance Admin.

RULE-G4: Setiap export data WAJIB dicatat: siapa, kapan, modul apa, berapa record.
```

### H. Queue & Notification

```
RULE-H1: Semua email dikirim via Laravel Queue — tidak pernah synchronous dalam request cycle.

RULE-H2: Retry policy: 3x dengan exponential backoff (30s, 60s, 120s).
         Gagal 3x → failed_jobs → alert Instance Admin.
```

### I. API & Response

```
RULE-I1: Backend tidak pernah serve HTML. Pure REST API.

RULE-I2: Semua response mengikuti format standard:
         { "success": bool, "data": {}, "message": "i18n.key", "meta": {} }

RULE-I3: Semua API call dari frontend WAJIB melalui service layer (/services/).
         Tidak pernah fetch langsung dari component.
```

---

## Do-Not-Touch Areas

```
DNT-1: Jangan modifikasi ModuleServiceProvider kecuali task eksplisit menyebutnya.
       Ini adalah titik registrasi semua modul — bug di sini mempengaruhi seluruh platform.

DNT-2: Jangan modifikasi struktur tabel companies dan instance_settings
       tanpa diskusi dengan Principal.

DNT-3: Jangan drop atau rename kolom di migration tanpa backup step eksplisit di task packet.

DNT-4: Jangan ubah format permission string (module.action) — ini kontrak global.
       Mengubah format = breaking change di seluruh codebase.

DNT-5: Jangan tambah dependency (composer/npm) baru tanpa menyebutkan di completion report.

DNT-6: Jangan ubah audit log table schema — append-only, tidak boleh ada delete.
```

---

## Coding Conventions

### PHP/Laravel
- Small pure functions, nama mencerminkan business intent
- Typed properties dan return types (PHP 8.x)
- Explicit error handling — tidak pernah swallow exception
- `$fillable` di semua Eloquent model — tidak pernah `$guarded = []` global
- Decimal/monetary: BCMath atau Money library — TIDAK float
- Magic numbers → konstanta atau config

### TypeScript/React
- Strict TypeScript — tidak ada `any` tanpa alasan kuat + komentar
- Component single-responsibility, file max 300 baris
- Custom hooks untuk business logic reusable
- Semua API call melalui `/services/` — tidak langsung dari component

### Umum
- File tidak melebihi 300 baris — jika lebih, pecah jadi file lebih kecil
- Tidak ada TODO pada flow kritis
- Semua perubahan behavior → update test yang relevan

---

## Verification Rule

Sebelum klaim task selesai, AI WAJIB:

1. Jalankan test relevan dan laporkan hasilnya
2. Jalankan lint/typecheck jika berlaku
3. Verifikasi tidak ada file di luar scope yang tersentuh
4. Laporkan semua file yang diubah
5. Laporkan semua test yang dijalankan dan statusnya
6. Laporkan jika ada unresolved issue atau risiko residual

**Completion report format:**
```
## Completion Report

### Implementation Summary
[Apa yang diimplementasikan]

### Files Changed
- path/to/file.php — [deskripsi singkat]

### Tests Run
- TestClass::testMethod → PASS

### Risks & Follow-up
- [Jika ada]

### Violations Found
- [Jika ada violation dari AGENTS.md]
```

---

## Security Rules

```
SEC-1: TIDAK PERNAH hardcode secret, API key, password di kode. Semua via .env.
SEC-2: TIDAK PERNAH log credential, token, NIK, rekening, atau data pribadi spesifik.
SEC-3: Semua external input WAJIB divalidasi via FormRequest sebelum diproses.
SEC-4: Raw SQL DILARANG kecuali sangat perlu — wajib parameter binding, tidak pernah string concatenation.
SEC-5: Semua file upload WAJIB divalidasi: MIME type, ukuran max, extension whitelist.
```

---

## Compliance Rules (UU PDP No. 27/2022 & UU Ketenagakerjaan)

```
COMP-1: Data retensi (minimum, tidak bisa dikurangi via settings):
        - Data karyawan + dokumen pembukuan: 10 tahun (UU KUP Pasal 28 ayat 11)
        - Data karyawan non-pembukuan: 5 tahun setelah hubungan kerja berakhir
        - Data kandidat tidak lolos: 1 tahun
        - Audit log: 2 tahun minimum

COMP-2: Sistem TIDAK auto-delete data yang melewati retensi.
        Sistem kirim notifikasi → Instance Admin / HR review dan approve penghapusan.

COMP-3: Warning UU PDP wajib tampil setiap kali ada aksi yang berpotensi hapus data permanen
        (uninstall modul, proses retensi).

COMP-4: Data model modul Karyawan WAJIB accommodate PKWT dan PKWTT
        sesuai UU Ketenagakerjaan No. 13/2003.

COMP-5: Export data karyawan WAJIB tersedia dan dicatat di audit log.
```

---

*AGENTS.md adalah living document — update ketika ada aturan baru yang berlaku lintas sesi.*
*Setiap perubahan harus di-commit dengan message jelas dan dicatat di CHANGELOG.md.*

---

## Context Files

Sebelum memulai task baru, baca file berikut secara berurutan:

1. `START_HERE.md` — entry-point status retrofit dan task berikutnya.
2. `AGENTS.md` — aturan tertinggi repository.
3. `VIBE_CODING_CONTEXT.md` — framework kerja, workflow Git, dan protokol task.
4. `TASK_BREAKDOWN.md` — checklist milestone dan status pekerjaan.
5. `docs/UIUX_SPEC.md` — spesifikasi UI/UX untuk retrofit frontend.
6. `docs/alignment/OPERATING_PLAN.md` — protokol resume lintas sesi.
7. `docs/alignment/AUDIT_REPORT_2026-06-02.md` — audit alignment terakhir dan risiko residual.
