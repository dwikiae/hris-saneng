# ALL_ADR.md
## Dictive-HR — Architecture Decision Records
### Version: 1.0 | Status: FINAL | Phase: Foundation

> ADR mendokumentasikan keputusan arsitektur yang signifikan — konteks, opsi, keputusan, alasan, dan konsekuensi.
> Baca sebelum mengusulkan perubahan arsitektur apapun.
> Setiap supersede harus melalui ADR baru dengan justifikasi kuat.

---

## Daftar ADR

| ADR | Judul | Status |
|---|---|---|
| ADR-001 | Self-Hosted Multi-Company Single Instance | ACCEPTED |
| ADR-002 | Tech Stack: Laravel + Next.js | ACCEPTED |
| ADR-003 | Single Database dengan company_id Isolation | ACCEPTED |
| ADR-004 | Module System: Toggle per Instance, Enable per Company | ACCEPTED |
| ADR-005 | Mandatory Modules: Karyawan + Kalender | ACCEPTED |
| ADR-006 | Single Next.js App untuk Semua Surface | ACCEPTED |
| ADR-007 | API First Architecture | ACCEPTED |
| ADR-008 | Archive-Only Policy — Tidak Ada Hard Delete | ACCEPTED |
| ADR-009 | Zero Hardcoded String — Semua via i18n | ACCEPTED |
| ADR-010 | Dynamic RBAC — Roles dan Permissions sebagai Master Data | ACCEPTED |
| ADR-011 | Instance Admin dengan Full Access + Audit Log | ACCEPTED |
| ADR-012 | Uninstall Modul: Export per Company → Hapus Semua | ACCEPTED |
| ADR-013 | Adapter Pattern untuk Semua External API | ACCEPTED |
| ADR-014 | Queue + Retry untuk Semua Notifikasi | ACCEPTED |
| ADR-015 | Docker Compose untuk Deployment | ACCEPTED |
| ADR-016 | Employee Number Token Engine | ACCEPTED |

---

## ADR-001 — Self-Hosted Multi-Company Single Instance

**Status:** ACCEPTED

### Konteks
Platform HRIS dibutuhkan oleh perusahaan yang ingin kontrol penuh atas data karyawan mereka. Model SaaS berarti data di server vendor — tidak acceptable untuk perusahaan yang compliance-conscious. Satu instance perlu bisa melayani beberapa company (holding + anak perusahaan, atau konsultan yang manage beberapa klien).

### Opsi
| Opsi | Deskripsi |
|---|---|
| A: SaaS multi-tenant | Data di server vendor |
| **B: Self-hosted, single instance multi-company** ← dipilih | Customer install di server sendiri, bisa banyak company |
| C: Self-hosted, satu instance per company | Terlalu berat untuk konsultan yang manage banyak klien |

### Keputusan
Self-hosted. Satu instance bisa menampung banyak company. Data sepenuhnya di server customer.

### Alasan
- UU PDP No. 27/2022 mendorong perusahaan Indonesia kontrol data sendiri
- Model self-hosted memberikan fleksibilitas penuh ke customer
- Multi-company dalam satu instance memudahkan konsultan dan holding company

### Konsekuensi
- Isolasi data antar company adalah tanggung jawab application layer
- Maintenance dan update di tangan vendor (Dictive-HR)
- Customer butuh server dan kemampuan IT dasar

### Aturan Turunan
- Semua tabel utama wajib punya `company_id`
- Semua query wajib filter `company_id` via Global Scope
- Instance Admin tidak punya `company_id` (NULL = akses semua company)

---

## ADR-002 — Tech Stack: Laravel + Next.js

**Status:** ACCEPTED

### Konteks
Platform butuh backend yang robust untuk enkripsi, RBAC, audit log, queue, dan multi-tenancy. Frontend butuh SSR untuk public-facing pages (SEO) dan SPA experience untuk dashboard internal.

### Opsi
| Opsi | Deskripsi |
|---|---|
| **A: Laravel 11 + Next.js 14** ← dipilih | Mature ecosystem, familiar, proven untuk kebutuhan ini |
| B: NestJS + Next.js | Full TypeScript, tapi ekosistem HR-specific tidak se-mature Laravel |
| C: Go + Next.js | Performa tinggi, tapi ekosistem sangat terbatas untuk solo developer |

