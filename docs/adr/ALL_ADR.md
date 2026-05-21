# ALL_ADR.md
## HRIS PT Saneng — Architecture Decision Records
### ADR-001 s/d ADR-015
### Version: 1.0 | Status: FINAL | Last updated: Sprint 0

> Architecture Decision Records (ADR) mendokumentasikan **keputusan arsitektur yang signifikan**:
> konteks mengapa keputusan dibuat, opsi yang dipertimbangkan, alasan pilihan final, dan
> konsekuensi yang diterima. Dokumen ini adalah memori permanen sistem — bacalah sebelum
> mengusulkan perubahan arsitektur apapun.
>
> **Format setiap ADR:**
> - Status: ACCEPTED | SUPERSEDED | DEPRECATED
> - Konteks: Mengapa keputusan ini perlu dibuat
> - Opsi yang dipertimbangkan
> - Keputusan: Apa yang dipilih
> - Alasan: Mengapa opsi ini dipilih
> - Konsekuensi: Trade-off yang diterima secara sadar
> - Aturan turunan: Rules yang berlaku di codebase sebagai hasil keputusan ini

---

## Daftar ADR

| ADR | Judul | Status |
|---|---|---|
| ADR-001 | Tech Stack: Laravel + Next.js (Separated) | ACCEPTED |
| ADR-002 | company_id di Semua Tabel Utama | ACCEPTED |
| ADR-003 | User ≠ Employee (Tabel Terpisah) | ACCEPTED |
| ADR-004 | Permission Check Selalu di Backend | ACCEPTED |
| ADR-005 | Zero Hardcoded String — Semua via i18n | ACCEPTED |
| ADR-006 | MinIO Self-Hosted untuk File Storage | ACCEPTED |
| ADR-007 | Archive-Only Policy — Tidak Ada Hard Delete dari UI | ACCEPTED |
| ADR-008 | Enkripsi At-Rest untuk Field Sensitif | ACCEPTED |
| ADR-009 | Adapter Pattern untuk Semua External API | ACCEPTED |
| ADR-010 | IP Whitelist + WireGuard VPN untuk Akses HRIS | ACCEPTED |
| ADR-011 | Dynamic RBAC — Roles dan Permissions sebagai Master Data | ACCEPTED |
| ADR-012 | Approval Assignment Manual per Karyawan | ACCEPTED |
| ADR-013 | Kolom Status di Semua Tabel Approval-Prone | ACCEPTED |
| ADR-014 | Queue + Retry 3x untuk Notifikasi | ACCEPTED |
| ADR-015 | Staging = Branch Berbeda di Server yang Sama | ACCEPTED |

---

---

## ADR-001 — Tech Stack: Laravel + Next.js (Separated)

**Status:** ACCEPTED
**Tanggal:** Sprint 0
**Decider:** Principal (owner sistem)

### Konteks

Sistem HRIS PT Saneng membutuhkan backend yang robust untuk mengelola data sensitif karyawan
dengan audit trail, enkripsi, dan RBAC yang kuat. Frontend membutuhkan pengalaman modern yang
responsif dengan dukungan i18n bilingual. Maintainer adalah solo developer dengan keahlian di
ekosistem Laravel dan React.

### Opsi yang Dipertimbangkan

| Opsi | Deskripsi |
|---|---|
| **A: Laravel + Next.js (Separated)** ← dipilih | Backend Laravel sebagai pure API, dua frontend Next.js terpisah |
| B: Laravel + Blade (monolithic) | Laravel full-stack dengan Blade template dan Livewire |
| C: Laravel + Vue.js (Inertia) | Laravel + Inertia.js + Vue.js, shared routing |
| D: Node.js + React | Full JavaScript stack |

### Keputusan

**Opsi A: Laravel sebagai REST API backend + dua frontend Next.js terpisah.**

- `/backend` → Laravel 11 (pure API, tidak serve HTML)
- `/frontend-hris` → Next.js 14 (internal portal, `hris.saneng.co.id`)
- `/frontend-web` → Next.js 14 (website publik, `saneng.co.id`)

### Alasan

- **Laravel** adalah pilihan terkuat untuk kebutuhan security, enkripsi, RBAC, audit log, dan
  queue yang sudah mature — semua tersedia via package ekosistem Laravel (Sanctum, Spatie).
- **Next.js** memberikan fleksibilitas SSR/SSG untuk website publik (SEO karir page) dan SPA
  experience untuk portal internal.
- **Separation of concerns** yang bersih: backend bisa di-scale atau diganti frontend tanpa
  mengubah API contract.
- **Dua frontend terpisah** memungkinkan deployment, security boundary, dan dependency yang
  berbeda antara portal internal (IP-locked) dan website publik (open).
- Solo developer lebih familiar dengan ekosistem ini daripada opsi lain.

### Konsekuensi yang Diterima

- Dua codebase frontend harus di-maintain secara terpisah (shared components tidak otomatis).
- CORS harus dikonfigurasi dengan benar antara frontend-hris dan backend.
- Deployment lebih kompleks dari Blade monolithic (tapi masih manageable untuk solo dev).
- Tidak ada SSR built-in untuk data private (hanya SSR untuk /karir yang memang public).

### Aturan Turunan

- Backend **tidak pernah** serve HTML. Semua via REST API.
- frontend-hris dan frontend-web adalah **dua aplikasi independen** — tidak ada shared build.
- API prefix wajib `/api/v1/` untuk semua endpoint.

---

---

## ADR-002 — company_id di Semua Tabel Utama

**Status:** ACCEPTED
**Tanggal:** Sprint 0
**Decider:** Principal (owner sistem)

### Konteks

