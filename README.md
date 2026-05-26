# HRIS PT Saneng

Monorepo HRIS internal PT Saneng.

## Struktur

- `backend/` - Laravel 11 API dan business logic.
- `frontend-hris/` - Next.js 14 portal internal HRIS.
- `frontend-web/` - Next.js 14 website publik.
- `docs/` - Dokumen fondasi, arsitektur, ADR, dan task sprint.

## Status

Sprint 0 sedang menyiapkan project skeleton dan local development infrastructure.

## Instruksi

Baca `AGENTS.md` sebelum menjalankan command atau mengubah file apa pun.

## Development helper Windows/PowerShell

Gunakan wrapper berikut dari root repository agar command lint/test/typecheck konsisten di Windows:

```powershell
.\scripts\dev.cmd status
.\scripts\dev.cmd install
.\scripts\dev.cmd test
.\scripts\dev.cmd lint
.\scripts\dev.cmd typecheck
.\scripts\dev.cmd verify
```

Catatan: di PowerShell, gunakan `npm.cmd` atau wrapper di atas. Pemanggilan `npm` langsung bisa terkena Execution Policy karena PowerShell memilih `npm.ps1`. `dev.cmd` menjalankan `dev.ps1` dengan bypass hanya untuk proses tersebut, tanpa mengubah policy global Windows.
