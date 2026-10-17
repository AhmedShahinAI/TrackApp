# Security review — the student support channel (Amendment 2)

**Task:** T460 · **Date:** 2026-10-13 · **Scope:** everything Amendment 2 added in Phases 18–25: the
public form, its challenge, the confirmation, the status lookup, the staff queue and detail, and the
retention sweep. This is the platform's first unauthenticated surface.
**Method:** OWASP Top 10 (2021), with each category checked against the code and the tests that pin it,
as in [security-review-001.md](./security-review-001.md). Particular attention goes to the
**enumeration channels** and the **accepted impersonation risk** (spec "Accepted Risks").
"Evidence" names the test that fails if the control regresses. A control with no test is marked as such.

## Summary

| | |
|---|---|
| Categories reviewed | 10 of 10 |
| Enumeration channels reviewed | 8 (body, status, headers, timing, rate limits, side effects, the staff side, retention) |
| Fixed during this review and Phase 25 | 6 (F-01 – F-06): three by the review, three by the Phase 25 code review |
| Residual risks | 6 (R-01 – R-06): one accepted by the spec, three for the owner and the data-protection officer, two infrastructure |
| Open defects | 0 |
| `composer audit` / `npm audit` | 0 advisories / 0 vulnerabilities (2026-10-13) |

## Fixed during the review

| ID | Finding | Fix | Evidence |
|---|---|---|---|
| F-01 | The retention sweep must clear `submitted_ip` (R-12), but the column was `NOT NULL`. A sweep written against it would have had to store an empty string, a "recorded, and blank" value that a later reader could not tell from a defect. | Migration `2026_10_13_000001` makes the column nullable. Creation still always sets it (FR-123). The detail screen shows a dash for a cleared address. | `AnonymiseTest`: "a request past its retention loses its student, contact and address, and nothing else". |
| F-02 | The sweep's own audit entry could have copied the cleared values into `old_values`, which would keep the IP and the student for the audit log's five years: the retention the sweep exists to end. | `support.anonymised` carries no old values; its new values are only `anonymised_at` and `channel: system`. | `AuditSurvivesAnonymisationTest`: "the sweep writes one system entry that does not keep what it removed". |
| F-03 | The confirmation page shows the reference, the student's only credential, with Laravel's default `Cache-Control: no-cache, private`. That still lets a browser keep the page on disk, so the next person at a shared lab machine could read it back from the cache. The status result already sent `no-store`. | `SupportRequestController::submitted` sends `no-store`. | `SubmitRequestTest`: "the reference is shown once, from the flash, and never in the URL". |
| F-04 | *(Phase 25 code review, H-1.)* The sign-in screen read `support_channel_enabled` without a guard. With the settings cache empty and the database unreachable, or the table not yet migrated on a fresh deploy, **the sign-in page returned 500**. Before T463 it survived this, because `SetLocale` wraps its own settings read on purpose. | `Settings::enabled()` reads as off when settings cannot be read. | `SignInLinkTest`: "the sign-in page renders without the link when settings cannot be read at all" (a mutant without the guard fails it). |
| F-05 | *(Phase 25 code review, H-2.)* One request the sweep could not save stopped the whole sweep. Rows go in id order, so it failed first every day and every expired request after it was kept forever. One way to reach this was a row breaking a staff-facing model rule, such as "rejected needs a reason". | Each row's failure is reported and the sweep continues. The run then fails as a job, which alerts. The write is `saveQuietly()`, so staff rules cannot block a legal retention write. | `AnonymiseTest`: "one row that cannot be anonymised does not stop the rest…", "a row that bypassed a staff rule is still anonymised" (two mutants fail them). |
| F-06 | *(Phase 25 code review, M-1.)* A sweep that never ran raised nothing: a stopped scheduler or stopped workers failed SC-037 in silence. | `track:monitor` raises `support_retention_overdue` when any request is 2 days past its retention, using the new `(anonymised_at, created_at)` index. | `MonitoringTest`: "a support request kept past its retention says the sweep has stopped". |

## Enumeration — can the channel tell anyone whether a University ID is enrolled?

