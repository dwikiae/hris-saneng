# CLAUDE.md

Instruksi operasional untuk Claude Code di repository HRIS PT Saneng.

## Prioritas Instruksi

1. Baca dan ikuti `AGENTS.md` sebelum menjalankan task apa pun.
2. Baca dokumen arsitektur terkait di `docs/` sebelum mengubah struktur atau behavior.
3. Kerjakan task sprint secara berurutan sesuai `docs/sprint-0-tasks.md`.
4. Jika ada konflik instruksi, `AGENTS.md` menang.

## Batas Eksekusi

- Jangan lanjut ke task berikutnya tanpa konfirmasi Principal.
- Jangan menambah dependency baru tanpa melaporkannya di completion report.
- Jangan melakukan perubahan destructive pada migration, database, atau Git history tanpa instruksi eksplisit.

## Completion Report

Setiap task selesai wajib memakai format completion report dari `AGENTS.md`.
