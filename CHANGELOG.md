# CHANGELOG

Semua perubahan penting project dicatat di file ini.

## 2026-06-02 — Tahap 1 Step 1

- Menyiapkan foundation frontend unified agar `/frontend` mulai punya design system yang konsisten dengan UIUX_SPEC.
- Menambahkan shadcn/ui, React Query, dan Zustand karena dashboard butuh komponen UI, data fetching state, dan state shell yang rapi.
- Membagi App Router menjadi route groups dashboard, public website, dan candidate portal supaya URL tetap sama tetapi struktur kerja lebih jelas.
- Membuat AppShell dashboard dengan sidebar, topbar, token warna, typography Inter, dan komponen shared dasar untuk langkah UI berikutnya.
- Mencatat risiko dependency audit npm supaya sesi berikutnya tahu ada temuan keamanan package tree yang belum ditindaklanjuti.

## Alignment 1

- Menjadikan blueprint Dictive-HR sebagai authority repository melalui pembaruan AGENTS.md, CLAUDE.md, project brief, architecture, dan ADR.
- Menambahkan docs/ALL_ADR.md sesuai lokasi blueprint baru dan menyelaraskan salinan legacy docs/adr/ALL_ADR.md agar tidak konflik.
- Mencatat bahwa sprint-0-tasks.md dan HRIS_MASTER_DECISION_LOG.md di Downloads masih versi PT Saneng lama sehingga tidak dijadikan authority baru.

## Alignment 2

- Menambahkan skeleton `backend/app/Core` untuk area core platform Dictive-HR tanpa memindahkan runtime code.
- Memverifikasi skeleton modul Karyawan, Kalender, Recruitment, Aset, dan Website tetap tersedia dengan manifest dan folder utama.
- Menambahkan dokumen mapping struktur legacy menuju struktur Dictive-HR untuk milestone refactor berikutnya.

## Alignment 3

- Menambahkan boundary backend `CompanyContext` dan middleware `ResolveCompany` untuk request company-scoped.
- Mendaftarkan alias middleware `company.resolve` tanpa rebase route API existing.
- Menyelaraskan global scope `HasCompany` agar memakai resolved company context saat tersedia.
- Menambahkan dokumen alignment Company Context / ResolveCompany beserta test feature boundary.

## Alignment 4

- Membersihkan dokumen planning lama yang sudah bentrok dengan blueprint Dictive-HR aktif.
- Mengganti README root dan backend dari konteks lama/template default menjadi entrypoint Dictive-HR.
- Menambahkan `docs/alignment/GAP_REGISTER.md` sebagai memory sementara untuk gap alignment yang masih terbuka.
- Menambahkan translation key company context di backend bahasa Indonesia dan Inggris.

## Alignment 5

- Menambahkan `docs/alignment/OPERATING_PLAN.md` sebagai protokol resume lintas session.
- Menetapkan urutan milestone alignment berikutnya agar perintah "selanjutnya" bisa dilanjutkan secara reliable di session baru.
- Memperbarui `GAP_REGISTER.md` dengan next milestone dan aturan lifecycle gap sementara.

## Alignment 6

- Memisahkan boundary route API backend menjadi public/setup/auth, instance-level, dan company-scoped tanpa rebase path endpoint existing.
- Menerapkan middleware `company.resolve` pada endpoint private company-scoped dan menjaga route instance-level tetap terpisah.
- Menambahkan fallback company context dari authenticated company user, dengan penolakan jika user meminta konteks company lain.
- Menyelaraskan settings dan RBAC role agar memakai resolved company context pada route company-scoped.

## Alignment 7

- Memindahkan runtime manifest module registry dari `App\Support\Modules` ke `backend/app/Core/ModuleRegistry`.
- Menempatkan `ModuleRegistry` di layer Application dan `ModuleDefinition` di layer Domain Core ModuleRegistry.
- Memperbarui unit test manifest module agar memakai namespace Core baru tanpa mengubah behavior lifecycle module.

## Alignment 8

- Menambahkan guard lifecycle berbasis manifest untuk install, enable, disable, dan uninstall module di Core ModuleRegistry.
- Memastikan mandatory module tidak bisa di-install ulang, di-toggle, atau di-uninstall melalui lifecycle guard.
- Menambahkan validasi dependency optional module sebelum install/enable dan precondition export/konfirmasi sebelum uninstall.

## Alignment 9

- Menambahkan task packet `docs/alignment/FRONTEND_REBASE_TASK_PACKET.md` untuk eksekusi frontend rebase Alignment 10.
- Mendefinisikan target satu aplikasi `/frontend` Next.js 14 App Router beserta mapping surface dashboard, website publik, karir, dan kandidat.
- Menegaskan bahwa Alignment 9 hanya planning dan tidak mengubah frontend dirty files.

## Alignment 10

- Menambahkan aplikasi unified `/frontend` berbasis Next.js 14 App Router untuk dashboard, website publik company, halaman karir, quiz kandidat, dan pemberkasan.
- Memindahkan API call frontend ke service layer `/frontend/src/services` dan locale bilingual ke `/frontend/src/locales/id` serta `/frontend/src/locales/en`.
- Mengarahkan frontend CI/checks ke `/frontend` sambil mempertahankan `frontend-hris` dan `frontend-web` sebagai referensi legacy tanpa diedit atau dihapus.

## Gap Closure

