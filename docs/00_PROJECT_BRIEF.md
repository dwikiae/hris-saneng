# 00_PROJECT_BRIEF.md
## Dictive-HR — Platform HRIS Self-Hosted
### Version: 1.0 | Status: FINAL | Phase: Foundation

---

## 1. Objective

> **Dictive-HR adalah platform HRIS self-hosted yang memungkinkan perusahaan men-deploy sistem HR mereka sendiri di server mereka, mengaktifkan modul yang dibutuhkan, dan mengelola data HR secara mandiri, terpusat, dan aman — sesuai regulasi ketenagakerjaan Indonesia.**

---

## 2. Problem Statement

**Gejala:**
- Sistem HRIS yang ada di pasar adalah SaaS (data di server vendor) atau terlalu generik
- Perusahaan Indonesia yang butuh kontrol penuh atas data karyawan tidak punya pilihan self-hosted yang baik
- UU PDP No. 27/2022 mendorong perusahaan untuk lebih bertanggung jawab atas pengelolaan data pribadi

**Masalah Akar:**
- Tidak ada platform HRIS modular self-hosted yang didesain khusus untuk konteks regulasi Indonesia
- SaaS HRIS berarti data karyawan (NIK, gaji, rekening) ada di server pihak ketiga

**Dampak:**
- Risiko compliance UU PDP jika data bocor dari server vendor
- Ketergantungan pada vendor untuk fitur dan harga
- Tidak ada fleksibilitas untuk customize sesuai kebutuhan spesifik perusahaan

---

## 3. Target Pengguna Platform

Dictive-HR ditargetkan untuk tiga tipe installer:

| Tipe | Deskripsi |
|---|---|
| Perusahaan dengan IT internal | Punya server dan tim IT sendiri, install dan manage mandiri |
| HR Profesional / Konsultan | Install dan manage untuk klien mereka |
| Developer | Install, customize, dan kembangkan di atas platform |

**End user di dalam platform:**

| Role | Deskripsi |
|---|---|
| Instance Admin | Administrator platform — manage company, modules, users lintas company |
| HR Manager | Manage data HR, approve proses, akses penuh modul aktif |
| HR Staff | Input data, akses terbatas sesuai permission |
| Manager/Atasan | Approval only, lihat data relevan departemennya |
| Kandidat (publik) | Apply lamaran via website karir, kerjakan tes, upload dokumen pemberkasan |

---

## 4. Model Bisnis

- **Core subscription**: Akses platform + semua modul standar
- **Custom development**: Penambahan atau modifikasi modul, berbayar terpisah, bukan bagian arsitektur platform
- **Open source**: Roadmap ke depan setelah platform stabil

---

## 5. Scope v1 — Core Platform

### Foundation Layer (Core)
- Company Management — create, manage, delete company dalam instance
- User & Authentication — login, session, password policy, lockout
- Permission System — RBAC dynamic, field-level permission
- Module Registry — install/uninstall modul di level instance, enable/disable per company
- Settings Engine — platform settings + company settings
- i18n Engine — ID/EN, extensible untuk bahasa lain
- Audit Log Engine — semua aksi tercatat, tidak bisa dihapus
- Notification Engine — in-app + email via queue
- File Storage Engine — abstraction layer (MinIO)
- Archive Engine — soft-delete policy berlaku untuk semua data semua modul

### Mandatory Modules (sepaket dengan core, tidak bisa uninstall)
- **Modul Karyawan** — root dependency semua modul HR. Data karyawan, struktur organisasi (department, jabatan, level), tipe kontrak, dokumen karyawan
- **Modul Kalender** — root dependency absensi, cuti, payroll. Kalender kerja, hari libur nasional, shift

### Optional Modules v1 (bisa install & uninstall)
- **Recruitment** — job posting, pipeline kandidat, tes tulis, pemberkasan, integrasi website karir
- **Aset** — pencatatan dan assignment aset ke karyawan
- **Website** — company website publik, halaman karir, candidate portal

### Defer (bukan scope v1)
- Payroll & PPh 21
- Absensi & fingerprint integration
- Cuti online
- Self-service portal karyawan
- Mobile app
- Multi-country / multi-language regulasi
- WhatsApp / SMS notification
- Module marketplace (third-party modules)

