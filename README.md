# Dictive-HR

Self-hosted modular HRIS platform for Indonesian companies.

## Structure

- `backend/` - Laravel 11 REST API and backend business logic.
- `frontend-hris/` - Legacy Next.js HRIS frontend, pending future frontend rebase.
- `frontend-web/` - Legacy Next.js public website frontend, pending future frontend rebase.
- `docs/` - Authority documents, architecture, ADR, and temporary alignment gap tracking.

## Authority

Read these first before changing code:

- `AGENTS.md`
- `CLAUDE.md`
- `docs/00_PROJECT_BRIEF.md`
- `docs/02_ARCHITECTURE.md`
- `docs/ALL_ADR.md`

Temporary alignment gaps are tracked in `docs/alignment/GAP_REGISTER.md`. Delete that file when all tracked gaps are resolved and recorded in `CHANGELOG.md`.