Sistem dibangun untuk PT Saneng sebagai single-tenant. Namun ada kemungkinan — meski kecil —
sistem ini suatu saat dipakai untuk anak perusahaan atau entitas lain. Merombak skema database
di kemudian hari (ALTER TABLE di ratusan tabel dengan jutaan record) adalah operasi berisiko
tinggi dan mahal.

### Opsi yang Dipertimbangkan

| Opsi | Deskripsi |
|---|---|
| **A: company_id di semua tabel utama sejak awal** ← dipilih | Kolom selalu ada, nilai selalu sama saat ini |
| B: Tidak tambahkan company_id, refactor nanti jika perlu | Lebih simpel sekarang, berisiko besar nanti |
| C: Separate database per company | Overkill untuk kebutuhan saat ini |

### Keputusan

Tambahkan kolom `company_id` (FK ke tabel `companies`) di **semua tabel utama** sejak awal.
Saat ini nilai selalu sama (ID PT Saneng). Tidak ada UI multi-company dibangun di v1.

### Alasan

- **Cost of adding later >> cost of adding now.** Menambah kolom ke tabel dengan ratusan ribu
  record + update semua query di codebase adalah operasi berisiko dengan downtime risk.
- Overhead saat ini nyaris nol: satu kolom integer, satu index, nilai selalu sama.
- Global scope `whereCompanyId()` juga berfungsi sebagai **safety net** jika ada bug query
  yang tidak sengaja mengembalikan data lintas entitas.

### Konsekuensi yang Diterima

- Setiap query harus filter `company_id` — menambah sedikit verbosity.
- Seeder harus membuat default company record PT Saneng sebagai langkah pertama.
- Developer baru harus memahami konvensi ini (didokumentasikan di AGENTS.md).

### Aturan Turunan

- `company_id` adalah kolom **wajib** di semua tabel utama tanpa pengecualian.
- Semua query **wajib** filter `company_id` — diimplementasikan via Global Scope di Model.
- Seeder pertama sistem: insert record PT Saneng ke tabel `companies`.
- Nilai default `company_id` diambil dari config/env, bukan hardcode di kode.

---

---

## ADR-003 — User ≠ Employee (Tabel Terpisah)

**Status:** ACCEPTED
**Tanggal:** Sprint 0
**Decider:** Principal (owner sistem)

### Konteks

Perlu ditentukan apakah "pengguna sistem" dan "karyawan perusahaan" adalah entitas yang sama
atau berbeda. Ini mempengaruhi desain tabel, relasi, dan flow pembuatan akun.

### Opsi yang Dipertimbangkan

| Opsi | Deskripsi |
|---|---|
| **A: Tabel terpisah `users` dan `employees`** ← dipilih | Relasi opsional via FK |
| B: Satu tabel gabungan | User = Employee, tidak ada pemisahan |
| C: Tabel user extend tabel employee | Employee sebagai base |

### Keputusan

Tabel `users` dan tabel `employees` **terpisah sepenuhnya**.
- `users.employee_id` → nullable, unique (one-to-one jika ada relasi)
- Ada **user tanpa employee**: System Admin IT yang bukan karyawan HR
- Ada **employee tanpa user**: Karyawan yang belum dibuatkan akses sistem
- HR/Admin yang membuat user untuk karyawan — **tidak ada self-register**
- **Email verification tidak diperlukan** saat user baru dibuat (HR yang buat, bukan pendaftar)

### Alasan

- Realita operasional: tidak semua user adalah karyawan, tidak semua karyawan punya akun.
- Pemisahan memungkinkan System Admin (IT) punya akses tanpa harus terdaftar sebagai karyawan.
- Data karyawan (NIK, gaji, kontrak) bisa ada tanpa akun login.
- Relasi one-to-one via FK lebih bersih daripada penggabungan kolom yang tidak selalu relevan.

### Konsekuensi yang Diterima

- Join diperlukan saat perlu data user + data karyawan bersamaan.
- Flow "buat karyawan baru" dan "buat akun user" adalah dua operasi terpisah.
- HR harus ingat bahwa menghapus user tidak otomatis menghapus data karyawan (dan sebaliknya).

### Aturan Turunan

- Tabel `users`: data auth (email, password, token, role).
- Tabel `employees`: data HR (NIK, jabatan, kontrak, gaji, dokumen).
- `users.employee_id` nullable — jangan buat NOT NULL.
- HR yang membuat user untuk karyawan via UI, bukan karyawan yang register sendiri.
- Pembuatan user dan linking ke employee adalah **dua langkah terpisah** yang bisa dilakukan
  tidak bersamaan.

---

---

## ADR-004 — Permission Check Selalu di Backend

**Status:** ACCEPTED
**Tanggal:** Sprint 0
**Decider:** Principal (owner sistem)

### Konteks

Sistem punya dynamic RBAC dengan field-level permission. Perlu ditentukan di layer mana
enforcement permission dilakukan — hanya di frontend (sembunyikan tombol/field), hanya di
backend (API return 403), atau keduanya.

### Opsi yang Dipertimbangkan

| Opsi | Deskripsi |
|---|---|
| **A: Backend sebagai enforcement, frontend sebagai UX** ← dipilih | Backend selalu cek, frontend hanya sembunyikan UI |
| B: Frontend-only | Sembunyikan UI = cukup, tidak cek di backend |
| C: Backend-only | Frontend selalu render semua, backend yang filter |

### Keputusan

**Permission check SELALU dilakukan di backend (Laravel Gate/Policy).**
Frontend `PermissionGate` component hanya berfungsi untuk UX — menyembunyikan tombol atau
field yang tidak boleh diakses. Frontend bukan security layer.

### Alasan

