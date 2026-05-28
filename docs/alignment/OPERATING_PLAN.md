# Dictive-HR Alignment Operating Plan

This document is the resume protocol for continuing the Dictive-HR blueprint alignment across fresh AI sessions. It exists so a new session can continue reliably when the user says "selanjutnya".

## Resume Protocol

When the user says "selanjutnya", the agent must do this before editing files:

1. Read `AGENTS.md`.
2. Read `CLAUDE.md`.
3. Read `docs/02_ARCHITECTURE.md`.
4. Read `docs/ALL_ADR.md`.
5. Read `docs/alignment/GAP_REGISTER.md`.
6. Read the top of `CHANGELOG.md`.
7. Run `git status --short`.
8. Identify the next open alignment milestone from this file and `GAP_REGISTER.md`.
9. Restate the exact files intended to modify before editing.
10. Confirm no do-not-touch area, secret, destructive migration, dependency change, or unrelated frontend file is affected.

## Authority Order

1. `AGENTS.md`
2. `docs/02_ARCHITECTURE.md`
3. `docs/ALL_ADR.md`
4. The current milestone task packet
5. `docs/alignment/GAP_REGISTER.md`
6. `CHANGELOG.md`

If any instruction conflicts with `AGENTS.md`, `AGENTS.md` wins.

## Current Checkpoint

- Alignment 1: Authority docs aligned to the Dictive-HR blueprint.
- Alignment 2: Repository structure skeleton aligned with `backend/app/Core`.
- Alignment 3: Company context and `ResolveCompany` backend boundary established.
- Alignment 4: Distracting obsolete planning docs removed; gap register introduced.
- Alignment 5: Operating plan introduced for reliable cross-session continuation.

## Next Milestones

1. Alignment 6: Route & API Boundary Alignment
   - Apply company context middleware to the correct private company-scoped API groups.
   - Do not rebase every route path unless explicitly approved.
   - Keep instance-level routes separate from company-scoped routes.

2. Alignment 7: Core Runtime Relocation, Phase 1
   - Move a small, low-risk Core area into `backend/app/Core`.
   - Preserve namespaces or add compatibility wrappers only if needed.
   - Do not perform broad namespace moves.

3. Alignment 8: Module Registry Lifecycle Boundary
   - Strengthen install/enable/disable/uninstall rules.
   - Mandatory modules must not be uninstallable or toggleable.
   - Optional module dependency checks must be explicit.

4. Alignment 9: Frontend Rebase Planning Only
   - Produce a task packet for moving toward one `/frontend` Next.js App Router app.
   - Do not implement the frontend rebase in this milestone.

5. Alignment 10: Frontend Rebase Execution
   - Execute only after a dedicated approved task packet.
   - Treat existing dirty frontend files as user-owned unless explicitly included.

## Per-Milestone Protocol

Every alignment milestone must follow this sequence:

1. Prepare a Task Packet unless the user provides one.
2. Confirm exact in-scope and out-of-scope files.
3. Implement only the approved scope.
4. Run relevant tests/checks.
5. Update `CHANGELOG.md`.
6. Update `docs/alignment/GAP_REGISTER.md`.
7. If a gap is fully resolved, move it from Open Gaps to Resolved Gaps.
8. If all gaps are resolved, delete `docs/alignment/GAP_REGISTER.md`.

## Standard Checks

Use these checks when applicable:

```bash
git status --short
php artisan test --filter ModuleManifest
php artisan test --filter CompanyContext
php artisan test --filter ResolveCompany
git diff --check
```

If a DB-backed test fails because the local PHP environment lacks SQLite/PDO SQLite, report it clearly and do not mark the feature as functionally failed without evidence.

## Do Not Touch By Default

- `.env` or secrets
- dependencies
- destructive migrations
- `ModuleServiceProvider`
- `ArchiveService`
- `IPWhitelist`
- unrelated frontend files
- user-owned dirty work

## Gap Register Lifecycle

`docs/alignment/GAP_REGISTER.md` is temporary. Keep it only while there are open alignment gaps.

When the last open gap is resolved:

1. Record the final resolution in `CHANGELOG.md`.
2. Delete `docs/alignment/GAP_REGISTER.md`.
3. Keep this operating plan only if ongoing cross-session alignment remains useful.
