# Dictive-HR

Self-hosted modular HRIS platform for Indonesian companies.

## Structure

- `backend/` - Laravel 11 REST API and backend business logic.
- `frontend/` - Official unified Next.js 14 App Router frontend for dashboard, public website, careers, and candidate portal.
- `frontend-hris/` - Legacy Next.js HRIS frontend kept as a migration reference.
- `frontend-web/` - Legacy Next.js public website frontend kept as a migration reference.
- `docs/` - Authority documents, architecture, ADR, and temporary alignment gap tracking.

## Authority

Read these first before changing code:

- `AGENTS.md`
- `CLAUDE.md`
- `docs/00_PROJECT_BRIEF.md`
- `docs/02_ARCHITECTURE.md`
- `docs/ALL_ADR.md`

Temporary alignment gaps are tracked in `docs/alignment/GAP_REGISTER.md`. Delete that file when all tracked gaps are resolved and recorded in `CHANGELOG.md`.
