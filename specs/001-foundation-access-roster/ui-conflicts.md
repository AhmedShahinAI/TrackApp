# UI ↔ Specification Conflict Register

**Feature**: `001-foundation-access-roster` | **Date**: 2026-09-27

> ## ✅ All items resolved — Product Owner decisions recorded 2026-09-27
>
> | Item | Decision |
> |---|---|
> | **C-04** programs | **Accepted into this feature.** New `programs` table, `course_program` pivot, required `sections.program_id`, 3 permissions, 5 endpoints, CRUD screen, 4 audit keys |
> | **C-05** course + staff import | **Accepted.** Added as import modes 4 and 5, with a new User Story 8 |
> | **C-08** initiative model | **PRD's dynamic fields stand.** Ordered stages become one preset of Yes/No fields. The EAV capacity spike is **ungated** and proceeds as specified. Consequence: Phase 2 needs a dynamic-field-editor UI the design system does not cover — logged as **C-18** below |
> | **C-09** registrar file format | **Accepted as a third import mode**, reading the metadata block and resolving branch from campus |
> | **C-10** extra fields | **All accepted**: `sections.degree`, `sections.activity`, `section_enrollments.academic_status`, `section_enrollments.roster_sequence`, `users.last_login_at` |
>
> Spec amendments FR-089 … FR-113, User Story 8, SC-019 … SC-024, and the data-model and contract
> changes are all in place. Nothing in this register now blocks work.
>
> **One item added by me as a consequence, not offered as an option**:
> `section_enrollments.roster_sequence` (FR-112). The registrar mode reads a "تسلسل" column and the
> approved UI displays it, so accepting the mode implies somewhere to put it. It is a nullable
> integer. Say so if you would rather drop it.

Every discrepancy found between the approved UI design system, the existing specification, and
the PRD. Each item states what each source says, who decides, and what was done pending that
decision.

**Resolution rule applied throughout** (from the integration brief): the UI is the source of
truth for *visual design*; the specification is the source of truth for *functionality and
business requirements*. Where the UI implies new functionality, nothing is silently adopted.

| Status | Meaning |
|---|---|
| **RESOLVED** | Decided by the resolution rule; plan and tasks updated; no owner action needed |
| **DECIDED-UI** | UI wins because it is visual; spec artifacts amended |
| **BLOCKED** | Needs a Product Owner decision before any task can be executed |
| **FLAGGED** | Not blocking this feature; must be settled before a later phase |

---

## C-01 · Stack: React reference vs Blade production — **RESOLVED**

- **UI kit**: React 18 + Babel-in-browser + inline `style={{…}}` objects, 27 components, loaded
  from `unpkg.com`.
- **Spec / constitution**: Blade server-rendered, Bootstrap 5.3 RTL/LTR, Alpine.js.
- **Design system's own readme**: "Target stack per plan.md: Laravel + Blade + Alpine.js,
  Bootstrap 5.3 RTL/LTR builds."

**Resolution**: The kit is a visual and behavioural reference, not code to port. Each component
is re-authored as a Blade component with Alpine behaviour, reading the `.jsx` for anatomy and the
`.prompt.md` for contract. React, Babel, and every CDN dependency are absent from production.
Recorded in [design-system.md](./design-system.md) §1 and §5. No owner action needed — the design
system and the plan already agree.

---

## C-02 · Bootstrap 5.3 vs design-system tokens — **RESOLVED**

- **UI kit**: does not use Bootstrap at all. Custom CSS over 141 custom properties.
- **Constitution / PRD §9.1**: Bootstrap 5.3 is mandated, with separate RTL and LTR builds.

Left unmanaged, Bootstrap's defaults (its blue `#0d6efd`, 0.375rem radii, 1rem body size, its own
shadows and focus rings) would fight every token.

**Resolution**: Bootstrap is retained for the grid, utilities, and RTL/LTR build machinery — what
the constitution actually needs it for — and its Sass variables are overridden from the tokens
through `resources/css/_bootstrap-bridge.scss`. Tokens define; Bootstrap consumes. The full
variable mapping is in [design-system.md](./design-system.md) §2. Bootstrap components with no
design-system counterpart are not used.

**Residual risk**: Bootstrap's box model and utility specificity can still produce small
differences against the React reference. The visual regression threshold (≤ 1%) is set to catch
drift while tolerating anti-aliasing, and any deliberate difference is recorded.

---

## C-03 · Arabic font: Cairo vs IBM Plex Sans Arabic — **DECIDED-UI**

- **UI / brand board**: **Cairo** 400–800 for Arabic, Inter for Latin and all numerals.
- **PRD §9.1 and the previous plan.md / task T047**: **IBM Plex Sans Arabic** for Arabic, Inter
  for Latin.

