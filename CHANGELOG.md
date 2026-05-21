# CHANGELOG

Semua perubahan penting project dicatat di file ini.

## Sprint 0

- Menyiapkan scaffold monorepo Laravel 11 + dua frontend Next.js 14.
- Menambahkan Docker Compose local dev untuk PostgreSQL, Redis, MinIO, Soketi, dan Mailpit.
- Mengonfigurasi backend Laravel untuk PostgreSQL, Redis, MinIO, Soketi, Mailpit, Sanctum, Spatie Permission, dan Activitylog.
- Menambahkan foundation database: companies, company_settings, users, cache, jobs, failed_jobs.
- Menambahkan seed data awal PT Saneng, default settings, dan System Admin.
- Menambahkan endpoint `GET /health` untuk health check service dependencies.
- Menambahkan GitHub Actions CI untuk backend dan kedua frontend.
- Menambahkan baseline test suite Sprint 0 untuk models, migrations, seeders, archive scope, dan health check.
