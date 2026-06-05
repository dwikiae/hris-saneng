# CHANGELOG

Semua perubahan penting project dicatat di file ini.

## 2026-06-05 - Phase D2-3 Frontend List Karyawan

- Mengganti placeholder `/dashboard/employees` menjadi halaman daftar karyawan memakai `ListPageTemplate`, lengkap dengan breadcrumb, filter, summary strip, tabel desktop, bulk select, dan card stack mobile.
- Menambahkan halaman `/dashboard/employees/archived` dengan banner MODE ARSIP, kolom arsip, dan aksi Pulihkan.
- Menambahkan `employee.service.ts`, tipe kontrak frontend employee, dan `AvatarWithInfo` untuk daftar karyawan.
- Menambahkan dukungan breadcrumb dan banner pada `ListPageTemplate` tanpa mengubah halaman existing yang sudah memakai template tersebut.
- Mengubah sidebar saat berada di konteks `/dashboard/employees/*` agar menampilkan navigasi modul Karyawan dan footer Pengaturan sesuai permission `karyawan.settings`.
- Mencatat gap backend: endpoint list karyawan belum support archived, sort server-side, contract_type, employee export, dan restore module-specific.

## 2026-06-04 - Phase D2-2 Frontend Settings Modul Karyawan

- Menambahkan halaman `/dashboard/employees/settings?section={slug}` dengan layout settings modul, vertical nav, dan mobile section dropdown.
- Menghubungkan section Level/Grade dan Lokasi Kerja ke endpoint company-scoped modul Karyawan, termasuk tambah/edit inline modal, filter aktif/nonaktif, toast, dan arsip via ConfirmDialog.
- Menambahkan form konfigurasi modul Karyawan untuk format nomor karyawan, masa probasi, notifikasi kontrak berakhir, dan batas maksimal PKWT.
- Menambahkan service frontend `employee-master.service.ts`, `employee-settings.service.ts`, dan `wilayah.service.ts`.
- Menambahkan footer Pengaturan di sidebar saat user berada di konteks `/dashboard/employees/*`, gated dengan permission `karyawan.settings`.
- Menampilkan API-not-ready state untuk section master data lain yang belum punya endpoint spec backend modul Karyawan.

## 2026-06-04 - Phase D2-1 Backend Settings Modul Karyawan

- Menambahkan backend Settings Modul Karyawan untuk lokasi kerja, level karyawan, konfigurasi modul, wilayah Indonesia, dan negara ISO.
- Menambahkan migration modul Karyawan untuk `work_locations`, `employee_levels`, `employee_module_settings`, `provinces`, `cities`, dan `countries`.
- Menambahkan endpoint company-scoped `/api/v1/{company}/employees/master/*` dan `/api/v1/{company}/employees/settings` dengan permission `karyawan.settings`.
- Menambahkan endpoint authenticated read-only `/api/v1/instance/wilayah/*` untuk provinsi, kota/kabupaten, dan negara tanpa permission khusus.
- Menambahkan seeders offline 38 provinsi, 514 kota/kabupaten, 249 negara ISO, serta command `wilayah:sync` dan `countries:sync`.
- Menambahkan feature test untuk permission, company isolation, archive/restore, settings default/override, wilayah read-only, dan command sync idempotent.

## 2026-06-04 - Fix Company Edit Cache Refresh

- Memastikan save Company Management yang berhasil langsung memperbarui cache React Query untuk detail company.
- Meng-invalidate query Settings Companies setelah create/update agar halaman detail dan list tidak menampilkan data lama selama `staleTime` frontend.
- Memverifikasi proxy Next `/api/v1/*` tetap mengarah ke Laravel `http://localhost:8000/api/v1` dan request update company lewat proxy berhasil mengubah data.

## 2026-06-04 - Phase D1 Backend Grup 5 Module Registry

- Menambahkan endpoint instance-level Module Registry di `/api/v1/instance/modules` khusus Platform Administrator.
- Menambahkan tabel `module_registry` untuk status install instance-level tanpa `company_id`.
- Menggabungkan data registry database dengan manifest `module.json` dari filesystem, termasuk status mandatory, dependency, installed state, dan missing dependency.
- Menambahkan lifecycle install/uninstall modul optional dengan migration runner yang aman saat folder migration modul masih kosong.
- Menambahkan export-data per company ke private storage path `exports/{company_id}/{date}/{module}.xlsx`, signed URL, export marker di `instance_settings`, dan audit log dengan warning UU PDP saat uninstall.