**Decision**: Cairo. Typography is visual, and the brand board is the approved identity. The
type scale, weights, and numeral handling in [design-system.md](./design-system.md) §3 are
adopted with it.

**Amended**: `plan.md` Technical Context and Constitution Check row VI; task T047 (now
design-system font vendoring). **PRD §9.1 is now stale on this point** and should be corrected at
the next PRD revision — noted here rather than edited, since the PRD is the owner's document.

Both families are **self-hosted** as woff2 rather than loaded from Google Fonts as the kit does,
because of the strict CSP and the PDPL constraint on third-party origins.

---

## C-04 · `programs` — a new entity the specification does not have — **✅ ACCEPTED 2026-09-27**

The UI kit ships a full **Programs** screen (`ProgramsScreen`), a sidebar entry
(`programs` / `graduation-cap`), a permission (`program.create`), and audit actions
(`program.created`, `program.updated`).

Its data shape, from the kit's store:

| Field | Type | Notes |
|---|---|---|
| `code` | string, unique, uppercase | e.g. `HR`, `ACC`, `CS`, `APP`, `GD`, `RE`, `EC` |
| `name_ar`, `name_en` | string, both required | |
| `unit` | enum | `admin` (البرامج الإدارية) / `tech` (البرامج التقنية) — "الوحدة التخصصية" |
| `active` | boolean | Deactivate, not delete: "لا يمكن حذف برنامج مرتبط بشعب" |

Relationships the UI asserts:
- **`sections.program_id`** — every section belongs to **exactly one** program. The screen states
  it outright: "كل شعبة في الكلية مرتبطة ببرنامج أكاديمي واحد."
- **`course_program`** — a course belongs to **many** programs (the kit seeds a many-to-many map).

**Not in** PRD §11.4, not in [data-model.md](./data-model.md), not in [spec.md](./spec.md). This
is new functionality, not a visual matter, so the resolution rule does not decide it.

**Decision needed from the Product Owner** — one of:

| Option | Consequence |
|---|---|
| **A · Add programs to Phase 1** | New table, new pivot, `sections.program_id` (nullable then backfilled, or required), 3 permissions, CRUD screen, 2 audit actions, import resolution by program. Adds roughly 14 tasks and touches the sections migration, form, list, and detail. Spec, data model, and contracts all need amending first. |
| **B · Defer to a later feature** | Phase 1 ships without programs. The sidebar entry is omitted rather than stubbed, because a visible dead entry is worse than none. Sections carry no program until it lands. |
| **C · Reject** | The UI screen is dropped as out of scope; the design author is told. |

**DECIDED 2026-09-27 — option A, accepted into this feature.** Specified as FR-089 to FR-095, with
the table, pivot, and required `sections.program_id` in [data-model.md](./data-model.md), five
endpoints in [contracts/academic.md](./contracts/academic.md), and tasks T294-T305 (schema) and
T306-T321 (endpoints and screens) in [tasks.md](./tasks.md) Phases 13 and 14. `program_id` is
created **non-nullable from the start**, so the backfill risk below never materialises.

**My read**: this looks like real institutional structure that surfaced during design, not
decoration — Majma'ah's college does organise sections under named academic programs, and the
`unit` split (administrative vs technical) matches how such colleges are structured. If that is
right, option A is correct and it is cheaper to add now, before 3,000 sections exist, than to
backfill `program_id` afterwards. But it is an academic-structure decision, not a design one.

---

## C-05 · Course import and user import dialogs — **✅ ACCEPTED 2026-09-27**

The kit ships `CourseImportDialog` (a full Excel import for courses, reachable from the Programs
screen: "استيراد المقررات من Excel") and `UserImportDialog` (bulk user import).

[spec.md](./spec.md) specifies exactly **two** import modes — central roster (FR-052) and
instructor section roster (FR-053). Neither a course import nor a user import is in the spec, the
PRD's P0-F004, or the contracts.

**Decision needed**: add these as import modes 3 and 4, or defer, or reject. Each adds a column
contract, a validator, a resolver path, a permission, and a wizard entry point — roughly 6–8
tasks each, reusing the existing pipeline.

**Pending**: not built. The Programs screen is blocked anyway (C-04), which is where the course
import lives.

---

## C-06 · Logo assets are raster upscales — **FLAGGED**

The design system states plainly: logos were "cropped from the brand board … raster, ~3×
upscaled", the reversed variants were recoloured by hand, and "**Vector originals are needed.**"

