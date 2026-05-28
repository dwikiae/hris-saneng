# Dictive-HR Backend

Laravel 11 REST API for the Dictive-HR modular HRIS platform.

## Boundaries

- Backend serves JSON API only.
- Core platform code belongs under `app/Core` as alignment work progresses.
- Business modules belong under `app/Modules`.
- Core database migrations stay in `database/migrations`.
- Module migrations stay inside each module folder.

## Before Editing

Read the root `AGENTS.md`, `docs/02_ARCHITECTURE.md`, and `docs/ALL_ADR.md` first. They define the active architecture and safety rules.

## Common Checks

```bash
php artisan test
php artisan test --filter ModuleManifest
php artisan test --filter ResolveCompany
```
