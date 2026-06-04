---
Fase: 1
Status: Fase 1 - retrofit sedang berjalan
Terakhir dikerjakan: 4 Juni 2026
Task terakhir selesai: Fix auth flow end-to-end frontend logout, login/auth hydration verification, dan Settings Platform Admin
Task berikutnya: Phase D1 Backend Grup 3 - Platform Config & Audit instance-level endpoints
Known issues: Tabel attendance/leave belum ada, sehingga present_today, absent_today, dan leave_today masih 0. npm audit di /frontend melaporkan 5 vulnerability dari dependency tree; belum diperbaiki karena npm audit fix --force berpotensi breaking. PHP GD extension belum aktif, sehingga test employee photo variant di-skip sampai extension tersedia. Backend spec endpoint `/api/v1/instance/modules`, `/api/v1/instance/config`, dan `/api/v1/instance/audit` belum ada; Settings Platform frontend sudah contract-ready dan menampilkan API-not-ready state untuk endpoint yang belum tersedia.
Instruksi sesi ini: Baca START_HERE.md -> Baca AGENTS.md -> Baca docs/UIUX_SPEC.md -> Baca docs/PLATFORM_UI_SPEC.md -> Lanjutkan Phase D1 Backend Grup 3 Platform Config & Audit setelah approval.
---

# Start Here

Dictive-HR sedang menjalankan Fase 1 retrofit. Repository authority tetap mengikuti `AGENTS.md`, dengan `/frontend` sebagai target frontend unified dan `frontend-hris` serta `frontend-web` sebagai referensi legacy.

Step 3b selesai: backend test environment sekarang memakai PostgreSQL database `hris_local_test`, sehingga `DashboardStatsControllerTest` dan full backend suite bisa dijalankan tanpa `pdo_sqlite`.

Phase B selesai: platform sekarang punya `ListPageTemplate`, `DetailPageTemplate`, `FormPageTemplate`, dan `WizardShell` di `/frontend/src/components/templates`.

Phase C selesai: platform sekarang punya PermissionGate, ConfirmDialog, Toast system, NotificationBell + Soketi stub, LanguageSwitcher, ExportButton, DocumentUpload, ApprovalPanel, ApprovalTimeline, ChatLog, `/dashboard/notifications`, dan demo visual `/dashboard/platform-demo`. Modul belum disentuh.

Phase D1 Sub-task 1 selesai: auth surface frontend tersedia di `/login`, `/forgot-password`, `/reset-password`, dan `/set-password`. Login sudah memakai backend existing; forgot/reset/set-password invitation menunggu endpoint backend.

Phase D1 Sub-task 2 selesai: Company Management frontend tersedia di `/dashboard/settings/companies`, `/dashboard/settings/companies/new`, `/dashboard/settings/companies/[id]`, dan `/dashboard/settings/companies/[id]/edit`. Frontend menarget endpoint spec `/api/v1/instance/*`, bukan endpoint legacy `/api/v1/companies`.

Phase D1 Sub-task 3 selesai: Users & Access frontend tersedia di `/dashboard/settings/users`, `/dashboard/settings/users/new`, `/dashboard/settings/users/[id]`, `/dashboard/settings/users/[id]/edit`, `/dashboard/settings/roles/new`, dan `/dashboard/settings/roles/[id]`. Frontend menarget endpoint spec `/api/v1/instance/*`, bukan endpoint legacy `/api/v1/users`, `/api/v1/roles`, atau `/api/v1/permissions`.

Phase D1 Sub-task 4 selesai: Platform Config, Audit Log, dan Module Registry frontend tersedia di `/dashboard/settings/config`, `/dashboard/settings/audit`, dan `/dashboard/settings/modules`. Frontend menarget endpoint spec `/api/v1/instance/*`, bukan endpoint legacy `/api/v1/settings/instance` atau `/api/v1/audit`.

Phase D1 Backend Grup 1 selesai secara implementasi: endpoint instance-level Company Management tersedia di `/api/v1/instance/companies`, hanya untuk Platform Administrator, dengan create/update/detail/list dan archive tanpa hard delete. Field extended frontend disimpan di `company_settings`, sedangkan tabel `companies` hanya ditambah kolom archive yang sudah disetujui.

Phase D1 Backend Grup 2 selesai secara implementasi: endpoint instance-level Users & Access tersedia di `/api/v1/instance/users`, `/api/v1/instance/roles`, dan `/api/v1/instance/permissions/structure`, hanya untuk Platform Administrator. Auth password recovery dan set-password invitation tersedia di `/api/v1/auth/forgot-password`, `/api/v1/auth/reset-password`, dan `/api/v1/auth/set-password`. Invitation email dan reset password email dikirim via queue, token invitation berlaku 24 jam, token reset password berlaku 1 jam, role tetap per company, dan archive user/role tidak memakai hard delete.

Fix auth flow end-to-end selesai: `/dashboard/logout` placeholder dihapus, tombol Keluar di Sidebar dan Topbar sekarang memanggil `POST /api/v1/auth/logout`, membersihkan token `dictive_hr_token`, clear auth store, dan redirect ke `/login`. Login tetap memakai `POST /api/v1/auth/login`, menyimpan token, dan redirect ke `/dashboard` saat user tidak wajib reset password. `AuthHydrator` tetap memakai `GET /api/v1/auth/me`, permissions masuk ke auth store, dan test backend memastikan `admin@saneng.co.id` dengan `company_id = null` mendapat `platform.settings`.

Mulai sesi berikutnya dari Phase D1 Backend Grup 3 Platform Config & Audit. Keputusan tambahan di `docs/UIUX_SPEC.md` sudah final: light mode only, onboarding wizard, language switcher ID/EN, notifikasi WebSocket via Soketi, dan company branding per company.