The binding contract (contracts/README.md) is that it cannot. Each channel through which an answer
could leak:

| Channel | Control | Evidence |
|---|---|---|
| Response body, status, headers | A missing, unenrolled or withdrawn student gets one refusal, byte for byte. So does every lookup failure: missing, mismatched, anonymised, or a wrong check character. | `Enumeration/SubmissionFailuresIdenticalTest`, `Enumeration/RetrievalFailuresIdenticalTest` (with a control case that proves the comparison can see a difference) |
| Timing | Both padded routes are held to `support_min_response_ms`, found or not. Verification is one query of one shape for every failure. Work that outruns the floor is logged, so a floor set too low is visible. | `Enumeration/ResponseTimeFloorTest`, `RetrievalFailuresIdenticalTest` ("the same database work"), `SecurityServicesTest` |
| Rate limits | The identifier is counted after the challenge and field rules, but **before** the roster is checked (`SubmitSupportRequestRequest`). So an unenrolled ID spends its limit exactly like an enrolled one, and the 429 is no oracle. Both refusals share one body and one `Retry-After`, and no response carries `X-RateLimit-*`. Limiter keys are an HMAC of the ID, never the ID itself. | `Throttle/ThrottleMessageHidesWhichLimitTest`, `Throttle/IdentifierThrottleTest`, `SecurityServicesTest` ("the identifier never appears in a limiter key") |
| Status-lookup limits | The per-reference counter counts references that do not exist exactly like ones that do. Only a found request is refunded, and finding it already discloses the result to its owner. | `Throttle/StatusThrottleTest` |
| Side effects | A refused submission writes no row, no audit entry and no session state that a later request could read. | `SubmissionFailuresIdenticalTest` ("none of the three leaves a trace"), `AuditOnPublicSubmissionTest` |
| Format errors | A malformed ID is told the expected format **before** the roster is queried, so the message depends only on what was typed. Arabic-Indic digits are well formed. | `FormatFailureIsSpecificTest` |
| The staff side | A request outside a coordinator's branches is a 404, the same as a reference that does not exist (scope before policy). A coordinator naming another branch in a filter gets their own scope back, not an answer to the probe. | `tests/Scope/*`, `QueueFiltersTest`, `Authorization/SupportEndpointsTest` |
| Retention | An anonymised request's old reference and ID fail exactly as a reference that never existed. | `Retention/AnonymisedVisibilityTest` |

**Result: no channel found.**

## The accepted impersonation risk

Anyone who knows a valid University ID can raise a request in that student's name. The roster holds no
second factor to check a claimant against (spec "Accepted Risks"). The review confirms that the
mitigations the spec lists are in place:

- **Limits on both axes:** per address (IPv6 /64 as one address) and per identifier. Evidence: the
  `Throttle/*` tests.
- **The person-versus-script challenge:** self-hosted proof-of-work. It fails closed, its solutions are
  single-use, it is never cached, and its difficulty is held server-side. Evidence: `ChallengeTest`,
  `SecurityServicesTest`, `Architecture/ChallengeSolverIsSelfHostedTest`.
- **The address is recorded** on every accepted request and in its creation entry. Evidence:
  `AuditOnPublicSubmissionTest`.
- **Nothing readable behind the channel.** The status page shows only the request's own state and
  replies. Evidence: `StatusExposesNothingElseTest`.
- **The contact is unverified.** It is always shown as unverified, in words. Evidence:
  `ContactMarkedUnverifiedTest`.

One consequence the spec does not spell out is recorded as R-02 below: what staff **write back** is
readable by whoever raised the request.

## By category

### A01 Broken access control — controlled

- **The public surface is bounded:**
  - six routes for the five entries in contracts/support-public.md, all on the `public` group;
  - a seventh route fails the build;
  - every unauthenticated route outside that group is a 001 sign-in or infrastructure route.
  - Evidence: `Architecture/PublicSurfaceBoundedTest`, `Architecture/PublicSurfaceTest`.
- **Nothing public creates an account,** a credential or a signed-in session (FR-124). `students` has
  exactly its roster columns, and no migration alters it. Evidence: `SubmitRequestTest`,
  `Architecture/NoStudentAuthColumnsTest`.