- Menambahkan slug company secara additive pada tabel `companies`, termasuk backfill slug unik dan dukungan resolve company context via numeric id maupun slug.
- Menyelaraskan flow company create/update/setup agar menghasilkan slug dan mengeksposnya di response company API.
- Memindahkan runtime Core Health, Notification, dan FileStorage dari folder shared legacy ke `backend/app/Core`.
- Menghapus `docs/alignment/GAP_REGISTER.md` karena seluruh gap alignment yang tercatat sudah terselesaikan atau dipindahkan ke batas modul non-Core eksplisit.

## Sprint 0

- Menyiapkan scaffold monorepo Laravel 11 + dua frontend Next.js 14.
- Menambahkan Docker Compose local dev untuk PostgreSQL, Redis, MinIO, Soketi, dan Mailpit.
- Mengonfigurasi backend Laravel untuk PostgreSQL, Redis, MinIO, Soketi, Mailpit, Sanctum, Spatie Permission, dan Activitylog.
- Menambahkan foundation database: companies, company_settings, users, cache, jobs, failed_jobs.
- Menambahkan seed data awal PT Saneng, default settings, dan System Admin.
- Menambahkan endpoint `GET /health` untuk health check service dependencies.
- Menambahkan GitHub Actions CI untuk backend dan kedua frontend.
- Menambahkan baseline test suite Sprint 0 untuk models, migrations, seeders, archive scope, dan health check.
- Menambahkan Settings API dengan repository, permission guard, bulk update, dan masking password SMTP.
- Menambahkan translation key backend untuk modul audit, archive, dan settings.
- Menambahkan coverage feature test untuk filter audit, filter archive, restore archive, dan settings.

## Milestone 6

- Memperketat baseline redaction audit backend untuk credential, token, NIK, NPWP, rekening, dan kompensasi.
- Menambahkan guardrail test agar production backend code tidak memakai hard delete.
- Menambahkan test konvensi activity log agar field sensitif tidak disimpan ke audit properties.

## Milestone 7

- Menambahkan skeleton backend mandatory module Karyawan dan Kalender beserta manifest `module.json`.
- Menambahkan registry ringan untuk validasi manifest module wajib tanpa install/uninstall execution.
- Menambahkan test manifest untuk memastikan module wajib tidak bisa di-uninstall atau di-toggle.

## Milestone 8

- Menambahkan baseline backend pengelolaan company dan user dengan boundary Instance Admin versus Company User.
- Menambahkan dukungan Instance Admin `company_id` nullable, role Manager/Staff baseline, dan permission company/user.
- Menambahkan test company management, company-scoped user isolation, dan proteksi role lintas company.

## Milestone 9

- Menambahkan skeleton backend optional module Recruitment, Aset, dan Website beserta manifest `module.json`.
- Menambahkan deklarasi dependency optional module: Recruitment dan Aset bergantung pada Karyawan, Website tanpa dependency.
- Menambahkan test manifest untuk memastikan optional module valid dan migration path tetap di dalam folder module.

## Milestone 10

- Menambahkan baseline instance settings, setup status, dan first-time setup backend tanpa integrasi SMTP/MinIO nyata.
- Menyelaraskan company settings agar tetap company-scoped dan menambahkan placeholder storage/password/lockout.
- Menambahkan test setup guard dan settings isolation untuk boundary instance versus company.

## Milestone 11

- Menambahkan baseline backend in-app notification, queued email notification job, dan API notifikasi.
- Menetapkan retry/backoff notification email `[30, 60, 120]` dengan failed handler yang traceable.
- Menambahkan permission dan i18n notification serta test baseline queue/in-app notification.

## Milestone 12

- Menambahkan baseline storage abstraction, MinIO/filesystem adapter boundary, dan path convention file.
- Menyelaraskan upload dokumen/foto existing agar melalui storage service tanpa menambah fitur bisnis baru.
- Menambahkan validasi file terpusat, signed URL private, public URL asset, dan test unit storage.

## Sprint 7

- Menambahkan migration, model Eloquent, enum, company settings, dan smoke test dasar untuk fondasi modul Recruitment.
- Menambahkan repository contracts, Eloquent repository implementations, binding provider, dan test repository untuk modul Recruitment.
- Menambahkan Application Service untuk job posting dan test management recruitment, termasuk status transition, lock enforcement, dan service tests.
- Menambahkan Application Service untuk applicant pipeline dan quiz flow recruitment, termasuk queued job stubs, duplicate/blacklist handling, quiz token flow, dan auto-grading.
- Menambahkan Application Service untuk interview scheduling/confirmation dan portal pemberkasan recruitment, termasuk token expiry, queued email job stubs, dan service tests.
- Mengimplementasikan queued job Recruitment, mailable kandidat, template email Blade, dan pembuatan draft employee dari kandidat hired.
- Melengkapi Application Service Recruitment dengan note, blacklist, stage attachment, facade method ApplicantService, dan alias job sesuai kontrak Sprint 7-A3.
- Menambahkan HTTP layer Recruitment: controller private/public, FormRequest, Resource, route API, middleware IP whitelist, dan translation key response.
- Menambahkan portal pemberkasan publik Pages Router di frontend HRIS, termasuk status dokumen, upload modal, validasi file client-side, progress dokumen wajib, dan i18n ID/EN.
- Menambahkan halaman karir publik frontend-web dengan ISR, service public jobs, JobCard, empty state, dan i18n recruitment ID/EN.
- Menambahkan form lamaran kandidat di halaman karir frontend-web dengan prefill posisi, validasi client-side, multipart submit, dan i18n form ID/EN.
- Menambahkan halaman statis frontend-web untuk Home, About, Services, dan Contact dengan layout publik, hero Unsplash, konten dummy PT Saneng, dan i18n ID/EN.
