# Contracts — Staff endpoints (authenticated)

Conventions: [README.md](./README.md). These five endpoints sit inside the existing
`/api/v1` authenticated stack and reuse feature 001's session guard, policies, and branch scope
unchanged.

**Scope applies to every endpoint here.** `SupportRequest` carries feature 001's
`BelongsToBranchScope`, resolved through the student's active enrollments. Route-model binding resolves
through the scoped query, so a request outside the caller's visibility raises `ModelNotFoundException`
→ **404, never 403** (constitution I, FR-143). Every endpoint carries the contractual allow/deny test
pair, plus an out-of-scope 404 test and an instructor-denied test.

| Role | Sees |
|---|---|
| `SUPER_ADMIN` | Every request, including anonymised ones |
| `BRANCH_COORDINATOR` | A request whose student holds an active enrollment in one of their branches (FR-140, FR-141) |
| `INSTRUCTOR` | Nothing — holds none of the four support permissions (FR-142) |

*As built (Phase 23, SC-038):* to an instructor every address here is a **404** — a bound request through the scope, and the queue through `SupportRequestPolicy::viewAny`'s `denyAsNotFound` — so the feature does not show itself to someone who has no route to any request. A coordinator refused an action they lack (assign, close) still gets 403.

---

## GET /api/v1/support-requests

The queue. Permission `support.view`.

**Query**: `status`, `category`, `branch_id`, `assigned_to`, `date_from`, `date_to`, `q` (reference or
student name), `page`, `per_page`. Default ordering is oldest-waiting first, since the queue exists to
be worked down.

*As built (Phase 21, and its review):* `date_from` / `date_to` are calendar days (`Y-m-d`, `date_to` not
before `date_from`, else 422) meaning the day **in the display timezone**, converted to UTC before
comparing. `q` is tried as a reference, then as a University ID, then as part of a name — one at a time,
the name match resolved once against students and bounded. `per_page` is capped at 100.

**200**: paginated requests, each carrying the reference, the student's name and University ID, their
branch, the category, the status with its localized label, the assignee, and **the waiting time** —
computed, because "how long has this been sitting" is the question the queue answers (FR-137).
`waiting_seconds` is **null** once the request stops waiting on staff (`resolved`, `rejected`, `closed`):
a finished request is not sitting anywhere. The people a coordinator can filter `assigned_to` by are
themselves and the staff of their own branches — never another branch's.

`branch_id` is available to a Super Admin. For a coordinator it is implicit and **cannot be widened**
beyond their own branches — passing another branch's id returns their own scope, not an error, so the
filter cannot be used to probe.

*As built (Phase 23, T442; recorded by its review):* the branch **column and filter** are shown to the
Super Admin and to a coordinator of **more than one** branch, who may narrow between their own; they are
hidden from a coordinator of one branch, whose queue is already single-scoped. T442's "Super Admin-only"
is widened to the multi-branch coordinator because the narrowing this enables is within their own scope
and FR-141 makes multi-branch queues real.

An **anonymised** request appears with the student shown as removed and no branch, and only to a Super
Admin — a consequence of the nullable student link recorded in [data-model.md](../data-model.md).

---

## GET /api/v1/support-requests/{reference}

One request in full. Permission `support.view`.

Bound by **`reference`**, not by `id` — the reference is already unguessable, so the URL carries no
sequential identifier a coordinator could iterate.

**200**: the request with its description, its full response thread, its state history, its assignee, its
originating IP, and its `contact` **flagged as unverified** (FR-145) — the flag is part of the payload,
not a UI decoration, so a client cannot render it as though it were confirmed.

*As built:* the thread and the history carry the latest 50 entries each, with `responses_total` and
`history_total`. The state history is read from the audit log's `support.request_created` and
`support.status_changed` entries — who, when, from what to what, and why — and nothing else of those
entries' payloads.

**404**: outside the caller's visibility, or no such reference. Indistinguishable, and the attempt writes
`access.denied` (FR-158).

**Side effect**: if the status is `new`, opening it transitions to `in_progress` and records who did so
(FR-147), audited as `support.status_changed`. A read with a side effect is unusual, so it is stated
here rather than left implicit — the alternative, an explicit "claim" action, adds a click to every
request for no benefit. It is decided under a row lock: two people opening a `new` request at once
both get the page, and exactly one transition and one entry are written.

---

## POST /api/v1/support-requests/{reference}/responses

Write a response. Permission `support.respond`.

**Request**: `body` (required, non-empty, at most the configured maximum)

**201**: the created response. Visible to the student on their next status retrieval (FR-151).

**409**: the request is `closed` — a closed request accepts no further response (FR-150).

A response to a `new` request first opens it (`new` → `in_progress`, its own `support.status_changed`),
as viewing it would. The body's maximum is `support_description_max`.

**Audited**: `support.responded`.

Stored raw and escaped on output; no markup is interpreted at any point (FR-153).

---

## PATCH /api/v1/support-requests/{reference}/status

Transition the state. Permission `support.respond`, and additionally `support.close` to reach `closed`.

**Request**: `status` (required, one of the permitted targets), `reason` (**required** when `status` is
`rejected`)

**200**: the request with its new status and its appended state history.

**409**: a transition the state machine in [data-model.md](../data-model.md) does not permit — notably
`new` → `closed`, `new` → `resolved`, and anything out of `closed`.

**422**: `rejected` without a reason (FR-149). The reason is shown to the student, so it is written for
them, not as an internal note.

**403**: a coordinator attempting `closed`, which requires `support.close`.

**Audited**: `support.status_changed` with the old and new status, and the reason where given — for
`rejected` it is also stored on the request; for any other transition it is kept in the entry only.

**409** also when the target is `resolved` and no response is on record.

---

## PATCH /api/v1/support-requests/{reference}/assignee

Reassign. Permission `support.assign` — **Super Admin only**.

**Request**: `assigned_to` (required; may be null to unassign)

**200**: the request with its new assignee.

**422**: the target user holds no `support.view` permission — assigning a request to someone who cannot
open it would silently strand it. *As built:* likewise for a deactivated user and for a coordinator
none of whose branches holds the student's active enrollment — the same stranding from the other side.

**Audited**: `support.reassigned` with the previous and new assignee (FR-152).

Reserved to the Super Admin because reassignment is a triage decision needing the college-wide view: a
coordinator who could reassign could move a request out of their own branch and out of their own sight.

---

## Absent by design

- **No `DELETE`** on a request or a response. Retention anonymises; it never deletes
  ([research.md](../research.md) R-12), and responses are append-only.
- **No `PUT`/`PATCH` on a response.** A correction is a new response, because the student may already
  have read the original.
- **No endpoint that returns a student's academic data** alongside a request. A staff member who needs it
  opens the student's own page, where feature 001's scope and audit already apply.
- **No bulk action.** Closing or rejecting many requests at once would produce one audit entry per
  request with no individual reason, which defeats FR-149. At the expected volume — fewer than 1 request
  per 10 users per month — bulk handling solves a problem the feature does not have.
