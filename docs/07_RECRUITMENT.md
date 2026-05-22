# 07_RECRUITMENT.md
## HRIS PT Saneng — Modul Recruitment
### Version: 1.0 | Status: FINAL | Sprint: 7

> Dokumen ini adalah source of truth untuk modul Recruitment.
> Semua keputusan di sini sudah final dan hasil diskusi pre-development.
> Perubahan harus melalui diskusi dengan Principal dan dicatat di CHANGELOG.md.

---

## 1. Ruang Lingkup Modul

Modul Recruitment mencakup seluruh siklus rekrutmen PT Saneng, mulai dari pembuatan
lowongan, penerimaan lamaran via website publik, proses seleksi bertahap, hingga kandidat
diterima dan data karyawan otomatis terbentuk.

Semua sub-fitur berada dalam satu modul Recruitment — tidak ada modul terpisah:

```
Recruitment/
├── Job Postings       → kelola lowongan kerja
├── Applicants         → pipeline kandidat
├── Master Data Tes    → kelola tes tulis (soal, timer, passing grade)
├── Blacklist          → daftar kandidat yang diblokir
├── Quiz Portal        → halaman publik kandidat kerjakan tes (akses via token)
└── Portal Pemberkasan → halaman publik kandidat upload dokumen (akses via token)
```

---

## 2. Job Posting

### 2.1 Status & Transisi

```
draft ↔ published
  ↓
archive (harus dari draft dulu)
  ↓
hard delete (dari halaman archive, manual oleh HRD)
```

- `draft` → `published`: job posting tayang di website publik
- `published` → `draft`: job posting tidak menerima apply baru, kandidat existing tidak terpengaruh
- `draft` → `archive`: job posting diarsipkan, tidak muncul di list aktif
- Archive → hard delete: HRD hapus permanen dari halaman archive

### 2.2 Aturan Kunci Job Posting

- Satu job posting per posisi jabatan
- **Tidak ada field kuota** — kebutuhan jumlah orang dikelola manual oleh HRD
- Saat `published`: semua field **terkunci** (tidak bisa diedit)
- Untuk edit: harus tarik ke `draft` dulu, edit, lalu publish ulang
- **Expired date opsional**:
  - Jika diisi → posting otomatis pindah ke `draft` saat expired
  - Jika kosong → HRD tarik ke `draft` manual
- Kandidat yang sudah apply tidak terpengaruh saat posting ditarik ke `draft` atau diarsip

### 2.3 Field Job Posting

```
nama_posisi         → nama jabatan yang dibuka
departemen          → departemen terkait
deskripsi_pekerjaan → job description
persyaratan_umum    → requirement umum (teks bebas)
expired_at          → nullable, tanggal posting otomatis draft
test_id             → FK ke master data tes (dropdown, wajib dipilih saat draft)
status              → draft | published | archived
```

### 2.4 Kriteria Screening (per Job Posting)

Diset saat drafting, terkunci saat published. Field kriteria:

| Field | Tipe | Keterangan |
|---|---|---|
| `min_age` | integer nullable | Usia minimum (tahun) |
| `max_age` | integer nullable | Usia maksimum (tahun) |
| `min_height_male` | integer nullable | Tinggi minimum pria (cm) |
| `min_height_female` | integer nullable | Tinggi minimum wanita (cm) |
| `allow_glasses` | boolean | Boleh berkacamata |
| `allow_color_blind` | boolean | Boleh buta warna |
| `require_welding` | boolean | Wajib bisa las listrik/elektroda |

Jika field nullable dibiarkan null → kriteria tersebut tidak dicek (tidak ada batasan).

---

## 3. Pipeline Kandidat (State Machine)

### 3.1 Alur Stage

```
apply
  ↓ (auto screening)
screening
  ↓ (manual HRD)
tes_tulis
  ↓ (auto-grade)
interview
  ↓ (manual HRD — khusus Staff & Welding/Maintenance)
tes_kemampuan
  ↓ (manual HRD)
mcu
  ↓ (manual HRD)
pemberkasan
  ↓ (manual HRD — SATU-SATUNYA jalan ke hired)
hired ✓

rejected ✗ (bisa dari stage manapun, reversible)
```

### 3.2 Aturan Override Stage

- HRD bisa **memindahkan kandidat ke stage manapun dari stage manapun** tanpa alasan
- **Kecuali `hired`** — hanya bisa dicapai dari stage `pemberkasan`
- Semua perpindahan stage tercatat otomatis di audit log (siapa, kapan, dari stage apa ke stage apa)
- `rejected` bukan state final — HRD bisa pindahkan kandidat rejected ke stage manapun

