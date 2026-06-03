---
Fase: 1
Status: Fase 1 - retrofit sedang berjalan
Terakhir dikerjakan: 3 Juni 2026
Task terakhir selesai: Phase C - Platform Behaviors dibuat di /frontend
Task berikutnya: Phase D - Modul Karyawan memakai platform templates dan behaviors
Known issues: Tabel attendance/leave belum ada, sehingga present_today, absent_today, dan leave_today masih 0. npm audit di /frontend melaporkan 5 vulnerability dari dependency tree; belum diperbaiki karena npm audit fix --force berpotensi breaking. PHP GD extension belum aktif, sehingga test employee photo variant di-skip sampai extension tersedia. Backend PostgreSQL/Docker daemon lokal sedang tidak aktif pada verifikasi Phase C, sehingga UserPreferencesTest belum bisa dijalankan ulang di sesi ini.
Instruksi sesi ini: Baca START_HERE.md -> Baca AGENTS.md -> Baca docs/UIUX_SPEC.md -> Baca docs/PLATFORM_UI_SPEC.md -> Lanjutkan Phase D Modul Karyawan setelah approval.
---

# Start Here

Dictive-HR sedang menjalankan Fase 1 retrofit. Repository authority tetap mengikuti `AGENTS.md`, dengan `/frontend` sebagai target frontend unified dan `frontend-hris` serta `frontend-web` sebagai referensi legacy.

Step 3b selesai: backend test environment sekarang memakai PostgreSQL database `hris_local_test`, sehingga `DashboardStatsControllerTest` dan full backend suite bisa dijalankan tanpa `pdo_sqlite`.

Phase B selesai: platform sekarang punya `ListPageTemplate`, `DetailPageTemplate`, `FormPageTemplate`, dan `WizardShell` di `/frontend/src/components/templates`.

Phase C selesai: platform sekarang punya PermissionGate, ConfirmDialog, Toast system, NotificationBell + Soketi stub, LanguageSwitcher, ExportButton, DocumentUpload, ApprovalPanel, ApprovalTimeline, ChatLog, `/dashboard/notifications`, dan demo visual `/dashboard/platform-demo`. Modul belum disentuh.

Mulai sesi berikutnya dari Phase D Modul Karyawan di atas platform template/behavior. Keputusan tambahan di `docs/UIUX_SPEC.md` sudah final: light mode only, onboarding wizard, language switcher ID/EN, notifikasi WebSocket via Soketi, dan company branding per company.