### Keputusan
**Laravel 11** sebagai backend REST API. **Next.js 14** (App Router) sebagai frontend untuk semua surface.

### Alasan
- Laravel punya ekosistem lengkap untuk kebutuhan platform ini: Sanctum (auth), Spatie Permission (RBAC), Spatie ActivityLog (audit), Queue (notification), Service Provider (module system)
- Next.js App Router bisa handle multiple surface (dashboard + public website + candidate portal) dalam satu aplikasi dengan rendering strategy berbeda per route group
- Solo developer — ekosistem yang familiar dan mature lebih penting dari performa marginal

### Konsekuensi
- PHP di stack (beberapa orang anggap "kurang modern" — tidak relevan untuk kebutuhan ini)
- Dua bahasa pemrograman (PHP + TypeScript)

### Aturan Turunan
- Backend tidak pernah serve HTML — pure REST API
- Semua API call dari frontend melalui service layer (`/services/`), tidak langsung dari component
- API prefix wajib `/api/v1/`

---

## ADR-003 — Single Database dengan company_id Isolation

**Status:** ACCEPTED

### Konteks
Multi-company membutuhkan isolasi data. Ada tiga pendekatan: single DB dengan row-level isolation, single DB dengan schema per company, atau database terpisah per company.

### Opsi
| Opsi | Deskripsi |
|---|---|
| **A: Single database, company_id di semua tabel** ← dipilih | Simpel, isolasi di application layer |
| B: Single database, schema terpisah per company | Isolasi lebih kuat tapi kompleksitas tinggi |
| C: Database terpisah per company | Isolasi sempurna tapi terlalu kompleks untuk solo dev |

### Keputusan
Single database PostgreSQL. Semua tabel utama punya `company_id`. Isolasi dijaga di application layer via Global Scope.

### Alasan
- Opsi B dan C adalah overengineering untuk v1 dengan solo developer
- Bug query yang bocorkan data bisa diperbaiki — arsitektur yang terlalu kompleks sulit di-maintain
- Global Scope di semua model adalah safety net yang sufficient untuk v1
- Migration, backup, dan restore jauh lebih simpel dengan single database

### Konsekuensi
- Isolasi hanya di application layer — perlu disiplin tinggi di semua query
- Satu backup mencakup semua company (tidak bisa restore per company secara independen)

### Aturan Turunan
- Semua model utama wajib punya Global Scope `whereNull('archived_at')` DAN `where('company_id', currentCompanyId())`
- Middleware `ResolveCompany` inject `company_id` ke setiap request company-scoped
- Test wajib cover skenario "query tidak sengaja return data lintas company"

---

## ADR-004 — Module System: Toggle per Instance, Enable per Company

**Status:** ACCEPTED

### Konteks
Platform harus modular — modul bisa ditambah dan dihapus. Perlu ditentukan di level mana install/uninstall dan enable/disable dikelola.

### Opsi
| Opsi | Deskripsi |
|---|---|
| A: Install + enable semua di level company | Setiap company install sendiri |
| **B: Install di level instance, enable di level company** ← dipilih | Instance Admin install global, company tinggal enable |
| C: Semua di level instance | Tidak fleksibel per company |

### Keputusan
**Install/uninstall** di level instance (global, oleh Instance Admin). **Enable/disable** di level company (company bisa pilih modul mana yang aktif dari yang sudah terinstall).

### Alasan
- Instance Admin bertanggung jawab atas apa yang tersedia di platform
- Company-level flexibility tetap terjaga — tidak semua company perlu semua modul
- Satu migration dijalankan sekali untuk semua company (lebih simpel)
- Sesuai dengan model bisnis: vendor yang menentukan modul apa yang tersedia

### Konsekuensi
- Uninstall modul di instance level berdampak ke semua company — perlu safeguard
- Instance Admin punya kekuasaan besar atas ketersediaan fitur semua company

### Aturan Turunan
- `module_registry` untuk state instance level
- `company_module_settings` untuk state company level
- Uninstall: wajib export data per company terlebih dahulu
- Module manifest (`module.json`) wajib ada di setiap modul

---

## ADR-005 — Mandatory Modules: Karyawan + Kalender

**Status:** ACCEPTED

### Konteks
Hampir semua modul HR bergantung pada data karyawan dan konsep kalender kerja. Jika ini adalah optional module, dependency antar modul menjadi sangat kompleks.