### 3.3 Stage Tes Kemampuan

Hanya berlaku untuk posisi **Staff** dan **Welding/Maintenance**.
Untuk posisi lain (Operator Produksi, QC, Driver, dll), stage ini dilewati otomatis.

---

## 4. Proses Per Stage

### Stage 1 — Apply

**PIC:** Kandidat (via website publik)

**Mekanisme:**
- Kandidat mengisi form lamaran di website `saneng.co.id/karir`
- Sistem auto-cek kriteria screening job posting
- Jika tidak memenuhi kriteria → status otomatis `rejected` (tanpa notifikasi ke kandidat)
- Jika memenuhi kriteria → status `screening`
- **Semua kandidat** (termasuk yang akan auto-rejected) mendapat email konfirmasi lamaran diterima

**Cek duplikasi (setting global — bisa diaktifkan/nonaktifkan HRD):**
- Jika duplikasi dinonaktifkan: kandidat dengan email/phone yang sama tidak bisa apply lagi selama data masih ada di sistem
- Jika duplikasi diaktifkan: kandidat bisa apply, namun sistem kirim email penolakan otomatis berisi informasi bahwa mereka sudah pernah mendaftar dan diminta untuk mencoba kembali setelah periode retensi data berlalu (sesuai setting global retensi)

**Field form apply:**
```
Nama Lengkap, Tempat Lahir, Tanggal Lahir, Jenis Kelamin
Alamat KTP, Alamat Domisili
Tinggi Badan (cm), Berat Badan (kg)
Menggunakan Kacamata/Lensa Kontak (ya/tidak)
Memiliki Buta Warna (ya/tidak)
Status Pernikahan, Kewarganegaraan
Email, Nomor WhatsApp
Posisi Pekerjaan yang Dilamar (terhubung ke job posting aktif)
Memiliki Kemampuan Pengelasan Listrik/Elektroda (ya/tidak)
Upload CV (PDF/JPG/PNG, max 10MB)
Pengalaman Kerja[] → Nama Perusahaan, Posisi, Masa Kerja
Pendidikan[] → Tingkat Pendidikan, Nama Sekolah/Universitas, Jurusan, Tahun Lulus, Nilai Akhir
Upload Sertifikat (PDF/JPG/PNG, max 10MB, opsional)
Source → dari mana kandidat tahu lowongan ini (dropdown: Website Perusahaan, Jobstreet, LinkedIn, Referral, Lainnya)
```

### Stage 2 — Screening

**PIC:** HRD

**Mekanisme:**
- HRD review form kandidat secara manual
- HRD memiliki tombol **"Kirim Tes Tulis"** yang:
  1. Memindahkan kandidat ke stage `tes_tulis`
  2. Otomatis mengirim email ke kandidat berisi link quiz unik

### Stage 3 — Tes Tulis

**PIC:** Kandidat (via Quiz Portal publik)

**Mekanisme:**
- Kandidat mengerjakan tes via link unik yang dikirim email
- Link expire sesuai setting global `recruitment_link_expires_hours` di Company Settings
- Jika link expired → tombol "Kirim Tes Tulis" aktif kembali di dashboard HRD → HRD kirim ulang → link baru
- Timer sesuai master data tes yang di-assign di job posting
- Soal ditampilkan secara acak (dari list soal yang terdaftar di tes tersebut)
- Setelah dikerjakan → auto-grade otomatis
  - Nilai > passing grade → otomatis advance ke stage `interview`
  - Nilai ≤ passing grade → otomatis `rejected`
- HRD hanya melihat hasil, tidak perlu input apapun

### Stage 4 — Interview

**PIC:** HRD & User (atasan posisi terkait)

**Mekanisme:**
- HRD menjadwalkan interview di sistem (set tanggal, waktu, lokasi/platform)
- Sistem kirim email ke kandidat berisi:
  - Detail jadwal interview
  - Link konfirmasi kehadiran (hadir / tidak hadir)
  - **Nomor WhatsApp HRD** untuk reschedule (reschedule dilakukan di luar sistem)
- Link konfirmasi expire sesuai setting global `recruitment_link_expires_hours`
- Kandidat klik **Hadir** → status konfirmasi tercatat, pipeline tetap di stage `interview`
- Kandidat klik **Tidak Hadir** → otomatis `rejected`
- Jika HRD perlu reschedule: ubah tanggal interview di sistem → tombol kirim jadwal aktif kembali → kirim ulang
- Setelah interview selesai: HRD input hasil manual (lolos/tidak lolos + catatan opsional) → advance stage

