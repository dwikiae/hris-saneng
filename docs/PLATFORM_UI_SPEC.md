# PLATFORM_UI_SPEC.md — Dictive-HR
**Status:** Settled — siap dipakai Claude Code
**Dibuat:** 2 Juni 2026
**Update:** 3 Juni 2026 — tambah keputusan Settings, RBAC, Navigation, Search, Arsip
**Relasi:** Turunan dari docs/UIUX_SPEC.md
**Scope:** Platform UI layer — berlaku untuk semua modul tanpa kecuali

> Dokumen ini mendefinisikan semua UI component dan pattern yang disediakan platform.
> Developer modul TIDAK boleh membangun ulang component yang sudah ada di sini.
> Jika modul butuh variasi — extend, jangan replace.

---

## 1. Prinsip Dasar

### Platform vs Modul
```
PLATFORM menyediakan:
  - Template halaman (List, Detail, Form)
  - Behavior universal (approval flow, audit trail, export, upload)
  - Pattern navigasi dan URL
  - State management UI (loading, empty, error)
  - Permission guard
  - Notification system
  - Settings Platform (company, users, config, audit, modules)

MODUL menyediakan:
  - Kolom tabel spesifik modul
  - Filter options spesifik modul
  - Form fields spesifik modul
  - Summary strip content spesifik modul
  - Tab content spesifik modul
  - Master data modul (ada di dalam modul, bukan platform)
  - Settings modul (ada di dalam modul, bukan platform)
  - Visualisasi khusus (kanban, kalender, dll)
```

### Aturan Tidak Boleh Dilanggar
```
PLAT-1:  Setiap halaman list WAJIB pakai ListPageTemplate
PLAT-2:  Setiap halaman detail WAJIB pakai DetailPageTemplate
PLAT-3:  Setiap form tambah/edit WAJIB pakai FormPageTemplate
PLAT-4:  Tab state WAJIB ada di URL query parameter, bukan hanya React state
PLAT-5:  Loading state WAJIB pakai skeleton yang menyerupai konten, bukan spinner
PLAT-6:  Spinner HANYA untuk aksi singkat (submit button, quick action)
PLAT-7:  Tabel di mobile WAJIB berubah jadi card stack
PLAT-8:  Export WAJIB tersedia di semua ListPage
PLAT-9:  Approval flow UI WAJIB konsisten di semua modul
PLAT-10: Audit trail/Chatter WAJIB ada di semua DetailPage
PLAT-11: Breadcrumb WAJIB ada di semua halaman — terutama Settings multi-level
PLAT-12: Sidebar modul WAJIB pakai section grouping untuk modul dengan banyak menu
PLAT-13: Halaman arsip WAJIB terpisah dari halaman list aktif
PLAT-14: Search WAJIB scoped per modul yang sedang aktif (bukan lintas modul)
PLAT-15: Menu dalam modul muncul/tidak berdasarkan permission — tidak hardcoded
```

---

## 2. URL & Navigation Pattern

### Standard URL Structure (Berlaku Semua Modul)
```
List:
  /dashboard/{module}

List arsip:
  /dashboard/{module}/archived

Detail — tab pertama (default):
  /dashboard/{module}/{id}

Detail — tab spesifik:
  /dashboard/{module}/{id}?tab={tab-slug}

Detail item dalam tab:
  /dashboard/{module}/{id}/{sub-resource}/{sub-id}

Settings modul:
  /dashboard/{module}/settings
```

### Contoh Implementasi per Modul
```
Karyawan:
  /dashboard/employees
  /dashboard/employees/archived
  /dashboard/employees/EMP-001
  /dashboard/employees/EMP-001?tab=kontrak
  /dashboard/employees/EMP-001/contracts/CTR-001
  /dashboard/employees/settings

Rekrutmen:
  /dashboard/recruitment/jobs
  /dashboard/recruitment/jobs/archived
  /dashboard/recruitment/jobs/JOB-001
  /dashboard/recruitment/jobs/JOB-001?tab=pelamar

Settings Platform:
  /dashboard/settings/companies
  /dashboard/settings/companies/[id]
  /dashboard/settings/users
  /dashboard/settings/roles/[id]
  /dashboard/settings/config
  /dashboard/settings/audit
  /dashboard/settings/modules
```

