# UIUX_SPEC.md — Dictive-HR
**Status:** Settled — siap dipakai Claude Code
**Dibuat:** 2 Juni 2026
**Sumber:** Interogasi owner + 3 design reference (HRdream, Decathlon HRIS, Humanet)

---

## 1. Design Foundation

### Component Library
- **shadcn/ui** — sesuai ARCHITECTURE.md
- Belum di-install di `/frontend` — instalasi adalah bagian dari Tahap 1 Retrofit

### Design Language
- **Tone:** Profesional HRIS, clean, mudah dipandang — bukan kaku enterprise
- **Referensi visual:** HRdream (layout + sidebar), Decathlon HRIS (detail form karyawan), Humanet (dashboard stat cards + tabel)
- **Tidak ada Figma** — shadcn/ui default styling sebagai base, dikustomisasi dengan token di bawah

### Color Palette
```
Primary:     #2563EB  (blue-600)   — aksen utama, active state, CTA
Primary Hover: #1D4ED8 (blue-700)
Background:  #FFFFFF               — putih bersih
Surface:     #F8FAFC  (slate-50)   — card, sidebar background
Border:      #E2E8F0  (slate-200)  — garis pemisah
Text Primary:   #0F172A (slate-900)
Text Secondary: #64748B (slate-500)
Text Muted:     #94A3B8 (slate-400)
Success:     #16A34A  (green-600)
Warning:     #D97706  (amber-600)
Danger:      #DC2626  (red-600)
```

### Typography
```
Font Family:  Inter (Google Fonts) — clean, highly legible di semua ukuran
Heading 1:    24px / font-semibold / slate-900
Heading 2:    20px / font-semibold / slate-900
Heading 3:    16px / font-semibold / slate-900
Body:         14px / font-normal  / slate-700
Body Small:   13px / font-normal  / slate-500
Label:        12px / font-medium  / slate-500 / uppercase tracking-wide
```

### Spacing & Radius
```
Border radius card:   8px  (rounded-lg)
Border radius button: 6px  (rounded-md)
Border radius input:  6px  (rounded-md)
Sidebar width:        240px (desktop), collapsible ke 64px (icon-only)
Topbar height:        56px
Card padding:         16px / 24px
Page padding:         24px (desktop), 16px (mobile)
```

### Accessibility Baseline
- **WCAG 2.1 AA** — standar industri
- shadcn/ui sudah comply secara default
- Minimum contrast ratio: 4.5:1 untuk teks normal, 3:1 untuk teks besar
- Semua interactive element harus keyboard-navigable
- Form fields wajib punya label yang proper (tidak hanya placeholder)

---

## 2. Responsive Strategy

**Pendekatan:** Responsive penuh — desktop dan mobile sama-sama harus nyaman.

| Breakpoint | Lebar | Behavior |
|---|---|---|
| Mobile | < 768px | Sidebar hidden, hamburger menu, layout single column |
| Tablet | 768px–1024px | Sidebar collapsible (icon-only), layout 2 kolom |
| Desktop | > 1024px | Sidebar expanded (240px), layout penuh |

**Prioritas render:** Desktop-first untuk layout kompleks (tabel, form multi-kolom), tapi semua halaman harus usable di mobile.

**Mobile behavior spesifik:**
- Sidebar menjadi bottom sheet atau drawer dari kiri
- Tabel: horizontal scroll atau card-based layout di mobile
- Form multi-kolom: stack ke single column di mobile
- Topbar: search collapse ke ikon di mobile

---

## 3. Layout Architecture

### Shell Utama (Platform Dashboard)

```
┌─────────────────────────────────────────────────────┐
│                    TOPBAR (56px)                     │
│  [Logo + Nama Platform]  [Search]  [Notif] [Avatar] │
├──────────────┬──────────────────────────────────────┤
│              │                                      │
│   SIDEBAR    │           MAIN CONTENT               │
│   (240px)    │           (flex-1)                   │
│              │                                      │
│  Dashboard   │   [Page Header]                      │
│  Karyawan    │   [Page Content]                     │
│  Kalender    │                                      │
│  Rekrutmen   │                                      │
│  ...modul    │                                      │
│  opsional    │                                      │
│              │                                      │
│  ─────────── │                                      │
│  ⚙ Settings  │                                      │
│  👤 Profile  │                                      │
│  🚪 Logout   │                                      │
└──────────────┴──────────────────────────────────────┘
```

### Sidebar Detail
```
Bagian Atas (navigasi modul):
  - Logo + nama platform (header sidebar)
  - Dashboard         [ikon + label]
  - Karyawan          [ikon + label]  
  - Kalender          [ikon + label]
  - [Modul opsional aktif muncul di sini]

Bagian Bawah (footer sidebar, dipisah garis):
  - Settings          [⚙ ikon + label]  ← visual berbeda, warna muted
  - Profile/Account   [👤 ikon + label]
  - Logout            [🚪 ikon + label]
```