### Stage 5 — Tes Kemampuan *(hanya Staff & Welding/Maintenance)*

**PIC:** User (atasan/kepala divisi terkait)

**Mekanisme:**
- Dilakukan secara offline di area PT Saneng
- HRD input hasil manual: lolos/tidak lolos + catatan opsional → advance stage

### Stage 6 — MCU (Medical Check Up)

**PIC:** Pihak ke-3 (klinik rekanan PT Saneng)

**Mekanisme:**
- Dilakukan secara offline di klinik rekanan
- HRD input hasil manual: lolos/tidak lolos → advance stage

### Stage 7 — Pemberkasan

**PIC:** Kandidat (via Portal Pemberkasan publik)

**Mekanisme:**
- HRD advance kandidat ke stage `pemberkasan`
- Sistem kirim email ke kandidat berisi:
  - Ucapan selamat (kandidat diterima, bersiap onboarding)
  - Link portal upload dokumen pemberkasan
- Link expire sesuai setting global `recruitment_link_expires_hours`
- Jika link expired → tombol kirim ulang aktif di dashboard HRD
- Kandidat upload dokumen berikut via portal:
  ```
  Fotokopi KTP
  Fotokopi KK
  Fotokopi NPWP
  Fotokopi Rekening Mandiri
  Fotokopi BPJS Kesehatan (opsional)
  Fotokopi BPJS Ketenagakerjaan (opsional)
  ```
- HRD verifikasi kelengkapan dokumen → advance ke `hired`

### Stage 8 — Hired

**PIC:** HRD

**Mekanisme:**
- HRD advance dari `pemberkasan` → `hired` (satu-satunya jalan ke hired)
- Sistem **otomatis membuat draft data karyawan** di modul Employee dengan mapping:
  ```
  Nama Lengkap     → employees.full_name
  Email            → employees.email
  Nomor WhatsApp   → employees.phone
  Posisi Dilamar   → employees.job_position_id
  Pendidikan       → employee_educations (relasi)
  Pengalaman Kerja → employee_experiences (relasi)
  ```
- HRD melengkapi data karyawan yang tersisa di modul Employee

---

## 5. Master Data Tes

### 5.1 Struktur

```
tests (master data)
├── nama_tes        → nama bebas (misal: "Tes Operator", "Tes Staff Accounting")
├── timer           → durasi pengerjaan dalam menit (bisa diubah di UI)
├── passing_grade   → nilai minimum kelulusan, skala 0–10 (bisa diubah di UI)
└── questions[]
    ├── tipe              → pilihan_ganda | isian_singkat
    ├── pertanyaan        → teks soal
    ├── pilihan[]         → array pilihan (hanya jika pilihan_ganda)
    ├── jawaban_benar     → string (exact match untuk isian_singkat)
    └── bobot             → bobot nilai soal ini
```

### 5.2 Aturan Master Data Tes

- Soal dibuat langsung di dalam tes (tidak ada bank soal terpisah)
- Soal ditampilkan ke kandidat secara **acak** dari list soal yang terdaftar di tes
- Satu job posting hanya bisa assign **satu tes** (dipilih via dropdown saat draft)
- Edit tes hanya bisa dilakukan jika **semua job posting yang menggunakan tes tersebut** dalam stage `draft`
- Tes yang sedang dipakai job posting `published` → **terkunci**, tidak bisa diedit

### 5.3 Auto-grade

- Pilihan ganda: jawaban benar = nilai bobot soal, salah = 0
- Isian singkat: exact match (case-insensitive) = nilai bobot soal, tidak match = 0
- Total nilai = (sum bobot soal benar / sum total bobot) × 10
- Nilai akhir dibandingkan dengan `passing_grade` tes tersebut

---

## 6. Link System

### 6.1 Jenis Link

| Link | Trigger | Konten |
|---|---|---|
| Quiz Portal | HRD klik "Kirim Tes Tulis" | Link tes unik per kandidat |
| Konfirmasi Interview | HRD set jadwal interview | Link hadir/tidak hadir + detail jadwal |
| Portal Pemberkasan | HRD advance ke pemberkasan | Link upload dokumen |

### 6.2 Aturan Expire

