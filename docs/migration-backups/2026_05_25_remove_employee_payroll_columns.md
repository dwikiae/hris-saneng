# Backup Step: Remove Employee Payroll Columns

Before running migration `2026_05_25_060003_remove_employee_payroll_columns.php`
against any non-local database, export the current `employees` table.

PostgreSQL:

```bash
pg_dump --table=employees --data-only --column-inserts "$DATABASE_URL" > backups/$(date +%F)/employees_before_payroll_column_removal.sql
```

SQLite local test database:

```bash
sqlite3 database/database.sqlite ".dump employees" > backups/$(date +%F)/employees_before_payroll_column_removal.sql
```

Reason: Sprint 6 removes payroll and compensation fields from the Employee
module scope. This backup is a pre-migration recovery artifact, not application
data, and must not be imported unless a rollback is approved.