## 2026-06-04 - Phase D1 Backend Grup 4 Audit Log

- Menambahkan endpoint instance-level Audit Log di `/api/v1/instance/audit` khusus Platform Administrator.
- Menambahkan export audit log di `/api/v1/instance/audit/export` untuk format CSV dan XLSX tanpa dependency Composer baru, termasuk kompatibilitas POST untuk `ExportButton` frontend existing.
- Mengambil data dari tabel `activity_log` Spatie dengan filter tanggal, causer, log name, event, dan alias frontend actor/module/action/search.
- Mengembalikan response frontend-ready berisi `items`, `meta`, dan `diffs` dengan redaction field sensitif berbasis permission `audit.view_sensitive`.
- Mencatat setiap export audit log ke audit log itu sendiri dengan format, filter, record count, dan waktu export.

## 2026-06-04 - Phase D1 Backend Grup 3 Platform Config

- Menambahkan endpoint instance-level Platform Config di `/api/v1/instance/config` untuk membaca dan menyimpan konfigurasi platform khusus Platform Administrator.
- Menyimpan konfigurasi di tabel `instance_settings` existing tanpa migration baru, termasuk timezone, bahasa, format tanggal, sesi, lockout, dan SMTP.
- Mengenkripsi `smtp_password` sebelum disimpan dan hanya mengembalikan mask di response API.
- Menambahkan endpoint `/api/v1/instance/config/test-smtp` yang mengantre email test SMTP ke Platform Admin yang sedang login.
- Menambahkan queued job, mailable, Blade email, resource, FormRequest, service, dan feature test untuk flow Platform Config.

## 2026-06-04 - Fix Settings Index Navigation

- Mengubah `/dashboard/settings` dari placeholder menjadi redirect otomatis ke `/dashboard/settings/companies` melalui Next config, dengan fallback redirect di page index.
- Menjadikan Company Management sebagai default landing page Settings karena semua sub-menu Settings sudah tersedia melalui `SettingsNav` di halaman detailnya.
- Menjaga subpage Settings existing tetap tidak berubah.

## 2026-06-04 - Fix Auth Flow End-to-End

- Menghapus halaman placeholder `/dashboard/logout` supaya logout berjalan sebagai aksi, bukan navigasi ke halaman logout.
- Menyambungkan tombol Keluar di Sidebar dan menu Topbar ke `authService.logout()`, lalu membersihkan `dictive_hr_token`, clear auth store, dan redirect ke `/login`.
- Memastikan login tetap memakai `POST /api/v1/auth/login`, menyimpan token, mengisi auth store, dan redirect ke `/dashboard` saat login sukses tanpa force password reset.
- Memperketat coverage auth agar `admin@saneng.co.id` sebagai Instance Admin (`company_id = null`) menerima permission virtual `platform.settings` dari login dan `/auth/me`.
- Memverifikasi `/login` dan `/dashboard/settings/companies` bisa dirender dari build lokal, sedangkan `/dashboard/logout` sudah tidak tersedia.

## 2026-06-03 - Phase D1 Backend Grup 2 Users & Access

- Menambahkan endpoint instance-level Users di `/api/v1/instance/users` untuk list, create, detail, update, archive, dan resend invitation khusus Platform Administrator.
- Menambahkan endpoint instance-level Roles di `/api/v1/instance/roles` untuk list, create, detail, update, archive, dan replace permission role via `PATCH /permissions`.
- Menambahkan endpoint `/api/v1/instance/permissions/structure` dengan tree modules, menus, dan actions dari module registry serta fallback permission database.
- Menambahkan auth password recovery backend: `forgot-password`, `reset-password`, dan `set-password` invitation.
- Menambahkan tabel `user_invitations`, model invitation, queued jobs, mailables, dan Blade email untuk invitation user serta reset password tanpa dependency baru.
- Memperbaiki behavior sidebar Settings agar tidak hilang saat auth hydration masih berjalan dan membersihkan token frontend jika `/auth/me` gagal.
- Menambahkan feature test untuk Users & Access dan auth password access, dengan catatan test runtime masih terblokir PostgreSQL lokal yang belum aktif.