### Behavior
- Tab state di URL → shareable, bookmarkable, browser back/forward berfungsi benar
- Deep link ke tab tertentu harus langsung membuka tab tersebut tanpa redirect
- Query parameter `?tab=` menggunakan slug lowercase dengan tanda hubung
- Breadcrumb selalu mencerminkan posisi user dalam hierarki navigasi

---

## 3. Sidebar Pattern

### Sidebar Platform (Navigasi Utama)
```
[Logo + Nama Platform]

[Core Modules]
  Dashboard
  Karyawan
  Kalender
  [Modul opsional aktif — muncul otomatis]

─────────────────────   ← garis pemisah

⚙ Settings             ← footer, visual berbeda (hanya Platform Admin)
👤 Profile
🚪 Logout
```

### Sidebar Modul (Section Grouping)
Untuk modul dengan banyak menu, gunakan section grouping dengan label:

```
Contoh Modul Karyawan:
  ADMINISTRASI
    Data Karyawan      ← muncul jika punya karyawan.data.view
    Kontrak            ← muncul jika punya karyawan.kontrak.view
    Dokumen            ← muncul jika punya karyawan.dokumen.view

  PROSES
    Offboarding        ← muncul jika punya karyawan.offboarding.view

  ─────────────────────
  ⚙ Pengaturan        ← muncul jika punya karyawan.settings
```

### Rules Sidebar Modul
- Label section adalah visual separator — tidak bisa diklik
- Menu muncul/tidak berdasarkan permission — PLAT-15
- Settings modul selalu di footer sidebar modul — terpisah garis
- Active state: background biru-100, teks biru-700, left border biru-600 (2px)

---

## 4. Settings Platform

### Struktur
```
/dashboard/settings/
  companies     → Company Management
  users         → Users & Access (Tab Users + Tab Roles)
  config        → Platform Config
  audit         → Audit Log
  modules       → Module Registry
```

### Akses
- Semua halaman `/dashboard/settings/*` hanya untuk Platform Administrator
- Company Administrator hanya bisa akses settings dalam company-nya
- Gunakan `<PermissionGate permission="platform.settings">` di sidebar dan routes

### Company Management
Data form company:

```
IDENTITAS PERUSAHAAN
  Nama perusahaan *
  Logo (upload)
  Tagline / deskripsi singkat
  Jenis perusahaan (PT / CV / Yayasan / Koperasi / dll)
  Bidang usaha
  Tanggal berdiri

ALAMAT & KONTAK
  Alamat lengkap *
  Kota *, Provinsi *, Kode Pos
  Nomor telepon
  Email perusahaan
  Website
  PIC HR: nama + nomor HP + email

DATA LEGAL INDONESIA
  NPWP perusahaan
  NIB / SIUP
  Nomor BPJS Ketenagakerjaan
  Nomor BPJS Kesehatan
  Nomor WLKP (Wajib Lapor Ketenagakerjaan)
  Nama direktur / penanggung jawab

KONFIGURASI OPERASIONAL
  Timezone *
  Format tanggal
  Bahasa default (ID / EN)
  SMTP: host, port, username, password (masked), from name, from email
        + tombol "Kirim Email Test"
  Lockout login: jumlah percobaan + durasi lockout (menit)
  Durasi sesi (jam) — default 8 jam

SKALA KARYAWAN
  Auto-fetch dari jumlah karyawan aktif — read-only, tidak bisa di-input

MODUL AKTIF
  Toggle per modul opsional yang terinstall
```

### Users & Access

