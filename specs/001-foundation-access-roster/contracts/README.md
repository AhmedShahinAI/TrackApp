# API Contracts: Foundation — Access Control & Academic Roster

**Feature**: `001-foundation-access-roster` | **Date**: 2026-09-26

All endpoints in this feature are prefixed `/api/v1`, authenticated on the Laravel session
guard via Sanctum (same-origin), and back the Blade screens. Endpoint paths and verbs are taken
verbatim from PRD section 11.3 for the Phase 1 features (P0-F001, P0-F002, P0-F003, P0-F004,
P0-F010) — its 39 endpoints, plus three the design adds: `POST /imports/{id}/resume` (FR-065
resumability), `GET /audit-logs/export` (FR-032 export), and `GET /{entity}/{id}/activity`
(FR-033 per-entity activity), then **Amendment 1 (2026-09-27)** added eight more: five for academic
programs (C-04) and three upload endpoints for the registrar, course, and staff import modes (C-09,
C-05), then **Amendment 2 (2026-09-27)** merged in the student support channel: five unauthenticated
routes and five authenticated ones. **Amendment 4 (2026-09-28)** then added four section-approval routes and amended two existing ones.
**64 distinct routes**, split across six files. `academic.md` carries 27 headings for 25 distinct routes,
because `POST /api/v1/sections` and `GET /api/v1/sections` each appear twice — once in their original form
and once in an "amended" block under Section approval. Both blocks are authoritative together: the
amendment narrows behaviour rather than replacing the endpoint.

| File | Covers | Endpoints |
|---|---|---|
| [auth-users.md](./auth-users.md) | P0-F001 authentication and accounts, P0-F002 roles, branches, access grants | 12 |
| [academic.md](./academic.md) | P0-F003 terms, branches, **programs**, courses, sections, students, enrollments, **section approval** | 25 |
| [imports.md](./imports.md) | P0-F004 the six-step import wizard, **five modes** | 12 |
| [audit.md](./audit.md) | P0-F010 audit log query, export, and per-entity activity | 3 |
| [support-public.md](./support-public.md) | **Amendment 2** — the unauthenticated student support surface | 5 |
| [support-staff.md](./support-staff.md) | **Amendment 2** — the authenticated support queue and handling | 5 |

---

## Conventions

### Response envelope

Single resource:

```json
{ "data": { "id": 1, "...": "..." } }
```

Collection:

```json
{
  "data": [ { "id": 1 } ],
  "meta": { "current_page": 1, "per_page": 50, "total": 1234, "last_page": 25 },
  "links": { "first": "...", "prev": null, "next": "...", "last": "..." }
}
```

Queued acceptance (validation, commit):

```json
{ "data": { "import_job_id": 42, "status": "validating" } }
```

### Error shape

```json
{
  "message": "The given data was invalid.",
  "errors": { "email": ["This email is already in use."] }
}
```

`message` is always localized to the caller's locale. `errors` keys are field paths; values are
arrays of localized strings. No response body ever contains a stack trace, a SQL fragment, an
internal class name, or an id the caller cannot otherwise see (FR-080).

### Status codes — fixed meanings across every endpoint

| Code | Meaning | Applies when |
|---|---|---|
| 200 | Success with a body | Reads, successful updates |
| 201 | Created | Resource creation |
| 202 | Accepted | Work handed to the queue (validate, commit) |
| 204 | Success, no body | Nothing in this feature |
| 401 | Not authenticated | No session, or session expired mid-request (FR-016) |
| 403 | Authenticated, visible record, **missing permission** | Permission check fails on a record the caller can see |
| 404 | Record **not visible to this caller**, or genuinely absent | Out-of-scope id — indistinguishable from absent by design (FR-024) |
| 409 | State conflict | Invalid state transition |
| 422 | Validation failure | Form request rules |
| 423 | Locked | Reserved for closed-term writes — **not emitted in this feature** (deferred; see plan.md) |
| 429 | Throttled | Login 5/min per email+IP; API 120/min per user |

**The 403-versus-404 rule is a contract, not an implementation detail.** Scoping runs as a
query-level global scope, so an out-of-scope id never reaches a policy and 404 is produced by
route-model binding. Any endpoint returning 403 for an out-of-scope record is a defect against
constitution principle I.

### Pagination

Page-based: `?page=N&per_page=M`, `per_page` default 50, maximum 200. Every collection endpoint
supports it.

### Localization

`Accept-Language` is ignored; locale comes from the authenticated user's `locale` column so
that the API and the screens always agree. All human-readable strings in responses —
`message`, validation errors, import row reasons, status labels — are localized server-side.

### Scope behaviour, per role

| Role | Sees |
|---|---|
| `SUPER_ADMIN` | Everything (`scope.bypass_all`) |
| `BRANCH_COORDINATOR` | Only records belonging to their `coordinator_branches` |
| `INSTRUCTOR` | Only sections where they are `instructor_id`, plus sections in `section_access_grants`; students and enrollments reachable through those sections |

This applies identically to every read endpoint, every list, and every export. An export can
never contain a record the same caller cannot retrieve individually (FR-023).

### Audit

Every endpoint below marked **Audited** writes exactly one `audit_logs` row inside the same
transaction as its effect, carrying old and new values (FR-028, FR-029). If that write fails,
the endpoint returns 500 and the effect is rolled back (FR-030).

---

## Testing requirement (contractual, not advisory)

Constitution principle VIII makes these part of the contract. For **every** endpoint below:

1. An **allow** test: a role holding the required permission, in scope, succeeds.
2. A **deny** test: a role lacking the required permission receives 403.
3. For every endpoint bound to a scoped model, an **out-of-scope** test: a caller from another
   branch or another instructor's section receives **404**, and the attempt appears in
   `audit_logs` as `access.denied`.

An endpoint merged without all applicable cases is incomplete. A Pest architecture test
enumerates the registered `/api/v1` routes and fails the build on any route with no
corresponding authorization test and no authorization middleware.

---

## Amendment 2 — the public surface

The support channel introduces the platform's **first unauthenticated routes**. Constitution v1.1.0's
"Public endpoints" subsection permits them only where they create no account and expose no record, and
names the support form and its status check as the only such functions.

Two contracts in [support-public.md](./support-public.md) are therefore binding in a way nothing else
here is:

- **The anti-enumeration contract.** Three submission failures and two retrieval failures must be
  indistinguishable in body, status, headers, **and timing**. The one deliberate exception is a
  University ID format failure, which names the expected format because the format is published.
- **The bounded surface.** `routes/public.php` holds all five public routes, and
  `tests/Architecture/PublicSurfaceBoundedTest.php` fails the build on a sixth.
