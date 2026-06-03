# PLATFORM_UI_SPEC.md — Dictive-HR
**Status:** Settled — siap dipakai Claude Code
**Dibuat:** 2 Juni 2026
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

MODUL menyediakan:
  - Kolom tabel spesifik modul
  - Filter options spesifik modul
  - Form fields spesifik modul
  - Summary strip content spesifik modul
  - Tab content spesifik modul
  - Visualisasi khusus (kanban, kalender, dll)
```

### Aturan Tidak Boleh Dilanggar
```
PLAT-1: Setiap halaman list WAJIB pakai ListPageTemplate
PLAT-2: Setiap halaman detail WAJIB pakai DetailPageTemplate
PLAT-3: Setiap form tambah/edit WAJIB pakai FormPageTemplate
PLAT-4: Tab state WAJIB ada di URL query parameter, bukan hanya React state
PLAT-5: Loading state WAJIB pakai skeleton yang menyerupai konten, bukan spinner
PLAT-6: Spinner HANYA untuk aksi singkat (submit button, quick action)
PLAT-7: Tabel di mobile WAJIB berubah jadi card stack
PLAT-8: Export WAJIB tersedia di semua ListPage
PLAT-9: Approval flow UI WAJIB konsisten di semua modul
PLAT-10: Audit trail/Chatter WAJIB ada di semua DetailPage
```

---

## 2. URL & Navigation Pattern

### Standard URL Structure (Berlaku Semua Modul)
```
List:
  /dashboard/{module}

Detail — tab pertama (default):
  /dashboard/{module}/{id}

Detail — tab spesifik:
  /dashboard/{module}/{id}?tab={tab-slug}

Detail item dalam tab:
  /dashboard/{module}/{id}/{sub-resource}/{sub-id}
```

### Contoh Implementasi per Modul
```
Karyawan:
  /dashboard/employees
  /dashboard/employees/EMP-001
  /dashboard/employees/EMP-001?tab=kontrak
  /dashboard/employees/EMP-001/contracts/CTR-001

Rekrutmen:
  /dashboard/recruitment/jobs
  /dashboard/recruitment/jobs/JOB-001
  /dashboard/recruitment/jobs/JOB-001?tab=pelamar
  /dashboard/recruitment/jobs/JOB-001/applicants/APP-001

Aset:
  /dashboard/assets
  /dashboard/assets/AST-001
  /dashboard/assets/AST-001?tab=riwayat
```

### Behavior
- Tab state di URL → shareable, bookmarkable, browser back/forward berfungsi benar
- Deep link ke tab tertentu harus langsung membuka tab tersebut tanpa redirect
- Query parameter `?tab=` menggunakan slug lowercase dengan tanda hubung

---

## 3. ListPageTemplate

### Anatomi (Urutan Wajib, Tidak Boleh Diubah)
```
┌─────────────────────────────────────────────────────────┐
│ PAGE HEADER                                             │
│ [Judul Halaman]          [Export] [+ Tambah {Entity}]  │
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
│ ☐  Kolom 1  Kolom 2  Kolom 3  Kolom 4  Status  Aksi   │
│ ─────────────────────────────────────────────────────  │
│ ☐  ...      ...      ...      ...      Badge    ⋮      │
│ ☐  ...      ...      ...      ...      Badge    ⋮      │
├─────────────────────────────────────────────────────────┤
│ PAGINATION                                              │
│ Menampilkan 1-20 dari 142   [< 1 2 3 ... >]  [20 ▾]   │
└─────────────────────────────────────────────────────────┘
```

### Komponen: `<ListPageTemplate>`
```typescript
interface ListPageTemplateProps {
  // Page Header
  title: string
  addButton?: {
    label: string
    permission: string
    onClick: () => void
  }
  exportConfig?: ExportConfig

  // Filter Bar
  searchPlaceholder?: string
  filters: FilterConfig[]

  // Summary Strip
  summaryItems?: SummaryItem[]   // opsional — modul yang tidak relevan bisa kosong

  // Table
  columns: ColumnDef[]
  data: any[]
  isLoading: boolean
  error?: Error | null
  pagination: PaginationState

