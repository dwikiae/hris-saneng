# Task Breakdown

Status ini disusun dari ringkasan `CHANGELOG.md` dan entry-point retrofit terbaru.

## Alignment

- [x] Alignment 1 — authority repository diselaraskan melalui AGENTS, CLAUDE, project brief, architecture, dan ADR.
- [x] Alignment 2 — skeleton `backend/app/Core` dan mapping struktur legacy dibuat.
- [x] Alignment 3 — boundary `CompanyContext`, middleware `ResolveCompany`, dan scope company disiapkan.
- [x] Alignment 4 — dokumen planning lama dibersihkan dan entrypoint Dictive-HR diperbarui.
- [x] Alignment 5 — operating plan lintas sesi dibuat.
- [x] Alignment 6 — boundary route API public, instance-level, dan company-scoped dipisahkan.
- [x] Alignment 7 — runtime manifest module registry dipindahkan ke Core.
- [x] Alignment 8 — guard lifecycle module registry ditambahkan.
- [x] Alignment 9 — task packet frontend rebase dibuat.
- [x] Alignment 10 — aplikasi unified `/frontend` Next.js 14 App Router dibuat sebagai target baru.
- [x] Gap Closure — gap alignment terselesaikan atau dipindahkan ke boundary modul eksplisit.

## Foundation Layer

- [x] Sprint 0 — scaffold monorepo Laravel 11, frontend Next.js, Docker dev services, CI, baseline database, seed, health check, settings, audit, archive, dan test baseline dibuat.
- [x] Milestone 6 — redaction audit dan guardrail zero hard delete diperketat.
- [x] Milestone 8 — baseline company dan user management dengan boundary Instance Admin versus Company User dibuat.
- [x] Milestone 10 — instance settings, setup status, dan first-time setup backend dibuat.
- [x] Milestone 11 — baseline notification, queued email job, retry/backoff, dan API notifikasi dibuat.
- [x] Milestone 12 — storage abstraction, adapter MinIO/filesystem, signed URL, dan validasi file terpusat dibuat.

## Mandatory Modules

- [x] Milestone 7 — skeleton backend mandatory module Karyawan dan Kalender dibuat.
- [x] Karyawan — manifest mandatory tersedia dan tidak bisa di-toggle/uninstall melalui lifecycle guard.
- [x] Kalender — manifest mandatory tersedia dan tidak bisa di-toggle/uninstall melalui lifecycle guard.
- [ ] Karyawan — lanjutkan implementasi domain lengkap PKWT/PKWTT sesuai compliance.
- [ ] Kalender — lanjutkan implementasi fitur operasional kalender.

## Optional Modules

- [x] Milestone 9 — skeleton backend optional module Recruitment, Aset, dan Website dibuat.
- [x] Recruitment — fondasi Sprint 7 untuk model, repository, service, job, HTTP layer, portal pemberkasan, dan halaman karir legacy dibuat.
- [x] Aset — manifest optional dan dependency ke Karyawan tersedia.
- [x] Website — manifest optional tersedia.
- [ ] Aset — lanjutkan implementasi domain dan UI operasional.
- [ ] Website — lanjutkan implementasi domain dan UI public website company di `/frontend`.

## Frontend Unified

- [x] `/frontend` — baseline Next.js 14 App Router dibuat untuk dashboard, website publik company, karir, quiz kandidat, dan pemberkasan.
- [x] `/frontend/src/services` — service layer menjadi jalur API call frontend.
- [x] `/frontend/src/locales/id` dan `/frontend/src/locales/en` — locale bilingual tersedia.
- [x] `docs/UIUX_SPEC.md` — spesifikasi UI/UX retrofit tersedia.
- [x] `/frontend` — install `shadcn/ui`.
- [x] `/frontend` — implementasikan UI foundation sesuai `docs/UIUX_SPEC.md`.
- [x] `/frontend` — lanjutkan surface dashboard platform.
- [x] Backend — buat endpoint `GET /api/v1/dashboard/stats` untuk data dashboard nyata.
- [x] Backend — pindahkan test environment dari SQLite in-memory ke PostgreSQL test database.
- [x] `/frontend` — Phase B Platform UI Templates selesai: ListPageTemplate, DetailPageTemplate, FormPageTemplate, dan WizardShell.
- [x] `/frontend` — Phase C Platform Behaviors: PermissionGate, ConfirmDialog, Toast, NotificationBell, LanguageSwitcher, ExportButton, DocumentUpload, Approval UI, dan ChatLog.
- [x] `/frontend` — halaman demo `/dashboard/platform-demo` dan halaman `/dashboard/notifications` untuk verifikasi platform behavior.
- [x] Backend — endpoint `PATCH /api/v1/users/me/preferences` untuk preference bahasa ID/EN.
- [ ] `/frontend` — Phase D Modul Karyawan memakai ListPageTemplate, DetailPageTemplate, dan FormPageTemplate.
- [ ] Backend — buat tabel/data source attendance dan leave agar dashboard tidak lagi mengembalikan 0 untuk hadir, tidak hadir, dan cuti.
- [ ] `/frontend` — lanjutkan surface public company website.
- [ ] `/frontend` — lanjutkan surface candidate portal.

## Git Workflow

- [x] Branch `dev` dibuat dari state lokal saat ini untuk menampung semua uncommitted changes.
- [x] Semua perubahan Tahap 0 di-commit dan push ke `origin/dev`.
- [x] Branch `staging` dibuat dari `origin/main`.
- [x] Branch `staging` di-push ke `origin/staging`.
