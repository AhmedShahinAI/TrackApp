# Architecture decisions — feature 001 (foundation, access, roster)

**Task:** T281 · **Why this file exists:** PRD risk R-05, a single-developer dependency. Each decision
says what was chosen, why, and what would break if someone "simplified" it. The full reasoning is in
`specs/001-foundation-access-roster/research.md` and `plan.md`; this is the version a new engineer reads
first. **Last updated:** 2026-10-09.

## Shape of the application

- **Laravel 13 / PHP 8.4, modular monolith.** Domain code lives in `app/Modules/<Module>/` (Actions,
  Http, Jobs, Policies, Routes, …); `ModuleServiceProvider` loads each module's routes. Models stay in
  `app/Models` because several modules share them.
- **Blade + Alpine + Bootstrap 5.3**, two compiled stylesheets (RTL and LTR). No SPA: the server renders
  every screen, Alpine adds behaviour.
- **One controller per resource answers both the browser and the API** (`RespondsToBoth`). A form posts
  to the `/api/v1` route; a browser gets a redirect with flashed errors, a JSON caller gets JSON. So the
  API is not a second implementation that can drift from the screens.
- **`/api/v1` runs on the web stack** (session + CSRF + Sanctum's session guard), because its caller is
  this application's own pages.

## Research decisions (research.md)

| ID | Decision | Why | Do not |
|---|---|---|---|
| R-01 | Branch and section scoping are Eloquent **global scopes**; route-model binding resolves through them, so an out-of-scope id is a **404 before any policy runs**. Policies only ever answer 403. | A policy deny is a 403, and a 403 confirms the record exists. New endpoints inherit the rule. | …check scope in a policy, or `withoutGlobalScopes()` outside `scope.bypass_all`. |
| R-02 | `Auditor::record()` is a plain insert **in the caller's transaction**; no `afterCommit`, no queue. `audit_logs` has no `updated_at`; a migration revokes UPDATE/DELETE from the app user. | Constitution II: a failed audit write must abort the action. The revoke makes append-only true against a compromised application. | …queue audit writes or add an "edit entry" path. See runbook §4. |
| R-03 | Imports are six steps: upload → mapping → validate (queued, streamed into `import_rows`) → preview → commit (500-row chunks, each its own transaction, checkpoint written last) → report. | Nothing reaches core tables before a person has seen the preview (FR-060); a failed chunk resumes from its checkpoint (FR-065). | …write core records during validation, or commit in one transaction. |
| R-04 | Duplicates are refused by a **unique index first**, an application check second; a unique violation is caught per row at commit. | Application-only checks race. | …drop an index because "the code already checks". |
| R-05 | Two Vite entries, `app-rtl.scss` and `app-ltr.scss` (tokens → bridge → Bootstrap → ours); `SetLocale` sets `<html lang dir>`. Fonts Cairo + Inter, self-hosted. | Two builds are simpler and faster than flipping direction at runtime. | …add `[dir=rtl]` overrides; use logical properties. |
| R-06 | The EAV storage model is spiked against 1.5 M values with p95 thresholds before Phase 2 depends on it. | PRD R-03 makes the numbers a gate. | …build on EAV before `spike-eav-results.md` says PASS. |
| R-07 | Versions confirmed and pinned (PHP 8.4.26, Laravel 13.34, Pest 5.3, openspout 5.3, Bootstrap 5.3.8, Alpine 3.17). Structured JSON logging ships in this feature; Sentry later. | Pest 5 needs PHP 8.4; production must match. | …deploy on PHP 8.3 (plan.md and PRD §9.5 are stale on this). |
| R-08 – R-12 | Public support channel (Amendment 2): self-hosted proof-of-work challenge, two rate limits, constant-time non-enumeration, unguessable reference numbers, 2-year anonymisation. | PDPL and the strict CSP rule out hosted CAPTCHAs; the natural implementation leaks roster membership. | See research.md; these land in Phases 18–25. |

## Design-system decisions (plan.md)

1. **React is the reference, Blade is production.** The kit in `design-system/` is vendored read-only
   and never shipped; 24 `x-track-*` Blade components re-author it. Visual baselines hold the reviewed
   Blade result (a pixel diff against the kit measures its harness, not the product).
2. **Tokens define, Bootstrap consumes.** Bootstrap's Sass variables are set from the tokens in
   `_bootstrap-bridge.scss`; no Bootstrap default may override a token. `TokenParityTest` and
   `NoHardcodedVisualValuesTest` enforce it.
3. **Cairo replaces IBM Plex Sans Arabic** (PRD §9.1 is stale; ui-conflicts C-03).
4. **Fonts and icons are self-hosted** — required by the strict CSP and by PDPL. Nothing loads from a
   CDN; the CSP would refuse it.
5. **Accessibility corrections to the approved palette**, darkened within the same hue; `#10B981` stays
   the brand green for non-text use. Seven at planning, nine after Phase 3's axe sweep
   (`specs/001-foundation-access-roster/ACCESSIBILITY-DELTA.md`).
6. **Out-of-phase screens are specified now, built in their phase.**

## Decisions made during implementation

| Area | Decision | Why |
|---|---|---|
| Formatting | Every number, date and term reaches a screen through `App\Modules\Shared\Support\Format`: Western digits, `DD/MM/YYYY`, Riyadh time (`app.display_timezone`; stored UTC), date-times isolated left-to-right (LRI…PDI), terms as "2026/2027 - الأول". | FR-084, and one place to change it. The LTR isolate stops Arabic paragraphs painting time before date. |
| Dialogs | A dialog form posts `_dialog`; a refused submission reopens that dialog with its errors (`x-track-dialog`). Record-specific dialogs rebuild their target from an id on the server. | The redirect-back pattern otherwise reloads with every dialog shut and the error invisible. |
| Import concurrency | Job status changes are compare-and-set; chunk jobs lock the job row; a worker silent for 15 minutes is treated as gone; every chunk re-checks the importer's authority. | Two workers, a re-click, or a reassigned section must not double-write. |
| Queue timing | `retry_after` 900 s > worker `--timeout` 870 s > longest job 840 s (runbook §2.2). | Otherwise a slow validation is handed to a second worker mid-run. |
| Logging | JSON lines with request id (Laravel `Context`, so it follows queued jobs), user id, route (`app/Modules/Shared/Logging`). | PRD 8.8; one id joins a request to the jobs it queued. |
| Monitoring | `track:monitor` every five minutes (error rate from per-minute request counts, queue backlog, disk) and a `JobFailed` listener raise PRD 8.8 alerts to `track.alerts-*.log` and `ALERT_EMAIL`, once per condition per 30 minutes; Sentry is installed, inert until its DSN is set. `track:audit-grants --strict` is a deploy step. | Signals must reach a person without anyone reading logs; a stuck queue must be one alert, not hundreds. |
| Health | `/health` outside the `web` group: no session per probe; `ok`/`fail` only, reasons to the log; workers judged by heartbeat **and** backlog age. | A load balancer probe must be cheap and must not leak internals. |
| CSP | `ContentSecurityPolicy` outermost in `web`: `'self'` only, no `'unsafe-eval'`, style attributes allowed via `style-src-attr`. Alpine runs as **`@alpinejs/csp`**, so a template holds one expression — no statements, arrow functions, template literals, `?.`/`??` or browser globals; anything more is a component method (`resources/js/components/forms.js`), and object data reaches a component through `data-*` attributes, never `@js([...])` (which compiles to `JSON.parse`). | Refusal pages need the header too. The CSP build is what lets script-src be `'self'` alone; `csp.spec.ts` fails on any Alpine expression error. |
| Tests as gates | Architecture tests (every API route authorized, no hard-coded strings or visual values, lang parity), the PHP screen sweeps (both directions, no untranslated output, every dialog's error state), and the browser sweeps (axe on every screen and dialog, RTL geometry, CSP violations, keyboard). | Each guards a rule that is invisible in code review and obvious only when it breaks. |

## Where to start reading

`bootstrap/app.php` (middleware order — the comments explain why each sits where it does) →
`app/Modules/Imports` (the most intricate module) → `tests/Support/Screens.php` (the screen registry
both the PHP and browser sweeps use) → `docs/runbook-deploy.md` → `docs/security-review-001.md`.