### Opsi
| Opsi | Deskripsi |
|---|---|
| A: Employee profile minimal di core | Core punya data karyawan basic |
| **B: Modul Karyawan dan Kalender sebagai mandatory module** ← dipilih | Sepaket dengan core, tidak bisa uninstall |
| C: Semua modul optional | Terlalu kompleks dependency-nya |

### Keputusan
Modul **Karyawan** dan **Kalender** adalah mandatory — sepaket dengan core, auto-install saat setup, tidak bisa uninstall.

### Alasan
- Modul Karyawan adalah root dependency semua modul HR (Recruitment, Absensi, Cuti, Payroll, Aset)
- Modul Kalender adalah root dependency semua modul time-based (Absensi, Cuti, Payroll)
- Dari sisi UU Ketenagakerjaan Indonesia, data karyawan dan kalender kerja adalah kebutuhan minimum setiap perusahaan
- Memisahkan ini ke optional module hanya menambah kompleksitas tanpa benefit nyata

### Konsekuensi
- Semua installer Dictive-HR mendapat modul Karyawan + Kalender — tidak bisa dikurangi
- Jika customer tidak butuh fitur Kalender, module tetap terinstall (overhead kecil, bisa diabaikan)

### Aturan Turunan
- Modul Karyawan dan Kalender tidak muncul di module toggle UI
- `is_mandatory: true` di `module.json` kedua modul ini
- Migration kedua modul dijalankan bersamaan dengan core migration saat install

---

## ADR-006 — Single Next.js App untuk Semua Surface

**Status:** ACCEPTED

### Konteks
Platform punya tiga surface berbeda: dashboard internal, company website publik, dan candidate portal. Perlu diputuskan apakah ini satu atau beberapa aplikasi frontend.

### Opsi
| Opsi | Deskripsi |
|---|---|
| **A: Satu Next.js App Router untuk semua surface** ← dipilih | Route groups berbeda per surface |
| B: Dua app (dashboard + public) | Separation of concerns tapi dua deployment |
| C: Tiga app terpisah | Terlalu banyak yang di-maintain |

### Keputusan
Satu aplikasi Next.js 14 dengan App Router. Route groups berbeda per surface dengan rendering strategy yang sesuai.

### Alasan
- Next.js App Router didesain untuk exactly ini — route groups bisa punya layout, auth, dan rendering strategy berbeda
- Shared components (header, form elements, UI library) tidak perlu diduplikasi
- Satu deployment, satu build pipeline
- Company website dan candidate portal bisa share theme system

### Konsekuensi
- Bundle size perlu dikelola dengan code splitting yang baik
- Routing bisa kompleks — perlu konvensi yang jelas

### Aturan Turunan
- `/dashboard/*` → Surface 1, protected, client-side heavy
- `/[company-slug]/*` → Surface 2, public, SSR
- `/[company-slug]/kandidat/*` → Surface 3, public, SSR, akses via token
- CSS variables di-load berdasarkan company slug untuk theming

---

## ADR-007 — API First Architecture

**Status:** ACCEPTED

### Konteks
Platform yang ingin di-open source dan dikembangkan komunitas harus memungkinkan integrasi eksternal. Perlu ditentukan apakah backend dan frontend tightly coupled atau terpisah via API.

### Opsi
| Opsi | Deskripsi |
|---|---|
| A: Tightly coupled (Blade/Livewire) | Simpel tapi tidak extensible |
| **B: API First — backend sebagai pure REST API** ← dipilih | Frontend adalah consumer pertama |
| C: GraphQL | Fleksibel tapi overkill untuk v1 |

### Keputusan
**API First**. Backend Laravel adalah pure REST API. Next.js adalah consumer pertama. Developer lain bisa build integrasi, mobile app, atau custom frontend di atas API yang sama.

### Alasan
- Open source roadmap membutuhkan API yang bisa dikonsumsi pihak ketiga
- Memungkinkan mobile app di masa depan tanpa ubah backend
- Separation yang bersih antara business logic dan presentation
- Odoo dan Dynamics 365 keduanya API first

### Konsekuensi
- CORS harus dikonfigurasi dengan benar
- API contract harus terdokumentasi dengan baik sebelum breaking change

