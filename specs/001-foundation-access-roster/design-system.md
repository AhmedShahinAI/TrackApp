# UI Design System Integration Contract

**Feature**: `001-foundation-access-roster` | **Date**: 2026-09-26

**Source**: `TRACK Design System_V2.zip` (11.59 MB, 180 files), vendored into the repository at
`design-system/` as read-only reference. `TRACK Design System.zip` (v1, 174 files) is retained
unmodified as the baseline the visual-regression suite was written against.

**v2 reviewed 2026-09-28.** The result is unusually clean on the foundation and unusually serious on
the screens:

| Layer | v1 → v2 |
|---|---|
| 141 CSS tokens | **byte-identical** — zero drift |
| 27 React components | **byte-identical** — no new primitives |
| 22 brand assets | **byte-identical** — still raster upscales, so [ui-conflicts.md](./ui-conflicts.md) C-06 stands |
| 21 guideline cards, `readme.md`, `styles.css` | **byte-identical** |
| Screens | **4 new files, 6 changed** — and four of the changes contradict the specification |

Every one of the 24 tokens the new screens reference already exists among the 141. **Section 2, 3, 4,
5, 6, 7, 8, 10, 11 and 13 of this document are therefore unaffected by v2**, and so is the entire
design-system foundation phase (T057–T103). What v2 changes is §9, the screen map — and it changes it
in ways that need a Product Owner decision before any of it is built. See §14.

**Status of this document**: it is the binding contract for *visual* implementation. Where it
and [spec.md](./spec.md) disagree, the split is:

| Domain | Source of truth |
|---|---|
| Colour, type, spacing, radii, shadows, motion, layout geometry, component anatomy, iconography, imagery, Arabic copy register | **This document / the design system** |
| Entities, permissions, scoping, endpoints, validation rules, workflow states, audit obligations, data retention | **[spec.md](./spec.md), [data-model.md](./data-model.md), [contracts/](./contracts/)** |

Anything the design system implies that the spec does not carry is **not** silently adopted — it
is registered in [ui-conflicts.md](./ui-conflicts.md) and waits for a Product Owner decision.

**Update 2026-09-27**: all such items have now been decided. The Product Owner accepted academic
programs, the registrar import mode, course and staff bulk import, and all five additional fields;
the PRD's dynamic-field model stands. The spec carries them as FR-089 … FR-113 and User Story 8, so
the screens that were blocked (rows 23–25 in §9) are now in scope for this feature.

---

## 1. What the design system contains

| Group | Count | Path |
|---|---|---|
| CSS custom-property tokens | 141 | `tokens/{fonts,colors,typography,spacing,base}.css` via `styles.css` |
| React reference components | 27 | `components/{core,forms,feedback,data,navigation}/` |
| Brand assets (logos, app icons, imagery, graphics) | 22 | `assets/` |
| Foundation specimen cards | 21 | `guidelines/` |
| Web-app click-through (spec 001) | 17 routes | `ui_kits/web-app/` |
| Mobile click-through (reference only — v2) | 2 screens | `ui_kits/mobile/` |
| Real registrar roster export | 1 | `uploads/filedownloadservlet(21).xlsx`, `scratch/roster.xlsx` |
| Brand board original | 1 | `uploads/الهوية البصرية للمنصة.png` |

**The React kit is a reference prototype, not the production frontend.** Its own readme states
the target stack as "Laravel + Blade + Alpine.js, Bootstrap 5.3 RTL/LTR builds" — the stack this
plan already mandates. Production re-authors each component as a Blade component with Alpine
behaviour; it does not port React, and it does not ship React, Babel-in-browser, or the kit's
inline `style={{…}}` objects.

---

## 2. Token pipeline

The design system's CSS custom properties are the **canonical** token layer and are copied
verbatim (subject to §6 accessibility corrections) into:

```
resources/css/tokens/fonts.css       <- design-system/tokens/fonts.css (rewritten to self-host)
resources/css/tokens/colors.css      <- design-system/tokens/colors.css
resources/css/tokens/typography.css  <- design-system/tokens/typography.css
resources/css/tokens/spacing.css     <- design-system/tokens/spacing.css
resources/css/tokens/base.css        <- design-system/tokens/base.css
```

A second, *derived* layer bridges the tokens into Bootstrap's Sass variables so Bootstrap's own
components inherit the brand instead of fighting it:

```
resources/css/_bootstrap-bridge.scss
```

Direction of authority: **tokens define, Bootstrap consumes.** No Bootstrap default may
override a token, and no component may hard-code a hex value, a px radius, a shadow, or a
duration that a token already expresses.

### Bootstrap variable mapping (the bridge)

| Bootstrap Sass variable | TRACK token |
|---|---|
| `$primary` | `--track-blue` `#0F4C5C` |
| `$success` | `--green-600` `#059669` (corrected, see §6) |
| `$danger` | `--red-700` `#B91C1C` (corrected) |
| `$warning` | `--amber-700` `#B45309` (corrected) |
| `$secondary` | `--slate-blue-600` `#475569` |
| `$body-bg` | `--track-bg` `#F8FAFC` |
| `$body-color` | `--track-dark` `#1F2937` |
| `$border-color` | `--gray-200` `#E5E7EB` |
| `$font-family-sans-serif` | `'Cairo','Inter',system-ui,sans-serif` |
| `$font-size-base` | `0.875rem` (14px body) |
| `$h1-font-size` … `$h4-font-size` | 28 / 22 / 18 / 16 px |
| `$border-radius-sm` / `$border-radius` / `$border-radius-lg` / `$border-radius-xl` | 6 / 8 / 12 / 16 px |
| `$box-shadow-sm` / `$box-shadow` / `$box-shadow-lg` | `--shadow-xs` / `--shadow-card` / `--shadow-overlay` |
| `$input-btn-focus-box-shadow` | `--ring-focus` |
| `$input-height` / `$btn-height` (via padding) | `--control-h-md` 40px |
| `$spacer` | 4px base, map 1–12 to `--space-*` |
| `$grid-gutter-width` | 14px (design system grid gap) |

Bootstrap is retained for its grid, utilities, and RTL/LTR build machinery — which is what the
constitution mandates it for. Its *visual* defaults are fully displaced by the bridge. Bootstrap
components with no design-system counterpart (accordion, carousel, offcanvas beyond the mobile
nav) are not used in this feature.

---

## 3. Typography and fonts

| Role | Family | Weights | Notes |
|---|---|---|---|
| Arabic + all UI text | **Cairo** | 400, 500, 600, 700, 800 | Replaces IBM Plex Sans Arabic — see [ui-conflicts.md](./ui-conflicts.md) C-03 |
| Latin text and **all numerals** | **Inter** | 400, 500, 600, 700 | `--font-numeric`, `font-variant-numeric: tabular-nums` |

Scale: display 40 · h1 28 · h2 22 · h3 18 · h4 16 · body 14 · body-sm 13 · caption 12 · micro 11
· **stat 26 bold Inter**. Line heights: tight 1.25, snug 1.4, body 1.6.

**Both families are self-hosted** under `public/fonts/` as woff2, not loaded from Google Fonts.
The design system's `tokens/fonts.css` uses a Google Fonts `@import`; production rewrites it as
`@font-face` blocks. Reason: the constitution's strict Content-Security-Policy and the PDPL
requirement that the platform not call third-party origins on every page load.

Numerals are Western digits 0–9 in both locales, dates `DD/MM/YYYY`, per FR-084. The design
system's top-level readme says `YYYY/MM/DD`; its own `i18n.js` `fmtDate()` emits `DD/MM/YYYY`,
matching the spec. `YYYY/MM/DD` applies to brand and marketing material only.

---

## 4. Layout shell

| Element | Specification |
|---|---|
| Sidebar | Fixed **240px** (`--sidebar-width`), on the **right in RTL** / left in LTR; background `--surface-sidebar` (vertical gradient `#0E4150` → `#0A2F3A`); logo `reversed` variant at top; nav items 8px radius, 42px tall, icon 19px; active item filled `--surface-sidebar-active` with a green indicator; hover white 8%; logout pinned to the footer slot |
| Topbar | **64px** (`--topbar-height`), white, `--border-default` bottom hairline; search field, notifications icon-button with red dot, help, user block (name + role, clicks through to profile), and an `end` slot holding the AR/EN switch |
| Content | Scrolls independently; padding `28px 28px 48px`; page background `--surface-page` |
| Page header | Breadcrumbs (when nested) → h1 28 bold → optional 14px secondary subtitle → right-aligned action cluster; 20px bottom margin |
| Cards / panels | White, 1px `--border-default`, 12px radius, `--shadow-card`; internal padding 20px; grid gaps 14px |
| Dialogs | 16px radius, width 480 (confirm) / 560 (form); teal 45% + 2px blur scrim; footer on a `--gray-50` band |
| Narrow (<768px) | Tables become stacked cards; sidebar collapses to an off-canvas drawer toggled from the topbar |

