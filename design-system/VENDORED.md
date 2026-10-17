# Vendored design system — read-only

**Source**: `TRACK Design System_V2.zip` (v2), 11,594,160 bytes, 2026-09-27
**Vendored**: 2026-10-13 by task T464 (Phase 26)
**Inventory**: **180 files** from the zip, verified on extraction, plus this record
**Previous version**: v1 was kept at `design-system-v1/` (untracked). It carried real student data, so
on 2026-10-17 it was moved out of the repository to the owner's machine (security plan SEC-017); it is
gitignored so it cannot come back by accident

## The rule

**Nothing in this folder is ever edited.** It is the approved UI/UX reference, and the
visual-regression suite compares the production frontend against it. An edit here would move the
target the suite measures against, which is the one thing that must not happen.

Changes to the design come as a **new zip**, vendored by a task, with its own inventory recorded
here — never as an edit to these files.

**The one exception: personal data (2026-10-17, security plan Phase 1, G-01).** Real students' names
and University IDs were replaced with synthetic ones, by one mapping applied identically everywhere, in
`ui_kits/web-app/store.js`, `ui_kits/web-app/ImportScreens.jsx`, `_ds_bundle.js` and the two registrar
spreadsheets. Nothing else in those files changed, so every comparison the suite makes still compares
the same design. A future zip must be redacted the same way **before** it is vendored.

## Accessibility corrections live outside this folder

The corrections the approved palette needs to meet WCAG 2.1 AA are recorded in
[specs/001-foundation-access-roster/ACCESSIBILITY-DELTA.md](../specs/001-foundation-access-roster/ACCESSIBILITY-DELTA.md)
(T061, amended 2026-10-06: nine corrections, not seven) and applied in the production token layer under
`resources/css/`. They are never written here. `tests/Architecture/TokenParityTest.php` reads this
folder's tokens and that record together, so re-vendoring re-applies them by construction.

## What changed from v1 — verified by SHA-256 on extraction

**The foundation is byte-identical.** `tokens/` (5 files), `components/` (86), `assets/` (16) and
`guidelines/` (21) match v1 file for file, so `TokenParityTest` passes without a baseline update.

**Added (6):**

- `ui_kits/web-app/StudentsAdmin.jsx`: the staff-side students screen (T468).
- `ui_kits/web-app/SectionApproval.jsx`: section approval (Phase 27, C-22).
- `ui_kits/web-app/StudentScreens.jsx` and `ui_kits/web-app/SupportScreens.jsx`: the student portal.
  **Retired, not to be built** (C-19 … C-21, decided 2026-09-28; see tasks.md "RETIRED").
- `scratch/roster_sample.xlsx` and `uploads/filedownloadservlet(21)-4d54b7af.xlsx`: two more real
  registrar exports (T467).

**Changed (10), all screen-level:** `Shell.jsx`, `i18n.js`, `store.js`, `AuthScreens.jsx`,
`InitiativesScreen.jsx`, `SectionsScreens.jsx`, `index.html`, `index-standalone.html`,
`TRACK Platform.html`, `_ds_bundle.js`. What each change means for the build is decided in
[ui-conflicts.md](../specs/001-foundation-access-roster/ui-conflicts.md) C-19 … C-23. Only the
adopted ones are built (tasks.md Phase 26).

## Real student data in this folder

**There is none any more (2026-10-17).** `uploads/filedownloadservlet(21).xlsx` and its byte-identical
twin `scratch/roster.xlsx` are the registration unit's real export with its personal data **redacted**:
only `xl/sharedStrings.xml` changed (22 IDs → `999000001`–`999000022`, 22 names → fictional names) and
`docProps/core.xml` lost its author. Every other part is the export's own, so the registrar import is
still tested against the real format (Scenario 9, T325). The two further copies v2 added
(`scratch/roster_sample.xlsx`, `uploads/filedownloadservlet(21)-4d54b7af.xlsx`) were unredacted and
were moved out of the repository with v1.

The **history** still holds the original until the owner approves its purge (security plan Phase 2).
`tests/Architecture/NoPersonalDataFixturesTest.php` keeps real rosters out from now on.

## What is here

| Path | Contents |
|---|---|
| `tokens/` | The canonical token layer |
| `components/` | 27 React reference components, each with `.d.ts` and `.prompt.md` |
| `guidelines/` | 21 foundation specimen cards |
| `assets/` | Brand assets: logos, app icons, imagery, graphic stripes |
| `ui_kits/web-app/` | The React click-through, the visual reference |
| `ui_kits/mobile/` | Two phone screens, reference only, not built |
| `uploads/` | The brand board original and the redacted registrar roster export |
| `scratch/` | The same redacted export, as `roster.xlsx` |
| `readme.md` | Brand rules, content register, visual foundations |
