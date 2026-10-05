# TRACK Design System

**TRACK** — منصة إدارة ومتابعة المبادرات الأكاديمية · *Academic Initiatives Management Platform*

TRACK is a central web platform for creating, running and following up academic initiatives inside a college's course sections. It follows a strict hierarchy:

الكلية (College) → الفروع (Branches) → المبادرات (Initiatives) → الشعب الدراسية (Sections) → أعضاء هيئة التدريس (Instructors) → الطلبة (Students) → بيانات المبادرة (Initiative data)

Roles:
- **Super Admin (سوبر أدمن)** — creates initiatives and picks target sections/branches.
- **Branch coordinator (منسق الفرع)** — follows execution across their branch's sections.
- **Instructor (مدرس الشعبة)** — manages student data, executes initiative requirements, updates results.

Everything is scoped to an academic term (الفصل الدراسي). Multiple terms, initiatives and branches coexist; historical terms are kept read-only and never mixed.

Surfaces: **desktop web app** (primary, RTL Arabic) and a **mobile app** (login + instructor view shown on the board).

## Sources
- `uploads/الهوية البصرية للمنصة.png` — brand board (copied to `assets/brand-board-reference.png`): logo + variations, colors, typography, icon set, app icons, graphic elements, taglines, dashboard mock, phone login mock, hero banner.
- Spec kit `specs/001-foundation-access-roster/` (uploaded to `uploads/`): `spec.md`, `plan.md`, `data-model.md`, `research.md`, `quickstart.md`, `tasks.md`. Original location on the author's machine: `D:\My_Developed_Apps\Track App\specs\001-foundation-access-roster`. The web-app UI kit implements this spec (Phase 1 — Foundation). Target stack per plan.md: Laravel + Blade + Alpine.js, Bootstrap 5.3 RTL/LTR builds.
- No Figma or codebase was attached.

## Index
- `styles.css` — entry point (imports only) → `tokens/fonts.css`, `colors.css`, `typography.css`, `spacing.css`, `base.css`
- `assets/` — logos (cropped from the board), app icons, graphic stripes, imagery
- `guidelines/` — foundation specimen cards (Colors, Type, Spacing, Brand)
- `components/` — React primitives (`core/`, `forms/`, `feedback/`, `data/`, `navigation/`), each with `.d.ts` + `.prompt.md` and one card per folder
- `ui_kits/web-app/` — interactive Phase 1 platform (spec 001): sign-in, users, terms, branches, courses, sections, students, 6-step import, audit log — AR/EN
- `ui_kits/mobile/` — phone login + instructor home
- `thumbnail.html`, `SKILL.md`

## Components
- core: **Button** (with `loading`), **IconButton**, **Icon**, **Logo**
- forms: **Input** (blur validation, required), **Select**, **Checkbox**, **Switch**
- feedback: **StatusBadge** (bilingual labels), **ProgressBar**, **Toast**, **Tooltip**, **Dialog**, **Alert**, **EmptyState**, **Skeleton**
- data: **Card**, **StatCard**, **DataTable** (skeleton loading, stacked cards < 768px), **BarChart**, **DonutChart**, **LineChart**
- navigation: **Sidebar**, **Topbar** (`end` slot for language switch), **Tabs**, **Breadcrumbs**, **Stepper**

No component library was supplied; this set is sized to the brand board plus spec 001's shared-behaviour rules (FR-074–FR-085: status never by colour alone, loading states, confirmation of high-impact actions, drill-down path, stacked cards on narrow screens). Charts are from the board's dashboard mock; Phase 1 screens don't use them.

Usage: load `styles.css`, the Lucide UMD script (`https://unpkg.com/lucide@0.460.0/dist/umd/lucide.min.js`), React 18, then `_ds_bundle.js`; read components from `window.TRACKDesignSystem_c33017`.

## CONTENT FUNDAMENTALS
- **Language:** Arabic first, RTL. English appears only as a secondary line under Arabic in brand material ("متابعة دقيقة / Accurate Tracking"). UI copy is Arabic only.
- **Register:** Modern Standard Arabic, formal-institutional, concise. Nouns for navigation (المبادرات، الفروع، الشعب الدراسية، الطلبة، التقارير، الإعدادات). Verbs in the masdar/imperative form for actions: "تسجيل الدخول"، "إنشاء مبادرة"، "عرض الكل"، "تصدير".
- **Address:** Second person, gender-neutral where possible: "مرحبًا بك في TRACK"، "ادخل بياناتك الجامعية". The platform speaks as the institution, never "I".
- **Headings:** descriptive, sentence-like: "نسبة الإنجاز حسب الفرع"، "توزيع الطلبة حسب النوع"، "معدل الإنجاز عبر الفصول"، "أحدث المبادرات"، "الشعب المتأخرة".
- **Taglines (brand only):** "معًا .. نحو أثر أكاديمي أكبر" / "Track Today, a Brighter Tomorrow"; "نحو بيئة أكاديمية أكثر تميزًا"; "المبادرات تصنع الفرق / Initiatives Make a Difference". Value words: متابعة دقيقة، مشاركة أوسع، أثر مستدام.
- **Status vocabulary (fixed):** فعّال، مكتمل، قيد التنفيذ، متأخرة، لم تبدأ، بانتظار الاعتماد، مغلق.
- **Numbers:** Western digits in Inter (3,482 · 76% · 2026/2027). Dates `YYYY/MM/DD`. Terms written "2026/2027 - الأول".
- **Brand name:** always "TRACK" in Latin capitals, even inside Arabic sentences.
- **Emoji:** never.