Logos appear in the Phase 1 sidebar and login screen, and in Phase 3 PDF export headers, where a
3× upscale will visibly degrade — and PDF exports are the artifact that reaches college leadership
and accreditation reviewers.

**Action**: obtain SVG or AI/EPS originals from whoever produced the brand board. Not blocking
Phase 1 — the raster assets are adequate at sidebar and login sizes — but it must be resolved
before Phase 3's PDF export work. A task exists to request them.

---

## C-07 · Icon set is a flagged substitution — **FLAGGED**

The brand board shows a bespoke outline set at ~1.75px stroke. No icon files were supplied, and
the design system substitutes Lucide `0.460.0`, marking it "⚠️ Flagged — replace if the team has
its own set."

**Action taken**: Lucide 0.460.0 is **self-hosted** as an SVG sprite (not CDN, per CSP and PDPL),
rendered through one `x-track-icon` component with a single name map. If the original set arrives,
only the sprite file and the map change — no screen touches an icon file directly. This
containment is the reason the substitution is acceptable.

---

## C-08 · Initiative data model: ordered stages vs 15 dynamic field types — **✅ DECIDED 2026-09-27: dynamic fields stand**

This is the most consequential finding, and it reaches back into Phase 1.

- **UI kit** (`InitiativesScreen`, `StagesEditor`, `store.marks`): an initiative carries an
  **ordered list of named stages** ("تسجيل الطالب" → "التوثيق" → "الانتهاء"). Per student, progress
  is a **linear sequence of booleans** — checking stage *n* marks stages 1…*n* done, unchecking *n*
  clears *n*…end. The editor adds, renames, reorders, and removes stages. There is no field type,
  no value, no validation per field.
- **PRD P0-F005 and the constitution**: an initiative carries **15 dynamic field types** (Text,
  Long Text, Integer, Decimal, Percentage, Date, Time, Yes/No, Checkbox, Dropdown, Multiple
  Choice, URL, Email, File Upload, Image Upload), each with required / default / options / order /
  visibility / editability / reporting / completion flags, stored in a **typed EAV** model across
  `value_text`, `value_number`, `value_date`, `value_bool`, `value_json`, versioned by schema.

These are not variations of one design. Stages are a fixed linear workflow; dynamic fields are an
arbitrary typed form. The stage model is a strict subset — expressible as a series of Yes/No
fields flagged required-for-completion, but only that.

**Why it affects Phase 1**: tasks T199–T206 build the typed EAV schema and seed **1,500,000
values** to prove its capacity, because PRD risk R-03 demands it. If the approved design is
stages, the Phase 2 store is far narrower — roughly 30,000 records × a handful of booleans, not
1.5M typed rows across 50 field definitions — and the spike is measuring a design that may not
ship.

**Decision needed before the EAV spike runs**:

| Option | Consequence |
|---|---|
| **A · PRD's dynamic fields stand** | The UI's stage editor is one preset of Yes/No fields. Spike proceeds as specified. Phase 2 must design the full 15-type field editor, which the approved UI does **not** cover — a UI gap to commission. |
| **B · Stages replace dynamic fields** | Phase 2 shrinks substantially. The EAV spike is over-engineering and should be cut to a much smaller measurement. PRD P0-F005 needs rewriting, and "any future initiative without developer involvement" weakens — a stage list cannot capture a certificate name or a verification URL, both of which the PRD's own worked example (§11.1 Flow A) requires. |
| **C · Both** | Stages as the default template over a dynamic-field engine. Most capable, most expensive. |

**Pending**: the EAV spike tasks stay in the plan **but are gated** on this decision, and the gate
is stated in the task itself. Running a 1.5M-row measurement before knowing which model ships
would waste the effort.

**My read**: the PRD's own example initiative — "Certificate Name (Text), Certificate Status
(Dropdown: Enrolled/Passed/Failed), Verification URL (URL)" — cannot be expressed as ordered
stages. Either that example is no longer representative, or the stage editor is a convenience
layer rather than the whole model. Worth resolving directly with whoever approved the UI.

---

## C-09 · Real roster file shape vs the specified central-import columns — **✅ ACCEPTED 2026-09-27 (mode 3)**

The design system ships an **actual registrar export**
(`uploads/filedownloadservlet(21).xlsx`, also `scratch/roster.xlsx`) and the kit treats it as the
reference import. Its shape:

- **Header metadata block** (not columns): الحرم/campus "المجمعة (طلاب)" · الدرجة/degree "الدبلوم
  المتوسط" · المقرر/course "برمجة 2" · النشاط/activity "محاضرة" · الشعبة/section "2298"
