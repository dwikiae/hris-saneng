# 00_PROJECT_BRIEF.md
## HRIS — PT Saneng
### Human Resource Information System

**Status:** Foundation Document v1.0 — Locked  
**Last Updated:** 2025  
**Owner:** PT Saneng — IT / Solo Developer  
**Audience:** AI coder, developer, stakeholder

---

## 1. OBJECTIVE

> **Sistem ini dibuat untuk membantu tim HR PT Saneng mengelola seluruh siklus data karyawan — dari rekrutmen, onboarding, kontrak, aset, hingga kepatuhan hukum — secara terpusat, teraudit, dan aman, sehingga operasional HR menjadi efisien, compliance-ready, dan dapat dipertanggungjawabkan.**

---

## 2. PROBLEM STATEMENT

### Gejala
- Data karyawan tersebar di spreadsheet, email, dan folder fisik yang tidak terpusat
- Tidak ada audit trail yang reliable — siapa mengubah apa dan kapan tidak terekam
- Proses rekrutmen dilakukan manual tanpa sistem terpadu
- Tidak ada enforcement compliance UU PDP No. 27/2022 dan UU KUP

### Masalah Akar
- Tidak ada sistem digital HR yang didesain khusus untuk kebutuhan PT Saneng (~700 karyawan)
- Sistem yang ada saat ini (spreadsheet) tidak memiliki access control, enkripsi, atau audit log

### Dampak
- Risiko kebocoran data pribadi karyawan (NIK, gaji, rekening)
- Risiko sanksi hukum karena pelanggaran UU PDP
- Inefisiensi operasional HR (manual, error-prone, duplikasi)
- Tidak ada visibilitas manajemen terhadap data karyawan secara real-time

### Urgensi
- UU PDP No. 27/2022 sudah berlaku dan mensyaratkan sistem pengelolaan data pribadi yang terdokumentasi
- Jumlah karyawan (~700) sudah melampaui kapasitas efektif spreadsheet
- Proses rekrutmen yang tidak terstruktur menyebabkan kehilangan kandidat potensial

---

## 3. ACTORS & USER FLOW

### Actors

| Role | Deskripsi |
|---|---|
| System Admin | Full access semua modul + settings sistem |
| HR Manager | Full access modul HR — kelola data, approve proses |
| HR Staff | Limited access — input data, tidak bisa delete, tidak lihat gaji |
| Dept Manager | Approval only — lihat karyawan di departemennya saja |
| Kandidat (publik) | Submit lamaran via website tanpa login |

### User Flow Utama

**Flow 1 — Rekrutmen:**
```
HR publish lowongan di HRIS
  → Otomatis muncul di saneng.co.id/karir
  → Kandidat isi form lamaran di website (tanpa login)
  → Data kandidat masuk ke HRIS sebagai applicant record
  → HR review, proses seleksi, update status
  → Kandidat lolos → proses onboarding → buat data karyawan
```

**Flow 2 — Manajemen Karyawan:**
```
HR input data karyawan baru (dengan consent checkbox mandatory)
  → HR submit untuk approval
  → Notifikasi ke Dept Manager (in-app + email)
  → Manager approve/reject
  → Status berubah, notifikasi ke HR
  → Data aktif, HR buatkan akun user jika diperlukan
```

**Flow 3 — Akses Sistem:**
```
User akses hris.saneng.co.id
  → Jika bukan dari IP kantor/VPN → 403 Forbidden
  → Login dengan email @saneng.co.id
  → Session timeout 60 menit tidak aktif
  → Fitur yang tersedia sesuai role & permission
```

**Flow 4 — File & Dokumen:**
```
HR upload dokumen karyawan (KTP scan, kontrak, BPJS, dll)
  → File tersimpan di MinIO (self-hosted, tidak keluar VPS)
  → Preview tersedia di browser (PDF/gambar)
  → Akses file via presigned URL — tidak exposed langsung
```

---

## 4. SCOPE v1 (YANG DIBANGUN)

### Sprint 0 — Project Skeleton & Infrastructure
- Monorepo setup: `/backend` + `/frontend-hris` + `/frontend-web`
- Docker Compose untuk local dev
- PostgreSQL, Redis, MinIO, Soketi, Nginx, PHP-FPM
- SSL Let's Encrypt, WireGuard VPN
- GitHub Actions CI, Git flow & branch protection
- AGENTS.md, CLAUDE.md, docs structure
- Health check endpoint, UptimeRobot
- Backup script otomatis (cron, daily, 30-hari rolling)

### Sprint 1 — i18n System
- Backend: `lang/id/` + `lang/en/`
- Frontend: `next-i18next` namespace per modul
- Language switcher, preferensi disimpan per user
- Zero hardcoded string di seluruh UI

### Sprint 2 — Master Data Engine
- Department / Division / Unit CRUD + UI
- Job Position, Job Level, Employee Type, Contract Type, Work Location
- Reference data (Bank List, Education Level, Relationship Type, Document Type)
- Seeder master data awal

### Sprint 3 — Auth + User Management
- Login, logout, force reset password pertama
- Forgot password via email, Session timeout middleware
- IP whitelist middleware (hanya jaringan kantor + VPN)
- Login lockout (5 gagal → 15 menit), rate limiting
- HR/Admin create user dan link ke employee

