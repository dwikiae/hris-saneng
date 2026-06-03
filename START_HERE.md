---
Fase: 1
Status: Fase 1 - retrofit sedang berjalan
Terakhir dikerjakan: 3 Juni 2026
Task terakhir selesai: Phase D1 Sub-task 1 - Auth pages frontend dibuat di /frontend
Task berikutnya: Phase D1 Sub-task 2 - Company Management di /dashboard/settings/companies
Known issues: Tabel attendance/leave belum ada, sehingga present_today, absent_today, dan leave_today masih 0. npm audit di /frontend melaporkan 5 vulnerability dari dependency tree; belum diperbaiki karena npm audit fix --force berpotensi breaking. PHP GD extension belum aktif, sehingga test employee photo variant di-skip sampai extension tersedia. Backend PostgreSQL/Docker daemon lokal sedang tidak aktif pada verifikasi Phase C, sehingga UserPreferencesTest belum bisa dijalankan ulang. Backend auth endpoint `forgot-password`, `reset-password`, dan `set-password` invitation belum ada; halaman frontend sudah contract-ready dan menampilkan API-not-ready state.
Instruksi sesi ini: Baca START_HERE.md -> Baca AGENTS.md -> Baca docs/UIUX_SPEC.md -> Baca docs/PLATFORM_UI_SPEC.md -> Lanjutkan Phase D1 Sub-task 2 setelah approval.
---

# Start Here

Dictive-HR sedang menjalankan Fase 1 retrofit. Repository authority tetap mengikuti `AGENTS.md`, dengan `/frontend` sebagai target frontend unified dan `frontend-hris` serta `frontend-web` sebagai referensi legacy.

Step 3b selesai: backend test environment sekarang memakai PostgreSQL database `hris_local_test`, sehingga `DashboardStatsControllerTest` dan full backend suite bisa dijalankan tanpa `pdo_sqlite`.

Phase B selesai: platform sekarang punya `ListPageTemplate`, `DetailPageTemplate`, `FormPageTemplate`, dan `WizardShell` di `/frontend/src/components/templates`.

Phase C selesai: platform sekarang punya PermissionGate, ConfirmDialog, Toast system, NotificationBell + Soketi stub, LanguageSwitcher, ExportButton, DocumentUpload, ApprovalPanel, ApprovalTimeline, ChatLog, `/dashboard/notifications`, dan demo visual `/dashboard/platform-demo`. Modul belum disentuh.

Phase D1 Sub-task 1 selesai: auth surface frontend tersedia di `/login`, `/forgot-password`, `/reset-password`, dan `/set-password`. Login sudah memakai backend existing; forgot/reset/set-password invitation menunggu endpoint backend.

Mulai sesi berikutnya dari Phase D1 Sub-task 2 Company Management di `/dashboard/settings/companies`. Keputusan tambahan di `docs/UIUX_SPEC.md` sudah final: light mode only, onboarding wizard, language switcher ID/EN, notifikasi WebSocket via Soketi, dan company branding per company.
