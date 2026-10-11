# Security review — feature 001 (foundation, access, roster)

**Task:** T279 · **Date:** 2026-10-09 · **Scope:** everything shipped by `specs/001-foundation-access-roster`
Phases 1–11: sign-in and accounts, scoping and authorization, the audit log, academic structure,
central and section roster imports, both language surfaces, `/health`.
**Method:** OWASP Top 10 (2021), each category checked against the code and the tests that pin it.
"Evidence" names the test that fails if the control regresses; a control with no test is said so.

## Summary

| | |
|---|---|
| Categories reviewed | 10 of 10 |
| Fixed during this review | 4 (F-01 – F-04) |
| Residual risks | 5 raised (R-01 – R-05): **R-01 closed**, R-02 and R-03 closed in code with one deployment action each, R-04 and R-05 infrastructure (2026-10-09 follow-up) |
| Open defects | 0 |
| `composer audit` / `npm audit` | 0 advisories / 0 vulnerabilities (2026-10-09) |

## Fixed during the review

| ID | Finding | Fix | Evidence |
|---|---|---|---|
| F-01 | The `local` disk had `serve => true`, which registers `GET` and `PUT storage/{path}` over `storage/app/private` — the directory import files live in. Signature-gated and never signed by the application, but a URL into a tree the constitution says has none, and the `PUT` sat outside the CSRF check. | `serve => false` (`config/filesystems.php`). | `CsrfCoverageTest`: "the private file tree has no URL"; "every state-changing route resolves the CSRF middleware". |
| F-02 | The language switch redirected with `back()`, which follows the `Referer` header — caller input. CSRF already stops a cross-site post, so this was hardening, not an exploitable redirect. | Redirects only to a same-host previous URL, else the landing page (`LocaleController`). | `PreferencesTest`: "never to another host". |
| F-04 | *(2026-10-09 follow-up)* The sidebar's sign-out button measured about 1.3:1 — a ghost button's brand-teal label on the teal gradient. axe files text on a gradient as "needs review", so the sweep could not see it. Accessibility rather than security, recorded here because it was found in the same pass. | Sidebar footer buttons take the sidebar text colour. | `axe-all.spec.ts`: "the sidebar's text meets AA against both ends of its gradient". |
| F-03 | No Content Security Policy or companion headers were sent. | `ContentSecurityPolicy` middleware, outermost in the `web` group (T277). | `ContentSecurityPolicyTest` (policy pinned directive by directive, no third-party host, refusal pages covered); `tests/Visual/csp.spec.ts` (zero violations on every screen, both locales). |

## By category

### A01 Broken access control — controlled

- **Scope before policy.** Branch and section scopes are Eloquent global scopes, so a route-bound record
  outside the caller's scope is a 404 before any policy runs — existence is never confirmed (research
  R-01). Evidence: `tests/Scope/*`, `InstructorCannotImportForeignSectionTest`.
- **Every `/api/v1` route authorizes**, by middleware or in the action; self-scoped routes are listed by
  name. Evidence: `tests/Architecture/EveryRouteAuthorizedTest.php`, `tests/Authorization/*` (allow and
  deny per endpoint).
- **Refusals are audited** (`access.denied`) by `AuditDeniedAccess`, which wraps the binding that refuses.
- **Imports re-check authority per chunk**, so an instructor whose section is reassigned mid-import stops
  writing to it (`ImportJobPolicy::run`, `CommitImportChunkJob::stillAuthorised`).
- **Dialog state is rebuilt server-side.** The withdraw and account dialogs reopen from posted ids, never
  from a posted URL (`DialogReturnsWithErrorsTest`: "a forged enrollment … cannot point the form elsewhere").

### A02 Cryptographic failures — controlled, one infrastructure dependency

- Passwords: Argon2id (`config/hashing.php`, `HASH_DRIVER=argon2id`); no plaintext in any column or log
  (`NoSecretsInPayloadTest`).
- Session cookie: `Secure`, `HttpOnly`, `SameSite=Lax` by default (`config/session.php`); HSTS is sent on
  HTTPS responses.
- Encryption at rest and TLS 1.2+ are infrastructure (constitution compliance); see R-04.

### A03 Injection — controlled

