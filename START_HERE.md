---
Fase: 1
Status: Fase 1 — retrofit sedang berjalan
Terakhir dikerjakan: 2 Juni 2026
Task terakhir selesai: Tahap 1 Step 3 — endpoint Laravel GET /api/v1/dashboard/stats dibuat
Task berikutnya: lanjutkan surface public company website di /frontend
Known issues: Tabel attendance/leave belum ada, sehingga present_today, absent_today, dan leave_today masih 0. npm audit di /frontend melaporkan 5 vulnerability dari dependency tree; belum diperbaiki karena npm audit fix --force berpotensi breaking.
Instruksi sesi ini: Baca START_HERE.md -> Baca AGENTS.md -> Baca docs/UIUX_SPEC.md -> Lanjutkan surface public company website.
---

# Start Here

Dictive-HR sedang menjalankan Fase 1 retrofit. Repository authority tetap mengikuti `AGENTS.md`, dengan `/frontend` sebagai target frontend unified dan `frontend-hris` serta `frontend-web` sebagai referensi legacy.

Mulai sesi berikutnya dari pengembangan surface public company website di `/frontend`. Keputusan tambahan di `docs/UIUX_SPEC.md` sudah final: light mode only, onboarding wizard, language switcher ID/EN, notifikasi WebSocket via Soketi, dan company branding per company.

