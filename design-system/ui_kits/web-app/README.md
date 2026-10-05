# TRACK web app — UI kit (spec 001 · Foundation)

Interactive click-through of Phase 1 as specified in `specs/001-foundation-access-roster/spec.md`. Arabic RTL by default; the topbar/login language switch flips the whole UI to English LTR (US7).

**Demo accounts** (password `Track2026`, buttons on the login screen): Ms. Maha — Super Admin · Dr. Noura — coordinator, Zulfi — Female · Dr. Faisal — instructor (first sign-in; teaches 214 & 215, shared access to 305) · a deactivated account.

Coverage by user story:
- **US1 accounts & sign-in** — generic failure message, 5-attempt lockout with 60s countdown, deactivated-account message, first-sign-in password banner, profile + password change (policy, same-as-current, mismatch), users list, create/edit (branches required for coordinators, duplicate email incl. deactivated), deactivate & reset with confirmation.
- **US2 scoping** — nav and every list filtered by role; out-of-scope section → 404 (+ audit entry, try "Demo: open another branch's section"); missing permission → full-page 403; shared-access badge.
- **US3 audit** — filter by actor/action/record/date, export, before/after detail, immutable notice; activity tabs on section & student pages.
- **US4 structure** — terms (one current, state transitions with confirmation), branches (campus + gender, deactivate not delete), courses, sections (duplicate key refused, same number in another branch allowed), enrollment, withdraw with reason, instructor change + history, access grants.
- **US5/US6 import** — 6-step wizard (upload limits, re-upload warning, column mapping with required targets, background check, preview counts + row reasons, create-or-skip for unknown courses/instructors, chunked import, report + error download). Section mode opens from a section page and refuses sections you don't teach.

Files: `i18n.js` (all AR/EN strings, DD/MM/YYYY), `store.js` (seed data, scope + permission helpers, audit writer), `Shell.jsx`, `AuthScreens.jsx`, `HomeScreen.jsx`, `UsersScreen.jsx`, `StructureScreens.jsx`, `SectionsScreens.jsx`, `ImportScreens.jsx`, `AuditScreen.jsx`, `index.html` (router; state in localStorage `track-kit-v2`).

`data.js` is only used by the mobile kit.