## 2026-06-03 - Fix Frontend API Proxy

- Menambahkan rewrite Next.js untuk meneruskan `/api/v1/*` dari frontend dev server ke Laravel API, sehingga `AuthHydrator` bisa membaca `/api/v1/auth/me` dari backend.
- Menjaga target default Laravel di `http://localhost:8000/api/v1` dan menyediakan override via `API_BASE_URL` atau `NEXT_PUBLIC_API_BASE_URL`.

## 2026-06-03 - Fix Settings Navigation Permission

- Menambahkan permission virtual `platform.settings` pada response login dan `/auth/me` untuk Instance Admin (`company_id = null`), supaya menu Settings di sidebar tidak tersembunyi untuk Platform Administrator.
- Menambahkan coverage auth untuk memastikan permission virtual tersebut tetap dikirim ke frontend.

## 2026-06-03 - Phase D1 Backend Grup 1 Company Management

- Menambahkan endpoint instance-level Company Management di `/api/v1/instance/companies` untuk list, create, detail, update, dan archive company.
- Membatasi endpoint hanya untuk Platform Administrator, memakai FormRequest authorization dan guard private API existing.
- Menambahkan layer controller, application service, repository contract/implementation, resource camelCase, dan feature test `InstanceCompanyControllerTest`.
- Menambahkan kolom `archived_at` dan `archived_by` ke tabel `companies` agar archive company tidak memakai hard delete.
- Menyimpan field company extended frontend di `company_settings`, sementara field inti tetap memakai tabel `companies`.
- Mencatat bahwa verifikasi test backend Grup 1 masih terblokir karena PostgreSQL/Docker daemon lokal tidak berjalan saat sesi ini.

## 2026-06-03 - Phase D1 Sub-task 4 Platform Config, Audit Log, dan Module Registry

- Menambahkan frontend Platform Config di `/dashboard/settings/config` dengan form lokalisasi, keamanan sesi, lockout login, SMTP, toggle password, test email, dan save langsung.
- Menambahkan frontend Audit Log di `/dashboard/settings/audit` dengan list read-only, summary strip, filter tanggal/actor/modul/action, export Excel/CSV, dan drawer detail diff dengan redaction field sensitif.
- Menambahkan frontend Module Registry di `/dashboard/settings/modules` dengan grid card modul, badge dependency, flow install, dan flow uninstall dua tahap dengan warning UU PDP serta export data per company.
- Menambahkan service `platform-config.service.ts`, `audit-log.service.ts`, `module-registry.service.ts`, dan type contract-ready untuk Platform Settings.
- Mencatat gap backend: endpoint spec `/api/v1/instance/config`, `/api/v1/instance/audit`, dan lifecycle `/api/v1/instance/modules` belum tersedia, sehingga halaman menampilkan API-not-ready state.

## 2026-06-03 - Phase D1 Sub-task 3 Users & Access

- Menambahkan Users & Access frontend contract-ready di `/dashboard/settings/users` dengan tab Users dan Roles.
- Menambahkan halaman tambah/edit/detail user serta halaman tambah/detail role memakai FormPageTemplate dan DetailPageTemplate.
- Menambahkan permission matrix role 3 level yang menarget `GET /api/v1/instance/permissions/structure` dan `PATCH /api/v1/instance/roles/{id}/permissions`.
- Menambahkan `user.service.ts`, `role.service.ts`, dan type contract-ready untuk user, role, assignment company/role, login activity, dan permission tree.
- Mencatat gap backend: endpoint instance users, roles, resend invitation, dan permission structure belum tersedia, sehingga halaman menampilkan API-not-ready state.

## 2026-06-03 - Phase D1 Sub-task 2 Company Management

- Menambahkan Company Management frontend contract-ready di `/dashboard/settings/companies`, termasuk list, form tambah/edit, detail, tab modul, dan tab catatan platform.
- Menambahkan service `company.service.ts` yang menarget endpoint spec `/api/v1/instance/companies` dan `/api/v1/instance/modules`.
- Menambahkan Settings sub-navigation dan membatasi menu Settings di sidebar dengan `PermissionGate permission="platform.settings"`.
- Menambahkan translation key ID/EN untuk seluruh field company sesuai Section 4 PLATFORM_UI_SPEC.
- Mencatat gap backend: endpoint instance company/modules dan field company extended belum tersedia, sehingga halaman menampilkan API-not-ready state.