- **Four data columns**: `تسلسل` (sequence) · `رقم الطالب` (university id) · `اسم الطالب` (name,
  full four-part Arabic) · `الحالة` (academic status: منتظم / منسحب / معتذر)

[spec.md](./spec.md) FR-052 specifies central import as **ten flat columns**: Term, Branch,
Gender, Course Code, Course Name, Section Number, Instructor Name, Instructor Email, Student
University ID, Student Name.

The real file has **no** term, no branch, no gender, no course code, no instructor, and no
instructor email — and what it does carry (degree, activity, academic status, sequence) the spec
has no column for. Branch must be inferred from "المجمعة (طلاب)", course matched by Arabic name
rather than code, and the instructor supplied out of band.

This is exactly PRD risk **R-02** ("Roster exports from the registration unit are inconsistent",
likelihood High) materialising with evidence, before a line of import code exists.

**Decision needed**:

| Option | Consequence |
|---|---|
| **A · Support the real format as a third mode** | A "registrar export" mode that reads the metadata block, resolves branch from campus + gender, matches course by Arabic name, and takes term and instructor from the wizard rather than the file. Most faithful to reality. Adds a header-block parser, a resolver path, and its own validation categories. |
| **B · Require the registration unit to supply the 10-column format** | Keeps the spec as-is. Depends entirely on a unit outside the project agreeing — PRD §4.5 lists exactly this as an external dependency, and R-02 rates it High likelihood of not happening. |
| **C · Flexible mapping absorbs it** | The existing column mapper handles renamed columns but cannot invent term, branch, gender, or instructor, and cannot read a metadata block above the header row. Insufficient alone. |

**Pending**: FR-052's ten-column contract stands and is what the tasks build. A task exists to
validate the mapper against the real file in week 1 — which is PRD R-02's own stated mitigation
("obtain sample files in week 1 and test against them"). The sample is already in hand, which is
why this is surfaced now rather than discovered in testing.

---

## C-10 · Section and enrollment fields the UI carries and the spec does not — **✅ ALL ACCEPTED 2026-09-27**

| Field | UI values | Spec status |
|---|---|---|
| `sections.degree` | "البكالوريوس", "الدبلوم المتوسط" | absent |
| `sections.activity` | "محاضرة" | absent |
| `enrollments.acad` | "منتظم", "منسحب", "معتذر" | absent — and **distinct** from the spec's `status` |
| `enrollments.seq` | integer from the roster file | absent |
| `users.last_login_at` | timestamp, shown as "آخر دخول" | absent — spec has `first_login_at` only |

`enrollments.acad` deserves attention: it is the **registrar's** academic standing, arriving in
the roster file, while the spec's `status` (`active` / `withdrawn`) is **TRACK's own** enrollment
state set by an instructor with a reason (FR-047). The UI shows both. Collapsing them would lose
information and break FR-047's audit trail; keeping both needs a column the spec does not have.

`users.last_login_at` is the cheapest and least contentious — one column, displayed on the users
list, and useful for the PRD's own "instructor activation" metric (§12.2, "≥ 85% of assigned
instructors log in within 7 days").

**Decision needed** per field. **Pending**: none are added; the screens render only what
[data-model.md](./data-model.md) carries, and the UI's extra columns are omitted rather than faked.

---

## C-11 · Audit action-key naming drift — **RESOLVED**

| UI kit | Specification ([contracts/audit.md](./contracts/audit.md)) |
|---|---|
| `auth.signed_in` | `auth.login` |
| `term.current_set` | `term.set_current` |
| `user.role_changed` | `user.roles_changed` |
| `import.completed` | `import.committed` |

**Resolution**: the specification's keys stand — action keys are functional identifiers that the
audit filter, the tests, and five years of retained records depend on. The UI kit's Arabic display
labels are adopted as the `action_label` translations for the spec's keys. No task changes beyond
task T115 gaining the kit's label strings as its source.

---

## C-12 · Permissions the UI uses that the spec does not seed — **✅ FULLY RESOLVED 2026-09-27**

| Permission | Status |
|---|---|
| `dashboard.view` | **Seed it now.** Phase 3 needs it; seeding a permission that no Phase 1 route checks is harmless and avoids a later migration. Added to task T041's list, taking it from 35 to 36. |
| `program.create` | **Blocked** with C-04. Not seeded until programs is decided. |

---

## C-13 · Queue-unavailable state missing from the UI — **RESOLVED**

[spec.md](./spec.md) requires that when background processing is unavailable, an import stays
queued and the screen says it is waiting and will be reported when it finishes — explicitly *not*
an error (spec Edge Cases, [contracts/imports.md](./contracts/imports.md), quickstart scenario
5.12). The kit has no such state; its imports always progress.