---

## 6. Non-Goals v1

- Bukan SaaS — tidak ada hosted version yang dikelola vendor
- Bukan multi-country — regulasi Indonesia-specific
- Bukan platform terbuka untuk third-party module developer (belum)
- Tidak ada automatic data deletion — hanya notifikasi, admin yang approve
- Tidak ada offline mode

---

## 7. Key Business Flows

### Flow 1 — Wizard Install (First Time Setup)
```
Step 1: Database connection setup
Step 2: Instance Admin account creation
Step 3: Company pertama — nama, logo, timezone
Step 4: Mandatory modules auto-install (Karyawan + Kalender)
Step 5: Redirect ke dashboard
```

### Flow 2 — Tambah Company Baru
```
Step 1: Instance Admin buat company (nama, logo, timezone)
Step 2: Pilih modul yang di-enable untuk company ini
Step 3: Buat user pertama untuk company ini
Step 4: Default roles otomatis tersedia (Manager, Staff)
Step 5: Done
```

### Flow 3 — Install Optional Module
```
Step 1: Instance Admin pilih modul dari registry
Step 2: Sistem cek dependencies
Step 3: Jalankan migration modul
Step 4: Modul tersedia untuk di-enable per company
```

### Flow 4 — Enable Module per Company
```
Step 1: User dengan akses settings company pilih modul yang tersedia
Step 2: Enable → modul muncul di navigasi company
```

### Flow 5 — Uninstall Optional Module
```
Step 1: Instance Admin pilih modul untuk di-uninstall
Step 2: Sistem generate export data per company yang punya data modul ini
Step 3: Instance Admin konfirmasi + warning UU PDP
Step 4: Data dihapus, migration di-rollback
Step 5: Modul tidak tersedia di semua company
```

### Flow 6 — Kandidat Apply via Website Karir
```
Step 1: Kandidat buka halaman karir company (public website)
Step 2: Isi form lamaran di halaman karir
Step 3: Data langsung masuk modul Recruitment di dashboard HR
Step 4: HR proses via pipeline recruitment
Step 5: Link tes tulis / pemberkasan dikirim via email ke kandidat
Step 6: Kandidat kerjakan tes / upload dokumen via candidate portal
        (same theme dengan company website, akses via token)
```

---

## 8. Compliance & Regulasi

| Regulasi | Implementasi |
|---|---|
| UU PDP No. 27/2022 | Audit log wajib, enkripsi field sensitif, archive policy, notifikasi retensi, warning saat uninstall modul |
| UU Ketenagakerjaan No. 13/2003 | Data model accommodate PKWT/PKWTT di modul Karyawan |
| BPJS | Field data di profil karyawan, kalkulasi defer ke modul Payroll |
| PPh 21 | Defer ke modul Payroll |
| Wajib Lapor Ketenagakerjaan | Export data karyawan wajib tersedia |

---

## 9. Success Metrics v1

| Metrik | Target |
|---|---|
| Core platform + mandatory modules berjalan stabil | Phase Core selesai |
| Minimal satu optional module berfungsi penuh (Recruitment) | Phase Module 1 selesai |
| Company website + candidate portal terintegrasi | Modul Website selesai |
| Semua aksi sensitif terekam di audit log | Ongoing sejak core |
| Export data karyawan tersedia | Modul Karyawan selesai |

---

## 10. Technical Identity

| Parameter | Keputusan |
|---|---|
| Platform | Dictive-HR |
| Model | Self-hosted, single instance multi-company |
| Target pasar | Indonesia |
| Backend | Laravel 11 (PHP) |
| Frontend | Next.js 14 (App Router) |
| Database | PostgreSQL |
| Cache & Queue | Redis |
| File Storage | MinIO (self-hosted) |
| WebSocket | Soketi |
| Auth | Laravel Sanctum |
| Deployment | Docker Compose |
| Bahasa | ID + EN (extensible) |
| Maintainer | Solo developer (vendor) |

---

*Document owner: Dictive-HR Vendor*
*Perubahan scope harus didiskusikan dan dicatat di CHANGELOG.md*
*Detail teknis ada di 02_ARCHITECTURE.md dan ALL_ADR.md*