- Frontend adalah **untrusted environment** — JavaScript bisa dimanipulasi, request bisa
  dikirim langsung ke API tanpa melalui UI.
- Jika permission hanya di frontend: seorang user dengan akses DevTools bisa mengirim request
  langsung ke `/api/v1/employees/1` dan mendapatkan data yang seharusnya tidak boleh dilihat.
- Backend Gate/Policy adalah satu-satunya yang bisa dipercaya sebagai enforcement.
- Frontend PermissionGate tetap diperlukan untuk UX yang baik (jangan tampilkan tombol yang
  akan selalu return 403).

### Konsekuensi yang Diterima

- Setiap endpoint harus explicitly define Policy/Gate check — tidak boleh lupa.
- Penambahan permission baru harus dilakukan di dua tempat: Gate/Policy (backend) + PermissionGate (frontend UX).
- Test wajib cover skenario "user tanpa permission mencoba akses endpoint" → expect 403.

### Aturan Turunan

- Setiap controller method yang butuh permission **wajib** ada Gate::authorize() atau
  $this->authorize() di baris pertama setelah input validation.
- `PermissionGate` di frontend: sembunyikan UI, bukan block access.
- Test integration wajib: "User tanpa `employee.view_salary` → GET /employees/{id} →
  response tidak mengandung field salary."
- Field-level permission enforcement di API response layer (Resource/Transformer).

---

---

## ADR-005 — Zero Hardcoded String — Semua via i18n

**Status:** ACCEPTED
**Tanggal:** Sprint 1
**Decider:** Principal (owner sistem)

### Konteks

Sistem harus bilingual (ID/EN), dengan preferensi bahasa per user yang disimpan di DB.
Perlu ditentukan apakah i18n diterapkan dari awal atau ditambahkan belakangan.

### Opsi yang Dipertimbangkan

| Opsi | Deskripsi |
|---|---|
| **A: i18n dari hari pertama, zero hardcoded string** ← dipilih | Semua string via key sejak Sprint 1 |
| B: Hardcode dulu, refactor nanti | Lebih cepat di awal, mahal belakangan |
| C: i18n hanya untuk label, error message hardcode | Setengah-setengah, tidak konsisten |

### Keputusan

**Zero hardcoded string** di seluruh codebase — baik backend (PHP) maupun frontend
(TypeScript/TSX). Semua string user-facing melalui i18n key. Sprint 1 khusus membangun
infrastruktur i18n sebelum modul bisnis apapun dibangun.

### Alasan

- **Menambah i18n belakangan = refactor ratusan file.** Setiap komponen, setiap error message,
  setiap label form harus ditelusuri dan diganti satu per satu.
- Biaya membangun i18n di awal jauh lebih rendah daripada refactor di tengah jalan.
- Konsistensi: developer tidak perlu memutuskan "ini perlu i18n tidak?" — jawabannya selalu ya.
- Backend i18n juga diperlukan untuk error message di API response yang dibaca frontend.

### Konsekuensi yang Diterima

- Sprint 1 tidak menghasilkan fitur bisnis — murni infrastruktur.
- Setiap string baru harus ditambahkan ke dua file (id + en) — sedikit lebih verbose.
- Review harus memastikan tidak ada string yang lolos tanpa key.
- AI coder harus selalu diingatkan via AGENTS.md untuk tidak hardcode string.

### Aturan Turunan

- Backend: gunakan `__('employee.form.name')` atau `trans('employee.form.name')`.
- Frontend: gunakan `t('employee:form.name')` via `useTranslation` hook.
- Format key: `{namespace}.{context}.{label}` — konsisten tanpa pengecualian.
- Setiap PR yang tambah komponen UI baru **wajib** tambah translation key di kedua bahasa.
- CI check (opsional): scan untuk hardcoded string pattern di JSX/TSX.

---

---

## ADR-006 — MinIO Self-Hosted untuk File Storage

**Status:** ACCEPTED
**Tanggal:** Sprint 0
**Decider:** Principal (owner sistem)

### Konteks

Sistem menyimpan dokumen sensitif karyawan (KTP, NPWP, kontrak, foto) dan dokumen recruitment.
UU PDP mensyaratkan pengelolaan data pribadi yang bertanggung jawab. Perlu dipilih solusi
penyimpanan file yang sesuai.

### Opsi yang Dipertimbangkan

| Opsi | Deskripsi |
|---|---|
| **A: MinIO self-hosted di VPS** ← dipilih | S3-compatible, data tidak keluar VPS |
| B: AWS S3 | Managed, andal, tapi data di server AWS (luar negeri) |
| C: Google Cloud Storage | Sama seperti AWS |
| D: Simpan di filesystem server | Tidak ada abstraksi, sulit migrasi |

### Keputusan

**MinIO self-hosted** berjalan di VPS yang sama dengan aplikasi. Diakses via S3-compatible API.
Semua akses file melalui `Storage::disk()` abstraction di Laravel.

### Alasan

- **UU PDP compliance**: Data pribadi karyawan tidak boleh keluar dari infrastruktur
  perusahaan ke layanan consumer-grade cloud asing.
- **Zero additional cost**: MinIO gratis, sudah jalan di VPS yang sama.
- **S3-compatible**: Jika suatu saat ingin migrasi ke enterprise cloud storage, hanya ganti
  `.env` tanpa ubah satu baris kode pun (berkat abstraction layer).
- **Kontrol penuh**: Backup, akses, dan enkripsi dikontrol sendiri.
- Untuk 700 karyawan, volume file masih sangat manageable di satu VPS.

### Konsekuensi yang Diterima

- MinIO adalah satu lagi service yang harus di-maintain di VPS.
- Jika VPS down, file storage juga down (single point of failure — dimitigasi dengan backup).
- Tidak ada CDN built-in (tidak diperlukan untuk sistem internal).
- Admin perlu memahami dasar MinIO untuk troubleshooting.