- Queries are Eloquent or the query builder. The three raw fragments (`BranchController`
  `count(distinct …)`, `SectionController` `orderByRaw(case …)`, the health probe's `select 1`) are
  constant strings with no input in them.
- Output is escaped: no `{!! !!}` in any view; HTML built in PHP for table cells goes through `e()`.
- Spreadsheet formula injection: exports write every cell as an explicit string cell (`StringCell`),
  so a value beginning `= + - @` or a tab is stored as text and never evaluated as a formula. Evidence:
  `ExportSafetyTest` (one case per leading character).
- Log injection: an upstream `X-Request-Id` is kept only if it matches `[A-Za-z0-9._-]{8,64}`
  (`AssignRequestId`). Evidence: `StructuredLoggingTest`.

### A04 Insecure design — controlled

- The import cannot write core records before a person has seen the preview and confirmed it (research
  R-03); commit is chunked and resumable; a duplicate file is detected by content hash. Evidence:
  `tests/Feature/Imports/*`, `ResumeAfterChunkFailureTest`.
- High-impact actions require a confirmation that restates the impact, and the server enforces the rule a
  confirmation asks for (a withdrawal reason, a term transition reason). Evidence:
  `ConfirmationRequiredTest`, `ValidationStatesTest`.

### A05 Security misconfiguration — controlled, deployment-dependent

- Strict CSP (F-03): `'self'` only; no inline script and no `'unsafe-eval'` — Alpine runs as its CSP
  build (R-01, closed). `X-Content-Type-Options`, `Referrer-Policy`, `X-Frame-Options`, `Permissions-Policy`.
- No self-registration: `/register` is 404. The component gallery and the design-reference routes are
  registered only when `APP_ENV=local`.
- `/health` reports `ok`/`fail` per check and never an error message (`HealthEndpointTest`: "says fail and
  nothing more").
- `APP_DEBUG=true` in `.env.example` is the local default; production must set `APP_DEBUG=false`
  (runbook, pre-flight checklist).

### A06 Vulnerable and outdated components — controlled

- `composer audit` and `npm audit` are clean on 2026-10-09. Versions are pinned in `composer.lock` and
  `package-lock.json`; research R-07 records the confirmed versions.
- No third-party script, stylesheet or font is loaded at runtime — fonts, icons and Alpine are bundled —
  so there is no CDN in the supply chain at all (the CSP enforces it).

### A07 Identification and authentication failures — controlled

- Login throttled at 5/min per email and IP; an account lockout with a countdown; one generic message
  for wrong email, wrong password and unknown account (FR-009, FR-010).
- Session id regenerated on sign-in; 60-minute inactivity expiry; a password change ends the account's
  other sessions (`AuthenticateWebSession`); a deactivated account is refused on every request
  (`EnsureUserIsActive`).
- Password policy: 8+ characters with a letter and a digit, reuse refused.
- Evidence: `tests/Feature/Authentication/*`.

### A08 Software and data integrity failures — controlled, one infrastructure dependency

- No runtime code from another origin (A06). Assets are built in CI from the lockfiles.
- Queued jobs are PHP-serialized into the queue store; anyone able to write to that store can run code
  in a worker. Mitigated by the store being private infrastructure; see R-05.
- The audit log is append-only in three layers (no update path in the model, no `updated_at`, and a
  `REVOKE UPDATE, DELETE` from the application's database user); see R-03.

### A09 Security logging and monitoring failures — controlled, alerting pending

- Every sensitive action and every refusal writes an audit entry inside the action's own transaction
  (`TransactionalWriteTest`, `ActionCoverageTest`).
- Application logs are structured JSON with request id, user id and route (T275); the request id
  follows the request into its queued jobs.
- `/health` covers the database, Redis, queue workers and storage (T276).
- The PRD 8.8 alerts are raised by the application (error rate, failed job, backlog, unreachable queue,
  disk) to a dedicated alert log and `ALERT_EMAIL`; Sentry is installed and inert until its DSN is set
  (R-02).

### A10 Server-side request forgery — not applicable

- The application makes no outbound HTTP request. No URL is accepted from a user and fetched.

## Accepted residual risks

| ID | Risk | Why accepted now | Action and owner |
|---|---|---|---|
| R-01 | ~~CSP `script-src 'unsafe-eval'`, required by Alpine's standard build.~~ **Closed 2026-10-09.** | Alpine now runs as `@alpinejs/csp`; template logic that needed eval moved into components (`resources/js/components/forms.js` and others). | Evidence: `ContentSecurityPolicyTest` pins `script-src 'self'`; `csp.spec.ts` — zero violations and zero Alpine errors on every screen and through every rewritten handler. Keep templates to single expressions. |
| R-02 | ~~No error tracking or alerting.~~ **Closed in code 2026-10-09.** | Alerts: `track:monitor` (every 5 min) and the `JobFailed` listener raise PRD 8.8's conditions to `track.alerts-*.log` and `ALERT_EMAIL` (`MonitoringTest`). Sentry: `sentry/sentry-laravel` installed, PII off. | Set `SENTRY_LARAVEL_DSN` (the PRD's instance is TBD), `ALERT_EMAIL`, and the scheduler cron (runbook §2.3, §7). Operations. |
| R-03 | The audit `REVOKE` can only be verified on MySQL; the migration silently skips it without GRANT OPTION. | The grant is a database-administrator act, and SQLite has no grants. | **Gated 2026-10-09:** `track:audit-grants --strict` runs in every deploy and fails it if the app user can UPDATE or DELETE `audit_logs` (`AuditGrantsCommandTest`); the migration logs CRITICAL on staging/production when its revoke fails. A DBA applies the revoke once (runbook §4). |
| R-04 | Encryption at rest, TLS configuration and backup encryption are outside the application. | Constitution compliance assigns them to hosting. | Confirm with the KSA hosting provider before go-live (runbook §6). Operations. |
| R-05 | Queue payloads are serialized PHP. | Standard Laravel; the store is private. | Redis with a password, bound to the private network, not reachable from the internet (runbook §2). Operations. |

## Re-running this review

```
composer audit && npm audit
php artisan test tests/Feature/Security tests/Architecture tests/Authorization tests/Scope
npx playwright test csp axe-all
```