**Tab Users:**
```
ListPageTemplate dengan kolom:
  Nama, Email, Role (badges, multi), Company (badges, multi), Status, Aksi

Form user baru / edit:
  Nama lengkap *
  Email * (unik)
  Password (hanya saat buat baru — kirim via email invitation)
  Kaitkan ke karyawan (searchable dropdown, opsional)
    → jika dikaitkan: scope otomatis company karyawan
    → jika tidak: assign company manual
  Assign role (multi-select, per company)
  Assign company (multi-select)
    → Platform Admin: akses semua company (tidak perlu assign)
```

**Tab Roles:**
```
List role dengan filter per company

Form role baru:
  Nama role *
  Deskripsi
  Salin dari job position (opsional — tarik template permission)
  Salin dari role lain (opsional)

Detail role → Matrix Permission:
  Tree structure 3 level:
    Level 1: Modul (auto-fetch dari module registry)
      Level 2: Menu dalam modul (auto-fetch per modul)
        Level 3: Action checkbox (view/create/update/archive/
                 export/approve/view_sensitive/settings)
  Header row: check-all per modul
  Endpoint: GET /api/v1/instance/permissions/structure

Tab Users dalam detail role:
  List user yang punya role ini
```

**Default role saat company baru (auto-generate):**
```
Instance level (tidak bisa dihapus):
  Platform Administrator

Company level (per company):
  Company Administrator
  HR Manager
  HR Staff
```

**Level user:**
```
Platform Administrator → instance-level, akses semua company + Settings Platform
Company Administrator  → akses company yang di-assign + Settings Modul
Company User           → akses fitur operasional sesuai role, scope company sendiri
```

**Multi-role:**
- User bisa punya banyak role
- Permission additive — jika satu role bilang "bisa", user bisa
- Tidak ada role yang cancel role lain

### Platform Config
```
Timezone (dropdown)
Bahasa default (ID / EN)
Lockout login:
  Jumlah percobaan gagal sebelum lockout (default: 3)
  Durasi lockout (menit, default: 15)
Durasi sesi:
  Token berlaku (jam, default: 8)
  Auto logout saat expired: ya/tidak
SMTP:
  Host, Port, Encryption (TLS/SSL/None)
  Username, Password (masked)
  From Name, From Email
  [Kirim Email Test]
```

### Audit Log
```
ListPageTemplate dengan kolom:
  Waktu, Actor, Aksi, Modul, Entity, Detail perubahan

Filter:
  Rentang tanggal, Actor, Modul, Tipe aksi

Export: wajib ada (Excel/CSV)

Detail log (klik row → drawer/modal):
  Old value → New value per field
  Field sensitif: [REDACTED] tanpa permission audit.view_sensitive
```

### Module Registry
```
List modul: nama, versi, deskripsi, dependencies, status (installed/not installed)

Aksi per modul:
  Install:
    → cek dependency terpenuhi
    → jika tidak: tampilkan pesan modul apa yang dibutuhkan
    → jalankan migration
    → modul tersedia untuk di-enable per company

  Uninstall:
    → generate export data per company dulu (wajib selesai)
    → tampilkan warning UU PDP yang eksplisit
    → konfirmasi dengan ketik nama modul
    → hapus data + rollback migration
```

---

## 5. Settings Modul

### Lokasi
Settings modul ada di dalam modul masing-masing — bukan di Settings Platform.

### Akses
```
Permission: {module}.settings
Contoh:
  karyawan.settings  → akses /dashboard/employees/settings
  it.settings        → akses /dashboard/it/settings
```

Company Administrator otomatis punya semua `module.settings`.
HR Staff bisa di-assign `karyawan.settings` tanpa mendapat akses ke modul lain.

### Struktur Settings Modul (Pattern Standar)
```
/dashboard/{module}/settings
  Tab Master Data  → data referensi spesifik modul
  Tab Konfigurasi  → pengaturan behavior modul
```