**Resolution**: the state is specified in [design-system.md](./design-system.md) §10 and built
from the existing `x-track-alert` tone `info` plus the persistent progress indicator. No new
component. The visual regression baseline for that screen state has no React counterpart, so it is
baselined from the Blade implementation and reviewed by the design author.

---

## C-14 · Charts specified but unused in Phase 1 — **RESOLVED**

`BarChart`, `DonutChart`, and `LineChart` are fully specified, and the design system notes "Phase
1 screens don't use them."

**Resolution**: not built in this feature. They are listed in
[design-system.md](./design-system.md) §5 as rows 25–27 so Phase 3 inherits the contract, and the
`LineChart` LTR-inside-RTL rule is recorded now while the reason is fresh. Building three unused
components would be speculative work the constitution's complexity rule disallows.

---

## C-15 · Date format: two statements inside the design system — **RESOLVED (no conflict)**

The design system readme says dates are `YYYY/MM/DD`; its `i18n.js` `fmtDate()` emits
`DD/MM/YYYY`, matching PRD §11.6 and FR-084.

**Resolution**: `DD/MM/YYYY` in the application; `YYYY/MM/DD` is a brand-material convention.
Recorded in [design-system.md](./design-system.md) §3 so it is not rediscovered as a bug.

---

## C-16 · "UI copy is Arabic only" vs full bilingual requirement — **RESOLVED (no conflict)**

The readme states "UI copy is Arabic only", while the kit itself ships a complete AR/EN
`i18n.js` with a topbar switcher, and its own readme describes the switch flipping "the whole UI
to English LTR (US7)".

**Resolution**: the "Arabic only" rule governs brand and marketing material. The application is
fully bilingual per FR-074 and constitution VI, which the kit implements. Recorded in
[design-system.md](./design-system.md) §12.

---

## C-17 · WCAG 2.1 AA failures in the approved palette — **DECIDED-UI (corrected)**

Seven token pairings in the approved palette fail the AA floor that constitution VI mandates and
SC-014 gates. Measured ratios, required ratios, and the corrections are in
[design-system.md](./design-system.md) §6.

Summary: green `#10B981` fails as text and as a focus indicator (2.54:1); amber `#D97706` fails as
text (3.19:1) and as a badge label on amber-100 (2.86:1); red `#DC2626` fails as a badge label on
red-100 (3.95:1); gray-400 fails as text (2.54:1).

**Decision**: correct by darkening within the same hue — brand reading is unchanged, and no hue,
spacing, radius, or layout value moves. `#10B981` **remains** the brand green for every non-text
use (chart fills, progress bars, the active sidebar indicator, switch fills, badge backgrounds).

This is the one place where the UI is overridden, and only because accessibility is a stated
constitutional floor and a legal-adjacent obligation, not a preference. Every change is recorded
in `design-system/ACCESSIBILITY-DELTA.md` for the design author's review, and a task exists to
walk them through it.

---

---

## C-18 · No approved UI for the dynamic-field editor — **NEW, FLAGGED for Phase 2**

*Raised by the C-08 decision on 2026-09-27.*

Keeping the PRD's 15 dynamic field types means Phase 2 must ship a **field editor**: adding a field,
choosing among 15 types, setting required / default / options / order and the four flags
(`visible_to_instructor`, `editable_by_instructor`, `show_in_reports`,
`required_for_completion`), reordering fields, and the schema-version history with its
completion-impact warning.

The approved design system covers **none of this**. Its `StagesEditor` handles an ordered list of
names — add, rename, reorder, remove — which is a small fraction of the surface. There is no
reference for a type picker, a per-type options editor, the flag matrix, the live grid preview the
PRD describes (§P0-F005 main flow step 5), or the "completion will drop from 75% to 0% for 1,500
students" confirmation.

**Not blocking this feature** — no Phase 1 screen touches it. But it is a real UI gap that must be
commissioned from the design author **before Phase 2 planning**, or Phase 2 will either stall or
invent its own visual language for the most complex screen in the platform.

**Action**: commission the field-editor screens, plus the initiative dashboard's field-driven grid,
as an addition to the design system. The `StagesEditor` is retained as the visual reference for the
default Yes/No-field preset.

---

## Summary

| Status | Items |
|---|---|
| **RESOLVED** — decided by the authority split, no owner action | C-01, C-02, C-11, C-13, C-14, C-15, C-16 |
| **DECIDED-UI** — spec artifacts amended | C-03 (Cairo), C-17 (7 accessibility corrections) |
| **DECIDED-OWNER 2026-09-27** — accepted and specified | **C-04** programs · **C-05** course + staff import · **C-08** dynamic fields stand · **C-09** registrar mode · **C-10** all five fields |
| **FLAGGED** — settle before a later phase | C-06 (vector logos, before Phase 3) · C-07 (icon set, contained) · **C-18** (field-editor UI, before Phase 2 planning) |