- Expire di-set di **Company Settings** → field `recruitment_link_expires_hours`
- **Tidak ada nilai default** — HRD wajib mengisi nilai ini sebelum fitur link bisa digunakan
- Satu nilai berlaku untuk **semua jenis link** recruitment
- Jika link expired:
  - Quiz: tombol "Kirim Tes Tulis" aktif kembali di dashboard HRD
  - Interview: tombol "Kirim Jadwal Interview" aktif kembali (tanpa perlu ubah tanggal)
  - Pemberkasan: tombol "Kirim Link Pemberkasan" aktif kembali
- Kirim ulang = generate token baru, expire baru, link lama otomatis tidak valid

---

## 7. Notifikasi Email ke Kandidat

| # | Trigger | Penerima | Isi Email |
|---|---|---|---|
| 1 | Kandidat submit apply | Semua kandidat (termasuk yang akan auto-rejected) | Konfirmasi lamaran diterima |
| 2 | Kandidat duplikat apply (jika setting duplikasi aktif) | Kandidat duplikat | Penolakan + informasi bisa apply kembali setelah periode retensi |
| 3 | HRD klik "Kirim Tes Tulis" | Kandidat di stage screening | Link quiz unik + instruksi pengerjaan |
| 4 | HRD set jadwal interview | Kandidat di stage tes_tulis (lolos) | Detail jadwal + link konfirmasi kehadiran + nomor WA HRD |
| 5 | HRD advance ke pemberkasan | Kandidat hired | Ucapan selamat + link portal upload dokumen |

**Catatan:**
- Email template hardcode (tidak bisa dikustomisasi via UI di Sprint 7)
- Semua email dikirim via Queue (tidak blocking request cycle) — sesuai RULE-J1 AGENTS.md
- Nomor WA HRD diambil dari Company Settings → field `hr_whatsapp_number`

---

## 8. Kandidat Management

### 8.1 Auto-delete Data Kandidat

- Setting global di Company Settings → field `applicant_data_retention_days`
- **HRD wajib mengisi** nilai ini (tidak ada default)
- Dihitung sejak **tanggal apply** (`created_at` tabel applicants)
- Kandidat dengan status `hired` **dikecualikan** dari auto-delete (datanya sudah jadi data karyawan)
- Sistem tidak langsung menghapus — sistem mengirim notifikasi ke System Admin → Admin review dan approve penghapusan (sesuai COMP-2 AGENTS.md)
- Penghapusan yang diapprove tetap dicatat di audit trail

### 8.2 Blacklist Kandidat

- HRD bisa blacklist kandidat dari profil kandidat manapun (tombol "Blacklist")
- Data kandidat yang di-blacklist masuk ke **daftar blacklist** (menu tersendiri di modul Recruitment)
- Kandidat blacklisted yang apply lagi → form tetap bisa disubmit, namun sistem langsung auto-rejected + kirim email penolakan
- HRD bisa hapus kandidat dari daftar blacklist (unblacklist)
- Identifikasi blacklist berdasarkan: email + nomor WhatsApp

### 8.3 Duplikasi Kandidat

- Setting global di Company Settings → field `allow_duplicate_applicant` (boolean)
- **Nonaktif (default):** kandidat dengan email/phone yang sama tidak bisa apply selama data masih ada
- **Aktif:** kandidat bisa submit form, namun sistem kirim email penolakan otomatis berisi info periode retensi
- Identifikasi duplikat berdasarkan: email ATAU nomor WhatsApp

---

## 9. Catatan Internal & Attachment per Kandidat

### 9.1 Catatan Internal

- HRD bisa menulis catatan bebas (text area) per kandidat
- Catatan bersifat internal — tidak terlihat oleh kandidat
- Akses dikontrol via RBAC: permission `recruitment.notes.view` dan `recruitment.notes.create`
- Catatan tercatat dengan timestamp dan user yang menulis (audit trail)

### 9.2 Attachment per Stage

- HRD bisa upload file (PDF/JPG/PNG, max 10MB) per stage per kandidat
- Contoh use case: hasil penilaian interview tertulis, hasil tes kemampuan, dokumen MCU
- File disimpan di MinIO dengan path: `recruitment/{job_id}/applicants/{applicant_id}/stages/{stage_name}/`
- Upload bersifat opsional di semua stage

---

## 10. Source Tracking

- Field `source` di form apply kandidat (dropdown di website)
- Pilihan: Website Perusahaan, Jobstreet, LinkedIn, Referral, Lainnya
- Data ini tersimpan di tabel `applicants` untuk kebutuhan laporan rekrutmen di masa depan
- Dashboard/laporan rekrutmen akan dikembangkan setelah modul recruitment settle