### Aturan Turunan

- Semua akses file **wajib** via `Storage::disk('documents')->...` — tidak pernah langsung
  ke MinIO SDK atau S3 API dari luar adapter.
- Config MinIO ada di `.env` — jika suatu saat pindah ke S3, hanya ganti env variable.
- Dokumen sensitif: akses via **signed URL** (time-limited), bukan public URL permanent.
- Foto karyawan public thumbnail: bisa via public disk jika diperlukan untuk tampilan.
- Path file mengikuti konvensi yang terdokumentasi di `02_ARCHITECTURE.md`.

---

---

## ADR-007 — Archive-Only Policy — Tidak Ada Hard Delete dari UI

**Status:** ACCEPTED
**Tanggal:** Sprint 0
**Decider:** Principal (owner sistem)

### Konteks

Sistem menyimpan data karyawan yang merupakan data pribadi (UU PDP) dan data pembukuan
(UU KUP — retensi 10 tahun). Penghapusan data secara permanen bisa menimbulkan masalah
compliance dan audit. Perlu kebijakan yang jelas tentang siklus hidup data.

### Opsi yang Dipertimbangkan

| Opsi | Deskripsi |
|---|---|
| **A: Archive-only, tidak ada hard delete dari UI biasa** ← dipilih | Data hanya bisa di-archive via menu khusus |
| B: Soft delete standard (Laravel SoftDeletes) | Bisa restore, tapi UI bisa delete |
| C: Hard delete diizinkan | Berisiko compliance, tidak ada safety net |
| D: Immutable — tidak bisa hapus sama sekali | Terlalu ketat, tidak practical |

### Keputusan

**Archive-only policy** dengan implementasi:
- Kolom `archived_at` (nullable timestamp) + `archived_by` (nullable FK ke users) di semua tabel utama.
- Global scope di semua model: semua query default `whereNull('archived_at')`.
- Satu-satunya cara "hapus": melalui `ArchiveService` yang hanya bisa dipanggil via menu Archive khusus.
- Hard delete (permanenet) hanya diizinkan via proses retensi data yang sudah di-approve Admin,
  dicatat di audit trail, dan bukan dari UI biasa.
- Tidak ada tombol "Delete" di CRUD biasa — hanya tombol "Archive".

### Alasan

- **Audit trail**: Jika data dihapus dan kemudian dipertanyakan, tidak ada yang bisa
  dibuktikan. Archive mempertahankan record untuk audit.
- **UU KUP Pasal 28 ayat 11**: Data pembukuan wajib disimpan 10 tahun. Hard delete dari UI
  biasa memungkinkan pelanggaran tidak disengaja.
- **UU PDP Pasal 31**: Semua pemrosesan data pribadi wajib dicatat.
- **Safety net**: HR tidak sengaja hapus data karyawan yang sudah tidak aktif — archive bisa
  dikembalikan (via Admin), hard delete tidak.
- **Penggantian nama/jabatan**: History tetap terjaga untuk laporan historis.

### Konsekuensi yang Diterima

- Database akan terus tumbuh (archive records tidak pernah benar-benar hilang dari DB).
- Query dengan `withArchived()` diperlukan untuk melihat data ter-archive.
- UI harus jelas membedakan "Archive" vs "Delete" — membutuhkan UX yang bijak.
- Penghapusan permanent membutuhkan alur approval tambahan (lebih panjang tapi lebih aman).

### Aturan Turunan

- **Tidak ada `->delete()` di production code** kecuali dari `ArchiveService`.
- Semua model utama **wajib** punya kolom `archived_at` dan `archived_by`.
- Semua model utama **wajib** punya Global Scope yang filter `whereNull('archived_at')`.
- Method `archive(int $userId)` tersedia di semua model utama.
- UI: tombol "Arsipkan" (bukan "Hapus") dengan konfirmasi dialog.
- Untuk melihat data ter-archive: gunakan `Model::withoutGlobalScope('not_archived')->...`.

---

---

## ADR-008 — Enkripsi At-Rest untuk Field Sensitif

**Status:** ACCEPTED
**Tanggal:** Sprint 0
**Decider:** Principal (owner sistem)

### Konteks

Sistem menyimpan data yang termasuk kategori **Data Pribadi Spesifik** per Pasal 4 UU PDP
No. 27/2022: data keuangan (gaji, nomor rekening) dan data identitas (NIK, NPWP). Jika
database diakses oleh pihak tidak berwenang (breach), data ini tidak boleh terbaca.

### Opsi yang Dipertimbangkan

| Opsi | Deskripsi |
|---|---|
| **A: Laravel `encrypted` cast di Model level** ← dipilih | Enkripsi transparan di application layer |
| B: PostgreSQL column encryption (pgcrypto) | Enkripsi di database level |
| C: Tidak enkripsi, hanya access control | Tidak memenuhi UU PDP untuk data spesifik |
| D: Enkripsi seluruh database | Overkill, performa berat, tidak targeted |

### Keputusan

Field sensitif menggunakan **Laravel `encrypted` cast** di Model. Enkripsi/dekripsi terjadi
otomatis di application layer menggunakan `APP_KEY` di `.env`.

Field yang dienkripsi:
- `employees.nik` (NIK KTP)
- `employees.npwp` (NPWP pribadi)
- `employees.bank_account_number` (nomor rekening)
- `employees.salary` (gaji pokok)
- `employees.allowances` (tunjangan)
- `employees.deductions` (potongan)
- Data biometrik (sidik jari) — **wajib enkripsi saat diintegrasikan** di masa depan

### Alasan