- **Staff routes:**
  - scope before policy, so a request outside the caller's scope is a 404;
  - instructors reach nothing;
  - only a Super Admin closes or reassigns;
  - a request is never given to someone who could not open it.
  - Evidence: `FoundationModelsTest`, `StateMachineTest`, `ReassignTest`,
    `Authorization/SupportEndpointsTest`.
- **Staff URLs carry the reference, never the sequential id.** Evidence:
  `OpenTransitionsToInProgressTest`.
- **There is no file upload route,** and nothing accepts PUT, PATCH or DELETE publicly. Evidence:
  `NoAttachmentRouteTest`.

### A02 Cryptographic failures — controlled

- **References** are 50 random bits from a CSPRNG plus a check character, not time-ordered or
  sequential (R-11). Evidence: `ReferenceGeneratorTest`.
- **Limiter keys** are an HMAC under the application key, so a nine-digit ID cannot be recovered by
  brute-forcing a plain hash.
- **The reference is never placed in a URL.** It is flashed once to the confirmation page, which sends
  `Cache-Control: no-store, private` (F-03), as the status result does. Evidence: `SubmitRequestTest`, `StatusRetrievalTest`.

### A03 Injection — controlled

- **Descriptions, contacts, replies and student names are escaped** on every screen:
  - there is no `{!! !!}` under `resources/views/support`;
  - the queue builds its cell markup from `e()`'d parts.
  - Evidence: `NoMarkupInterpretedTest`, `StatusExposesNothingElseTest` ("a reply full of markup reaches
    the student as text"), `Architecture/NoRawOutputInSupportViewsTest`.
- **Queries go through Eloquent or the query builder.** The queue's search binds its term.

### A04 Insecure design — controlled, one accepted risk

- **Threat model:** enumeration, impersonation and flooding are each designed for (sections above).
- **Impersonation is accepted by decision** (R-01).
- **The state machine is closed:** a transition succeeds exactly when the table permits it, and
  rejection needs a reason. Evidence: `StateMachineTest`, `RejectRequiresReasonTest`.

### A05 Security misconfiguration — controlled, deployment-dependent

- **The public pages run in the `web` group**, with CSRF via a guest session, the CSP, and no new CSP
  exemption (the proof-of-work worker is a same-origin file). Evidence:
  `Security/NoNewCspExemptionTest`, the axe and CSP Playwright specs.
- **Proxy trust:** a wildcard `TRUSTED_PROXIES` is refused. Without proxy trust the whole institution
  shares one address (runbook §6a). Evidence: `SecurityServicesTest`.
- **Settings fail safe:**
  - a malformed limit throws rather than reading as zero or unlimited;
  - a floor of 0 keeps the documented floor;
  - a retention period below one month refuses to run.
  - Evidence: `SecurityServicesTest`, `Retention/AnonymiseTest`.
- **The sign-in link to `/support` is off by default** (`support_channel_enabled`), so opening the
  channel is a decision, not a side effect of deploying. Evidence: `SignInLinkTest`.

### A06 Vulnerable and outdated components — controlled

- `composer audit`: no advisories. `npm audit`: 0 vulnerabilities (2026-10-13).
- ALTCHA was evaluated and **not** used: its `blob:`/`data:` workers would need a CSP exemption
  (research R-08). The proof-of-work code is first-party.

### A07 Identification and authentication failures — controlled by design

- **Students do not authenticate; this is the design** (spec, FR-124). The identifier and the
  reference are the only factors, and each is rate-limited separately: per address, per reference, and
  per identifier.
- **A signed-in staff session on the same browser** does not narrow or widen verification. Evidence:
  `SecurityServicesTest`.
- **Staff authentication is 001's, unchanged.**

### A08 Software and data integrity failures — controlled

- **Responses are append-only** at the schema (no `updated_at`) and in the model. A reference never
  changes. Evidence: `FoundationModelsTest`.
- **The audit trail survives anonymisation unchanged** (FR-160). Evidence:
  `Retention/AuditSurvivesAnonymisationTest`.
- **A submission whose audit entry cannot be written is not recorded** (constitution II). Evidence:
  `AuditOnPublicSubmissionTest`.

### A09 Security logging and monitoring failures — controlled

- **Every state change is audited** with its contract key and actor:
  - `support.request_created` (null actor, `channel: public`);
  - `support.responded`, `support.status_changed` and `support.reassigned`;
  - `support.anonymised` (null actor, `channel: system`).
  - Evidence: `Audit/ActionCoverageTest` (all contract keys now performed), `Support/AuditCoverageTest`.
- **Status lookups are deliberately not audited:** an entry would carry the identifier and become a
  disclosure surface. The rate limiter is the control.
- **Padding overruns are logged**, at most once a minute.

### A10 Server-side request forgery — not applicable

The channel makes no outbound request. The challenge is issued and verified locally.

## Residual risks

| ID | Risk | Status | Owner / action |
|---|---|---|---|
| R-01 | **Impersonation.** Anyone with a valid University ID can raise a request in that student's name. | Accepted (spec "Accepted Risks"). Revisit when university SSO is available. | Product |
| R-02 | **Replies reach whoever raised the request.** The status page shows every staff reply to anyone holding the reference and the ID, and under R-01 that may not be the student. A reply that volunteers personal data (a grade, a contact, another person's details) discloses it to an impersonator. The contact the "student" left is unverified and may be the impersonator's. | Open, procedural. The UI marks the contact unverified, but nothing in the composer warns about reply content. | Support staff guidance: answer the request as asked and never add personal data to a reply. Consider a one-line notice beside the composer. |
| R-03 | **The creation audit entry outlives anonymisation.** For the audit log's 5 years (FR-160), `support.request_created` holds `student_id` and the submitting IP **in its payload**, by contract. It also holds the student's address and user agent **in the row's own `ip_address` and `user_agent` columns**, because `Auditor` records the caller of every entry and the caller here is the student. So for about three years after the sweep, anyone who can read the audit log can link an anonymised request back to its student. **In practice, that largely defeats FR-159 for that period.** Redacting the payload alone would not end it, because the columns would remain. | By design (FR-160, R-12), but the trade-off is not stated in the spec. | Owner and data-protection officer. Smallest options: **(a)** when the sweep runs, clear the payload's `student_id` and `submitted_ip` and the row's `ip_address` and `user_agent` on that one creation entry. This edits the audit log, so FR-160 needs an amendment; **(b)** at creation, record a keyed, non-reversible token in place of the IP, both in the payload and in the column, and drop `student_id` from the payload. The entry still proves the request existed. Throttling never read the audit log, so it is unaffected; **(c)** accept it, and record why in the spec. |
| R-04 | **The fail-closed challenge depends on Redis.** A Redis outage closes the channel (FR-129) rather than opening it. | Accepted. It is an availability cost, not a security one. | Infrastructure: Redis monitoring (runbook §6a, §7). |
| R-05 | **A distributed flood.** Per-address limits and the padding cap protect the PHP pool from one source. Many sources, each under its limit, can still exhaust it; the cap answers them with a fast 503, which reveals nothing. | Infrastructure. | WAF or edge rate limiting in front of `/support` (runbook §6a). |
| R-06 | **The description is kept indefinitely.** The sweep clears the contact and keeps the description, as FR-159 is written. But the description is the student's own free text, and may hold their name, number or circumstances: the very data R-12 cites PDPL for. | Open. Spec-conformant, but in tension with R-12's reasoning. | Owner and data-protection officer. **(a)** keep it, for the trend analysis PRD §12.4 relies on; **(b)** clear it in the sweep as a fourth column, keeping the category for trends. This is one line in `AnonymiseExpiredRequests`, and FR-159 changes. |

Backups hold cleared columns until the backups themselves age out (30 days). Runbook §5a says a
restore older than the last sweep must be followed by a sweep run.

## Re-running this review

Re-run it when a route is added to `routes/public.php` (which needs a constitution amendment first),
when verification or the limits change, or when university SSO lands (R-01). The quick check is:

```sh
php artisan test --testsuite=Enumeration
php artisan test tests/Feature/Support tests/Architecture
composer audit && npm audit
```