### Aturan Turunan
- Backend tidak pernah serve HTML
- Semua fungsi platform harus accessible via API — tidak ada fungsi yang hanya bisa diakses via UI
- Versioning: `/api/v1/` — breaking change membutuhkan `/api/v2/`

---

## ADR-008 — Archive-Only Policy — Tidak Ada Hard Delete

**Status:** ACCEPTED

### Konteks
Data karyawan adalah data pribadi yang punya retention period berdasarkan UU KUP (10 tahun untuk data pembukuan) dan UU PDP. Hard delete dari UI bisa menyebabkan pelanggaran compliance yang tidak disengaja.

### Keputusan
Tidak ada hard delete dari UI untuk data utama. Semua "delete" adalah archive — data diberi `archived_at` dan `archived_by`, tidak muncul di query normal tapi tetap ada di database. Hard delete hanya terjadi saat uninstall modul (dengan export wajib terlebih dahulu) atau via proses retensi yang diapprove admin.

### Alasan
- UU KUP Pasal 28 ayat 11: data pembukuan wajib disimpan 10 tahun
- UU PDP Pasal 31: semua pemrosesan data pribadi wajib dicatat
- Safety net: data yang ter-archive bisa di-restore, data yang ter-hard delete tidak bisa

### Konsekuensi
- Database bertumbuh — perlu monitoring disk usage
- UI harus jelas membedakan "Arsipkan" bukan "Hapus"

### Aturan Turunan
- Tidak ada `->delete()` di production code kecuali dari ArchiveService
- Semua model utama wajib punya Global Scope `whereNull('archived_at')`
- Tombol di UI: "Arsipkan" bukan "Hapus"
- Warning UU PDP wajib muncul saat proses uninstall modul

---

## ADR-009 — Zero Hardcoded String — Semua via i18n

**Status:** ACCEPTED

### Konteks
Platform harus bilingual (ID/EN) dan extensible untuk bahasa lain. Menambah i18n belakangan berarti refactor ratusan file.

### Keputusan
Zero hardcoded string di seluruh codebase. Semua string user-facing via i18n key sejak baris kode pertama. Backend: `lang/id/` dan `lang/en/`. Frontend: `next-i18next` namespace per modul.

### Alasan
- Cost refactor i18n belakangan jauh lebih besar dari membangunnya dari awal
- Konsistensi: tidak perlu memutuskan "ini perlu i18n tidak?" — jawabannya selalu ya

### Konsekuensi
- Setiap string baru harus ditambahkan ke dua file (id + en)
- Sprint pertama fokus infrastruktur i18n sebelum fitur bisnis

### Aturan Turunan
- Format key: `{namespace}.{context}.{label}`
- Backend: `__('karyawan.form.nama')`
- Frontend: `t('karyawan:form.nama')`
- Setiap komponen UI baru wajib tambah translation key di kedua bahasa

---

## ADR-010 — Dynamic RBAC — Roles dan Permissions sebagai Master Data

**Status:** ACCEPTED

### Konteks
Platform multi-company butuh access control yang fleksibel. Setiap company bisa punya struktur organisasi dan kebutuhan permission yang berbeda.

### Keputusan
Dynamic RBAC menggunakan `spatie/laravel-permission`. Roles dan permissions adalah master data di database — bisa diubah dari UI tanpa deploy ulang. Format permission: `module.action`. Field-level permission via tabel custom `field_permissions`.

### Alasan
- Tanpa redeploy: company bisa buat role custom sesuai kebutuhan mereka
- Spatie Permission mature dan terintegrasi dengan Laravel Gate/Policy
- Audit trail untuk semua perubahan permission

### Konsekuensi
- Permission cache harus di-clear setiap ada perubahan
- Perlu UI management role yang lengkap

### Aturan Turunan
- Format permission tidak boleh berubah — breaking change global
- `Gate::authorize('module.action')` di setiap Application Service method
- Permission check SELALU di backend — frontend PermissionGate hanya untuk UX
- Default roles saat company baru: Manager, Staff

---

## ADR-011 — Instance Admin dengan Full Access + Audit Log

**Status:** ACCEPTED

### Konteks
Instance Admin perlu akses penuh untuk troubleshoot dan manage platform. Tapi akses ke data HR sensitif semua company menimbulkan risiko UU PDP.

