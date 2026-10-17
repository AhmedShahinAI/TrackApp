# Phase 0 Research: Foundation — Access Control & Academic Roster

**Feature**: `001-foundation-access-roster` | **Date**: 2026-09-26

The technology stack itself was not researched — it is mandated by the constitution and
PRD section 9, and treating it as open would be a waiver, not a decision. What follows
resolves the mechanism-level questions that the mandate leaves open, plus the two items in
Technical Context that need confirmation at kickoff.

---

## R-01: Enforcing 404-not-403 for out-of-scope records

**Decision**: Apply scoping as an Eloquent **global scope** and let route-model binding
resolve through it. An out-of-scope id then throws `ModelNotFoundException`, which Laravel
renders as 404 before any policy executes. Authorization policies handle only the
*permission* question and therefore only ever produce 403 for a record the caller can see.

Two traits carry this:

- `BelongsToBranchScope` — adds a global scope constraining the model's `branch_id` (directly
  or through its `section` relation) to `auth()->user()->coordinatorBranchIds()`.
- `BelongsToSectionScope` — constrains to `auth()->user()->accessibleSectionIds()`, the union
  of sections where the user is `instructor_id` and sections named in `section_access_grants`.

Both are inert for a user holding the `scope.bypass_all` permission (Super Admin), and inert
when no user is authenticated (console, queue, seeders) — queue jobs that must stay scoped
pass an explicit actor instead of relying on ambient state.

**Rationale**: Ordering is the whole point. If a policy runs first, a `deny` yields 403,
which confirms the record exists and lets a coordinator enumerate other branches' section
ids. Scoping the query first makes non-existence and non-visibility indistinguishable, which
is exactly constitution principle I. Global scopes also mean a developer adding an endpoint
next month inherits the behaviour without knowing the rule exists.

**Alternatives considered**:
- *Per-controller `where` clauses*: rejected outright by constitution I; every new endpoint is
  a fresh chance to forget.
- *Policy returning 404 via `Response::denyAsNotFound()`*: works, but requires a policy on
  every model-bound route and gives no protection to list queries, searches, or exports,
  which is where a leak is most likely.
- *Database views per role*: rejected as unmaintainable against a dynamic branch list and
  incompatible with Eloquent relations.

**Verification**: `tests/Feature/Scope` asserts 404 for a cross-branch id through the API,
the Blade route, the list endpoint, and the export endpoint; and asserts a *visible* record
with a missing permission yields 403, so the two codes are not conflated.

---

## R-02: Writing the audit entry inside the caller's transaction

**Decision**: One `Auditor` service with a single `record()` method that performs a plain
insert on the current connection — no `afterCommit`, no queue, no separate connection.
Audited writes are wrapped by the calling action in `DB::transaction()`. Model observers call
`Auditor::record()` for CRUD-shaped events; service-layer actions call it directly for events
that are not a single model write (login, password reset, access denied, import run).

`audit_logs` gets no `updated_at` column, no Eloquent model with a fillable update path, and a
migration that issues `REVOKE UPDATE, DELETE ON <database>.audit_logs FROM '<app_user>'@'%'`.
The revoke is guarded so it is a no-op when the connection lacks GRANT privileges (local
developer databases, CI), and a Pest test asserts the grant state in staging and production
environments.

**Rationale**: Constitution II requires that a failed audit write abort the action. Anything
deferred — `afterCommit` hooks, queued audit jobs, a second connection — completes the
business write and loses the entry, which is the failure mode the principle exists to
prevent. The grant revocation is what makes "append-only" true against a compromised
application rather than merely uncoded.

**Alternatives considered**:
- *Queued audit writes*: rejected; decouples exactly what must not be decoupled.
- *MySQL triggers writing the audit row*: rejected; cannot see the acting user, the request's
  IP, or the user agent, and duplicates business rules in SQL.
- *`spatie/laravel-activitylog`*: rejected; its default write path is not guaranteed to be
  inside the caller's transaction and its schema does not carry IP and user agent as
  first-class columns. Wrapping it to force both would be more code than the direct service.

**Verification**: A Pest test opens a transaction, forces the audit insert to fail (temporary
constraint violation), and asserts the business row is absent afterwards. A second test
asserts `audit_logs` has no route, no Eloquent `update`/`delete` path, and — where privileges
allow — that a direct UPDATE is refused by the engine.