---

## 11. Database Schema

```sql
-- Job Postings
job_postings
  id, company_id, job_position_id, department_id
  description, requirements (text)
  test_id (FK ke tests)
  status (draft | published | archived)
  expired_at (nullable timestamp)
  archived_at, archived_by, created_by, updated_by, created_at, updated_at

-- Kriteria Screening per Job Posting
job_posting_criteria
  id, company_id, job_posting_id
  min_age (nullable int), max_age (nullable int)
  min_height_male (nullable int), min_height_female (nullable int)
  allow_glasses (boolean), allow_color_blind (boolean)
  require_welding (boolean)
  created_at, updated_at

-- Master Data Tes
tests
  id, company_id
  nama_tes, timer (int, menit), passing_grade (decimal 3,1)
  is_active (boolean)
  archived_at, archived_by, created_by, updated_by, created_at, updated_at

-- Soal Tes
test_questions
  id, company_id, test_id
  tipe (pilihan_ganda | isian_singkat)
  pertanyaan (text)
  pilihan (JSON array, nullable — hanya untuk pilihan_ganda)
  jawaban_benar (string)
  bobot (decimal, default 1)
  urutan (int)
  created_by, updated_by, created_at, updated_at

-- Kandidat
applicants
  id, company_id, job_posting_id
  -- Data Pribadi
  full_name, birth_place, birth_date, gender
  address_ktp (text), address_domisili (text)
  height (int, cm), weight (int, kg)
  has_glasses (boolean), is_color_blind (boolean)
  marital_status, citizenship
  email, whatsapp_number
  has_welding_skill (boolean)
  source (website | jobstreet | linkedin | referral | other)
  -- File
  cv_path (MinIO path), certificate_path (nullable)
  -- Pipeline
  stage (apply | screening | tes_tulis | interview | tes_kemampuan | mcu | pemberkasan | hired | rejected)
  stage_changed_at, stage_changed_by
  -- Flags
  is_blacklisted (boolean, default false)
  blacklisted_at, blacklisted_by
  is_duplicate (boolean, default false)
  -- Timestamps
  archived_at, archived_by, created_by, updated_by, created_at, updated_at

-- Pendidikan Kandidat
applicant_educations
  id, applicant_id
  tingkat_pendidikan, nama_sekolah, jurusan
  tahun_lulus (int), nilai_akhir (decimal)
  created_at, updated_at

-- Pengalaman Kerja Kandidat
applicant_experiences
  id, applicant_id
  nama_perusahaan, posisi, masa_kerja_dari, masa_kerja_sampai
  created_at, updated_at

-- Sesi Quiz
quiz_sessions
  id, company_id, applicant_id, test_id
  token (unique string)
  expired_at (timestamp)
  started_at (nullable), finished_at (nullable)
  timer_snapshot (int, menit — snapshot saat kirim, bukan baca live)
  passing_grade_snapshot (decimal — snapshot saat kirim)
  score (nullable decimal)
  status (pending | in_progress | completed | expired)
  created_at, updated_at

-- Jawaban Quiz
quiz_answers
  id, quiz_session_id, test_question_id
  jawaban (text)
  is_correct (boolean)
  bobot (decimal — snapshot bobot soal saat dikerjakan)
  created_at

-- Jadwal Interview
interview_schedules
  id, company_id, applicant_id
  tanggal_interview (datetime)
  lokasi_atau_platform (string)
  catatan (text, nullable)
  token (unique string — untuk link konfirmasi)
  token_expired_at (timestamp)
  confirmation_status (pending | hadir | tidak_hadir)
  confirmed_at (nullable)
  created_by, updated_by, created_at, updated_at

-- Dokumen Pemberkasan
applicant_documents
  id, company_id, applicant_id
  document_type (ktp | kk | npwp | rekening | bpjs_kesehatan | bpjs_ketenagakerjaan)
  file_path (MinIO path)
  token (unique string — untuk akses portal pemberkasan)
  token_expired_at (timestamp)
  uploaded_at (nullable)
  created_at, updated_at

-- Catatan Internal per Kandidat
applicant_notes
  id, company_id, applicant_id
  catatan (text)
  created_by, created_at, updated_at

-- Attachment per Stage
applicant_stage_attachments
  id, company_id, applicant_id
  stage (enum — stage saat upload)
  file_path (MinIO path)
  file_name (string)
  created_by, created_at

-- Blacklist
applicant_blacklists
  id, company_id, applicant_id
  email, whatsapp_number
  alasan (text, nullable)
  blacklisted_by, blacklisted_at
  created_at, updated_at
```