### Opsi
| Opsi | Deskripsi |
|---|---|
| A: Batasi Instance Admin — tidak bisa lihat data HR | Aman tapi tidak praktis untuk support |
| **B: Full access + audit log wajib** ← dipilih | Akses tidak dibatasi, tapi sepenuhnya transparan |

### Keputusan
Instance Admin punya akses penuh ke semua data semua company. Setiap akses tercatat di audit log (sama seperti semua user lain) — tidak ada perlakuan khusus.

### Alasan
- Untuk troubleshoot production issue, Instance Admin butuh bisa lihat data aktual
- Audit log yang komprehensif adalah mitigasi yang sufficient — akses tidak dibatasi tapi transparan
- Pendekatan ini sama dengan yang dipakai AWS CloudTrail dan enterprise systems lainnya
- Menambah restriction khusus untuk Instance Admin adalah overengineering

### Konsekuensi
- Audit log harus benar-benar tidak bisa dimanipulasi — termasuk oleh Instance Admin
- Customer harus dipastikan memahami bahwa Instance Admin (vendor) punya akses ke data mereka

### Aturan Turunan
- Audit log tidak bisa dihapus oleh siapapun — termasuk Instance Admin
- Ini adalah satu-satunya table yang tidak punya Archive policy — append only

---

## ADR-012 — Uninstall Modul: Export per Company → Hapus Semua

**Status:** ACCEPTED

### Konteks
Saat modul di-uninstall di level instance, data semua company yang punya data modul tersebut ikut terhapus. Perlu policy yang jelas.

### Keputusan
Flow uninstall: sistem generate export data per company → Instance Admin konfirmasi dengan warning UU PDP → semua data dihapus → migration di-rollback.

### Alasan
- Simpel dan tidak bloat
- Customer bertanggung jawab atas compliance data retention setelah export
- Warning UU PDP memberikan paper trail bahwa customer sudah diinformasikan

### Konsekuensi
- Data hilang permanen setelah uninstall — tidak ada undo
- Customer yang uninstall modul dengan data sensitif harus pastikan mereka punya backup

### Aturan Turunan
- Export wajib selesai sebelum konfirmasi uninstall bisa dilanjutkan
- Warning UU PDP harus eksplisit menyebut konsekuensi penghapusan data
- Aksi uninstall tercatat di audit log dengan detail lengkap

---

## ADR-013 — Adapter Pattern untuk Semua External API

**Status:** ACCEPTED

### Konteks
Platform akan berinteraksi dengan MinIO, SMTP, dan di masa depan mungkin layanan lain. Domain logic tidak boleh bergantung pada vendor spesifik.

### Keputusan
Semua external API melalui Adapter di `app/Infrastructure/` yang mengimplementasikan Interface di domain/application layer.

### Alasan
- Vendor lock-in prevention: ganti vendor = ganti adapter, bukan ubah domain
- Testability: adapter bisa di-mock di unit test
- Single responsibility: domain tidak tahu detail HTTP client atau credential management

### Aturan Turunan
- Semua external API wajib melalui adapter
- Domain layer tidak boleh import class dari vendor SDK secara langsung
- Saat vendor baru: buat adapter baru, register di Service Provider, tidak ubah domain

---

## ADR-014 — Queue + Retry untuk Semua Notifikasi

**Status:** ACCEPTED

### Konteks
Notifikasi email bisa gagal (SMTP down, timeout). Pengiriman synchronous memblokir request cycle.

### Keputusan
Semua notifikasi email via Laravel Queue (Redis driver). Retry 3x dengan exponential backoff. Gagal → `failed_jobs` → alert Instance Admin.

### Alasan
- Non-blocking: request HTTP selesai tanpa menunggu email terkirim
- Reliability: SMTP sementara down tidak berarti notifikasi hilang
- Redis sudah ada untuk cache — tidak perlu infrastructure tambahan

### Aturan Turunan
- Tidak ada `Mail::send()` synchronous dalam request cycle
- Retry policy: 3x dengan backoff 30s, 60s, 120s
- Failed jobs harus visible di Instance Admin dashboard

---

## ADR-015 — Docker Compose untuk Deployment

**Status:** ACCEPTED

### Konteks
Self-hosted platform butuh cara install yang simpel dan reproducible. Customer punya variasi OS dan setup server yang berbeda.