---

## R-03: Six-step import — staging, chunking, and resumability

**Decision**: Parsed rows never go straight to core tables. The pipeline is:

1. **Upload** — validate extension, MIME, and magic bytes; store on the private disk;
   compute a SHA-256 `file_hash`; create `import_jobs` in `uploaded`.
2. **Mapping** — stream the header row only; propose a mapping by normalized column-name
   match; persist the confirmed mapping as JSON on the job.
3. **Validate** (queued `ValidateImportJob`) — stream the file with OpenSpout, writing one
   `import_rows` staging row per source row carrying the raw values, the resolved foreign
   keys where resolvable, a per-row `status` of `valid`/`warning`/`error`, and a JSON list of
   reasons. In-file duplicate detection uses a running hash set of the row's identity key.
4. **Preview** — read counts and the first 100 rows straight from `import_rows`. No core
   table has been touched.
5. **Commit** (queued `CommitImportChunkJob`, self-chaining) — process `import_rows` ordered
   by id in 500-row chunks, each chunk in its own `DB::transaction()`, using
   `firstOrCreate`-style resolution so existing records link rather than duplicate. Each
   chunk's transaction updates `import_jobs.last_committed_row_id` as its final statement.
6. **Report** — counts from `import_rows` aggregated by status; the error report streams from
   the same table.

Resumability falls out of step 5: a failed chunk leaves `last_committed_row_id` at the last
*successful* chunk because the checkpoint shares the chunk's transaction, so a resume simply
restarts from `id > last_committed_row_id`.

**Rationale**: Spec FR-060 forbids any core write before preview confirmation, and FR-065
requires resumability with committed chunks intact. Both are satisfied structurally rather
than by careful coding. Staging also makes the preview and the error report cheap reads
instead of a second parse, and keeps the 5-minute budget for 10,000 rows achievable.

**Alternatives considered**:
- *Validate in memory and hold results in cache*: rejected; 50,000 rows of row-level detail is
  not a safe cache payload, and a worker restart loses the preview the user already approved.
- *`maatwebsite/excel` chunked reading straight into core tables with a rollback on error*:
  rejected; violates FR-060, and a single 50,000-row transaction is not viable on InnoDB.
- *Batch jobs via `Bus::batch()` for the chunks*: kept as an option for progress reporting,
  but chunk order matters for the checkpoint, so the self-chaining single job is the primary
  design. `openspout` is used directly for reading because `maatwebsite/excel` wraps it and
  the streaming reader is what the row budget needs; `maatwebsite/excel` is retained for
  *writing* the error report, where its styling is useful.

**Verification**: Tests for each of the seven validation categories in FR-057; a test that
kills a chunk mid-run and asserts committed rows survive and the resume completes the rest; a
re-upload test asserting zero new records on a matching `file_hash`; and a `performance`-group test
timing 10,000 rows end to end.

---

## R-04: Duplicate detection at four levels

**Decision**: Each level is enforced by a database unique index first and an application
check second, and the application check exists only to produce a readable row-level reason:

| Level | Index |
|---|---|
| University ID | `students.university_id` unique |
| Section key | `sections(term_id, branch_id, course_id, section_number)` unique |
| Student + Section | `section_enrollments(student_id, section_id)` unique |
| Student + Assignment | `initiative_targets(assignment_id, student_id)` unique (schema created here; exercised in Phase 2) |

Commit-time resolution catches the unique-violation exception per row within the chunk
transaction, converts it to an `import_rows` error reason, and continues — a single bad row
does not fail its chunk.

**Rationale**: Constitution's data-model constraints require index-level enforcement, not
application checks alone; concurrent imports make application-only checks racy. Catching the
violation per row rather than pre-checking avoids a second query per row across 50,000 rows.

**Alternatives considered**: *Pre-flight `SELECT` per row* — rejected on cost at the stated
volume; the staging pass already resolves what it can, and the index is the authority.

---

## R-05: Bilingual RTL/LTR asset and layout strategy

**Decision**: Two Vite entry points, `resources/css/app-rtl.scss` and `app-ltr.scss`, each
importing the design-system token layer and `_bootstrap-bridge.scss` **before** the corresponding
Bootstrap 5.3 build (`bootstrap.rtl` for Arabic), so tokens always win. A `SetLocale` middleware
resolves locale from the authenticated user's `locale` column, falling back to the seeded default
setting, and the layout emits
`<html lang="{{ app()->getLocale() }}" dir="{{ LaravelLocalization::direction() }}">` and
loads the matching stylesheet.