---

## 12. Company Settings (Tambahan Sprint 7)

| Key | Tipe | Keterangan |
|---|---|---|
| `recruitment_link_expires_hours` | integer | Expire semua link recruitment (jam). Wajib diisi, tidak ada default. |
| `hr_whatsapp_number` | string | Nomor WA HRD yang tampil di email jadwal interview |
| `applicant_data_retention_days` | integer | Berapa hari data kandidat disimpan sejak apply. Wajib diisi. |
| `allow_duplicate_applicant` | boolean | Aktifkan/nonaktifkan cek duplikasi kandidat. Default: false. |

---

## 13. File Storage Path (MinIO)

```
recruitment/
└── {job_posting_id}/
    └── applicants/
        └── {applicant_id}/
            ├── cv/
            ├── certificate/
            ├── stages/
            │   └── {stage_name}/     → attachment HRD per stage
            └── pemberkasan/
                ├── ktp/
                ├── kk/
                ├── npwp/
                ├── rekening/
                ├── bpjs_kesehatan/
                └── bpjs_ketenagakerjaan/
```

---

## 14. Permissions (RBAC)

```
recruitment.job_posting.view
recruitment.job_posting.create
recruitment.job_posting.update
recruitment.job_posting.publish
recruitment.job_posting.archive

recruitment.applicant.view
recruitment.applicant.update_stage
recruitment.applicant.blacklist
recruitment.applicant.export

recruitment.notes.view
recruitment.notes.create

recruitment.test.view
recruitment.test.create
recruitment.test.update

recruitment.blacklist.view
recruitment.blacklist.manage

recruitment.quiz.view       → akses portal quiz (public, via token — tidak butuh login)
recruitment.pemberkasan.view → akses portal pemberkasan (public, via token — tidak butuh login)
```

---

## 15. Public API Endpoints

```
GET  /api/v1/public/jobs                    → daftar job posting published (untuk website)
POST /api/v1/public/applications            → submit lamaran (rate limit: 5/10mnt per IP)
GET  /api/v1/public/quiz/{token}            → ambil soal tes (validasi token + expired)
POST /api/v1/public/quiz/{token}/submit     → submit jawaban tes
GET  /api/v1/public/interview/{token}       → halaman konfirmasi kehadiran interview
POST /api/v1/public/interview/{token}/confirm → submit konfirmasi hadir/tidak hadir
GET  /api/v1/public/pemberkasan/{token}     → halaman upload dokumen pemberkasan
POST /api/v1/public/pemberkasan/{token}/upload → upload dokumen per tipe
```

---

## 16. Business Rules Ringkas

```
BR-01: Job posting published → semua field terkunci termasuk tes yang di-assign.
BR-02: Edit job posting → wajib tarik ke draft dulu.
BR-03: Edit tes → hanya bisa jika semua job posting yang pakai tes tersebut dalam stage draft.
BR-04: Auto-reject screening → tidak ada notifikasi email ke kandidat.
BR-05: Auto-reject tes tulis (nilai ≤ passing grade) → tidak ada notifikasi email ke kandidat.
BR-06: Kandidat konfirmasi tidak hadir interview → otomatis rejected.
BR-07: Hired → hanya bisa dari stage pemberkasan. Tidak ada override ke hired.
BR-08: Hired → otomatis create draft employee di modul Employee.
BR-09: Kandidat hired → dikecualikan dari auto-delete retensi data.
BR-10: Blacklisted kandidat apply → form submit berhasil, email penolakan otomatis terkirim.
BR-11: Link expired → token lama invalid, tombol kirim ulang aktif di dashboard HRD.
BR-12: Semua perpindahan stage tercatat di audit log (siapa, kapan, dari → ke).
BR-13: Portal quiz & pemberkasan = public page, akses via token (tidak butuh login HRIS).
BR-14: Soal quiz ditampilkan acak dari list soal yang terdaftar di tes tersebut.
BR-15: Score quiz = (sum bobot benar / sum total bobot) × 10, dibandingkan passing_grade snapshot.
BR-16: Quiz session menyimpan snapshot timer & passing_grade saat dikirim (bukan baca live dari master data).
```

---

*Document owner: Principal (owner sistem PT Saneng)*
*Update dokumen ini jika ada perubahan requirement recruitment sebelum atau selama development.*
*Setiap perubahan harus dicatat di CHANGELOG.md.*