  // Bulk Actions
  bulkActions?: BulkAction[]
}
```

### Summary Strip
- **Wajib ada** secara struktural di semua ListPage
- Jika modul tidak punya data yang relevan untuk ditampilkan — strip tetap render tapi kosong (tidak di-hide)
- Modul mendefinisikan `summaryItems` sendiri — platform hanya menyediakan container dan styling

### Filter Bar
- Search selalu di posisi paling kiri
- Filter chips di sebelah kanan search
- Tombol Reset di paling kanan — hanya muncul jika ada filter aktif
- Filter aktif ditandai dengan warna biru (primary color)

### Data Table
- Kolom pertama selalu checkbox
- Kolom terakhir selalu kolom Aksi (ikon ⋮ dropdown)
- Kolom Status menggunakan `StatusBadge` component
- Klik row → navigasi ke halaman detail
- Sort: klik header kolom — ascending/descending/none (3 state)
- Semua sort dan filter adalah server-side

### Bulk Action Bar
- Muncul di atas tabel (replace summary strip area) saat ada row terseleksi
- Menampilkan jumlah row yang dipilih
- Aksi bulk berbeda per modul — platform hanya menyediakan container
- Selalu ada tombol "Batalkan Pilihan"

### Pagination
- Server-side — tidak ada client-side pagination
- Showing X-Y dari Z di kiri
- Navigator halaman di tengah
- Dropdown per-page (10/20/50) di kanan
- Default: 20 per halaman

### Mobile Behavior
- Filter bar: collapse menjadi tombol "Filter (N aktif)" — tap membuka bottom sheet
- Tabel: berubah menjadi card stack
- Setiap card menampilkan informasi prioritas (ditentukan per modul via `mobileCardConfig`)
- Pagination tetap ada di bawah card stack
- Bulk select: long-press untuk masuk mode select di mobile

### Loading State
```
Skeleton ListPage:
  - Page header: 2 rectangle placeholder
  - Filter bar: 3 pill placeholder
  - Summary strip: 4 number placeholder
  - Table: 8 row skeleton, setiap row 5 kolom
```

### Empty State
```
Icon  + "Belum ada {entity}"
       + Deskripsi singkat apa yang bisa dilakukan
       + CTA button "Tambah {entity} Pertama" (jika punya permission)
```

---

## 4. DetailPageTemplate

### Anatomi (Urutan Wajib)
```
┌─────────────────────────────────────────────────────────┐
│ PAGE HEADER                                             │
│ ← Kembali ke List    [Breadcrumb]    [Edit] [Arsipkan] │
├─────────────────────────────────────────────────────────┤
│ ENTITY HEADER                                           │
│ [Foto/Avatar]  Nama Entity                              │
│                ID • Subtitle • Info ringkas             │
│                [Status Badge]                           │
├─────────────────────────────────────────────────────────┤
│ TAB NAVIGATION                                          │
│ [Tab 1]  [Tab 2]  [Tab 3]  ...  [Catatan]  (selalu ada)│
├─────────────────────────────────────────────────────────┤
│ TAB CONTENT                                             │
│ (konten spesifik tab yang aktif)                        │
└─────────────────────────────────────────────────────────┘
```

### Komponen: `<DetailPageTemplate>`
```typescript
interface DetailPageTemplateProps {
  // Page Header
  backUrl: string
  backLabel: string
  breadcrumbs: Breadcrumb[]
  actions?: ActionButton[]     // Edit, Arsipkan, dll per permission

  // Entity Header
  avatar?: string              // URL foto/gambar
  title: string                // Nama entity
  subtitle?: string            // Jabatan, kategori, dll
  entityId?: string            // ID yang ditampilkan
  metaInfo?: MetaItem[]        // Info ringkas (departemen, tanggal, dll)
  status?: StatusConfig        // Badge status

  // Tabs
  tabs: TabConfig[]
  defaultTab?: string          // default ke tab pertama jika tidak diset
  // Tab "Catatan" selalu di-inject platform sebagai tab terakhir
}
```

### Tab Navigation
- Tab aktif: border bottom biru, teks biru
- Tab state di URL: `?tab={slug}` — sync otomatis
- Tab "Catatan" selalu ada sebagai tab terakhir — di-inject platform, modul tidak perlu deklarasi
- Tab opsional (seperti Offboarding) bisa dikonfigurasi dengan `visible: boolean | () => boolean`

### Entity Header
- Avatar: foto jika ada, fallback ke initials avatar (2 huruf pertama nama)
- Untuk entitas non-personal (Aset, Job Posting): gunakan ikon modul sebagai avatar
- Status badge selalu di Entity Header — tidak di dalam tab content

### Mobile Behavior
- Entity Header: compact — foto lebih kecil, info ringkas dipotong
- Tab Navigation: horizontal scroll jika tab terlalu banyak
- Tab Content: full width, padding berkurang

### Loading State
```
Skeleton DetailPage:
  - Entity header: avatar circle + 3 line placeholder
  - Tab bar: 4 pill placeholder
  - Tab content: sesuai bentuk konten tab (ditentukan per tab)