- **UU PDP Pasal 4**: Data keuangan dan biometrik termasuk Data Pribadi Spesifik yang
  memerlukan perlindungan lebih ketat.
- **Laravel encrypted cast** adalah solusi paling simpel dengan maintenance overhead minimal.
- Enkripsi di application layer berarti bahkan DBA dengan akses langsung ke PostgreSQL tidak
  bisa membaca data plaintext.
- `APP_KEY` Laravel sudah ada dan dimanage via `.env` — tidak perlu key management tambahan.

### Konsekuensi yang Diterima

- **Field terenkripsi tidak bisa di-search/filter langsung via SQL** — harus ambil semua
  record dan filter di application layer, atau tidak menawarkan search untuk field ini.
- Jika `APP_KEY` berubah tanpa re-enkripsi data, semua data terenkripsi tidak bisa dibaca.
  `APP_KEY` adalah secret paling kritis — harus di-backup dengan aman.
- Performa sedikit lebih lambat untuk baca/tulis field terenkripsi (tradeoff diterima).
- 700 karyawan adalah volume kecil — tidak ada masalah performa yang signifikan.

### Aturan Turunan

- `APP_KEY` **wajib** di-backup secara terpisah dari backup database — kehilangan keduanya
  sekaligus berarti kehilangan data terenkripsi permanen.
- Field terenkripsi **tidak boleh** dimasukkan ke query `WHERE`, `LIKE`, atau `ORDER BY`.
- Field terenkripsi **wajib** di-mask di audit log (tampil sebagai `[REDACTED]`).
- Jika field terenkripsi perlu di-export: export hanya diizinkan untuk user dengan permission
  eksplisit, dan setiap export dicatat di log.
- Data biometrik di masa depan: enkripsi **wajib** sebelum disimpan, mengikuti pola yang sama.

---

---

## ADR-009 — Adapter Pattern untuk Semua External API

**Status:** ACCEPTED
**Tanggal:** Sprint 0
**Decider:** Principal (owner sistem)

### Konteks

Sistem akan berinteraksi dengan layanan eksternal: MinIO (storage), SMTP (email), dan di masa
depan fingerprint device API, AI services, dan lainnya. Perlu pola integrasi yang tidak
membuat domain logic bergantung pada vendor spesifik.

### Opsi yang Dipertimbangkan

| Opsi | Deskripsi |
|---|---|
| **A: Adapter pattern — semua external via interface di Infrastructure** ← dipilih | Domain hanya tahu interface, bukan implementasi |
| B: Langsung panggil SDK vendor dari service/domain | Cepat tapi coupling tinggi |
| C: Facade pattern | Lebih Laravel-idiomatic tapi tetap coupling |

### Keputusan

Semua integrasi external API melalui **Adapter** di `app/Infrastructure/` yang
mengimplementasikan **Interface** yang didefinisikan di domain/application layer.

```
app/Infrastructure/
  Storage/
    MinIOStorageAdapter.php       implements StorageAdapterInterface
  Notification/
    SmtpMailAdapter.php           implements MailAdapterInterface
  Biometric/
    FingerprintAdapterInterface.php   ← placeholder interface
    [FingerprintVendorAdapter.php]    ← dibuat saat vendor dipilih
  AI/
    AIAdapterInterface.php            ← placeholder interface
```

### Alasan

- **Vendor lock-in prevention**: Jika MinIO diganti S3, hanya `MinIOStorageAdapter` yang
  diubah. Domain logic tidak berubah sama sekali.
- **Testability**: Di unit test, adapter bisa di-mock dengan mudah tanpa perlu koneksi ke
  layanan eksternal.
- **Fingerprint vendor belum dipilih**: Interface sudah ada, implementasi menyusul saat vendor
  dipilih — domain code tidak perlu berubah.
- **Single responsibility**: Domain tidak perlu tahu detail HTTP client, retry logic, atau
  credential management dari vendor.

### Konsekuensi yang Diterima

- Lebih banyak file (interface + implementation) dibanding langsung panggil SDK.
- Developer harus paham pattern ini dan konsisten menerapkannya.
- Dependency injection harus dikonfigurasi di Service Provider.

### Aturan Turunan

- Semua external API **wajib** melalui adapter di `app/Infrastructure/`.
- Setiap adapter **wajib** mengimplementasikan interface yang didefinisikan di Application layer.
- Domain layer **tidak boleh** import class dari vendor SDK secara langsung.
- Saat vendor baru ditambahkan: buat adapter baru, register di Service Provider,
  **tidak ubah domain atau application code**.
- Untuk external API yang belum ada (fingerprint, AI): buat interface-nya dulu sebagai
  placeholder — ini memaksa desain kontrak sebelum implementasi.

---

---

## ADR-010 — IP Whitelist + WireGuard VPN untuk Akses HRIS

**Status:** ACCEPTED
**Tanggal:** Sprint 0
**Decider:** Principal (owner sistem)

### Konteks

Portal HRIS (`hris.saneng.co.id`) berisi data sensitif karyawan. Akses dari internet terbuka
— bahkan dengan username/password yang benar — menimbulkan risiko brute force, credential
stuffing, dan akses dari device yang tidak terpercaya.

### Opsi yang Dipertimbangkan

| Opsi | Deskripsi |
|---|---|
| **A: IP whitelist + WireGuard VPN** ← dipilih | Network-level restriction, hanya dari kantor/VPN |
| B: 2FA (TOTP) saja | Menambah layer auth tapi akses masih dari internet terbuka |
| C: Username/password saja | Tidak cukup untuk data sensitif |
| D: IP whitelist tanpa VPN | WFH tidak bisa akses |

### Keputusan