Contoh Modul Karyawan:
```
/dashboard/employees/settings
  Tab Master Data:
    Departemen, Jabatan, Level/Grade, Tipe Kontrak,
    Lokasi Kerja, Agama, Bank, Jenis Identitas,
    Jenis Dokumen, Provinsi & Kota, Negara

  Tab Konfigurasi:
    Format nomor karyawan
    Masa probasi default
    Notifikasi kontrak berakhir (hari sebelumnya)
    Batas maksimal PKWT
```

---

## 6. Auth Flow UI

### Login
```
Halaman /login
  Email + Password
  "Lupa Password?" link
  Rate limit: 5 req/menit per IP (backend)
  Lockout: sesuai setting Platform Config
  Setelah login → redirect ke /dashboard
```

### Session
```
Token Sanctum berlaku sesuai durasi sesi di Platform Config (default 8 jam)
Tidak ada "remember me"
Token expired → auto logout → redirect ke /login + pesan "Sesi berakhir"
```

### Email Invitation (User Baru)
```
Admin buat user baru
  → sistem kirim email invitation
  → link berisi token unik, berlaku 24 jam
  → user klik link → halaman /set-password
  → setelah set password → redirect ke /login
  → jika token expired → admin bisa resend invitation
```

### Forgot Password
```
/login → klik "Lupa Password?"
  → halaman /forgot-password
  → isi email
  → sistem kirim email reset link (berlaku 1 jam)
  → user klik → halaman /reset-password
  → isi password baru
  → redirect ke /login
```

---

## 7. Search Pattern

### Scope
Search di Topbar adalah **scoped per modul yang sedang aktif** — bukan lintas modul.

```
Sedang di /dashboard/employees → search cari karyawan
Sedang di /dashboard/recruitment → search cari pelamar/job posting
Sedang di /dashboard/settings → search cari user/role/company
Sedang di /dashboard (home) → search cari karyawan (default)
```

### Behavior
```
Shortcut: Cmd+K (Mac) / Ctrl+K (Windows/Linux)
Placeholder: "Cari {entitas modul aktif}..."
Minimum 2 karakter untuk trigger search
Hasil muncul sebagai dropdown (max 8 item)
Klik hasil → navigasi ke halaman detail
"Lihat semua hasil" → navigasi ke ListPage dengan filter search aktif
```

### Permission
- Search hanya return data yang user punya akses
- Backend enforce permission — bukan hanya frontend

---

## 8. Arsip Pattern

### Halaman Arsip
```
/dashboard/{module}/archived
  → pakai ListPageTemplate
  → banner "MODE ARSIP" di atas tabel (background amber-50, border amber-200)
  → tombol aksi utama: "Pulihkan" (bukan "Arsipkan")
  → tidak ada tombol "Tambah" di halaman arsip
  → filter dan search tetap tersedia
```

### Navigasi ke Halaman Arsip
```
Di ListPage header (kanan, setelah Export):
  [Lihat Arsip]  → link ke /dashboard/{module}/archived
```

### Pulihkan Data
```
Klik "Pulihkan" di row atau bulk action
  → ConfirmDialog: "Pulihkan {nama entity}? 
     Entity akan kembali muncul di daftar aktif."
  → Konfirmasi → data kembali aktif
  → Toast success
```

---

## 9. ListPageTemplate

### Anatomi (Urutan Wajib, Tidak Boleh Diubah)
```
┌─────────────────────────────────────────────────────────┐
│ PAGE HEADER                                             │
│ [Breadcrumb]                                            │
│ [Judul]          [Lihat Arsip] [Export] [+ Tambah]     │
├─────────────────────────────────────────────────────────┤
│ FILTER BAR                                              │
│ [🔍 Search...]  [Filter 1 ▾]  [Filter 2 ▾]  [Reset]   │
├─────────────────────────────────────────────────────────┤
│ SUMMARY STRIP                                           │
│ Total: 142  |  Aktif: 130  |  Pending: 5  |  ...       │
├─────────────────────────────────────────────────────────┤
│ BULK ACTION BAR (muncul saat ada row terseleksi)        │
│ 3 dipilih  [Aksi 1]  [Aksi 2]  [Batalkan Pilihan]      │
├─────────────────────────────────────────────────────────┤
│ DATA TABLE                                              │
│ ☐  Kolom 1  Kolom 2  Kolom 3  Status  Aksi             │
│ ☐  ...      ...      ...      Badge   ⋮                │
├─────────────────────────────────────────────────────────┤
│ PAGINATION                                              │
│ Menampilkan 1-20 dari 142   [< 1 2 3 >]  [20 ▾]       │
└─────────────────────────────────────────────────────────┘
```

