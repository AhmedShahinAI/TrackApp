# Accessibility Delta — the nine corrections to the approved palette

**Task**: T061 · **Applies to**: `resources/css/tokens/colors.css` · **Recorded**: 2026-10-06

This is the record the design author reviews. Every value below was **recomputed from the hex
pairs** using the WCAG 2.1 relative-luminance formula, not copied from the specification; all seven
reproduce [design-system.md](./design-system.md) §6 to two decimal places.

> **Location note.** [tasks.md](./tasks.md) T061 asks for this file at
> `design-system/ACCESSIBILITY-DELTA.md`. It is here instead, because the standing instruction for
> this project is that nothing in `design-system/` is ever written to — that directory is vendored
> read-only reference ([design-system/VENDORED.md](../../design-system/VENDORED.md)). It sits beside
> `design-system.md`, whose §6 table it answers, so the review path is unchanged.

## Why the palette moved at all

Constitution principle VI sets WCAG 2.1 AA as a floor and FR-085 / SC-014 make it an acceptance
gate, so a failing pairing is a build failure rather than a design preference. Measuring the
approved tokens found seven failures. **Every correction darkens within the same hue.** No hue
rotates, and no spacing, radius, or layout value changes — the brand reads identically.

**Two more were found during Phase 3** (A11Y-08, A11Y-09) by running axe-core over the built
components rather than over the palette. Both are *surface-dependent*, which is why measuring tokens
in isolation missed them:

- **A11Y-08** — §6 measured green text **on white**. It did not measure white text **on green**.
  The accent button puts a white label on `--track-green`, which is 2.54:1. So does the completed
  step marker, with a white check. Both now use `--action-accent-strong`; `--action-accent` keeps
  the brand green for fills that carry nothing — the progress bar, the switch, the sidebar
  indicator.
- **A11Y-09** — `--text-secondary` measures 4.83:1 on white and passes. On the segmented tab
  strip's `--surface-subtle` track it drops to 4.41:1 and fails. A token can be compliant against
  one surface and not another, and only rendering it finds that.

`#10B981` **remains** the brand green for all non-text use: chart fills, progress bars, the active
sidebar indicator, switch fills, and badge backgrounds. Only *text and focus indicators* move to
the darker step. `--gray-400` remains valid for genuinely disabled controls, which WCAG 1.4.3
exempts, and for decorative rules; it may not carry information.

## The seven corrections

The table is parsed by [tests/Architecture/TokenParityTest.php](../../tests/Architecture/TokenParityTest.php),
which fails the build if a token in `resources/css/tokens/` diverges from the design system in any
way this table does not authorise. Columns are fixed; do not reorder them.

| ID | Usage | Token changed | Was | Becomes | Before | Required | After | Criterion |
|---|---|---|---|---|---|---|---|---|
| A11Y-01 | KPI stat numeral, 26px bold, on white | `--stat-green` *(new)* | `#10B981` | `#059669` | 2.54:1 | 3.0:1 | 3.77:1 | 1.4.3 (large text) |
| A11Y-02 | Any green text at body size, on white | `--text-success` *(new)* | `#10B981` | `#047857` | 2.54:1 | 4.5:1 | 5.48:1 | 1.4.3 |
| A11Y-03 | Focus indicator border, on white | `--border-focus` | `#10B981` | `#059669` | 2.54:1 | 3.0:1 | 3.77:1 | 1.4.11 |
| A11Y-04 | Warning text, on white | `--text-warning` *(new)* | `#D97706` | `#B45309` | 3.19:1 | 4.5:1 | 5.02:1 | 1.4.3 |
| A11Y-05 | Danger badge label, on `--red-100` `#FEE2E2` | `--status-danger-fg` | `#DC2626` | `#B91C1C` | 3.95:1 | 4.5:1 | 5.30:1 | 1.4.3 |
| A11Y-06 | Warning badge label, on `--amber-100` `#FEF3C7` | `--status-warning-fg` | `#D97706` | `#92400E` | 2.86:1 | 4.5:1 | 6.37:1 | 1.4.3 |
| A11Y-07 | `--text-muted` used for real text, on white | `--text-muted` | `#9CA3AF` | `#6B7280` | 2.54:1 | 4.5:1 | 4.83:1 | 1.4.3 |
| A11Y-08 | White label or icon **on** an accent fill | `--action-accent-strong` *(new)* | `#10B981` | `#047857` | 2.54:1 | 4.5:1 | 5.48:1 | 1.4.3, 1.4.11 |
| A11Y-09 | Secondary text on `--surface-subtle` `#F1F5F9` | `--text-secondary-on-subtle` *(new)* | `#6B7280` | `#4B5563` | 4.41:1 | 4.5:1 | 6.90:1 | 1.4.3 |

Each correction is marked in `colors.css` with its ID in a trailing comment, so the reason for a
value is readable where the value lives.

## How that lands in `colors.css`

**Four existing properties change value.** These are the only properties in
`resources/css/tokens/colors.css` whose value differs from `design-system/tokens/colors.css`:

| Property | design-system | production | Correction |
|---|---|---|---|
| `--text-muted` | `var(--gray-400)` | `var(--track-gray)` | A11Y-07 |
| `--border-focus` | `var(--track-green)` | `var(--green-600)` | A11Y-03 |
| `--status-danger-fg` | `var(--red-600)` | `var(--red-700)` | A11Y-05 |
| `--status-warning-fg` | `var(--amber-600)` | `var(--amber-800)` | A11Y-06 |

**Six properties are added.** Three are palette steps the approved scales did not contain, and
three are semantic aliases for usages that previously had no token of their own — which is *why*
they were failing: with nothing to name the colour of green body text, authors reached for
`--track-green`.

| Property | Value | Reason |
|---|---|---|
| `--red-700` | `#B91C1C` | step needed by A11Y-05 |
| `--amber-700` | `#B45309` | step needed by A11Y-04 |
| `--amber-800` | `#92400E` | step needed by A11Y-06 |
| `--green-800` | `#065F46` | hover step for A11Y-08 (white on it is 7.68:1) |
| `--stat-green` | `var(--green-600)` | A11Y-01 — the KPI numeral |
| `--text-success` | `var(--green-700)` | A11Y-02 — green text at body size |
| `--text-warning` | `var(--amber-700)` | A11Y-04 — warning text on white |
| `--action-accent-strong` | `var(--green-700)` | A11Y-08 — accent fills that carry text or an icon |
| `--action-accent-strong-hover` | `var(--green-800)` | A11Y-08 — its hover state |
| `--text-secondary-on-subtle` | `var(--gray-600)` | A11Y-09 — secondary text on a tinted surface |
| `--surface-scrim` | `rgb(15 76 92 / 45%)` | not a correction; §4 specifies the dialog scrim as "teal 45% + 2px blur" and gave it no token |

Nothing is removed. All 141 design-system properties survive with their names intact.

## Verification

```
php artisan test --testsuite=Architecture --filter=TokenParity
npx playwright test tests/Visual/axe.spec.ts
```

The first recomputes each ratio from the hex values rather than trusting this table, so a wrong
figure here fails the build too. The second is what found A11Y-08 and A11Y-09: it runs axe-core over
every component in every state, in both locales, and requires zero violations — which is the only
check that sees a token in the context it is actually used in.