**Dua lapis network restriction:**
1. **IP whitelist**: Middleware `IPWhitelist` di Laravel memblokir semua request dari IP yang
   bukan IP range jaringan kantor PT Saneng atau IP WireGuard VPN.
2. **WireGuard VPN**: Self-hosted di VPS yang sama. Karyawan yang WFH wajib connect VPN
   sebelum bisa akses HRIS.

Response jika diblokir: `403 — "Akses hanya dari jaringan perusahaan"`

### Alasan

- **Defense in depth**: Bahkan jika credentials bocor, akses dari luar tidak bisa.
- **Prosedur IT perusahaan**: Sesuai standar keamanan sistem kritikal internal.
- **WireGuard**: Protocol VPN modern, performa tinggi, konfigurasi lebih simpel dari OpenVPN.
  Self-hosted di VPS yang sama = zero additional cost.
- **Manageable untuk 700 karyawan**: IP whitelist mudah di-manage via Settings UI.
- Website publik (`saneng.co.id`) dan endpoint `/api/v1/public/*` **tidak** kena IP whitelist.

### Konsekuensi yang Diterima

- WFH wajib connect VPN dulu — sedikit friction untuk user.
- Jika VPN down, semua WFH tidak bisa akses HRIS.
- IP range kantor harus didaftarkan di Settings UI — perlu update jika ISP kantor ganti IP.
- Onboarding pengguna baru: perlu setup WireGuard client.

### Aturan Turunan

- Middleware `IPWhitelist` diterapkan di semua route group `/api/v1/*` private dan `hris.saneng.co.id`.
- `/api/v1/public/*` dan `saneng.co.id` **bebas** dari IP whitelist.
- IP range yang diizinkan: **configurable via Settings UI** (bukan hardcode).
- WireGuard client config didistribusikan oleh IT owner.
- Test wajib: request dari IP tidak dikenal → 403, bukan 401 atau redirect login.

---

---

## ADR-011 — Dynamic RBAC — Roles dan Permissions sebagai Master Data

**Status:** ACCEPTED
**Tanggal:** Sprint 0
**Decider:** Principal (owner sistem)

### Konteks

Sistem perlu access control yang fleksibel untuk 4 tipe user dengan kebutuhan berbeda.
Perlu ditentukan apakah permission dikodekan di codebase (static) atau bisa diubah dari UI
tanpa deploy ulang (dynamic).

### Opsi yang Dipertimbangkan

| Opsi | Deskripsi |
|---|---|
| **A: Dynamic RBAC — roles dan permissions sebagai master data di DB** ← dipilih | Fleksibel, tidak perlu redeploy |
| B: Static roles hardcoded di codebase | Sederhana tapi tidak fleksibel |
| C: ABAC (Attribute-Based Access Control) | Terlalu kompleks untuk kebutuhan saat ini |
| D: ACL per user | Sulit di-maintain untuk 700 user |

### Keputusan

**Dynamic RBAC menggunakan spatie/laravel-permission** yang di-extend dengan field-level
permission custom.

```
roles           → master data role (bisa tambah dari UI)
permissions     → master data permission (format: module.action)
role_permissions → many-to-many
user_roles      → many-to-many

field_permissions → custom: kontrol field mana yang bisa dilihat per role
```

Default roles (seeder):
- `system_admin` → full access
- `hr_manager` → full HR access
- `hr_staff` → limited (no delete, no salary)
- `dept_manager` → approval only + view own dept

### Alasan

- **Tanpa redeploy**: Jika HR perlu satu staff baru dengan kombinasi permission berbeda,
  admin bisa buat role baru dari UI tanpa minta developer.
- **Audit friendly**: Role dan permission tercatat di DB — bisa di-audit siapa punya akses apa.
- **Spatie/laravel-permission**: Package mature, well-maintained, terintegrasi dengan Laravel
  Gate/Policy secara native.
- **Format `module.action`**: Konsisten, mudah di-grep di codebase, mudah di-display di UI.

### Konsekuensi yang Diterima

- Permission harus di-cache (spatie handle ini) untuk performa.
- Cache permission harus di-clear setiap kali ada perubahan role/permission.
- Perlu UI management role yang cukup lengkap (Sprint 4).
- Risiko: admin bisa buat role dengan permission terlalu luas — dimitigasi dengan audit log
  setiap perubahan permission.

### Aturan Turunan

- Format permission: `{module}.{action}` — konsisten, tidak boleh berubah (breaking change global).
- Permission catalog (semua `module.action` yang valid) ada di seeder — tidak dibuat random.
- `Gate::authorize('module.action')` di setiap Application Service method — wajib.
- Cache permission di-clear setiap kali role/permission diubah via `artisan permission:cache-reset`.
- UI permission management: hanya System Admin yang bisa akses.
- Field-level permission: tabel `field_permissions` custom, bukan fitur bawaan spatie.

---

---

## ADR-012 — Approval Assignment Manual per Karyawan

**Status:** ACCEPTED
**Tanggal:** Sprint 0
**Decider:** Principal (owner sistem)

### Konteks

Sistem memerlukan approval workflow untuk perubahan data karyawan (HR submit → Manager approve).
Perlu ditentukan bagaimana approver ditentukan — otomatis berdasarkan struktur organisasi,
atau manual per karyawan.

### Opsi yang Dipertimbangkan

| Opsi | Deskripsi |
|---|---|
| **A: Assignment manual per karyawan** ← dipilih | HR admin set `approver_id` per karyawan di UI |
| B: Otomatis berdasarkan Department/Division manager | Butuh struktur org yang formal dan konsisten |
| C: Semua approval ke HR Manager | Bottleneck, tidak scalable |
| D: Approval tidak diperlukan | Tidak memenuhi kebutuhan governance |

