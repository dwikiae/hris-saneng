# CHANGELOG

Semua perubahan penting project dicatat di file ini.

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

## Sprint 6

- Menambahkan fondasi database modul Karyawan tahap A1 secara aditif: master pendukung employee, kontrak, offboarding, riwayat, keluarga, dokumen, foto, dan chatter.
- Menambahkan model Eloquent untuk tabel Employee Sprint 6, termasuk relasi kontrak, offboarding, chatter, pendidikan, pengalaman, dan keluarga.
- Menambahkan seeder termination reason, contract type, dan offboarding template sesuai referensi UI HTML.
- Menambahkan focused test A1 untuk migration, model scope/encryption, seeder, dan unique active contract constraint.
- Menambahkan domain layer Sprint 6 A2 untuk status karyawan, status kontrak, state machine approval, validasi NIK, validasi consent UU PDP, dan unit test domain pure PHP.
- Menambahkan repository layer Sprint 6 A3 untuk query/filter/pagination employee, generator nomor karyawan/kontrak, contract repository, binding container, dan focused repository tests.
- Membersihkan sisa field payroll dari modul employee sesuai non-goal Sprint 6, mengganti permission salary menjadi `employee.view_sensitive`, dan mendokumentasikan backup step migration sebelum penghapusan kolom legacy.
- Menambahkan Application Service Sprint 6 A4 untuk create/update/approve/archive employee core, termasuk validasi consent/NIK, approval by assigned approver, employee number saat approve, dan chatter system log redacted.
- Menambahkan Application Service Sprint 6 A5 untuk lifecycle kontrak: create, update draft, renew PKWT, terminate, validasi overlap/end date, auto-expire kontrak lama, job notifikasi expiry, command `employee:check-contract-expiry`, dan permission `contract.*`.
- Menambahkan Application Service Sprint 6 A6 untuk offboarding, checklist snapshot/finalize atomic, chatter manual dengan mention, upload foto/dokumen, processing foto via queue, dan integrasi CreateEmployeeFromApplicant status pending tanpa employee number.