```

---

## 5. FormPageTemplate

### Anatomi
```
┌─────────────────────────────────────────────────────────┐
│ PAGE HEADER                                             │
│ ← Batal         Tambah/Edit {Entity}                   │
├─────────────────────────────────────────────────────────┤
│ FORM CONTENT                                            │
│                                                         │
│  [Section Header ▾] ─────────────────────────────────  │
│  ┌────────────────────────────────────────────────────┐ │
│  │ Label *     Label *     Label          Label       │ │
│  │ [Input]     [Input]     [Input]        [Select ▾]  │ │
│  │                                                    │ │
│  │ Label *                 Label                      │ │
│  │ [Input — full width]    [Input]                    │ │
│  └────────────────────────────────────────────────────┘ │
│                                                         │
│  [Section Header ▾] ─────────────────────────────────  │
│  ...                                                    │
│                                                         │
├─────────────────────────────────────────────────────────┤
│ STICKY FOOTER                                           │
│ [Batal]                    [Simpan Draft] [Ajukan ▾]   │
└─────────────────────────────────────────────────────────┘
```

### Komponen: `<FormPageTemplate>`
```typescript
interface FormPageTemplateProps {
  title: string
  backUrl: string
  sections: FormSection[]
  footerActions: FooterAction[]
  isDirty: boolean             // tombol save disabled jika false
  isSubmitting: boolean
}
```

### Form Sections
- Setiap section punya header yang bisa di-collapse/expand
- Section collapsed by default jika tidak ada required field di dalamnya
- Section dengan error: header merah + expand otomatis

### Form Grid
- Desktop: 2 kolom default, bisa 1/3/4 kolom per field
- Mobile: selalu 1 kolom
- Full-width field: `span: 2` di desktop

### Validation
- Inline error di bawah field — merah, 12px
- Required field: asterisk (*) di label
- Error muncul saat: blur field atau submit attempt
- Tidak ada live validation saat mengetik (kecuali format seperti email/NIK)

### Sticky Footer
- Selalu visible saat scroll — tidak ikut scroll
- Tombol "Batal" di kiri — navigasi kembali tanpa save, ada konfirmasi jika dirty
- Tombol primary di kanan — berbeda per role:
  - HR Staff: "Ajukan Approval" (submit ke HR Manager)
  - Administrator: "Simpan" (langsung save)
  - HR Manager: tidak punya akses form (read-only)
- Tombol disabled saat `isDirty = false` atau `isSubmitting = true`
- Loading spinner di dalam tombol saat submitting

### Mobile Behavior
- Sticky footer tetap ada di bawah
- Section collapse toggle lebih besar (touch-friendly)
- Grid selalu 1 kolom

---

## 6. Approval Flow UI

Platform menyediakan UI approval yang konsisten untuk semua modul.

### Status Badge Standard
```
draft       → abu-abu   "Draft"
pending     → kuning    "Menunggu Approval"
approved    → hijau     "Disetujui"
rejected    → merah     "Ditolak"
active      → biru      "Aktif"
inactive    → abu-abu   "Nonaktif"
archived    → abu-abu   "Diarsipkan"
```

### Approval Panel (di DetailPage)
Muncul di Entity Header area saat status = pending:

```
┌─────────────────────────────────────────────────────┐
│ ⏳ Menunggu Approval                                 │
│ Diajukan oleh Budi Santoso · 2 jam lalu              │
│                                                     │
│ [✕ Tolak]                    [✓ Setujui]            │
└─────────────────────────────────────────────────────┘
```

- Hanya muncul untuk user dengan permission `{module}.approve`
- Tolak: wajib isi catatan penolakan (dialog konfirmasi)
- Setujui: konfirmasi singkat, langsung proses

### Approval History Timeline
Di tab Catatan, selalu ada timeline approval:
```
✓ Disetujui oleh Siti HR Manager · 5 Jan 2026, 14:32
  "Data sudah lengkap dan valid"

⏫ Diajukan oleh Budi HR Staff · 5 Jan 2026, 10:15