**Nothing blocks work.** Every decision is reflected in [spec.md](./spec.md) (FR-089 … FR-113, User
Story 8, SC-019 … SC-024), [data-model.md](./data-model.md), [contracts/](./contracts/),
[plan.md](./plan.md), [tasks.md](./tasks.md), and [design-system.md](./design-system.md).

Two items remain for a later phase and neither affects this feature: vector logo originals before
Phase 3's PDF exports (C-06), and the dynamic-field-editor UI before Phase 2 planning (C-18).

---

# Design System v2 review (2026-09-28)

`TRACK Design System_V2.zip` was reviewed against the merged specification. **The foundation did not
drift at all** — 141 tokens, 27 components, 22 assets and 21 guideline cards are byte-identical to v1,
and every one of the 24 tokens the new screens use already exists. Sections 2–8 and 10–13 of
[design-system.md](./design-system.md) stand unchanged, as does the entire design-system phase
(T057–T103).

The screens are a different matter. v2 contains **the student-portal design that was explicitly
rejected on 2026-09-27**, plus three further features. Five conflicts follow.

> ## ✅ Decision 2026-09-28 — no student authentication in this release
>
> The Product Owner confirmed **option A**: the specification stands. Students get **no account, no
> password, and no session** in the current version, and the **public support form remains the approved
> design**.
>
> | Item | Outcome |
> |---|---|
> | **C-19** student authentication | **Not in this release.** Constitution v1.1.0 stays at 1.1.0 — no 2.0.0 amendment, no MUST reversed |
> | **C-20** student-visible initiative data | **Not in this release** — it requires authentication to be possible at all |
> | **C-21** support as authenticated tickets | **Rejected.** FR-114 … FR-169 stand unchanged: public form, roster verification, two-axis throttle, challenge, non-derivable reference, enumeration suite |
> | **C-22** instructor-requested sections | **Still open** — unrelated to authentication |
> | **C-23** student contact columns | **Still open**, but its motivation changed — see the note under C-23 |
>
> **Consequences, all favourable:**
>
> - Phases 18–25 (the support channel, T354–T463) proceed **exactly as written**. Nothing is rewritten.
> - The 25 blocked tasks drop to **11**: `T-S01` … `T-S14` are retired, leaving C-22's eight and C-23's
>   three.
> - No constitution amendment. No spec rewrite. No `/speckit-analyze` re-run needed on this account.
> - The impersonation risk already recorded in spec "Accepted Risks" stays as it was — bounded, because a
>   public form exposes no data. v2's design would have put that same guessable credential in front of
>   personal data instead.
>
> **Action for the design author**: v2's four student screens — `ForcePasswordScreen`, `StudentHome`,
> `StageTrack`, and the authenticated `SupportScreens` — are **not approved for this release**. They stay
> in the design system as reference for a possible future version, ideally built on university SSO
> (PRD P3-F002, v2). What is needed instead is the **public** support form and status page specified in
> [CR-002 §12](../changes/CR-002-student-support.md): five public screens plus three staff screens.

---

## C-19 · Student authentication — **DECIDED 2026-09-28: NOT in this release (option A)**

v2 implements authenticated student accounts:

- `students` gain `email` (derived as `{university_id}@s.mu.edu.sa`), `phone`, `pw`, `mustChange`,
  `active`, `firstLogin`, `lastLogin`, and `role: 'STUDENT'`.
- Sign-in becomes a tabbed screen — **Faculty & staff** / **Student**.
- **The initial password is the student's university ID**, with a forced change on first sign-in
  (`ForcePasswordScreen`).
- New audit actions `student.signed_in` and `student.password_set`.
- A `STUDENT` role holding `['ini.self','support.view']`.

**What this contradicts, precisely:**

| Source | Statement |
|---|---|
| Constitution **v1.1.0**, Public endpoints | "It MUST NOT create an account, a password, or a session. **A student never authenticates.**" |
| [spec.md](./spec.md) **FR-124** | "The platform MUST NOT create any account, credential, or session for a student **under any circumstance**." |
| [PRD.md](../../PRD.md) §1 (v1.2) | Project type: "authenticated staff; **plus an unauthenticated student support form**" |
| [CR-002](../changes/CR-002-student-support.md) §3 | Option A (authenticated portal) was considered and **rejected**, with three candidate second factors each rejected in turn |