**Active state sidebar:** Background biru-100, teks biru-700, left border biru-600 (2px)

### Topbar Detail
```
Kiri:   Logo (ikon 20px) + nama platform (font-semibold, slate-800)
Tengah: Search bar global (placeholder: "Cari karyawan, menu, atau fitur...")
Kanan:  Ikon notifikasi (bell + badge) | Avatar user + nama + dropdown
```

---

## 4. Component Hierarchy

### Shared Components (dipakai lintas modul)
```
/components/layout/
  AppShell.tsx          — wrapper utama: topbar + sidebar + content area
  Sidebar.tsx           — navigasi kiri dengan active state
  Topbar.tsx            — search + notifikasi + user menu
  PageHeader.tsx        — judul halaman + breadcrumb + action button

/components/ui/          — shadcn/ui components (di-generate via CLI)
  Button, Input, Select, Checkbox, Badge, Avatar, Dialog, etc.

/components/shared/
  StatCard.tsx          — kartu angka besar (ala Humanet dashboard)
  DataTable.tsx         — tabel dengan sort, filter, pagination
  EmptyState.tsx        — tampilan saat data kosong
  LoadingSkeleton.tsx   — skeleton loader untuk semua kondisi loading
  ConfirmDialog.tsx     — dialog konfirmasi untuk aksi destruktif
  StatusBadge.tsx       — badge status (Active/Inactive/Pending/dll)
  FormSection.tsx       — wrapper section dalam form (collapsible)
  AvatarWithInfo.tsx    — avatar + nama + jabatan (untuk tabel karyawan)
```

### Page-Specific Components
```
/components/dashboard/
  WelcomeGreeting.tsx   — "Good morning, [nama]"
  QuickStatsRow.tsx     — row stat cards (total karyawan, hadir, absen, cuti)
  RecentActivity.tsx    — aktivitas terbaru

/components/karyawan/
  EmployeeCard.tsx      — card profil karyawan di list view
  EmployeeProfileHeader.tsx  — foto + nama + jabatan + info ringkas
  ProfileTabNav.tsx     — tab horizontal (Profile, Kontrak, Gaji, dll)

/components/rekrutmen/
  ApplicantCard.tsx     — kartu pelamar
  PipelineStage.tsx     — kolom kanban per tahap rekrutmen
```

---

## 5. Information Architecture

### Navigasi Utama
```
Dashboard
Karyawan
  └─ Daftar Karyawan
  └─ Tambah Karyawan
  └─ [sub-menu per kebutuhan modul]
Kalender
  └─ Kalender Kerja
  └─ Hari Libur
[Modul Opsional Aktif]
  └─ Rekrutmen
     └─ Job Posting
     └─ Pelamar
     └─ Pipeline
────────────────────
Settings           ← footer
  └─ Platform
  └─ Akses & Role
  └─ Master Data
Profile            ← footer
Logout             ← footer
```

### Halaman Settings (Platform)
Settings adalah bagian dari platform core — bukan modul. Isinya:
- **Platform** — nama platform, logo, konfigurasi umum
- **Akses & Role** — manajemen role dan permission (RBAC)
- **Master Data Platform** — data referensi yang dipakai lintas modul (jabatan, departemen, lokasi, dll)
- **Audit Log** — log semua aksi di sistem
- **Archive** — data yang diarsipkan

---

## 6. User Journey per Role

### Role: Administrator (Super User)
**Entry point:** Login → Dashboard

**Journey utama:**
```
Login
  → Dashboard (overview: total karyawan, stat hari ini)
  → Settings → Akses & Role → buat/edit role + assign permission
  → Settings → Master Data → kelola data referensi platform
  → Karyawan → Daftar Karyawan → tambah/edit/nonaktifkan karyawan
  → Settings → Audit Log → pantau semua aktivitas sistem
  → [Modul Opsional] → aktifkan/nonaktifkan modul per kebutuhan
```

**Screen kritis:**
- Dashboard: stat cards + aktivitas terbaru
- Settings/RBAC: tabel role + detail permission per modul
- Karyawan: tabel list + form detail karyawan

---

### Role: HR Manager (Approver)
**Entry point:** Login → Dashboard

**Journey utama:**
```
Login
  → Dashboard (overview + pending approvals)
  → Notifikasi → lihat item yang butuh approval
  → [Konteks modul] → review submission dari HR Staff
  → Approve / Reject dengan catatan
```

**Screen kritis:**
- Dashboard: widget "Pending Approval" yang menonjol
- Detail item untuk di-review: tombol Approve (hijau) dan Reject (merah) jelas terlihat
- History approval yang sudah dilakukan