> **Amended 2026-09-27** — fonts are **Cairo** (Arabic and all UI) and **Inter** (Latin and all
> numerals), self-hosted, per the approved design system. This supersedes the original decision of
> IBM Plex Sans Arabic, which came from PRD §9.1; see [ui-conflicts.md](./ui-conflicts.md) C-03 and
> [design-system.md](./design-system.md) §3. The token bridge described above is also an amendment
> — the original decision predates the design system.

String discipline is enforced by two architecture tests: one diffs the key sets of `lang/ar`
and `lang/en` recursively and fails on any key present in one and not the other; another
scans Blade files for text nodes that are not inside `__()`, `@lang`, or an allowlist of
non-translatable tokens (numbers, punctuation, component slots).

**Rationale**: Constitution VI makes AR and EN equal, and the PRD makes an Arabic rendering
defect a release blocker. Two builds are simpler and faster than one build with runtime
direction flipping, and the parity test converts "remember to translate" from a review
concern into a build failure.

**Alternatives considered**:
- *Single stylesheet with CSS logical properties only*: attractive, but Bootstrap 5.3's own
  RTL build already exists and mixing approaches produces inconsistent mirroring in
  third-party components (Tabulator in Phase 2 especially).
- *`mcamara/laravel-localization` for URL-segment locales*: rejected for this internal tool;
  locale is a per-user property, not a URL dimension, and URL locales would double every
  route. The direction helper is a small internal helper instead.

---

## R-06: Typed EAV performance spike — method and thresholds

> **Result (2026-10-09): PASS on SQLite, PROVISIONAL** — all five thresholds met with a 59× margin;
> the deciding MySQL run is outstanding. Measurements, plans, findings and the index recommendation:
> [spike-eav-results.md](./spike-eav-results.md). Note on method: "cold" runs need the engine's cache
> emptied (`EAV_SPIKE_COLD_COMMAND`); a fresh connection alone gives warm-cache numbers.

**Decision**: An `EavSpikeSeeder` generates, without Eloquent, a realistic shape:
1 term, 6 branches, 3,000 sections, 30,000 students, 30,000 enrollments, 10 initiatives ×
5 schema versions, 50 field definitions, 30,000 `student_initiative_records`, and
**1,500,000** `student_initiative_values` rows distributed across the typed columns roughly
as the PRD's field mix implies (≈45% `value_text`, ≈25% `value_number`, ≈15% `value_bool`,
≈10% `value_date`, ≈5% `value_json`). Insertion is chunked raw multi-row INSERT.

Measured queries, each run 10 times after a cold buffer pool and reported as p95:

| Query | Threshold (constitution VII) |
|---|---|
| One assignment's grid: 200 records × 15 fields, pivoted | < 2 s |
| Per-section completion counts across one initiative (≈3,000 sections) | < 3 s |
| Initiative-wide student-weighted completion at 30,000 targets | < 3 s |
| Full recalculation sweep over 30,000 records | < 2 min |
| Single value upsert | < 500 ms |

Output: a `specs/001-foundation-access-roster/spike-eav-results.md` recording the schema
measured, index set, row counts, `EXPLAIN` output per query, the measured p95s, and a
PASS/FAIL per threshold. On any FAIL, the remedy proposal is recorded in the same file
before Phase 2 planning begins (spec FR-088).

Candidate remedies, ranked, to be applied only if a threshold fails: composite covering
index on `student_initiative_values(record_id, field_id)` plus a secondary on
`(field_id, value_bool)` for completion scans; a `is_complete` denormalization on
`student_initiative_records` (already in the PRD data model); the `assignment_stats` and
`initiative_branch_stats` aggregate tables (already in the PRD data model, Phase 2); and only
as a last resort, a JSON column alongside the EAV rows for read-optimized grid loads.

**Rationale**: PRD risk R-03 scores 6 and names this spike as its mitigation; constitution
VII makes the numbers gates rather than goals. Measuring on the real schema with the real
index set is the only measurement that transfers to Phase 2.

