# Alignment 10 Task Packet: Frontend Rebase Execution

## Objective

Create one Next.js 14 App Router frontend at `/frontend` that replaces the current split `frontend-hris` and `frontend-web` apps while preserving their current user-facing behavior.

This task packet is executable only for Alignment 10. Alignment 9 must not implement it.

## Authority

Follow this order:

1. `AGENTS.md`
2. `docs/02_ARCHITECTURE.md`
3. `docs/ALL_ADR.md`
4. This task packet
5. `docs/alignment/GAP_REGISTER.md`
6. `CHANGELOG.md`

If there is any conflict, `AGENTS.md` wins.

## Scope

In scope for Alignment 10:

- Create `/frontend` as the single Next.js 14 App Router app.
- Migrate currently useful UI, services, types, and locale content from:
  - `frontend-hris` for dashboard, candidate quiz, and pemberkasan portal.
  - `frontend-web` for public website, career page, and application form.
- Move all frontend API calls under `/frontend/src/services`.
- Move all frontend locales under:
  - `/frontend/src/locales/id`
  - `/frontend/src/locales/en`
- Preserve strict TypeScript and i18n behavior.
- Update frontend build/check references after the app exists.

Out of scope for Alignment 10:

- Backend feature work.
- Database migrations.
- Dependency changes beyond the new `/frontend` package manifest required to run the unified app.
- Route/API contract changes.
- Production deployment changes beyond pointing frontend build/check commands at `/frontend`.
- Deleting old frontend apps unless all migrated checks pass and the deletion is explicitly included in the Alignment 10 execution report.

## Target Routes

Build these App Router surfaces:

- `/dashboard/*`
  - Protected platform/dashboard surface.
  - Migrates current `frontend-hris` platform pages such as dashboard home, employees, recruitment, settings, and portal entry where still applicable.

- `/[company]/*`
  - Public company website surface.
  - Migrates current `frontend-web` public pages such as home, about, services, and contact.

- `/[company]/karir/*`
  - Public career surface.
  - Migrates current `frontend-web/src/pages/karir/index.tsx`, job cards, and application form behavior.

- `/[company]/kandidat/*`
  - Public token-based candidate portal surface.
  - Migrates quiz and pemberkasan flows currently under `frontend-hris/src/pages/quiz/[token].tsx` and `frontend-hris/src/pages/pemberkasan/[token].tsx`.

## Proposed Directory Shape

```text
/frontend
  /src
    /app
      /dashboard
      /[company]
        /karir
        /kandidat
    /components
      /ui
      /core
      /modules
    /hooks
    /services
    /stores
    /types
    /locales
      /id
      /en
```

Use App Router route groups only when they clarify layouts or rendering boundaries. Do not recreate Pages Router under `/frontend/src/pages`.

## Migration Mapping

Use this source mapping as the starting point:

| Source | Target |
| --- | --- |
| `frontend-hris/src/components/platform` | `/frontend/src/components/core/platform` |
| `frontend-hris/src/components/quiz` | `/frontend/src/components/modules/recruitment/quiz` |
| `frontend-hris/src/components/pemberkasan` | `/frontend/src/components/modules/recruitment/pemberkasan` |
| `frontend-hris/src/services` | `/frontend/src/services` |
| `frontend-hris/src/types` | `/frontend/src/types` |
| `frontend-web/src/components/layout` | `/frontend/src/components/core/public-layout` |
| `frontend-web/src/components/recruitment` | `/frontend/src/components/modules/recruitment/public` |
| `frontend-web/src/services/public.service.ts` | `/frontend/src/services/public.service.ts` |
| `frontend-web/src/types` | `/frontend/src/types` |
| Both apps' `public/locales` | `/frontend/src/locales` |

Resolve duplicate names by grouping by surface or module, not by source app name.

## Implementation Steps

1. Create `/frontend` with Next.js 14, React 18, TypeScript, Tailwind, ESLint, and i18n support matching the existing apps.
2. Configure App Router layouts:
   - Dashboard protected layout under `/dashboard`.
   - Public company layout under `/[company]`.
   - Candidate layout under `/[company]/kandidat`.
3. Migrate locale JSON into `/frontend/src/locales/id` and `/frontend/src/locales/en`.
4. Migrate service functions into `/frontend/src/services`; no component should call `fetch` directly.
5. Migrate public website pages first, then career/apply, then candidate quiz/pemberkasan, then dashboard pages.
6. Replace Pages Router APIs with App Router equivalents:
   - `getStaticProps`/`getServerSideProps` become server components, route segment config, or client-side service calls as appropriate.
   - Browser-only interactive forms must be client components.
7. Update checks to use `/frontend` after the unified app builds.
8. Keep old frontend apps until the unified app passes checks. Remove or mark legacy apps only as part of the same verified Alignment 10 execution.

## Dirty File Rule

Existing dirty `frontend-hris` and `frontend-web` files are explicitly in scope only during Alignment 10. Before modifying them, the implementer must restate that those dirty frontend files are being included because this task packet authorizes the frontend rebase execution.

Do not touch unrelated backend files except docs/check config needed to point at `/frontend`.

## Acceptance Criteria

- `/frontend` exists and is the single intended frontend app.
- App Router routes exist for dashboard, public company site, careers/apply, and candidate portal.
- API calls live under `/frontend/src/services`.
- Locales exist for both `id` and `en`.
- No direct component-level API calls are introduced.
- Old frontend apps are either still present untouched as legacy references, or removed only after successful verification and clear reporting.
- `CHANGELOG.md` and `docs/alignment/GAP_REGISTER.md` are updated after execution.

## Verification

Run from `/frontend`:

```bash
npm run lint
npx tsc --noEmit
npm run build
```

Run from repo root:

```bash
git diff --check
git status --short
```

If dependency installation or package scripts are unavailable locally, report the blocker exactly and do not claim frontend execution is complete.
