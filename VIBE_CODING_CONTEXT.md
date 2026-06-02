# VIBE_CODING_CONTEXT.md
# Framework Vibe Coding — Solo Developer
# Paste file ini ke Claude Chat dan Claude Code di awal setiap sesi.
# Versi 2.0 — Komprehensif dengan UI/UX, Migration, dan Environment

---

## SIAPA SAYA DAN BAGAIMANA SAYA BEKERJA

Solo developer dengan profil:
- Punya visi dan bisa judgment setiap keputusan dengan jelas
- Tidak bisa membuat dokumen teknis dari nol sendiri
- Mudah lupa — tidak bisa diandalkan untuk mengingat proses
- Tidak bisa review raw diff secara teknis
- Tidak bisa update dokumentasi manual
- Bisa blank tidak tahu harus prompting apa tanpa panduan

Tugas AI: mengkompensasi kelemahan ini, bukan mengasumsikan saya bisa melakukannya sendiri.

---

## PEMBAGIAN PERAN — TIDAK BOLEH TERTUKAR

### Saya
- Decision maker semua keputusan arah sistem
- Menjawab pertanyaan visi, scope, dan bisnis
- Approve atau reject output AI berdasarkan judgment
- Tidak mengeksekusi hal teknis secara manual

### Claude Chat
- Menginterogasi saya untuk mengisi dokumen teknis
- Diskusi arsitektur dan trade-off
- Dipanggil hanya untuk Fase 0, Fase 1, dan ketika ada keputusan arsitektur baru di tengah Fase 2

### Claude Code
- Eksekutor dalam batas ketat
- Wajib Plan Mode sebelum eksekusi apapun
- Menjalankan semua Git commands
- Mengupdate semua dokumentasi otomatis setiap task selesai
- Menjelaskan hasil dalam bahasa plain — bukan technical summary
- Tidak membuat keputusan arsitektur tanpa konfirmasi
- Flag setiap dependency baru sebelum ditambahkan

---

## EMPAT FASE DEVELOPMENT

### Fase 0 — Discovery (di Claude Chat)
Claude Chat menginterogasi saya satu per satu. Saya menjawab bahasa natural. Claude Chat yang menulis dokumen.

Output: PROJECT_BRIEF.md

Area yang harus dijawab:
1. Problem statement — gejala, masalah akar, urgensi
2. Aktor dan user flow nyata
3. Scope v1 maksimal 5 item + non-goals eksplisit
4. Success metric dengan angka konkret
5. Platform target — web, mobile, desktop, atau kombinasi
6. Volume dan skala awal — personal/kecil, tim kecil, atau publik
7. Content strategy — apa yang ditampilkan, hierarki informasi, tone
8. Constraint teknis dan bisnis
9. Existing data — apakah ada data lama yang perlu dimigrasikan
10. Multi-language atau multi-timezone — yes/no
11. Offline capability — yes/no (relevan jika mobile atau PWA)
12. Risk dan asumsi terbesar

Exit gate:
- Bisa jelaskan sistem dalam 2 kalimat tanpa ambigu
- Non-goals tertulis eksplisit
- Success metric punya angka konkret
- Platform target sudah jelas

### Fase 1 — Arsitektur (di Claude Chat)
Input: PROJECT_BRIEF.md
Output: AGENTS.md + ARCHITECTURE.md + UIUX_SPEC.md + TASK_BREAKDOWN.md + START_HERE.md awal

Area arsitektur yang harus diputuskan:
1. System design — komponen utama maksimal 5
2. Layered architecture — dependency direction
3. Data model dan schema
4. API contract utama
5. Error handling dan edge case design
6. Security baseline — auth, input validation, secret management
7. Tech stack — termasuk testing framework dan CI/CD
8. Environment strategy — dev/staging/production, Docker atau tidak, env variable management
9. Monitoring tool — apa yang dipakai untuk error tracking dan logging
10. Notification layer — email/push/in-app: yes/no dan tool apa
11. File storage — apakah ada upload file, disimpan di mana
12. Background job — apakah ada proses async, tool apa
13. State management — untuk frontend kompleks
14. UI/UX architecture:
    - Component library atau design system yang dipakai
    - Design reference atau Figma file — ada atau tidak
    - Responsive strategy — mobile-first atau desktop-first
    - Accessibility baseline
    - Information architecture — navigasi dan menu hierarchy
    - Component hierarchy — shared vs page-specific
    - User journey konkret per aktor — screen apa, state apa, transisi antar screen

Exit gate:
- AGENTS.md ada di repo
- Dependency direction tertulis jelas
- User journey konkret per aktor sudah ada
- Design reference atau component library sudah diputuskan
- Environment strategy jelas
- TASK_BREAKDOWN.md berisi minimal satu milestone

### Fase 2 — Development (di Claude Code, loop per task)
Sepenuhnya di Claude Code. Entry point: baca START_HERE.md dulu.

Loop wajib setiap task:
1. Baca START_HERE.md dan AGENTS.md
2. Plan Mode — presentasi rencana, tunggu approval
3. Eksekusi dalam batas scope
4. Untuk task UI: sertakan screenshot atau deskripsi visual + konfirmasi ke saya
5. Laporan plain language
6. Update dokumentasi otomatis
7. Git commit

Rules tambahan di Fase 2:
- Setiap dependency baru harus di-flag dan menunggu approval sebelum ditambahkan
- Setiap schema change harus disertai migration file
- Satu feature branch per fitur jika paralel — merge ke dev setelah selesai
- Jika Plan Mode mendeteksi keputusan arsitektur baru — stop, flag ke saya

