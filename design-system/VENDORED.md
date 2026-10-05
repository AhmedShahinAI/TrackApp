# Vendored design system — read-only

**Source**: `TRACK Design System.zip` (v1), 11,474,992 bytes, 2026-09-26
**Vendored**: 2026-10-05 by task T004
**Inventory**: **174 files** — verified on extraction

## The rule

**Nothing in this folder is ever edited.** It is the approved UI/UX reference, and the
visual-regression suite compares the production frontend against it. An edit here would move the
target the suite measures against, which is the one thing that must not happen.

Changes to the design come as a **new zip**, vendored by a task, with its own inventory recorded
here — never as an edit to these files.

## The one exception

`ACCESSIBILITY-DELTA.md`, written by task T061, is added **to** this folder rather than editing
anything in it. It records the seven colour corrections that
[specs/001-foundation-access-roster/design-system.md](../specs/001-foundation-access-roster/design-system.md)
§6 requires, because seven token pairings in the approved palette measure below the WCAG 2.1 AA
floor that constitution principle VI sets as mandatory.

Each correction darkens within the same hue, so the brand reads identically. `#10B981` remains the
brand green for every non-text use.

## What is here

| Path | Contents |
|---|---|
| `tokens/` | 141 CSS custom properties — the canonical token layer |
| `components/` | 27 React reference components, each with `.d.ts` and `.prompt.md` |
| `guidelines/` | 21 foundation specimen cards |
| `assets/` | 22 brand assets — logos, app icons, imagery, graphic stripes |
| `ui_kits/web-app/` | The React click-through of this feature — the visual reference |
| `ui_kits/mobile/` | Two phone screens — v2 reference only, not built |
| `uploads/` | The brand board original and a real registrar roster export |
| `scratch/` | A roster sample |
| `readme.md` | Brand rules, content register, visual foundations |

## Pending migration to v2

`TRACK Design System_V2.zip` (11,594,160 bytes, 2026-09-27) exists in the repository root and **is
the approved reference**. Task **T464** (Phase 26) migrates it:

- `design-system/` becomes v2
- this v1 copy moves to `design-system-v1/` and stays as the visual-regression baseline

v2's foundation is **byte-identical** to v1 — all 141 tokens, all 27 components, all 22 assets and
all 21 guideline cards. Its changes are confined to screens, four of which conflicted with the
specification; see
[specs/001-foundation-access-roster/ui-conflicts.md](../specs/001-foundation-access-roster/ui-conflicts.md)
C-19 … C-23 for what was decided.

Because the foundation did not drift, `tests/Architecture/TokenParityTest.php` must pass **without**
a baseline update when T464 runs. A failure there means the re-vendoring went wrong, not that the
design changed.