### Sprint 4 — Dynamic RBAC
- Roles & permissions sebagai master data (bisa edit dari UI)
- Field-level permission untuk field sensitif
- Default roles (System Admin, HR Manager, HR Staff, Dept Manager)
- Frontend PermissionGate component

### Sprint 5 — Audit Log + Archive + Settings + Compliance
- spatie/laravel-activitylog
- Archive system (soft delete via `archived_at`)
- Sensitive field masking di log
- Log export tracking, Incident log tabel
- Retention notification system
- Settings UI (SMTP, password policy, session, retention, dll)
- Consent checkbox infrastructure (UU PDP Pasal 22)

### Sprint 6 — Employee Data
- Data karyawan lengkap (identitas, pendidikan, keluarga, kontak darurat, rekening bank)
- Upload & preview dokumen karyawan
- Foto profil karyawan (resize otomatis)
- Approver assignment per karyawan

### Sprint 7 — Recruitment Module + Website Integration
- Manajemen lowongan (job posting)
- Halaman karir dinamis di `saneng.co.id`
- Form lamaran publik dengan rate limiting
- Alur seleksi kandidat

### Sprint 8 — Asset Management
- Pencatatan dan assignment aset ke karyawan

---

## 5. NON-GOALS (TIDAK DIBANGUN di v1)

- **Payroll / Penggajian otomatis** — bukan scope v1
- **Absensi / Fingerprint integration** — infrastructure adapter sudah disiapkan, modul menyusul
- **Leave management (cuti online)** — modul tersendiri, menyusul setelah v1
- **Self-service portal karyawan** — karyawan tidak login sendiri di v1
- **Mobile app** — pure web responsive
- **Multi-company / multi-tenant** — single-tenant (PT Saneng), tapi `company_id` sudah di semua tabel untuk future-proof
- **Per-device session revocation** — logout semua device sekaligus sudah cukup
- **Automatic data deletion** — hanya notifikasi; admin yang approve
- **Data Subject Request modul** — ditangani manual oleh HR di luar sistem
- **E2E / Cypress test** — menyusul setelah core stabil
- **CMS untuk website static** — Home, About, Services, Contact hardcoded

---

## 6. SUCCESS METRICS

| Metrik | Target |
|---|---|
| Semua data karyawan (~700) tersimpan digital | Sprint 6 selesai |
| Zero data karyawan bocor (audit log membuktikan) | Ongoing |
| Rekrutmen end-to-end via sistem | Sprint 7 selesai |
| Compliance UU PDP terdokumentasi | Sprint 5 selesai |
| Health check 99%+ uptime | Sejak Sprint 0 |
| Seluruh action sensitif terekam di audit log | Sprint 5 selesai |

---

## 7. TECHNICAL IDENTITY

| Parameter | Keputusan |
|---|---|
| Domain HRIS | `hris.saneng.co.id` |
| Domain Website | `saneng.co.id` |
| Backend | Laravel 11 (PHP) |
| Database | PostgreSQL |
| Cache & Queue | Redis |
| Auth | Laravel Sanctum (SPA token) |
| WebSocket | Soketi (self-hosted) |
| File Storage | MinIO (self-hosted, S3-compatible) |
| Frontend HRIS | Next.js 14 + TypeScript + Tailwind + shadcn/ui |
| Frontend Website | Next.js 14 (static + dynamic `/karir`) |
| i18n | next-i18next, bilingual ID/EN |
| Server | VPS Linux single-server |
| Deployment | Nginx + PHP-FPM + Let's Encrypt |
| VPN | WireGuard (self-hosted) |
| Error Monitoring | Flare by Spatie |
| Activity Log | spatie/laravel-activitylog |
| Permission | spatie/laravel-permission + custom field-level |
| CI | GitHub Actions |
| Local Dev | Docker Compose |
| Timezone | WIB (Asia/Jakarta) |
| Target Karyawan | ~700 |
| Maintainer | Solo developer (owner sistem) |

---

## 8. RISIKO & MITIGASI AWAL

| Risiko | Mitigasi |
|---|---|
| Data karyawan bocor | Enkripsi at-rest (NIK, gaji, rekening), IP whitelist, audit log, MinIO self-hosted |
| Pelanggaran UU PDP | Retention enforcement, consent checkbox, incident log, field masking di audit log |
| Sistem down | UptimeRobot, health check, daily backup, staging environment |
| Solo developer bottleneck | AGENTS.md + docs lengkap → AI-assisted development disiplin, semua keputusan terdokumentasi |
| Scope creep | Non-goals eksplisit, SPARC loop per sprint, task packet per task |
| Silent bug merusak data | Approval workflow, archive-only (tidak ada hard delete), audit trail wajib |
| Duplicate order / race condition | Idempotency key, database transaction, optimistic locking untuk approval |

---

*Document ini adalah ringkasan eksekutif. Detail teknis ada di:*
- *`01_PRD.md` — requirement lengkap per modul*
- *`02_ARCHITECTURE.md` — system design dan data flow*
- *`03_TECH_SPEC.md` — API contract, DB schema, error handling*
- *`04_TASK_BREAKDOWN.md` — sprint plan dan task detail*