## 2026-06-03 - Phase D1 Sub-task 1 Auth Pages

- Menambahkan halaman auth frontend `/login`, `/forgot-password`, `/reset-password`, dan `/set-password` di luar dashboard AppShell.
- Menyambungkan login ke service layer dan backend `POST /api/v1/auth/login`, termasuk simpan token, hydrate auth store, dan redirect.
- Menambahkan contract-ready service untuk forgot password, reset password, set password invitation, serta fallback force password reset memakai endpoint `change-password` existing.
- Menambahkan translation key ID/EN untuk semua teks auth dan mencatat bahwa endpoint backend password recovery/invitation belum tersedia.

## 2026-06-03 - Phase C Platform Behaviors

- Menambahkan platform behaviors di `/frontend`: PermissionGate, ConfirmDialog, Toast system, NotificationBell, LanguageSwitcher, ExportButton, DocumentUpload, ApprovalPanel, ApprovalTimeline, dan ChatLog.
- Menyambungkan NotificationBell dan LanguageSwitcher ke Topbar dashboard, dengan stub WebSocket Soketi yang no-op jika env realtime belum tersedia.
- Menambahkan halaman `/dashboard/notifications` dan demo visual `/dashboard/platform-demo` untuk verifikasi templates Phase B dan behaviors Phase C dalam satu surface non-production.
- Menambahkan dependency frontend `sonner` untuk toast dan `pusher-js` untuk client WebSocket Soketi/Pusher protocol.
- Menambahkan endpoint backend `PATCH /api/v1/users/me/preferences` agar preference bahasa ID/EN bisa disimpan tanpa migration baru.
- Mencatat bahwa verifikasi backend test Phase C masih terblokir karena PostgreSQL/Docker daemon lokal tidak berjalan saat sesi ini.

## 2026-06-03 - Phase B Platform UI Templates

- Menambahkan `docs/PLATFORM_UI_SPEC.md` sebagai source of truth platform UI di repo.
- Membuat template platform frontend untuk halaman list, detail, form, dan wizard sebelum modul baru dibangun.
- Menambahkan translation key ID/EN untuk label default template.
- Menegaskan bahwa Phase C Platform Behaviors adalah task berikutnya sebelum menyentuh modul Karyawan atau modul lain.

## 2026-06-02 - Step 3b

- Memindahkan backend test environment dari SQLite in-memory ke PostgreSQL database `hris_local_test` karena PHP environment tidak punya `pdo_sqlite`.
- Membuat test dashboard stats berjalan stabil di PostgreSQL, termasuk urutan recent activity yang deterministic.
- Menyesuaikan beberapa test lama agar cocok dengan PostgreSQL, seeder terbaru, dan helper upload tanpa GD.
- Mencatat bahwa PHP GD extension belum aktif, sehingga test varian foto karyawan di-skip sampai extension tersedia.

## 2026-06-02 — Tahap 1 Step 3

- Menambahkan endpoint backend dashboard stats agar halaman `/dashboard` bisa membaca data nyata dari Laravel.
- Menjaga data dashboard tetap company-scoped, termasuk employee count, pending approvals, dan recent activity berbasis employee activity.
- Mencatat bahwa attendance dan leave belum punya tabel sendiri, sehingga angka hadir, tidak hadir, dan cuti masih 0 sampai sumber datanya dibuat.

## 2026-06-02 — Tahap 1 Step 2

- Membuat halaman `/dashboard` memakai data contract API, bukan data contoh permanen.
- Menambahkan greeting personal, stat cards HR, pending approvals khusus approver, dan recent activity dengan state loading, populated, dan error.
- Mencatat bahwa endpoint Laravel `/api/v1/dashboard/stats` belum ada sehingga dashboard menunggu backend sebelum bisa menampilkan data nyata.

## 2026-06-02 — Keputusan Tambahan UI/UX

- Menetapkan dark mode tidak masuk scope sehingga platform berjalan light mode only.
- Menetapkan onboarding first login sebagai wizard untuk nama platform, logo, dan role pertama.
- Menetapkan language switcher ID/EN, notifikasi real-time via Soketi, dan company branding per company sebagai keputusan final UI/UX.

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