Verified widths: **1280px and 1920px** (FR-085), plus the <768px stacked behaviour.

---

## 5. Component map — 27 design-system components to Blade

Every component becomes `resources/views/components/<name>.blade.php`, consumed as
`<x-track-…>`. Behaviour that needs JavaScript is an Alpine component in
`resources/js/components/`. The React `.jsx` is read as the visual reference; the `.prompt.md`
beside it is the behavioural contract and is quoted into the implementing task.

| # | Design system | Blade component | Alpine | Contract highlights |
|---|---|---|---|---|
| 1 | `core/Button` | `x-track-button` | `loading` state | Variants primary / accent / secondary / ghost / danger; sizes sm 32 / md 40 / lg 48; `icon` sits on the **right** in RTL; press `scale(.98)` |
| 2 | `core/IconButton` | `x-track-icon-button` | — | Square; ghost / outline / filled; `dot` red notification dot; `label` **required** (aria + title) |
| 3 | `core/Icon` | `x-track-icon` | — | Lucide line icon by kebab name, stroke **1.75**, self-hosted sprite (§7) |
| 4 | `core/Logo` | `x-track-logo` | — | Variants primary / reversed / mono / lockup / mark / mark-reversed |
| 5 | `forms/Input` | `x-track-input` | blur validation | Label, optional leading icon, hint, error; sizes sm/md/lg; green focus ring; `error` turns border red and replaces the hint |
| 6 | `forms/Select` | `x-track-select` | — | Native select styled to match Input; optional leading icon |
| 7 | `forms/Checkbox` | `x-track-checkbox` | — | Square, filled `--track-blue` when on |
| 8 | `forms/Switch` | `x-track-switch` | toggle | Green when on |
| 9 | `feedback/StatusBadge` | `x-track-status-badge` | — | 19 statuses (§8); **label always rendered** — satisfies FR-077 structurally |
| 10 | `feedback/Alert` | `x-track-alert` | dismiss | info / success / warning / danger; **always icon + text**; optional action and close |
| 11 | `feedback/Toast` | `x-track-toast` | 4s auto-dismiss | success / error / info; fixed bottom-**end** (bottom-left in RTL, clear of the sidebar) |
| 12 | `feedback/Dialog` | `x-track-dialog` | open/close, focus trap | Teal blurred scrim; footer band; `inline` variant without overlay |
| 13 | `feedback/ProgressBar` | `x-track-progress` | — | Thin bar + %; colour auto-shifts green / blue / red by value; `tone` override |
| 14 | `feedback/Skeleton` | `x-track-skeleton` | — | Pulsing placeholder; used by the table's `loading` state (FR-079) |
| 15 | `feedback/EmptyState` | `x-track-empty-state` | — | Centred icon + title + message + optional action; also renders full-page 403 / 404 |
| 16 | `feedback/Tooltip` | `x-track-tooltip` | hover/focus | Dark tooltip for icon buttons and truncated values |
| 17 | `data/Card` | `x-track-card` | — | Optional title row + action slot; `padding=0` when hosting tabs + table |
| 18 | `data/StatCard` | `x-track-stat-card` | — | Centred KPI: large Inter numeral over an Arabic label; tone blue / green / dark; optional icon and trend |
| 19 | `data/DataTable` | `x-track-data-table` | sort, filter, loading | Gray header, row hover `--blue-50`, custom cell slots, `numeric` columns in Inter, `dense`, `empty` message, **stacked cards <768px** |
| 20 | `navigation/Sidebar` | `x-track-sidebar` | active state, drawer | §4 |
| 21 | `navigation/Topbar` | `x-track-topbar` | search, drawer toggle | §4; `end` slot for the language switch |
| 22 | `navigation/Tabs` | `x-track-tabs` | switch | underline (green indicator) and segmented (gray track); optional counts |
| 23 | `navigation/Breadcrumbs` | `x-track-breadcrumbs` | — | Drill-down path above every detail title (FR-083); chevron follows `dir` |
| 24 | `navigation/Stepper` | `x-track-stepper` | — | The 6 import steps; done = green + check, active = blue + soft ring |
| 25 | `data/BarChart` | `x-track-bar-chart` | — | **Phase 3** — build in this feature only as an unused primitive, or defer (see §10) |
| 26 | `data/DonutChart` | `x-track-donut-chart` | — | **Phase 3** — as above |
| 27 | `data/LineChart` | `x-track-line-chart` | — | **Phase 3**; renders LTR even inside RTL pages |