### Rules
- Breadcrumb wajib di Page Header (PLAT-11)
- Tombol "Lihat Arsip" selalu ada di header
- Kolom pertama: checkbox
- Kolom terakhir: Aksi (⋮ dropdown)
- Klik row → navigasi ke detail
- Sort server-side, filter server-side
- Default 20 per halaman
- Mobile: tabel → card stack (PLAT-7)

---

## 10. DetailPageTemplate

### Anatomi (Urutan Wajib)
```
┌─────────────────────────────────────────────────────────┐
│ PAGE HEADER                                             │
│ [Breadcrumb]                                            │
│ ← Kembali             [Edit] [Arsipkan] [Aksi lain]    │
├─────────────────────────────────────────────────────────┤
│ ENTITY HEADER                                           │
│ [Avatar]  Nama Entity                                   │
│           ID • Subtitle • Info ringkas (4-6 item)       │
│           [Status Badge]                                │
├─────────────────────────────────────────────────────────┤
│ TAB NAVIGATION (horizontal scroll di mobile)            │
│ [Tab 1]  [Tab 2]  [Tab 3]  ...  [Catatan]              │
├─────────────────────────────────────────────────────────┤
│ TAB CONTENT                                             │
└─────────────────────────────────────────────────────────┘
```

### Rules
- Breadcrumb wajib (PLAT-11)
- Tab "Catatan" selalu di-inject platform sebagai tab terakhir
- Tab state di URL: `?tab={slug}` (PLAT-4)
- Tab opsional: `visible: boolean | () => boolean`
- Status badge selalu di Entity Header — tidak di dalam tab
- Info ringkas di Entity Header: 4-6 item paling penting (ala Decathlon HRIS)

---

## 11. FormPageTemplate

### Anatomi
```
┌─────────────────────────────────────────────────────────┐
│ PAGE HEADER                                             │
│ [Breadcrumb]                                            │
│ ← Batal         Tambah/Edit {Entity}                   │
├─────────────────────────────────────────────────────────┤
│ FORM CONTENT (scroll)                                   │
│  [Section ▾] ─────────────────────────────────────     │
│    [Grid 2 kolom desktop / 1 kolom mobile]              │
│  [Section ▾] ─────────────────────────────────────     │
│    ...                                                  │
├─────────────────────────────────────────────────────────┤
│ STICKY FOOTER                                           │
│ [Batal]              [Simpan Draft]  [Ajukan/Simpan]   │
└─────────────────────────────────────────────────────────┘
```

### Rules
- Breadcrumb wajib (PLAT-11)
- Section collapsible — auto-expand jika ada error
- Tombol sticky footer berbeda per role:
  - HR Staff → "Ajukan Approval"
  - Administrator / Company Admin → "Simpan"
  - HR Manager → tidak punya akses form (read-only)
- Tombol disabled jika `isDirty = false` atau `isSubmitting = true`
- Konfirmasi jika user klik Batal saat form dirty

---

## 12. Approval Flow UI

### Status Badge Standard
```
draft     → slate    "Draft"
pending   → amber    "Menunggu Approval"
approved  → green    "Disetujui"
rejected  → red      "Ditolak"
active    → blue     "Aktif"
inactive  → slate    "Nonaktif"
archived  → slate    "Diarsipkan"
probation → purple   "Probasi"
```