### Keputusan

**Approver di-assign manual per karyawan** oleh HR Admin. Field `approver_id` (FK ke `users`)
di tabel `employees`. Satu karyawan = satu approver. Setting bisa diubah kapan saja dari UI.

### Alasan

- **Struktur organisasi PT Saneng belum formal**: Mapping "karyawan X → manager Y" tidak bisa
  diotomatisasi dari data departemen yang ada karena struktur masih berkembang.
- **Fleksibilitas**: Ada kasus di mana approver bukan manager departemen langsung (direktur
  langsung, dll).
- **Implementasi lebih sederhana**: Tidak perlu logic traversal org chart yang kompleks.
- **Mudah di-audit**: `approver_id` tersimpan eksplisit di DB — jelas siapa harusnya approve.

### Konsekuensi yang Diterima

- HR Admin harus set approver untuk setiap karyawan baru — satu langkah tambahan di onboarding.
- Jika manager berubah, HR Admin harus update `approver_id` secara manual.
- Tidak ada otomatisasi org chart traversal.

### Aturan Turunan

- `employees.approver_id` adalah FK nullable ke `users.id`.
- Nullable: karyawan bisa ada tanpa approver (sebelum di-assign).
- Warning di UI: karyawan tanpa approver tidak bisa submit approval request.
- Ubah approver: hanya HR Manager atau System Admin yang bisa ubah `approver_id`.
- Setiap perubahan `approver_id` dicatat di activity log.

---

---

## ADR-013 — Kolom Status di Semua Tabel Approval-Prone

**Status:** ACCEPTED
**Tanggal:** Sprint 0
**Decider:** Principal (owner sistem)

### Konteks

Tidak semua modul punya approval workflow aktif di v1, tapi semua modul berpotensi
membutuhkannya di masa depan. ALTER TABLE di tabel besar (dengan ratusan ribu record)
adalah operasi berisiko dan memerlukan downtime.

### Opsi yang Dipertimbangkan

| Opsi | Deskripsi |
|---|---|
| **A: Tambah kolom status + approved_by sejak awal** ← dipilih | Future-proof, zero refactor saat approval diaktifkan |
| B: Tambahkan saat approval diperlukan | Lebih simpel sekarang, ALTER TABLE nanti |
| C: Tabel terpisah untuk approval state | Terlalu kompleks |

### Keputusan

Semua tabel yang **berpotensi** butuh approval workflow (sekarang maupun nanti) menyimpan:
```sql
status       VARCHAR NOT NULL DEFAULT 'draft'  -- saat ini: draft | active
approved_by  BIGINT NULL REFERENCES users(id)
approved_at  TIMESTAMP NULL
```

Saat ini nilai status hanya `draft` atau `active`. Saat approval workflow diaktifkan,
status diperluas ke `pending | approved | rejected` — **tanpa ALTER TABLE**.

### Alasan

- **Cost of ALTER TABLE later >> cost of adding column now.**
- ALTER TABLE di PostgreSQL pada tabel dengan jutaan record memerlukan lock dan bisa
  menyebabkan downtime.
- Kolom `status` + `approved_by` + `approved_at` overhead-nya sangat kecil saat ini.
- Konsistensi: semua tabel punya pola yang sama — developer tidak perlu tebak-tebak.
- Selaras dengan ADR-002 (company_id): prinsip yang sama untuk future-proofing.

### Konsekuensi yang Diterima

- Kolom `approved_by` dan `approved_at` akan selalu NULL sampai approval workflow diaktifkan.
- Perlu dokumentasi yang jelas agar developer tidak bingung dengan kolom yang "tidak dipakai".
- State machine untuk status transition harus didefinisikan bahkan sebelum approval aktif.

### Aturan Turunan

- Kolom `status`, `approved_by`, `approved_at` **wajib** ada di semua tabel yang punya workflow.
- Default nilai `status` di migration: `'draft'` atau `'active'` tergantung konteks.
- **Transisi status tidak boleh via mass assignment** — wajib melalui dedicated method.
- State machine yang valid (bahkan untuk `draft → active` sederhana) harus didefinisikan
  di Domain layer sejak awal.
- Saat approval workflow diaktifkan: hanya tambah value baru di enum status, tidak ALTER TABLE.

---

---

## ADR-014 — Queue + Retry 3x untuk Notifikasi

**Status:** ACCEPTED
**Tanggal:** Sprint 0
**Decider:** Principal (owner sistem)

### Konteks

Sistem mengirim notifikasi email (approval request, approval result, system alert) dan in-app
notification. Pengiriman email bisa gagal (SMTP down, timeout). Perlu strategi yang tidak
memblokir request cycle dan reliable.

### Opsi yang Dipertimbangkan

| Opsi | Deskripsi |
|---|---|
| **A: Queue + retry 3x + failed_jobs** ← dipilih | Non-blocking, reliable, observable |
| B: Synchronous (kirim email dalam request) | Blocking, lambatkan response, gagal = request gagal |
| C: Fire and forget (queue tanpa retry) | Non-blocking tapi tidak reliable |
| D: Third-party service (SendGrid, dll) | Tidak perlu self-managed tapi data keluar, biaya tambahan |

### Keputusan

**Semua notifikasi email via Laravel Queue (Redis driver) dengan:**
- Retry: 3x dengan exponential backoff
- Setelah 3x gagal: masuk tabel `failed_jobs`
- Alert ke System Admin jika ada failed job
- Admin bisa retry manual dari dashboard atau Artisan command

In-app notification: broadcast via Soketi WebSocket (tidak melalui queue, karena in-memory).

### Alasan

- **Non-blocking**: Request HTTP selesai dalam < 100ms meskipun email butuh beberapa detik.
  User tidak perlu menunggu email terkirim.