**The security problem is worse in v2 than in the option we rejected.** That rejection rested on the
roster holding no second identifying factor, so a password could not prove a claimant was that student.
v2 answers that by making **the university ID itself the initial credential** — a nine-digit number
that runs in sequence within a cohort, as the real registrar export shows:

```
471105041 · 471105043 · 471105048 · 471105050 · 471105056 · 471105058
```

Anyone who knows a classmate's ID can sign in as them before that student ever activates. And in v2
what sits behind the sign-in is not an empty form but their initiative progress, their sections, and
their instructors' names (C-20). The forced password change does not close this: it protects the
account only *after* the legitimate student reaches it first.

**Decision needed:**

| Option | Consequence |
|---|---|
| **A · Specification stands** | v2's student screens are not built. The design author is told the public-form design is the approved one and asked to redraw. No spec change; Phases 18–25 proceed as written |
| **B · Adopt v2's student portal** | Constitution goes to **2.0.0** — it reverses a MUST. FR-114 … FR-169 are rewritten: the public form, the two-axis throttle, the challenge, the non-derivable reference and the whole enumeration suite become irrelevant. New work: a student guard, credential storage under PDPL, activation, and password reset for up to 30,000 principals. **And a second factor must still be found**, or the impersonation hole ships |
| **C · Both, sequenced** | The public form stays for the unauthenticated case; an authenticated portal becomes its own later feature, built on university SSO once it exists (PRD defers SSO to v2 as P3-F002) |

**My read**: the 2026-09-27 decision was sound and no evidence has changed since. If the intent has
genuinely shifted toward a student portal, option C sequences it safely. Option B as drawn ships a
guessable default credential in front of personal data, and I would not implement that without a second
factor whatever is decided about the rest.

---

## C-20 · Students see initiative data — **DECIDED 2026-09-28: NOT in this release (falls with C-19)**

`StudentHome` shows each student their initiatives with stage-completion percentages, their sections
with course, branch and **instructor name**, and a per-stage progress track.

[spec.md](./spec.md) states the opposite. The support channel's scope exclusions bar "any display of
initiative data, enrollments, completion or grades", and FR-131 limits a student to "only the status of
their own request".

This is a **product expansion**, not a visual one: it turns a support channel into a student portal. It
also raises questions the specification does not answer — may a student see an instructor's name? May
they see stage progress the instructor has not yet submitted? Under PDPL, is a student entitled to their
own initiative record? Probably yes, which is an argument *for* the feature — but as a specified
feature, not as a side effect of a support screen.

**Decision needed alongside C-19**; the two stand or fall together.

---

## C-21 · Support as authenticated tickets — **DECIDED 2026-09-28: the public form stands**

`SupportScreens.jsx` is an authenticated ticket system, not the specified public form:

| | Specification | v2 |
|---|---|---|
| Access | Public, no sign-in | Authenticated, behind `support.view` |
| Reference | Crockford base32 + check character, **non-derivable** (FR-122, SC-027) | **`TK-1001`, `TK-1002`, `TK-1003` — sequential** |
| States | `new` → `in_progress` → `resolved` → `closed`, plus `rejected` with a mandatory reason | `open` → `in_progress` → `resolved` |
| Verification | Roster + active enrollment, two-axis throttle, challenge, no enumeration | None needed — the session is the identity |
| Student replies | Not possible; a correction is a new request | The student may reply, which reopens a resolved ticket |
| Scope | Coordinator sees their branch's students | `ticketScope` by section scope — equivalent |

Two points survive whatever is decided about authentication:

1. **`TK-1001` is sequential**, which violates FR-122 and SC-027 directly. Even inside an authenticated
   system it leaks ticket volume and ordering. The reference format must not be sequential.
2. v2 has **no `rejected` state**, so it drops the mandatory-reason requirement (FR-149) that exists so
   a student is told *why* their request was refused.

---

## C-22 · Section creation with approval — **DECIDED 2026-09-28: ACCEPTED**