## VISUAL FOUNDATIONS
- **Colors:** Academic blue `#0F4C5C` (primary, sidebar, headings of brand), growth green `#10B981` (success, active nav, KPI emphasis, accents), light mint `#A7F3D0`, dark `#1F2937` (text), gray `#6B7280` (secondary text), background `#F8FAFC`. Palette is cool, calm teal-green — no purple, no warm accents except red for "late" and amber for "pending".
- **Type:** Cairo for Arabic (400–800), Inter for Latin and all numerals. Page titles 28 bold; card titles 15 bold; body 14; captions 12. KPI numbers 26 bold Inter, colored blue or green.
- **Spacing:** 4px base; cards padded 20px; grid gaps 14px; page padding 28px. Dense but airy — lots of white card surface on a near-white page.
- **Backgrounds:** app surfaces are flat (`#F8FAFC` page, white cards). Brand/marketing uses dusk campus photography with deep teal overlays and a rising green arrow/bar motif. No textures, no patterns. Gradients only in brand: blue→green, and the sidebar's vertical deep-teal gradient.
- **Graphic elements:** slanted parallelogram stripes (blue/green/mint, ~40° lean) echoing the logo's cut bars. Use as a footer/edge accent, never as a full background.
- **Imagery:** architectural, cool-toned, evening light, teal-graded. Always with a dark teal protection gradient from the text edge.
- **Cards:** white, 1px `#E5E7EB` border, 12px radius, very soft teal-tinted shadow (`--shadow-card`). KPI cards center their content. No colored left borders.
- **Corner radii:** 6px badges, 8px buttons/inputs/nav items, 12px cards, 16px dialogs, 12–20px app icons.
- **Shadows:** all tinted with the primary blue at 4–8% alpha; overlay shadow for dialogs/toasts only.
- **Borders:** hairline gray-200 for cards and tables; gray-300 for secondary buttons.
- **Hover:** darker shade for filled buttons (blue-800), gray-100 fill for ghost/outline, blue-50 row tint in tables, white 8% on dark sidebar.
- **Press:** scale(.98) on buttons. **Focus:** green border + 3px green 25% ring.
- **Animation:** minimal — 120–280ms `cubic-bezier(.2,0,0,1)` fades/color transitions and progress width. No bounces, no parallax.
- **Transparency/blur:** only the dialog scrim (teal 45% + 2px blur) and white-alpha tiles on dark surfaces.
- **Layout:** fixed 240px right-side sidebar (RTL), 64px white topbar with search, notifications, help and user block; content scrolls.
- **Charts:** bars in green/teal alternation, donut blue+green, trend line green with dot markers and faint area.

## ICONOGRAPHY
- The board shows a consistent outline icon set (lightbulb, building, users, graduation cap, user, bar chart, pie chart, settings) at ~1.75px stroke with rounded caps, in academic blue. No icon files were supplied.
- **Substitution:** [Lucide](https://lucide.dev) via CDN (`lucide@0.460.0`), which matches stroke weight and style closely. ⚠️ Flagged — replace if the team has its own set.
- Mapping: المبادرات `lightbulb` · الفروع `building-2` · الشعب `users` · الطلبة `graduation-cap` · المدرسون `user` · التقارير `chart-column` · لوحة التحكم `chart-pie` · الإعدادات `settings` · الرئيسية `house`.
- Icons are blue on light surfaces, white on the sidebar, green when active/positive. Sizes 16 (inline), 18–20 (nav, buttons), 28 (feature). No emoji, no unicode glyph icons.
- Brand marketing uses larger duotone-green outline illustrations (growth chart, people, target) — these are not available as files.

## Logo
Cropped from the brand board (`assets/logo-*.png`) — raster, ~3× upscaled. `logo-reversed.png` / `logo-mark-reversed.png` were recolored (navy → white) from the primary for use on the dark sidebar. **Vector originals are needed.**