- **Reliability**: SMTP sementara down tidak berarti notifikasi hilang — akan di-retry.
- **Observability**: `failed_jobs` table memberikan visibility ke notification yang gagal.
- **Redis sudah ada**: Tidak perlu infrastructure tambahan — Redis sudah dipakai untuk cache.
- **3x retry** adalah sweet spot: cukup untuk handle transient failure, tidak terlalu agresif.

### Konsekuensi yang Diterima

- Queue worker harus selalu berjalan (supervisor/horizon) — satu service tambahan.
- Jika Redis down, queue tidak bisa diproses — Redis menjadi critical dependency.
- Email bisa delayed (tidak instant) — acceptable untuk use case ini.
- Admin harus monitoring `failed_jobs` secara berkala.

### Aturan Turunan

- **Tidak ada** `Mail::send()` atau `Notification::send()` synchronous dalam request cycle.
  Selalu `dispatch(new SendEmailJob(...))` atau `->delay()`.
- Retry policy: `public $tries = 3;` + `public function backoff(): array { return [30, 60, 120]; }`
- Failed jobs harus visible di admin dashboard (Sprint 5).
- System Admin menerima alert (log/email) jika ada failed job di queue.
- Queue worker monitoring: UptimeRobot atau supervisor heartbeat.

---

---

## ADR-015 — Staging = Branch Berbeda di Server yang Sama

**Status:** ACCEPTED
**Tanggal:** Sprint 0
**Decider:** Principal (owner sistem)

### Konteks

Development workflow memerlukan environment staging untuk testing sebelum deploy ke production.
Perlu ditentukan apakah staging berjalan di server terpisah atau di server yang sama dengan
production.

### Opsi yang Dipertimbangkan

| Opsi | Deskripsi |
|---|---|
| **A: Branch `staging` di folder berbeda, VPS yang sama** ← dipilih | Pragmatis, zero additional cost |
| B: VPS terpisah untuk staging | Ideal tapi biaya double untuk solo dev |
| C: Docker di local saja, tidak ada staging server | Tidak ada pre-production testing |
| D: Cloud staging (Railway, Render, dll) | Ada biaya, data keluar dari infrastruktur |

### Keputusan

**Staging environment di VPS yang sama** dengan production:
- Folder: `/var/www/hris-staging/` (terpisah dari `/var/www/hris/`)
- Branch: `staging`
- Port: berbeda dari production (contoh: port 8443)
- Database: `hris_staging` (bukan `hris_production`)
- MinIO bucket: `hris-staging` (terpisah)
- URL: `staging.hris.saneng.co.id` (internal, IP-locked)

### Alasan

- **Zero additional cost**: Solo developer dengan budget terbatas. VPS sudah dibayar untuk
  production — tidak perlu bayar double untuk staging.
- **Cukup untuk kebutuhan**: Testing pre-production sebelum merge ke main. Tidak perlu load
  testing atau performance testing pada environment terpisah.
- **Data terpisah**: Database dan bucket MinIO terpisah — tidak ada risiko staging corrupt
  production data.
- **Bisa upgrade nanti**: Jika tim berkembang dan butuh dedicated staging server, tinggal
  pindahkan folder ke VPS baru — tidak ada perubahan kode.
- Kondisi server yang sama bisa menjadi keuntungan: testing di environment yang identik
  dengan production (OS, PHP version, library version).

### Konsekuensi yang Diterima

- Jika VPS down, staging dan production down bersamaan.
- Resource VPS dibagi antara production dan staging — perlu monitor resource usage.
- Staging bukanlah environment yang truly isolated — bisa saja ada resource contention.
- Deployment staging dan production di server yang sama memerlukan kehati-hatian ekstra
  agar tidak salah path.

### Aturan Turunan

- Staging **wajib** menggunakan database terpisah: `hris_staging`.
- Staging **wajib** menggunakan MinIO bucket terpisah: `hris-staging`.
- Semua `.env` staging tersimpan di `/var/www/hris-staging/.env` — tidak di-commit.
- Deploy ke staging: `git pull origin staging && php artisan migrate && npm run build`.
- **Tidak pernah** `artisan migrate --force` di production tanpa test di staging dulu.
- Checklist sebelum deploy ke production: staging sudah di-test dan semua acceptance criteria terpenuhi.

---

---

## Catatan Akhir

### Cara Menambah ADR Baru

Jika ada keputusan arsitektur signifikan baru yang perlu didokumentasikan:

1. Tambahkan entry ke tabel Daftar ADR di atas.
2. Buat section baru dengan format yang konsisten (Konteks, Opsi, Keputusan, Alasan,
   Konsekuensi, Aturan Turunan).
3. Nomor ADR: increment dari yang terakhir (ADR-016, ADR-017, dst).
4. Commit dengan message: `docs: add ADR-XXX [judul keputusan]`.
5. Update `CHANGELOG.md`.

### Kapan Sebuah Keputusan Perlu ADR

Keputusan perlu ADR jika memenuhi minimal satu dari:
- Sulit untuk dibalik (backward-incompatible, butuh data migration besar)
- Mempengaruhi lebih dari satu modul
- Tidak intuitif tanpa penjelasan konteks
- Pernah didiskusikan dan ada opsi yang dipertimbangkan

Keputusan sederhana (pilihan library utilitas, naming convention kecil) tidak perlu ADR.

---

*Dokumen ini adalah memori arsitektur sistem HRIS PT Saneng.*
*Bacalah sebelum mengusulkan perubahan yang bertentangan dengan keputusan di sini.*
*Setiap supersede terhadap ADR yang ada harus melalui ADR baru dengan justifikasi yang kuat.*