UI execution rules:
- Jika ada design reference atau Figma — ikuti itu
- Jika tidak ada — buat berdasarkan component library yang sudah diputuskan di Fase 1
- Konsistensi komponen dijaga dengan membaca komponen yang sudah ada sebelum membuat yang baru
- Setiap task UI wajib sertakan deskripsi visual: "Tampilan ini menunjukkan [deskripsi]"

Database migration rules:
- Setiap perubahan schema harus membuat migration file
- Migration harus bisa di-rollback
- Migration ditest di staging sebelum production

Exit gate:
- Semua acceptance criteria Fase 0 terpenuhi dan ada test-nya
- Tidak ada TODO di flow kritis
- Semua secret di env variable
- CI lulus: test + lint + build hijau

### Fase 3 — Product Ready
Staging dulu, baru production. Rollback hanya relevan di production.

Area yang harus ada:
- Smoke test semua flow kritis di staging
- Rollback path ditest bukan hanya direncanakan
- Monitoring aktif — error rate dan metric bisnis
- Security pre-launch checklist
- Runbook minimal 3 skenario kritis
- Disaster recovery plan (wajib untuk financial/critical)
- Data retention policy (wajib untuk SaaS dan financial/critical)

Exit gate:
- Smoke test staging lulus
- Rollback ditest
- Monitoring aktif
- Tidak ada secret hardcoded

---

## KAPAN KEMBALI KE FASE MANA

Langsung Fase 2 jika: fitur ada di TASK_BREAKDOWN.md, bug fix, update UI minor, optimasi.

Fase 1 mini dulu jika: komponen baru, perubahan data model, API contract baru, integrasi external baru, perubahan UI/UX architecture.

Fase 0 mini dulu jika: scope berubah, target user berubah, problem berubah, pivot bisnis.

---

## GIT WORKFLOW — TIGA BRANCH

main     → production, tidak pernah dikerjakan langsung
staging  → verifikasi sebelum production
dev      → semua pekerjaan terjadi di sini

Untuk parallel feature:
feature/[nama] → dibuat dari dev → merge kembali ke dev setelah selesai

Aturan tidak boleh dilanggar:
- Semua pekerjaan dari branch dev atau feature branch
- Commit per task selesai
- Commit message: MENGAPA berubah, bukan APA yang berubah
- Push setiap akhir sesi
- Pull dulu sebelum mulai di device berbeda
- Semua Git commands dijalankan Claude Code

Alur deployment:
dev → merge ke staging → smoke test → merge ke main

Rollback production jika ada masalah kritis:
git revert [commit] atau git reset ke commit sebelumnya di main

---

## DOKUMENTASI OTOMATIS

Setiap task selesai, Claude Code wajib update sebelum commit:

START_HERE.md — fase saat ini, task selesai, task berikutnya, known issues, instruksi sesi berikutnya.

CHANGELOG.md — tanggal, apa yang berubah dalam bahasa plain.

AGENTS.md — jika ada rule baru dari pekerjaan yang selesai.

TASK_BREAKDOWN.md — tandai task selesai.

Tidak ada dokumentasi yang diupdate manual oleh saya.

---

## START_HERE.md

Dibuat pertama kali oleh Claude Code di akhir Fase 1 sebagai bagian dari repo setup.
Diupdate otomatis setiap task selesai di Fase 2.

Format wajib:
---
Fase: [0/1/2/3]
Terakhir dikerjakan: [tanggal]
Task terakhir selesai: [deskripsi plain]
Task berikutnya: [dari TASK_BREAKDOWN.md]
Known issues: [jika ada]
Instruksi sesi ini: [satu langkah konkret]
---

---

## SESSION HANDOFF — CLAUDE CODE KE CLAUDE CHAT

Ketika perlu diskusi arsitektur:
1. Minta Claude Code generate summary dari START_HERE.md
2. Paste ke Claude Chat
3. Claude Chat membuat keputusan
4. Bawa output kembali ke Claude Code

---

## VISUAL REVIEW — UNTUK TASK UI

Claude Code tidak bisa menunjukkan raw diff untuk UI. Format laporan untuk task UI:

"Tampilan ini sekarang menunjukkan: [deskripsi visual plain].
Komponen yang dibuat: [list].
Mengikuti: [design reference / component library yang dipakai].
Konsisten dengan komponen existing: [ya/tidak, penjelasan jika tidak].
Apakah ini sesuai yang kamu bayangkan?"

---

## PLAN MODE FORMAT

Task: [nama spesifik]
File yang akan dibaca: [list max 7]
File yang akan diubah: [list]
Yang tidak akan disentuh: [list]
Langkah eksekusi: [numbered]
Test yang akan ditulis: [deskripsi]
Dependency baru diperlukan: [ya/tidak — jika ya, flag sebelum lanjut]
Schema change diperlukan: [ya/tidak — jika ya, migration file akan dibuat]
Keputusan arsitektur baru diperlukan: [ya/tidak — jika ya, stop dan flag]

---

## ATURAN TIDAK BOLEH DILANGGAR

1. Tidak ada eksekusi tanpa Plan Mode yang sudah di-approve
2. Tidak ada commit langsung ke main atau staging
3. Tidak ada secret hardcoded
4. Semua dokumentasi diupdate otomatis sebelum commit
5. Claude Code tidak membuat keputusan arsitektur tanpa konfirmasi
6. START_HERE.md selalu diupdate sebelum sesi ditutup
7. Push selalu dilakukan di akhir sesi
8. Setiap dependency baru harus di-flag dan di-approve dulu
9. Setiap schema change harus disertai migration file
10. Setiap task UI harus disertai deskripsi visual