Components 1–24 are required by Phase 1 screens. Components 25–27 are charts that no Phase 1
screen uses — the design system says so explicitly ("Phase 1 screens don't use them").

---

## 6. Accessibility corrections to the approved palette (mandatory)

Constitution principle VI sets WCAG 2.1 AA as a floor, and FR-085 and SC-014 make it an
acceptance gate. Measuring the approved tokens against AA found **7 failing pairings**. The
palette is corrected by darkening within the same hue — the brand reads identically, and no hue,
spacing, radius, or layout value changes.

| # | Usage | Approved token | Measured | Required | Correction | Corrected |
|---|---|---|---|---|---|---|
| 1 | KPI stat numeral, 26px bold, on white | `--track-green` `#10B981` | 2.54:1 | 3.0:1 | `--green-600` `#059669` | 3.77:1 |
| 2 | Any green text at body size | `--track-green` `#10B981` | 2.54:1 | 4.5:1 | `--green-700` `#047857` | 5.48:1 |
| 3 | Focus indicator border on white | `--border-focus` `#10B981` | 2.54:1 | 3.0:1 | `--green-600` `#059669` | 3.77:1 |
| 4 | Warning text on white | `--amber-600` `#D97706` | 3.19:1 | 4.5:1 | `--amber-700` `#B45309` | 5.02:1 |
| 5 | Danger badge label on `--red-100` | `--red-600` `#DC2626` | 3.95:1 | 4.5:1 | `--red-700` `#B91C1C` | 5.30:1 |
| 6 | Warning badge label on `--amber-100` | `--amber-600` `#D97706` | 2.86:1 | 4.5:1 | `--amber-800` `#92400E` | 6.37:1 |
| 7 | `--text-muted` used for real text | `--gray-400` `#9CA3AF` | 2.54:1 | 4.5:1 | `--track-gray` `#6B7280` | 4.83:1 |

Notes:

- `#10B981` **remains** the brand green for non-text use — chart fills, progress bars, the active
  sidebar indicator, switch fills, badge backgrounds. Only *text and focus indicators* move to
  the darker step.
- `--gray-400` remains valid for genuinely disabled controls, which WCAG 1.4.3 exempts, and for
  decorative rules. It may not carry information.
- `--border-default` `#E5E7EB` at 1.24:1 against white is **not** counted as a failure: card and
  table borders are decorative here, with the card identified by its surface and shadow, not its
  outline. It is listed for completeness.
- The seven corrections are applied in `resources/css/tokens/colors.css` and recorded as a diff
  against the design system in `design-system/ACCESSIBILITY-DELTA.md` so the design author can
  review exactly what changed and why.

---

## 7. Iconography