### Approval Panel
Muncul di bawah Entity Header saat status = pending:
```
⏳ Menunggu Approval
Diajukan oleh {nama} · {waktu relatif}

[✕ Tolak]                    [✓ Setujui]
```
- Hanya untuk user dengan permission `{module}.approve`
- Tolak: wajib isi catatan (ConfirmDialog dengan textarea)
- Setujui: ConfirmDialog singkat

---

## 13. Audit Trail & Chatter (Tab Catatan)

Tab "Catatan" di-inject platform ke semua DetailPage — modul tidak perlu deklarasi.

```
Sub-tab: [Log Aktivitas]  [Catatan Manual]

Log Aktivitas:
  {actor} mengubah {field}: "{lama}" → "{baru}" · {waktu}
  Field sensitif → [REDACTED] tanpa permission
  Append-only, tidak bisa dihapus

Catatan Manual:
  Textarea + tombol Kirim
  Support @mention
  Append-only setelah disimpan
  Load more pagination
  Real-time via WebSocket
```

---

## 14. Document Upload Component

```
Drop zone + Browse button
  → Validasi: MIME type, max size (default 5MB), extension whitelist
  → Upload via backend (tidak pernah direct ke MinIO)
  → Progress bar per file
  → Success: muncul di document list
  → Error: inline, bisa retry

Document List:
  [Ikon]  Nama File · Size · Tipe
          Diupload {nama} · {tanggal}   [Unduh] [Arsipkan]

Unduh via signed URL (time-limited)
Arsipkan = soft delete (bukan hard delete)
Preview inline untuk PDF dan gambar
```

---

## 15. Export Component

```
[Export ▾]
  → Export Excel (.xlsx)
  → Export PDF
  → Export CSV

- Berdasarkan filter aktif saat itu
- Progress indicator untuk export besar
- Download otomatis
- Dicatat di audit log: siapa, kapan, filter, jumlah record
```

---

## 16. Permission Guard

```tsx
// Sembunyikan
<PermissionGate permission="employee.create">
  <Button>Tambah Karyawan</Button>
</PermissionGate>

// Disable
<PermissionGate permission="employee.update" fallback="disabled">
  <Button>Edit</Button>
</PermissionGate>

// Redact field sensitif
<PermissionGate permission="employee.view_sensitive" fallback="redacted">
  <span>{employee.nik}</span>
</PermissionGate>
```

**Penting:** PermissionGate hanya untuk UX. Security tetap di backend (RULE-D1 AGENTS.md).

---

## 17. Notification System

```
NotificationBell di Topbar:
  Badge merah → jumlah belum dibaca
  Klik → dropdown 10 terbaru
  "Lihat semua" → /dashboard/notifications
  Real-time via WebSocket (Soketi)

Toast:
  Success  → hijau,  3 detik,  auto-dismiss,  kanan bawah
  Error    → merah,  tetap,    manual dismiss, kanan bawah
  Warning  → amber,  5 detik,  auto-dismiss,  kanan bawah
  Info     → biru,   3 detik,  auto-dismiss,  kanan bawah

ConfirmDialog (aksi destruktif):
  Teks spesifik sebut nama entity
  Tombol destruktif di kanan, selalu merah
  Wajib untuk: arsipkan, tolak, uninstall modul
```

---

## 18. Onboarding Wizard (First Time Setup)

```
Step 1: Setup Platform
  Nama platform, upload logo, timezone default

Step 2: Buat Company Pertama
  Nama company, timezone, bahasa default

Step 3: Selesai
  Summary + CTA "Buka Dashboard"
```

Wizard Shell juga tersedia untuk dipakai modul yang butuh flow multi-step.

---

## 19. Language Switcher

```
Posisi: Topbar, sebelah kiri avatar
Toggle: ID | EN
Preference: localStorage + PATCH /api/v1/users/me/preferences
Switch: tanpa reload halaman (next-i18next client-side)
```

---

## 20. Komponen Platform — Daftar Lengkap

### Layout
```
AppShell, Sidebar, Topbar, PageHeader (dengan Breadcrumb)
```

### Templates
```
ListPageTemplate, DetailPageTemplate, FormPageTemplate, WizardShell
```

