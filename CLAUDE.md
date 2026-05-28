# CLAUDE.md
## Instruksi Operasional untuk Claude Code — Dictive-HR

## Prioritas Instruksi
1. Baca dan ikuti AGENTS.md sebelum menjalankan task apapun.
2. Baca dokumen arsitektur terkait di docs/ sebelum mengubah struktur atau behavior.
3. Kerjakan task berdasarkan task packet yang diberikan Principal di setiap sesi.
4. Jika ada konflik instruksi, AGENTS.md menang.

## Konteks Platform
Dictive-HR adalah platform HRIS self-hosted modular.
- Bukan sistem untuk satu perusahaan — ini platform yang dipakai banyak company dalam satu instance
- Setiap keputusan harus mempertimbangkan dampaknya ke multi-company, bukan single-company
- Module system adalah jantung platform — jangan pernah bypass module registry

## Status Development
- Phase: Foundation (Core Platform)
- Modul mandatory: Karyawan, Kalender (belum dimulai)
- Modul optional: Recruitment, Aset, Website (belum dimulai)

## Yang Wajib Dibaca Sebelum Task Apapun
- AGENTS.md — aturan absolute
- docs/02_ARCHITECTURE.md — gambaran sistem
- docs/ALL_ADR.md — keputusan yang sudah terkunci

## Batas Eksekusi
- Jangan lanjut ke task berikutnya tanpa konfirmasi Principal
- Jangan tambah dependency baru tanpa melaporkan di completion report
- Jangan lakukan perubahan destructive pada migration atau data tanpa instruksi eksplisit
- Jangan modifikasi area Do-Not-Touch tanpa task yang secara eksplisit menyebutnya

## Module Development Convention
Setiap modul baru harus mengikuti struktur:
```
/backend/app/Modules/{NamaModul}/
  module.json          → manifest wajib ada
  Domain/              → business rules
  Application/         → use cases
  Infrastructure/      → adapters spesifik modul
  Http/                → controllers, requests, resources
  Models/
  Repositories/
  Database/
    migrations/        → migration modul (bukan di /database/migrations core)
    seeders/
  Tests/
```

## Completion Report
Setiap task selesai wajib memakai format completion report dari AGENTS.md.