✎ Draft dibuat oleh Budi HR Staff · 4 Jan 2026, 09:00
```

---

## 7. Audit Trail & Chatter (Tab Catatan)

Platform meng-inject tab "Catatan" sebagai tab terakhir di semua DetailPage.

### Anatomi Tab Catatan
```
[Log Aktivitas]  [Catatan Manual]     ← sub-tab dalam tab Catatan

Log Aktivitas:
  Semua CRUD otomatis tercatat di sini
  Format: {actor} {aksi} {field} dari "{lama}" → "{baru}" · {waktu}
  Field sensitif: tampil sebagai [REDACTED] untuk user tanpa permission

Catatan Manual:
  HR bisa tambah catatan teks bebas
  Support @mention ke user lain
  Tidak bisa diedit atau dihapus setelah disimpan (append-only)
```

### Behavior
- Ini adalah read dari `activity_log` + `chatter` table di backend
- Platform menyediakan UI-nya — modul tidak perlu build
- Pagination: load more (bukan pagination tradisional)
- Real-time: catatan baru muncul otomatis via WebSocket

---

## 8. Document Upload Component

Platform menyediakan `<DocumentUpload>` yang konsisten untuk semua modul.

### Behavior
```
Drop zone + Browse button
  ↓
Validasi client-side:
  - MIME type whitelist (per konfigurasi)
  - Max file size (default 5MB, bisa dikonfigurasi)
  - Extension whitelist
  ↓
Upload ke MinIO via backend (tidak pernah direct upload)
  ↓
Progress bar selama upload
  ↓
Success: file muncul di list dokumen
Error: pesan error inline, bisa retry
```

### Document List
```
[Ikon tipe file]  Nama File          Diupload oleh · Tanggal
                  2.3 MB · PDF       [Unduh] [Hapus]
```

- Hapus = arsipkan (tidak pernah hard delete)
- Unduh via signed URL (time-limited, tidak pernah public URL permanen)
- Preview inline untuk PDF dan gambar

---

## 9. Export Component

Platform menyediakan `<ExportButton>` untuk semua ListPage.

### Behavior
```
[Export ▾]
  → Export Excel (.xlsx)
  → Export PDF
  → Export CSV
```

- Export selalu berdasarkan filter aktif — bukan semua data
- Progress indicator untuk export besar
- Download otomatis saat selesai
- Setiap export dicatat di audit log (COMP-5 AGENTS.md): siapa, kapan, filter apa, berapa record

---

## 10. Permission Guard

Platform menyediakan `<PermissionGate>` yang dipakai di semua modul.

### Usage
```tsx
// Sembunyikan element jika tidak punya permission
<PermissionGate permission="employee.create">
  <Button>Tambah Karyawan</Button>
</PermissionGate>

// Disable element
<PermissionGate permission="employee.update" fallback="disabled">
  <Button>Edit</Button>
</PermissionGate>

// Field sensitif
<PermissionGate permission="employee.view_sensitive" fallback="redacted">
  <span>{employee.nik}</span>
</PermissionGate>
```

### Penting
- `<PermissionGate>` hanya untuk UX — bukan security layer
- Security check tetap di backend (sesuai RULE-D1 AGENTS.md)
- Jangan andalkan PermissionGate untuk menyembunyikan data sensitif dari network response

---

## 11. Notification System

### In-App Notification Bell (Topbar)
- Badge merah dengan jumlah notifikasi belum dibaca
- Klik: dropdown list notifikasi terbaru (max 10)
- "Lihat semua" → halaman notifikasi lengkap
- Real-time via WebSocket (Soketi)

### Toast Notifications
```
Success  → hijau,  3 detik,  kanan bawah, auto-dismiss
Error    → merah,  tetap sampai di-dismiss, ada detail error
Warning  → amber,  5 detik,  kanan bawah
Info     → biru,   3 detik,  kanan bawah
```

### Confirm Dialog (Aksi Destruktif)
Wajib untuk: arsipkan, tolak, batalkan, hapus permanen.
```
[Ikon Warning]
"Arsipkan Karyawan Budi Santoso?"
"Karyawan tidak akan muncul di daftar aktif. Data tetap tersimpan."