**UX note:** HR Manager tidak perlu akses ke form input/edit data — mereka hanya mereview. Tombol edit disembunyikan atau disabled untuk role ini.

---

### Role: HR Staff (Operator)
**Entry point:** Login → Dashboard

**Journey utama:**
```
Login
  → Dashboard (tasks harian + kalender)
  → Karyawan → tambah karyawan baru / update data karyawan
  → [Modul Rekrutmen] → buat job posting → kelola pelamar → proses pipeline
  → Submit untuk approval ke HR Manager
  → Kalender → update hari libur / jadwal kerja
```

**Screen kritis:**
- Karyawan: form tambah/edit karyawan (multi-tab ala Decathlon HRIS)
- Rekrutmen: pipeline kanban atau list pelamar per tahap
- Submit flow: tombol "Ajukan Approval" yang jelas setelah isi form

---

## 7. State Design

Setiap halaman dengan data harus handle 4 state ini secara konsisten:

| State | Komponen | Visual |
|---|---|---|
| **Loading** | `LoadingSkeleton` | Skeleton abu-abu animated |
| **Empty** | `EmptyState` | Ilustrasi + teks + CTA button |
| **Error** | Inline error atau toast | Teks merah + retry button |
| **Populated** | Konten normal | Data tampil |

**Toast notifications:**
- Success: hijau, muncul 3 detik, kanan bawah
- Error: merah, muncul sampai di-dismiss
- Warning: amber, muncul 5 detik

**Konfirmasi aksi destruktif** (hapus, nonaktifkan, reject):
- Selalu pakai `ConfirmDialog` — tidak langsung eksekusi
- Teks konfirmasi spesifik: "Nonaktifkan karyawan Budi Santoso? Aksi ini tidak bisa dibatalkan."

---

## 8. Form Design Pattern

Mengacu referensi Decathlon HRIS untuk halaman detail karyawan:

```
[Page Header]
  Nama Halaman + breadcrumb + tombol aksi (Save, Cancel)

[Profile Header — untuk halaman karyawan]
  Foto | Nama + ID + Jabatan | Info ringkas (tanggal masuk, departemen, dll)

[Tab Navigation]
  Profile | Kontrak | Gaji | Dokumen | ...

[Form Content]
  [Section Header — collapsible]
    [Form Grid — 2 kolom di desktop, 1 kolom di mobile]
      Label + Input
      Label + Input
    [/Form Grid]
  [/Section]
```

**Form validation:**
- Inline error di bawah field — merah, font 12px
- Required field ditandai asterisk (*)
- Tombol Save disabled sampai ada perubahan (dirty state)
- Auto-save draft tidak ada — semua explicit save

---

## 9. Design Decisions yang Sudah Final (Tidak Perlu Didiskusikan Ulang)

| Keputusan | Alasan |
|---|---|
| shadcn/ui sebagai component library | Sesuai ARCHITECTURE.md, sudah disepakati |
| Inter sebagai font | Legibility terbaik untuk data-heavy interface |
| Settings di footer sidebar | Memisahkan konfigurasi sistem dari navigasi operasional harian |
| Blue-600 sebagai primary color | Profesional, tidak terlalu korporat, referensi HRdream + Humanet |
| WCAG 2.1 AA | Standar industri, shadcn/ui default sudah comply |
| Responsive penuh (bukan desktop-only) | HR Staff dan HR Manager perlu akses dari HP |
| Konfirmasi dialog untuk aksi destruktif | Mencegah kesalahan di data karyawan yang kritis |
| 4 state konsisten (loading/empty/error/populated) | Konsistensi UX lintas modul |
| Dark mode tidak didukung | Platform light mode only agar scope retrofit tetap fokus dan konsisten |
| Onboarding wizard first login | Administrator perlu setup nama platform, upload logo, dan buat role pertama |
| Multi-bahasa ID/EN | Language switcher tersedia di topbar atau Settings |
| Notifikasi in-app real-time | Menggunakan WebSocket via Soketi yang sudah tersedia di docker-compose |
| Company branding | Setiap perusahaan bisa upload logo sendiri |

---

## 10. Keputusan Tambahan (Settled 2 Juni 2026)

| Item | Keputusan |
|---|---|
| Dark mode | Tidak didukung — light mode only |
| Onboarding flow | Wizard saat pertama login: setup nama platform → upload logo → buat role pertama |
| Multi-bahasa | Ya — language switcher tersedia di topbar atau Settings (ID/EN) |
| Notifikasi in-app | Real-time via WebSocket menggunakan Soketi (sudah ada di docker-compose) |
| Company branding | Ya — setiap perusahaan bisa upload logo mereka sendiri |