**Alternatives considered**: *Defer the spike to Phase 2* — rejected; the PRD places it in
Phase 1 precisely because discovering an EAV ceiling after the initiative features are built
would invalidate them. *Synthetic uniform data* — rejected; uniform value distribution
flatters index selectivity and would produce optimistic numbers.

---

## R-07: Items confirmed at kickoff (2026-10-05)

> **Resolved during Phase 1 implementation.** The versions below are now facts, not open questions,
> and they are pinned in `composer.json` and `.github/workflows/ci.yml` together so the two cannot
> drift — which was the whole point of R-07.2.
>
> | Item | Decided |
> |---|---|
> | **PHP** | **8.4.26**. The constitution says 8.3+, and 8.3 was installed first. It does not work: **Pest 5 requires PHP ^8.4**, and Pest 4 cannot coexist with the PHPUnit and `laravel/pao` versions Laravel 13 ships. Staying on 8.3 meant abandoning Pest and rewriting roughly 200 test tasks for PHPUnit. `composer.json` now requires `^8.4`, and CI pins 8.4. **Production must therefore also be 8.4** |
> | **Laravel** | **13.34.0** (`^13.17`) |
> | **PHPUnit** | **13.3.6**. Laravel 13 pins `^12.5.12`; Pest 5 needs PHPUnit 13. The constraint was relaxed deliberately, and `laravel/pao` permits ≥13.1.7 |
> | **Pest** | 5.3.0 · **Larastan** 3.x on PHPStan 2.2.17 · **Pint** 1.32.1 |
> | **openspout** | **5.3.0**, not the latest 5.12, which requires PHP ≥8.4.0 in a narrower range. 5.3 provides the streaming reader R-03 depends on |
> | **Bootstrap / Alpine / Sass** | 5.3.8 · 3.17.4 · 1.105.1. Laravel 13 ships **Tailwind**, which was removed: the constitution mandates Bootstrap, and running both would put two competing utility layers over one token set |
> | **MySQL / Redis** | Still to be provisioned. Neither is needed until Phase 2's migrations |

### Original kickoff checklist, retained

These are recorded rather than resolved because they depend on the provisioned environment,
not on a design choice:

1. **Laravel major version.** Pin the latest stable release supported on PHP 8.3 at
   `composer create-project` time. The plan depends on nothing version-specific beyond
   `SoftDeletes`, global scopes, `Bus` chaining, and Sanctum — all long-stable. Record the
   pinned version in the repository README at initialization.
2. **PHP 8.3 vs 8.4.** Constitution says 8.3+. Use whichever the production image provides,
   and set the same version in CI so they cannot drift.
3. **MySQL 8 vs MariaDB 10.11.** The design uses no engine-specific feature; `utf8mb4` and
   InnoDB behave equivalently for this schema. Confirm which university IT will provision, and
   run CI against that one.
4. **SMTP relay** (PRD section 9.6, marked TBD). Not required by this feature — no email is
   sent in Phase 1. Needed before the Phase 3 notification feature.
5. **Sentry instance** (PRD section 8.8, marked TBD). Structured JSON logging with request id,
   user id, and route ships in this feature regardless; Sentry is a later wiring step.

---

---

## Amendment 2 (2026-09-27) — student support channel

Five mechanism decisions, four of which exist only because the platform is exposing a public
surface for the first time. Numbering continues from R-07.

## R-08: The person-versus-script challenge

**Decision**: A **self-hosted proof-of-work challenge**. The server issues a random challenge with a
difficulty parameter; the browser finds a nonce whose hash meets the target; the server verifies the
nonce and marks the challenge consumed so it cannot be replayed. Challenges are single-use, expire in
10 minutes, and are stored in Redis with the difficulty read from settings.

Candidate implementation: **ALTCHA**, an open-source self-hostable proof-of-work widget, if it can be
vendored and served entirely from our own origin. **Confirm at kickoff** that the version chosen has no
runtime call to any external origin — if it does, fall back to the hand-rolled variant below, which is
roughly 150 lines: a signed challenge endpoint, a client-side worker loop, and a server-side verify.

Layered with two cheap additions that cost nothing and catch unsophisticated bots:

- A **honeypot field** hidden from sight and from assistive technology, whose completion rejects the
  submission.
- A **minimum elapsed time** between the form being issued and submitted — a human cannot read the form
  and type a description in under a couple of seconds.