### Keputusan
Docker Compose sebagai deployment method utama. Satu `docker-compose up` menjalankan seluruh stack.

### Alasan
- Reproducible environment — tidak ada "works on my machine"
- Simpel untuk customer yang familiar dengan Docker
- Semua dependency (PostgreSQL, Redis, MinIO, Soketi, Nginx) terisolasi dalam container
- Standard de facto untuk self-hosted applications

### Konsekuensi
- Customer butuh Docker dan Docker Compose terinstall
- Resource overhead container (minor untuk server modern)

### Aturan Turunan
- `docker-compose.yml` untuk development (include Mailpit)
- `docker-compose.prod.yml` untuk production (SMTP real, tanpa Mailpit)
- Installer wizard dijalankan pertama kali setelah `docker-compose up`
- Minimum server spec didokumentasikan di README

---

## ADR-016 - Employee Number Token Engine

**Status:** ACCEPTED

### Konteks
Platform multi-company membutuhkan format nomor karyawan yang fleksibel per company tanpa deploy ulang. Setiap company bisa punya aturan penomoran berbeda sesuai kebijakan internal.

### Keputusan
Nomor karyawan menggunakan token engine dengan pembagian peran yang jelas:
- Developer/vendor menentukan token yang tersedia, validasi backend, dan menjaga keunikan nomor.
- HR/Admin dengan permission `karyawan.settings` menyusun format dari token yang tersedia, melihat preview sebelum simpan, dan tidak perlu coding.

Format disimpan di `employee_module_settings` per company. Backend generate nomor saat karyawan dibuat.

Token v1 yang tersedia:
- `{SEQ:N}`: sequence N digit, per company, tidak reset saat arsip.
- `{JOIN:format}`: tanggal join karyawan, format `DDMMYYYY`, `YYYY`, `MM`, atau `DD`.
- `{YYYY}`: tahun saat generate.
- `{MM}`: bulan saat generate.
- `{DEPT_CODE}`: kode departemen karyawan.
- `{CONTRACT_TYPE}`: `PKWT` atau `PKWTT`.

Token yang ditunda ke v2:
- `{BIRTH:format}`: butuh tanggal lahir sebagai dependency eksplisit.
- `{COMPANY_CODE}`: butuh field kode di company settings.

### Edge Case
- Jika format memakai token yang butuh data karyawan (`DEPT_CODE`, `CONTRACT_TYPE`, `JOIN`) tetapi data belum diisi saat create, save ditolak dengan pesan jelas field apa yang harus diisi dulu.
- Sequence per company dan tidak reset saat karyawan diarsipkan.
- Nomor yang sudah di-generate tidak bisa diubah kecuali oleh user dengan permission `employee.update`.

### Alasan
- Pola ini mengikuti model Odoo sequence engine dan SAP number range yang proven di enterprise.
- Multi-company friendly: tiap company bisa punya aturan nomor sendiri.
- Aman: tidak ada `eval()` atau arbitrary code execution.
- Bisa diaudit: format tersimpan di settings, nomor final tersimpan di employee record.
- Variasi format tidak butuh deploy ulang.

### Konsekuensi
- Developer wajib mendokumentasikan token baru di UI sebagai help text.
- Setiap token baru butuh implementasi di backend token resolver.
- Format lama tetap valid selama token yang dipakai masih terdaftar.

### Aturan Turunan
- Token vocabulary didefinisikan di backend sebagai whitelist.
- Preview dihasilkan dari data nyata company untuk tahun sekarang dan next sequence, serta dummy aman untuk token employee-data.
- Permission pengelolaan format: `karyawan.settings`.

---

## Cara Menambah ADR Baru

1. Tambah entry ke tabel Daftar ADR
2. Buat section baru dengan format konsisten
3. Nomor: increment dari yang terakhir
4. Commit: `docs: add ADR-XXX [judul]`
5. Update CHANGELOG.md

**Keputusan perlu ADR jika:**
- Sulit untuk dibalik
- Mempengaruhi lebih dari satu modul
- Tidak intuitif tanpa penjelasan konteks
- Ada opsi lain yang dipertimbangkan

---

*Dokumen ini adalah memori arsitektur Dictive-HR.*
*Baca sebelum mengusulkan perubahan yang bertentangan dengan keputusan di sini.*
