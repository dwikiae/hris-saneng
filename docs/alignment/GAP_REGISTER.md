# Dictive-HR Alignment Gap Register

This temporary file tracks alignment gaps while the repository is being brought into the Dictive-HR blueprint. Delete this file after all open gaps are resolved and the final state is recorded in `CHANGELOG.md`.

## How To Continue

When the user says "selanjutnya", read `docs/alignment/OPERATING_PLAN.md` first, then this file.

## Resolved Gaps

| Gap | Resolution | Record |
| --- | --- | --- |
| Authority docs still described the legacy PT Saneng single-company HRIS. | Root authority docs were aligned to Dictive-HR blueprint. | `CHANGELOG.md` Alignment 1 |
| Backend had no Core skeleton matching the Dictive-HR architecture. | `backend/app/Core` skeleton was added for the core platform areas. | `CHANGELOG.md` Alignment 2 |
| Company-scoped requests had no explicit request-level company context boundary. | `CompanyContext` and `ResolveCompany` baseline were added without rebasing existing routes. | `CHANGELOG.md` Alignment 3 |
| Legacy sprint and recruitment planning docs distracted from the active Dictive-HR authority docs. | Obsolete planning docs were removed from `docs/`. | `CHANGELOG.md` Alignment 4 |
| Fresh sessions had no explicit resume protocol for continuing alignment safely. | `docs/alignment/OPERATING_PLAN.md` was added as the cross-session operating plan. | `CHANGELOG.md` Alignment 5 |

## Next Milestone

Alignment 6: Route & API Boundary Alignment.

The next milestone should clarify which existing backend routes are instance-level, public, or company-scoped, then apply `company.resolve` only where safe. It must not perform a broad route rebase without an approved task packet.

## Open Gaps

| Gap | Boundary |
| --- | --- |
| Frontend still exists as `frontend-hris` and `frontend-web`, while the blueprint targets one `/frontend` App Router app. | Defer to a dedicated frontend rebase milestone. Do not touch dirty frontend files casually. |
| Current company context only supports numeric company identifiers because the current company schema has no slug. | Add slug support only through an explicit schema/task milestone. |
| Existing runtime Core code still lives in legacy shared Laravel folders. | Move incrementally in dedicated Core runtime relocation milestones. |
| Existing API routes are not yet rebased around company context middleware groups. | Defer to a dedicated route/API boundary milestone. |