**Rationale**: The constitution mandates a strict Content-Security-Policy, and PDPL constrains
third-party origins receiving requests from our pages. Every hosted CAPTCHA service — reCAPTCHA,
hCaptcha, Turnstile — requires a CSP exemption for its origin and sends visitor data to a third party
on every page load. That is a poor trade for a form expected to receive fewer than one request per ten
users per month. Proof-of-work is also accessible by default: there is no image to interpret, no audio
fallback to build, and nothing for a screen reader to fail at, which matters because FR-168 requires
WCAG 2.1 AA and the alternative would have meant an accessible-CAPTCHA problem of its own.

**Alternatives considered**:
- *Hosted CAPTCHA service*: rejected on CSP and PDPL, and it would have required an accessibility
  fallback path.
- *Honeypot and timing alone*: rejected as the sole control — they stop naive scripts but not a
  purpose-built one, and FR-128 requires an actual challenge.
- *Arithmetic or word puzzle*: rejected. Trivially solved by a script, and a genuine accessibility and
  localisation burden in two languages.

**Fails closed** (FR-129): if the challenge cannot be issued or verified — Redis unavailable, the widget
fails to load — submission is refused with a generic message. Failing open would remove a stated
protection silently, which is worse than an outage the user can see.

**Verification**: a test asserting submission is refused when no valid challenge is presented, when a
challenge is replayed, when it has expired, and when the challenge store is unavailable.

**Outcome (kickoff check, Phase 18, T358)**: ALTCHA 3.3.0 makes no call to an external origin, but it
starts its hashing workers from `blob:` URLs and falls back to `data:` URLs. Under the CSP both are
refused, because `worker-src` falls back to `script-src 'self'`, and allowing them would be a new
exemption (T359). So the **hand-rolled variant** is used. It lives in `resources/js/pow/`
(`puzzle.js`, `worker.js`, `solve.js`), and Vite emits the worker as its own hashed file on our
origin. The README section "Student support channel" describes it. The exact puzzle, which the T370
verifier must match:

- `challenge`: base64url, matching `[A-Za-z0-9_-]{16,256}`.
- `difficulty`: an integer from 1 to 32. The server chooses it, stores it with the challenge, and
  never reads it from the request.
- `nonce`: an integer from 0 to 2^53 − 1.
- An answer is valid when `SHA-256(UTF-8(challenge + ":" + canonicalDecimal(nonce)))` starts with at
  least `difficulty` zero bits, counted from the most significant bit across byte boundaries.
- Any nonce that meets the rule is valid; the verifier must not require the smallest one. Verifying an
  answer costs one hash.

---

## R-09: Two independent rate limits

**Decision**: Two separate limiters evaluated on every submission, both of which must pass:

| Axis | Key | Default |
|---|---|---|
| Network address | the originating IP | `support_throttle_ip` — 5 per hour |
| Identifier | the submitted University ID, hashed | `support_throttle_identifier` — 3 per hour |

Both thresholds and both windows are settings, per constitution IV. The identifier key is **hashed
before use** so that a raw University ID never becomes a cache key an operator might read.

Status retrieval carries its own, tighter IP limit (`support_status_throttle_ip`, default 10 per hour)
because it is a guessing surface: an attacker with a stolen reference number would otherwise brute-force
the University ID against it.

A refusal caused by either limit returns **one message** that says a limit was reached and when it may
be retried, **without saying which limit** (FR-130). Saying "too many requests for this University ID"
would confirm the identifier is being processed, which is an enumeration leak through the error path.

**When each is counted (Phase 19 review).** Both are counted with an atomic increment, and the
refusal compares the count it got back — a check followed by a separate count let a burst of
simultaneous requests all read zero and all pass. The address is counted on arrival. The identifier
is only *checked* on arrival and *counted* once a challenge has been verified and consumed **and the
fields are valid** (Phase 20 review: a student's typos must not spend their three attempts, and an
identifier that fails its format makes no key): counted
on arrival, three requests naming a student, with nothing solved, locked that student out for an hour
from any address. Counting after the challenge makes each spent attempt cost a solved proof-of-work.
It does not make the lock impossible — a per-identifier limit is by nature one an attacker can spend
— so this is a trade, accepted: the alternative, no identifier limit, reopens the distributed attack
on one identifier that is the reason the axis exists.