> **Decision.** An **instructor**, a **branch coordinator**, and a **Super Admin** may each create a
> section. A Super Admin's is **active immediately**; an instructor's or a coordinator's requires
> **approval**. Specified as FR-170 … FR-185, SC-039 … SC-043, [data-model.md](./data-model.md) Group 8,
> four new contract routes, and **Phase 27** (T470–T500).
>
> **Two things the decision implied but did not state, which I resolved and flag for correction:**
>
> 1. **Who approves a coordinator's section?** A **Super Admin only** (FR-178). A coordinator cannot
>    approve their own, and cannot approve a same-branch coordinator's either — both would be circular,
>    since the point of approval is a second pair of eyes and a same-branch peer is not one when the
>    branch is the unit of responsibility. If you would rather a peer coordinator could approve, or that a
>    coordinator's section skip approval, **FR-178** is the requirement to change.
> 2. **A rejection releases the section number.** Otherwise a rejected section holds its number forever
>    and nobody can create the section the rejection was meant to send back. This narrows FR-041's
>    uniqueness to **non-rejected** sections (FR-176) and needs a **stored generated column** in the
>    unique index — the three obvious alternatives all fail, for the reasons set out in
>    [data-model.md](./data-model.md) Group 8. **T471 replaces the index T023 builds.**
>
> **And one boundary held**: section approval and assignment review (P0-F008, Phase 2) are different
> decisions at different levels. FR-185 and T485 keep their vocabularies distinct, so "approved" never
> appears bare where either could be meant. No `section.request` permission was introduced — creating and
> requesting are the same act, and `requires_approval` carries the distinction.

### Original analysis, retained

`SectionApproval.jsx` introduces a workflow the specification does not have: an instructor **requests** a
section (`section.request`), optionally uploading a roster file, and a coordinator or Super Admin
**approves**, **returns**, or **rejects** it. `sections` gains a status of `pending` / `returned` /
`rejected` / `active` and a `req` object, with audit actions `section.requested` and
`section.resubmitted`.

Against the specification:

- **FR-040 and FR-041** give section creation to the Super Admin. An instructor creating a section, even
  provisionally, is new.
- New schema on `sections`; two new permissions, `section.request` and `section.approve`.
- **This is P0-F008 territory.** The PRD puts submission-and-review in **Phase 2**, and specifies that
  workflow over *assignments*, not over section creation. v2 proposes a second, different approval
  workflow at a different level — two approval concepts in one platform invites confusion about which
  one "approved" refers to.

Genuinely useful: it would let instructors self-serve section setup instead of queueing behind the
central office, which is the same problem the instructor roster import solves. But it is a new feature
in an unplanned phase, overlapping a P0 feature that is planned.

**Decision needed**: accept as a new feature with its own `/speckit-specify` run sequenced after
Phase 2 so it cannot collide with P0-F008, defer, or reject.

---

## C-23 · `students.email` and `students.phone` — **BLOCKED**

v2 gives every student a derived university email and a generated phone number.

**T105** is an architecture test asserting that `students` carries **no credential, contact, or
activation column**, and the reasoning is the PDPL minimisation rule the constitution carries: "Student
data is limited to academic identifiers and initiative data. Adding any personal field not required by
an initiative is prohibited."

A derived email is arguably not new personal data, since it is computed from the ID. A **phone number
is**, and nothing in the PRD says the registration unit supplies one — the real export has four columns
and none of them is a phone.

Note the opportunity here, which is the most productive finding in this review: **a verified contact is
exactly what C-19's impersonation problem needs.** If the registration unit can supply phone numbers, an
SMS second factor becomes possible and an authenticated student portal becomes defensible. That is the
question worth putting to them, because it could convert a blocked conflict into a solved one.

---

## Compatible in v2 — no decision needed

| Item | Action |
|---|---|
| **Zero foundation drift** | The 7 accessibility corrections, the token pipeline, the Bootstrap bridge, the 24 Blade components and the visual-regression baselines all stand. T057–T103 unaffected |
| `programs` icon `graduation-cap` → `library` | Adopted — [design-system.md](./design-system.md) §7 corrected |
| Four new `nav_*` translation keys | Adopted |
| `StudentsAdminScreen` | Adopted as an extension of the existing students screens, with its import dialog reconciled against the five already-specified import modes |
| Two further real registrar exports | Adopted as import test fixtures — more real column variation to test the registrar mode against |

---

## Summary of the v2 review

| Status | Items |
|---|---|
| **Compatible, adopted** | Zero foundation drift · the icon fix · four nav keys · the students-admin screen · two roster fixtures |
| **DECIDED 2026-09-28 — not in this release** | **C-19** student authentication · **C-20** student-visible initiative data · **C-21** support as authenticated tickets |
| **DECIDED 2026-09-28 — accepted** | **C-22** section creation with approval by originator — Phase 27, T470–T500 |
| **Still open** | **C-23** student contact columns (3 tasks) |

C-19, C-20 and C-21 were one decision, not three — the same product question from three screens, and the
answer was no. C-22 and C-23 are separable and still need yours.

Nothing blocked has been planned or tasked as executable work. The tasks that would build it exist in
[tasks.md](./tasks.md) as a clearly marked blocked backlog, sized so the decision can be made against
real numbers rather than impressions.