[Batal]                              [Arsipkan]
```
- Teks konfirmasi harus spesifik menyebut nama entity
- Tombol destruktif di kanan, selalu merah

---

## 12. Onboarding Wizard Shell

Platform menyediakan shell wizard multi-step yang dipakai untuk:
1. Setup platform pertama kali (Administrator baru)
2. Proses onboarding dalam modul (jika modul butuh wizard)

### Anatomi
```
┌─────────────────────────────────────────────────────────┐
│ WIZARD HEADER                                           │
│ [Logo]          Step 1 ─── Step 2 ─── Step 3           │
├─────────────────────────────────────────────────────────┤
│ STEP CONTENT                                            │
│                                                         │
│  Judul Step                                             │
│  Deskripsi singkat apa yang dilakukan di step ini       │
│                                                         │
│  [Konten step — form, upload, konfirmasi]               │
│                                                         │
├─────────────────────────────────────────────────────────┤
│ WIZARD FOOTER                                           │
│ [← Kembali]                          [Lanjut →]        │
└─────────────────────────────────────────────────────────┘
```

### Platform Onboarding Wizard (First Time Setup)
```
Step 1: Setup Platform
  - Nama platform
  - Upload logo
  - Timezone default

Step 2: Buat Role Pertama
  - Role Administrator sudah ada otomatis
  - Opsional: tambah role tambahan sekarang atau skip

Step 3: Selesai
  - Summary apa yang sudah disetup
  - CTA: "Buka Dashboard"
```

---

## 13. Language Switcher

- Posisi: di Topbar, sebelah kiri avatar user
- Toggle sederhana: `ID | EN`
- Preference disimpan per user di localStorage + backend user settings
- Switch bahasa: tidak reload halaman — next-i18next handle client-side

---

## 14. Komponen Platform — Daftar Lengkap

Semua komponen ini ada di `/frontend/src/components/` dan dipakai oleh modul.

### Layout
```
AppShell              — wrapper utama
Sidebar               — navigasi kiri
Topbar                — search + notifikasi + avatar + language switcher
PageHeader            — judul + breadcrumb + actions
```

### Templates
```
ListPageTemplate      — template halaman list
DetailPageTemplate    — template halaman detail
FormPageTemplate      — template halaman form
WizardShell           — template wizard multi-step
```

### Data Display
```
DataTable             — tabel dengan sort, filter, pagination, bulk select
StatCard              — kartu angka besar (summary strip)
StatusBadge           — badge status konsisten
AvatarWithInfo        — avatar + nama + subtitle
EmptyState            — tampilan data kosong
LoadingSkeleton       — skeleton per context (list, detail, form)
DocumentList          — list dokumen dengan download
ApprovalPanel         — panel approve/reject
ApprovalTimeline      — timeline history approval
ChatLog               — log aktivitas + catatan manual
```

### Input & Forms
```
FormSection           — section collapsible dalam form
DocumentUpload        — upload dokumen
ExportButton          — export Excel/PDF/CSV
```

### Feedback
```
Toast                 — notifikasi singkat
ConfirmDialog         — konfirmasi aksi destruktif
NotificationBell      — bell + dropdown notifikasi
PermissionGate        — guard UI berdasarkan permission
LanguageSwitcher      — toggle ID/EN
```

---

## 15. Urutan Build Platform UI

Ini urutan yang benar — platform component harus selesai sebelum modul manapun dibangun:

```
Phase A — Foundation (sudah selesai Step 1)
  ✅ AppShell, Sidebar, Topbar, PageHeader
  ✅ StatCard, DataTable shell, EmptyState, LoadingSkeleton, StatusBadge

Phase B — Templates (dikerjakan sebelum modul pertama)
  [ ] ListPageTemplate
  [ ] DetailPageTemplate
  [ ] FormPageTemplate
  [ ] WizardShell

Phase C — Platform Behaviors
  [ ] PermissionGate
  [ ] ConfirmDialog
  [ ] Toast system
  [ ] NotificationBell + WebSocket connection
  [ ] LanguageSwitcher
  [ ] ExportButton
  [ ] DocumentUpload
  [ ] ApprovalPanel + ApprovalTimeline
  [ ] ChatLog (Audit Trail + Chatter)

Phase D — Modul Karyawan (modul pertama di atas platform)
  [ ] List Karyawan (pakai ListPageTemplate)
  [ ] Detail Karyawan (pakai DetailPageTemplate)
  [ ] Form Karyawan (pakai FormPageTemplate)
```

Phase B dan C harus selesai semua sebelum Phase D dimulai.
Ini memastikan modul Karyawan adalah proof of concept bahwa platform template bekerja.

---

*Dokumen ini adalah living document — update ketika ada platform component baru.*
*Setiap perubahan harus di-commit dan dicatat di CHANGELOG.md.*
*Konflik antara dokumen ini dan AGENTS.md → AGENTS.md menang.*