**Status retrieval (Phase 22 review).** The lookup is counted twice, per address and per reference, and a
lookup that finds its request is refunded. A limit on failures can then be tight, 4 an hour per address,
without refusing a campus that shares one address. IPv6 is counted by /64 on every public limit, because
one subscriber holds a whole /64.

**One Retry-After.** Every submission refusal carries the longer of the two configured windows, not the
time left on whichever counter fired; two counters started at different moments would otherwise tell
the caller which limit it was. Refusals are not padded by the response-time floor (R-10): the 429 status
already says "refused", and padding it would make a refused flood hold the PHP workers.

**Rationale**: The constitution requires the two axes be independent, and the reason is concrete. An IP
limit alone lets a distributed attacker hammer one student's identifier from many addresses. An
identifier limit alone lets one address enumerate thousands of identifiers, one submission each. Either
limit alone leaves the other attack open, which is why a single combined limit — the natural reading of
"rate limit the form" — would satisfy a careless implementation and defeat the purpose.

**Alternatives considered**:
- *One combined limiter*: rejected for the reason above; it is the specific failure the constitution's
  wording was written to prevent.
- *Per-session limiting*: rejected. A visitor has no session worth trusting, and clearing cookies would
  reset the limit.

**Verification**: two tests that must both exist — sustained submission from one address with varying
identifiers, and sustained submission for one identifier from varying addresses. Each must be refused.
A single-limiter implementation passes one and fails the other, which is exactly the point.

---

## R-10: Non-enumeration, including through timing

**Decision**: Verification resolves to a **single boolean** through one query, and every failure path
returns the **identical** response body and status. Three distinct failures must be indistinguishable:
the identifier does not exist; it exists but has no enrollment in the current term; its only enrollment
is withdrawn.

Implementation shape: one query joining student to enrollment to term, filtered on active status and the
current term, returning at most one row. The controller branches on presence, never on the reason for
absence. No logging path may record the distinction where it could surface in a response.

Additionally, a `ConstantResponseTime` middleware pads every public response to a **fixed minimum
duration** (default 400 ms, configurable). Without it, "identifier not found" returns after one index
lookup while "found but not enrolled" returns after a join — a difference measurable over enough
samples, which reconstitutes the leak the identical body was meant to close.

**Rationale**: FR-119 and FR-135, SC-026 and SC-029 all restate this requirement, and the reason is that
the natural implementation violates it. A developer writing a validation rule per condition produces
"no such student" and "student is not enrolled" as separate messages, which together let an attacker
enumerate roster membership one ID at a time — exactly the disclosure the platform's 404-not-403 rule
exists to prevent on the authenticated side.

**Format failures are deliberately excluded** from this discipline. A University ID that does not match
the configured pattern gets a specific message naming the expected format (FR-117), because the format
is published, is not a secret, and telling the student their nine digits should be nine digits is the
difference between a usable form and a frustrating one.

**Alternatives considered**:
- *Identical bodies without timing padding*: rejected as incomplete — it closes the obvious channel and
  leaves a measurable one.
- *Randomised delay*: rejected in favour of a fixed floor. Random jitter still leaks the mean over
  enough samples; a floor above the slowest path leaks nothing.

**Verification**: the `tests/Feature/Support/Enumeration` suite asserts byte-identical bodies **and**
identical status codes across all three submission failures and both retrieval failures, and asserts the
padded floor holds.

---

## R-11: Reference number generation

**Decision**: **10 characters of Crockford base32** drawn from a cryptographically secure random source,
plus a check character, rendered uppercase and hyphenated for legibility — for example
`K7Q2-M9XV-T3X` (stored canonical, `K7Q2M9XVT3X`, in `char(11)`; the hyphens are presentation). Crockford base32 excludes `I`, `L`, `O` and `U`, which removes the digit-letter
confusions a student reads out over a phone and the accidental obscenity the `U` omission avoids.

Stored with a unique index. Generation retries on collision, which at 50 bits of entropy will not
happen but must not corrupt if it does.

**The check character (Phase 19)** is **Luhn mod 32** over the same alphabet, not Crockford's own check
symbol: Crockford's is mod 37 and needs five extra symbols, one of them `U`, which this decision excludes.
Luhn mod 32 catches every single-character substitution and every swap of adjacent characters except
`0`↔`Z` — Luhn's known blind spot, as `09`↔`90` is for mod 10 — and a unit test pins that exactly. Input is
forgiven the way Crockford intends: case, hyphens and spaces are ignored, `I` and `L` read as `1`, `O` as
`0`.