The brand board shows a bespoke outline set at ~1.75px stroke; **no icon files were supplied**,
and the design system substitutes [Lucide](https://lucide.dev) `0.460.0` via CDN and flags it.

Production decision: **self-host Lucide 0.460.0** as an SVG sprite at
`public/icons/lucide.svg`, rendered through `x-track-icon`. No CDN request, satisfying the CSP
and PDPL constraints. The substitution stays flagged in
[ui-conflicts.md](./ui-conflicts.md) C-07 — if the team supplies the original set, only the
sprite and the name map change.

Name map (from the design system):

| Concept | Icon |
|---|---|
| الرئيسية | `house` |
| لوحة التحكم | `layout-dashboard` (kit) / `chart-pie` (board) |
| المبادرات | `lightbulb` |
| الفروع | `building-2` |
| البرامج | `library` *(v2 corrected this from `graduation-cap`, which v2 reassigns to الطلبة)* |
| المقررات | `book-open` |
| الشعب الدراسية | `users` |
| المستخدمون | `user-cog` |
| الفصول الدراسية | `calendar-range` |
| الاستيراد | `file-up` |
| سجل التدقيق | `shield-check` |
| التقارير | `chart-column` |
| الإعدادات | `settings` |
| الطلبة | `graduation-cap` |
| المدرسون | `user` |

Colour: blue on light surfaces, white on the sidebar, green when active or positive. Sizes 16
inline, 18–20 nav and buttons, 28 feature. No emoji, ever.

---

## 8. Status vocabulary

`StatusBadge` carries a fixed bilingual label for 19 statuses. Every one renders **text beside
the colour**, which is how FR-077 is satisfied structurally rather than by review.

`active` · `completed` · `progress` · `late` · `pending` · `notStarted` · `closed` · `draft` ·
`archived` · `withdrawn` · `inactive` · `failed` · `queued` · `running` · `warning` · `error` ·
`valid` · `shared` · `current`

Approved Arabic vocabulary (fixed — not to be paraphrased): فعّال · مكتمل · قيد التنفيذ ·
متأخرة · لم تبدأ · بانتظار الاعتماد · مغلق · مسودة · مؤرشف · منسحب · موقوف · فشل · في الانتظار ·
قيد التشغيل · تحذير · خطأ · صالح · وصول مشترك · الحالي

Phase 1 uses: `active`, `inactive`, `draft`, `closed`, `archived`, `current`, `withdrawn`,
`shared`, `queued`, `running`, `failed`, `completed`, `valid`, `warning`, `error`. The remainder
(`progress`, `late`, `pending`, `notStarted`) belong to the Phase 2 assignment workflow and ship
with it.

---

## 9. Screen map — kit route to Phase 1 implementation

| # | Kit route / component | Production route | Phase | Spec anchor |
|---|---|---|---|---|
| 1 | `LoginScreen` | `GET /login` | 1 · US1 | FR-008 … FR-011, FR-017 |
| 2 | `ProfileScreen` | `GET /profile`, `GET /profile/preferences` | 1 · US1, US7 | FR-013, FR-074 |
| 3 | first-sign-in `Alert` | banner in `layouts/app` | 1 · US1 | FR-012 |
| 4 | `HomeScreen` | `GET /home` — role landing: 6 stat cards, recent activity, quick actions | 1 · US1 | FR-017, FR-083 |
| 5 | `UsersScreen` + `UserDialog` | `GET /users`, create / edit / deactivate / reset | 1 · US1 | FR-002 … FR-005, FR-018 |
| 6 | `RefusalScreen kind=403` | `errors/403` full page | 1 · US2 | FR-025 |
| 7 | `RefusalScreen kind=404` | `errors/404` full page | 1 · US2 | FR-024 |
| 8 | shared-access badge | in section list | 1 · US2 | FR-026 |
| 9 | `AuditScreen` + `AuditTable` | `GET /audit-logs`, export | 1 · US3 | FR-028 … FR-032 |
| 10 | activity tabs on section / student | `x-track-tabs` panel | 1 · US3 | FR-033 |
| 11 | `TermsScreen` + `TermDialog` | `GET /terms`, create / edit / status / set-current | 1 · US4 | FR-035 … FR-037 |
| 12 | `BranchesScreen` | `GET /branches` | 1 · US4 | FR-038 |
| 13 | `CoursesScreen` | `GET /courses` | 1 · US4 | FR-039 |
| 14 | `SectionsScreen` + `SectionDialog` + `AssignDialog` | `GET /sections`, `mysections` | 1 · US4 | FR-040 … FR-042, FR-048 |
| 15 | `SectionDetail` + `SectionMeta` + `RosterTable` | `GET /sections/{id}` | 1 · US4 | FR-045 … FR-048 |
| 16 | `StudentsScreen` | `GET /students` | 1 · US4 | FR-043 |
| 17 | `StudentDetail` | `GET /students/{id}` | 1 · US4 | FR-043, FR-033 |
| 18 | `ImportsScreen` | `GET /imports` (list, with **Resume** on failed) | 1 · US5 | FR-065, FR-070 |
| 19 | `ImportWizard` 6 steps + `Stepper` + `FileMeta` | `GET /imports/create` … `/report` | 1 · US5 | FR-051 … FR-073 |
| 20 | `SectionFilePicker` | `GET /sections/{id}/imports/create` | 1 · US6 | FR-053, FR-054 |
| 21 | `DashboardScreen` + `DashboardParts` + `dash-map` (13 panels, map, gauge, 3D progress) | — | **3** | P1-F001, P1-F002 |
| 22 | `InitiativesScreen` + `InitiativeDetailScreen` + `InitiativeDialog` + `InitiativeRoster` + `StagesEditor` | — | **2** | P0-F005 … P0-F009 |
| 23 | `ProgramsScreen` | `GET /programs`, create / edit / deactivate | **1 · US4** | FR-089 … FR-095 |
| 24 | `CourseImportDialog` | `GET /imports/courses/create` | **1 · US8** | FR-103, FR-106, FR-107 |
| 25 | `UserImportDialog` | `GET /imports/users/create` | **1 · US8** | FR-104 … FR-107 |
| 26 | registrar-mode wizard entry *(no kit reference)* | `GET /imports/new/registrar` | **1 · US5** | FR-096 … FR-102 |
| 27 | `ui_kits/mobile` (2 screens) | — | **v2** | P3-F001 won't-have |

Rows 1–20 and **23–26** are in scope for this feature — rows 23–25 were unblocked by the Product
Owner's 2026-09-27 decisions (C-04, C-05). Rows 21–22 are visually specified now and built in their
own phases; their shell nav entries are stubbed so the sidebar matches the approved design without
dead links. Row 27 is reference only.

**Row 26 has no kit reference.** The registrar import mode was accepted after the design system was
produced, so its wizard entry step — file upload plus the parsed-metadata confirmation panel showing
campus, degree, course, activity, and section — has no React counterpart to compare against. It is
built from the existing wizard components, and its visual-regression baseline is taken from the Blade
implementation and reviewed by the design author, exactly as the queue-waiting state is (C-13).

*Status (2026-10-10):* built in Phase 15 (T334). The spec `tests/Visual/us5-registrar.spec.ts` is
written against fixture jobs 17 (ar) and 18 (en); its baselines are **not yet captured** and await
the design author's review (T328).

**Phase 2 gap**: keeping the PRD's 15 dynamic field types (decision C-08) means Phase 2 needs a
field-editor UI that this design system does not cover — see [ui-conflicts.md](./ui-conflicts.md)
C-18. The `StagesEditor` remains the reference for the default Yes/No-field preset only.

---

## 10. Interaction and visual states — the matrix every screen must satisfy

Derived from the design system's component contracts and FR-078 … FR-082. Each screen task
carries this as its checklist.

| State | Specification | Spec anchor |
|---|---|---|
| **Default** | Tokens only; no hard-coded visual value | §2 |
| **Loading (list)** | `x-track-skeleton` rows inside the table, not a spinner overlay | FR-079 |
| **Loading (action)** | Button `loading` — spinner replaces the icon, control disabled, width held so layout does not shift | FR-079 |
| **Loading (queued work)** | Persistent progress indicator, polled every **5 s**, with chunk counter on import commit | FR-079 |
| **Empty** | `x-track-empty-state` with icon + title + message + optional action — never a blank region | FR-079 |
| **Validation (field)** | On **blur** and again on submit; border `--red-600`, message replaces the hint, `role="alert"` | FR-078 |
| **Error (recoverable)** | `x-track-toast` tone error **with a retry action**; no internal detail or trace | FR-080 |
| **Error (permission)** | Full-page `x-track-empty-state` + link back to the role landing page | FR-025, FR-080 |
| **Error (not found / out of scope)** | Full-page 404, identical for absent and out-of-scope | FR-024 |
| **Success** | `x-track-toast` tone success, ~4 s, **and** the visible state changes | FR-081 |
| **Confirmation** | `x-track-dialog` restating the impact; destructive action uses the `danger` variant | FR-082 |
| **Focus** | `--green-600` border + 3px green 25% ring; visible on every interactive element | FR-085, §6 |
| **Hover** | Filled buttons darken to `--blue-800`; ghost/outline fill `--gray-100`; table rows tint `--blue-50`; sidebar items white 8% | design system |
| **Press** | `scale(.98)` on buttons | design system |
| **Disabled** | `--gray-400` permitted here (WCAG 1.4.3 exempt); never the only signal of state | §6 |
| **Motion** | 120 / 180 / 280 ms on `cubic-bezier(.2,0,0,1)`; fades, colour, progress width only — no bounce, no parallax; honour `prefers-reduced-motion` | design system |
| **RTL/LTR** | Layout, tables, breadcrumb chevrons, icon side, toast corner all mirror; charts mirror except `LineChart`, which stays LTR | FR-076 |
| **Narrow <768px** | Tables to stacked cards; sidebar to off-canvas drawer | FR-085 |
| **Keyboard** | Every control reachable and operable; dialogs trap focus and restore it on close; skip link in the shell | FR-085, SC-014 |

---

## 11. Asset inventory and usage

| Asset | Used for |
|---|---|
| `logo-primary.png`, `logo-lockup-full.png` | Login screen, PDF export headers (Phase 3) |
| `logo-reversed.png`, `logo-mark-reversed.png` | Sidebar (dark) |
| `logo-mono.png`, `logo-on-dark.png`, `logo-white-on-gray.png`, `logo-mark.png`, `logo-primary.png` | Remaining approved variants, exposed through `x-track-logo` |
| `app-icon-{light,dark,green,black}.png` | Favicon and PWA icons |
| `graphic-stripes.png` | Footer / edge accent only — never a full background |
| `hero-campus.png`, `banner-initiatives.png` | Login split panel and marketing surfaces |
| `brand-board-reference.png` | Reference only, not shipped |

**All logo assets are raster, upscaled ~3× from the brand board, and the design system states
"Vector originals are needed."** Logos appear in the Phase 1 sidebar and login screen and in
Phase 3 PDF exports, where raster upscales will visibly degrade. Registered as
[ui-conflicts.md](./ui-conflicts.md) C-06.

Assets are served from `public/img/brand/`, copied at build time from `design-system/assets/`.

---

## 12. Copy and content rules

- **Arabic is the primary language**, right-to-left, and the interface defaults to it.
- English is a **full, equal locale** reachable from the topbar switch — required by FR-074 and
  constitution VI, and implemented in the kit's `i18n.js`. The design system readme's "UI copy is
  Arabic only" governs *brand and marketing* material, not the application.
- Register: Modern Standard Arabic, formal-institutional, concise. Nouns for navigation, masdar
  or imperative for actions ("تسجيل الدخول"، "إنشاء مبادرة"، "عرض الكل"، "تصدير").
- Address the user in the second person, gender-neutral where possible. The platform speaks as
  the institution, never in the first person.
- Headings are descriptive and sentence-like.
- "TRACK" is always Latin capitals, even inside an Arabic sentence.
- Terms are written "2026/2027 - الأول".
- Status vocabulary is fixed (§8). No emoji anywhere.
- Every string lives in `lang/ar` and `lang/en` with key parity enforced by test (FR-075).

---

## 13. Fidelity verification

"Pixel-perfect wherever technically feasible" is made checkable rather than aspirational:

1. **Token parity test** — asserts every token in `design-system/tokens/*.css` exists in
   `resources/css/tokens/*.css` with an identical value, except the seven §6 corrections, which
   must match `ACCESSIBILITY-DELTA.md` exactly. A silent drift fails the build.
2. **Component visual regression** — Playwright screenshots each Blade component against the
   corresponding React reference rendered at the same viewport, asserting ≤ 1% pixel difference
   at 1440×900. Anti-aliasing and font-hinting differences are why the threshold is not zero.
3. **Screen visual regression** — the same comparison for each of the 20 in-scope screens, in
   both `ar`/RTL and `en`/LTR, at 1280, 1440, and 1920, plus 390 for the stacked layout.
4. **No-hard-coded-visual-value test** — fails on a hex colour, a px radius, a px shadow, or a
   transition duration in any Blade or SCSS file outside `resources/css/tokens/`.
5. **Accessibility test** — axe-core against every screen in both locales, zero AA violations,
   plus an automated contrast assertion over the token pairings in §6.

Where a difference is deliberate — the §6 corrections, self-hosted fonts and icons, Bootstrap's
box model — it is recorded in `ACCESSIBILITY-DELTA.md` or the relevant task and the regression
baseline is updated with the reason. An unexplained difference is a defect.

---

## 14. v2 screen inventory (reviewed 2026-09-28)

### What v2 adds

| File | Components | Status |
|---|---|---|
| `SupportScreens.jsx` | `TicketBadge` `NewTicketDialog` `TicketDialog` `SupportScreen` `TicketList` | ⛔ **conflicts** — C-21 |
| `StudentScreens.jsx` | `ForcePasswordScreen` `StageTrack` `StudentHome` | ⛔ **conflicts** — C-19, C-20 |
| `SectionApproval.jsx` | `SecStatus` `SectionRequestDialog` `ApprovalDialog` `ApprovalsScreen` | ⛔ **conflicts** — C-22 |
| `StudentsAdmin.jsx` | `StudentsAdminScreen` `StudentDialog` `StudentImportDialog` | ✅ **compatible** — staff-side, extends the existing students screens |
| `scratch/roster_sample.xlsx` · `uploads/filedownloadservlet(21)-4d54b7af.xlsx` | — | ✅ **useful** — two further real registrar exports, valuable as import fixtures |

### What v2 changes

| File | Change | Status |
|---|---|---|
| `Shell.jsx` | Nav gains `studentsadmin`, `approvals`, `support` for staff, and a new **`STUDENT` role** with `myinitiatives` + `support`. `programs` icon `graduation-cap` → `library` | Icon ✅ · nav entries gated on their features · `STUDENT` role ⛔ C-19 |
| `AuthScreens.jsx` | A tabbed sign-in: **Faculty & staff** / **Student**. Student signs in with a derived university email; the first password **is the university ID**, then forced change | ⛔ **C-19** |
| `store.js` | `students` gain `email`, `phone`, `pw`, `mustChange`, `active`, `firstLogin`, `lastLogin`, `role:'STUDENT'`. New `S.tickets`. `STUDENT: ['ini.self','support.view']` | ⛔ **C-19, C-23** |
| `SectionsScreens.jsx` | Section statuses `pending`/`returned`/`rejected`/`active`; instructors request sections | ⛔ **C-22** |
| `InitiativesScreen.jsx` | Stage marks surfaced per student | Phase 2 scope |
| `i18n.js` | Four new nav keys: `nav_approvals`, `nav_myinitiatives`, `nav_studentsadmin`, `nav_support` | ✅ |

### Decision 2026-09-28 — no student authentication in this release

The Product Owner confirmed **option A**. v2's four student screens are **not approved for this release**:
`ForcePasswordScreen`, `StudentHome`, `StageTrack`, and the authenticated `SupportScreens`. They remain
here as reference for a possible future version, which should be built on university SSO (PRD P3-F002)
rather than on v2's scheme of using the university ID as the initial password.

The **public** support form specified in [CR-002 §12](../changes/CR-002-student-support.md) — five public
screens plus three staff screens — is what this release needs and what is still missing from the design
system. C-22 (instructor-requested sections) and C-23 (student contact columns) remain open.

### The shape of the disagreement

v2 is not a refinement of the approved support design — it is **the alternative that was explicitly
rejected on 2026-09-27**, plus three further features.

| | Specification (FR-114 … FR-169, constitution v1.1.0) | v2 design system |
|---|---|---|
| Student identity | **No account, no password, no session, ever** | University email + password; initial password **is the university ID**; forced change on first sign-in |
| Student sees | Only their own support request's status | Their initiatives with stage-completion percentages, their sections, and their **instructors' names** |
| Support channel | **Public**, unauthenticated, roster-verified, rate-limited on two axes, CAPTCHA, non-derivable reference | **Authenticated** ticket system, references `TK-1001` — **sequential** |
| Section creation | Super Admin only | Instructors **request**; coordinator or Super Admin approves |
| `students` table | **No credential or contact column, ever** (asserted by T105) | Gains `email`, `phone`, `pw`, `mustChange`, `active`, `firstLogin`, `lastLogin` |

Under the authority split in §0 — UI decides visual, specification decides functional — every row of
that table is **functional**, so the specification governs and v2 cannot simply be adopted. But the
divergence is too large to resolve by that rule alone: it means the design author was working from a
different product decision than the one recorded. Full detail and the decision required:
[ui-conflicts.md](./ui-conflicts.md) C-19 through C-23.

### What is safe to build from v2 today

- Everything in §2 through §8, §10 through §13 — **unchanged**, because the foundation did not drift.
- The `programs` icon correction (`library`).
- The four new `nav_*` translation keys.
- `StudentsAdminScreen` as an extension of the existing students screens (T202), subject to its import
  dialog being reconciled with the five import modes the specification already defines.
- The two new registrar exports as import test fixtures.

Student authentication, student-visible initiative data, and the authenticated ticket model are **closed
as out of scope for this release** (2026-09-28). Section approval (C-22) and student contact columns
(C-23) remain open and may not be built until decided.