### Data Display
```
DataTable, StatCard, StatusBadge, AvatarWithInfo
EmptyState, LoadingSkeleton
DocumentList, ApprovalPanel, ApprovalTimeline, ChatLog
```

### Input & Forms
```
FormSection, DocumentUpload, ExportButton
```

### Feedback
```
Toast, ConfirmDialog, NotificationBell
PermissionGate, LanguageSwitcher
```

---

## 21. Urutan Build — Updated

```
Phase A ✅ Foundation
  AppShell, Sidebar, Topbar, PageHeader
  StatCard, DataTable shell, EmptyState, LoadingSkeleton, StatusBadge

Phase B ✅ Templates
  ListPageTemplate, DetailPageTemplate, FormPageTemplate, WizardShell

Phase C ✅ Platform Behaviors
  PermissionGate, ConfirmDialog, Toast, NotificationBell
  LanguageSwitcher, ExportButton, DocumentUpload
  ApprovalPanel, ApprovalTimeline, ChatLog

Phase D1 — Settings Platform (NEXT)
  Company Management, Users & Access, Platform Config
  Audit Log, Module Registry

Phase D2 — Modul Karyawan
  Settings Modul Karyawan (master data + konfigurasi)
  List, Detail, Form Karyawan

Phase D3 — Modul Kalender
Phase D4 — Modul Rekrutmen
Phase D5 — Surface Public (company website + candidate portal)
```

---

*Dokumen ini adalah living document.*
*Setiap perubahan harus di-commit dan dicatat di CHANGELOG.md.*
*Konflik antara dokumen ini dan AGENTS.md → AGENTS.md menang.*

---

## 22. SettingsModuleLayout

Pattern standar untuk semua halaman settings modul yang punya banyak sub-item.
Berlaku untuk: `/dashboard/{module}/settings`

### Anatomi
```
┌─────────────────────────────────────────────────────────┐
│ PAGE HEADER                                             │
│ [Breadcrumb]                                            │
│ Pengaturan {Nama Modul}                                 │
├──────────────────┬──────────────────────────────────────┤
│ VERTICAL NAV     │ CONTENT PANEL                        │
│ (200px)          │                                      │
│                  │ [Judul Entitas]                      │
│ MASTER DATA      │ [ListPageTemplate mini atau Form]    │
│   Departemen  ←  │                                      │
│   Jabatan        │                                      │
│   Level/Grade    │                                      │
│   Tipe Kontrak   │                                      │
│   Lokasi Kerja   │                                      │
│   Agama          │                                      │
│   Bank           │                                      │
│   Jenis Dokumen  │                                      │
│   Pendidikan     │                                      │
│                  │                                      │
│ KONFIGURASI      │                                      │
│   Pengaturan  ←  │                                      │
│                  │                                      │
└──────────────────┴──────────────────────────────────────┘
```

### Rules
- Vertical nav di kiri (200px fixed) — tidak collapsible di desktop
- Di mobile: vertical nav collapse jadi dropdown di atas content
- Section label (MASTER DATA, KONFIGURASI) adalah visual separator — tidak bisa diklik
- Active item: background biru-50, teks biru-700, left border biru-600 (2px)
- Content panel kanan: ListPageTemplate mini untuk master data, FormPageTemplate untuk konfigurasi
- ListPageTemplate mini: sama dengan ListPageTemplate standar tapi tanpa summary strip
- Inline modal untuk tambah/edit master data — tidak perlu halaman terpisah
- URL pattern: `/dashboard/{module}/settings?section={entitas}`
  section di URL → active item di vertical nav sync otomatis

### Kapan Dipakai
- Modul dengan 5+ entitas master data
- Settings Platform sudah pakai SettingsNav yang mirip pattern ini
- Modul dengan < 5 entitas: boleh pakai tab horizontal biasa

### Referensi Visual
- Notion Settings, Linear Settings, GitHub Settings — vertical nav kiri
- Odoo Settings — panel kiri untuk navigasi master data