**Explicitly rejected: any time-ordered identifier.** ULID, UUIDv7, Snowflake, and an auto-increment
with an offset all encode creation order, and several encode creation time. FR-122 and SC-027 require a
reference that cannot be derived from another; a time-ordered reference hands an attacker both
neighbours and a timestamp.

**Rationale**: The reference is the student's only credential for retrieving their request, so it must be
unguessable. It is also read, written down, and typed by a person, so it must survive transcription:
short enough to copy, with no ambiguous glyphs, and with a check character that catches a single-character
error before it becomes a generic "not found".

**Alternatives considered**:
- *UUIDv4*: unguessable but 36 characters of hex with hyphens — hostile to type and to read aloud.
- *ULID*: rejected outright; time-ordered, so it leaks ordering and time.
- *Sequential with a large offset*: rejected; trivially derivable, which is the one thing FR-122 forbids.

**Verification**: SC-027's test generates 1,000 references and asserts no sequence, no timestamp
correlation, no shared prefix growth, and the absence of the four excluded characters.

---

## R-12: Retention and anonymisation

**Decision**: A scheduled daily job anonymises requests whose creation date is more than two years past.
Anonymisation **clears three columns** — `student_id`, `contact`, `submitted_ip` — and stamps
`anonymised_at`. The request, its reference, its category, its state, its state history, and all its
responses survive.

`student_id` is therefore **nullable**, and that has a visibility consequence worth stating plainly: an
anonymised request has no student, therefore no enrollments, therefore no branch. The branch scope
resolves to nothing and the request becomes visible to the **Super Admin only**. That is acceptable —
a two-year-old resolved request is a trend-analysis artefact, not something a coordinator needs — but it
is a design consequence, not an accident, and `data-model.md` records it.

The audit entries are **not** touched. They follow the constitution's 5-year audit retention, which is
longer. This is deliberate: the audit log records that a request existed and who acted on it, which is
the accountability record; the request body is the personal data. Anonymising one while preserving the
other is what lets both obligations be met at once.

**Rationale**: Constitution v1.1.0 sets this retention and its reason — a support request contains free
text the student wrote and is personal data under PDPL, unlike the academic identifiers the rest of the
platform stores, and PDPL requires personal data not be kept longer than its purpose needs. A resolved
support request has no accreditation value, which is why this period is shorter than the 5-year audit
and the indefinite term retention.

**The clock starts at creation, not at closure** (spec Assumptions), so that a request abandoned without
ever being closed is not retained indefinitely.

**Alternatives considered**:
- *Hard delete at two years*: rejected. It destroys the trend data the PRD's §12.4 metric needs and it
  conflicts with constitution V.
- *Anonymise at closure plus two years*: rejected for the abandoned-request hole above.
- *Retain indefinitely*: rejected; it is the PDPL violation the constitution amendment was written to
  prevent.

**Verification**: a test that ages a request past the threshold, runs the sweep, and asserts the three
columns are cleared, `anonymised_at` is stamped, the category, state history and responses survive, the
audit entries survive, and the request is no longer visible to a coordinator.

---

## Resolved unknowns summary

| Technical Context item | Status |
|---|---|
| Language/Version | Resolved: PHP 8.3+; exact minor confirmed at kickoff (R-07.2) |
| Primary Dependencies | Resolved: stack mandated; Laravel major pinned at init (R-07.1); `openspout` added for streaming reads (R-03) |
| Storage | Resolved: MySQL 8 / MariaDB 10.11, engine choice confirmed at kickoff (R-07.3) |
| Testing | Resolved: Pest + Larastan + Pint, with Architecture and Performance suites (R-01, R-05, R-06) |
| Target Platform | Resolved: Ubuntu 24.04 / Nginx / PHP-FPM / Supervisor, KSA-hosted |
| Project Type | Resolved: single Laravel app, modular, Blade + internal `/api/v1` |
| Performance Goals | Resolved: measurement method and thresholds fixed (R-06) and budget tests defined |
| Constraints | Resolved: no open questions |
| Scale/Scope | Resolved: no open questions |

No `NEEDS CLARIFICATION` markers remain.
