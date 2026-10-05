# Contracts — Audit Log (P0-F010)

Conventions, status-code meanings, and the mandatory test matrix: [README.md](./README.md).
Transactional write mechanism and the immutability grant: [research.md R-02](../research.md).

---

## GET /api/v1/audit-logs

Filterable audit log. Permission `audit.view`.

**Query**: `user_id`, `action`, `entity_type`, `entity_id`, `date_from`, `date_to`, `branch_id`,
`page`, `per_page`

**200**: paginated entries, each with:

```json
{
  "id": 88213,
  "user": { "id": 4, "name_ar": "...", "name_en": "..." },
  "action": "section.instructor_changed",
  "action_label": "Section instructor changed",
  "entity_type": "Section",
  "entity_id": 1204,
  "entity_label": "ENG101 — Section 214 — Zulfi (Female)",
  "old_values": { "instructor_id": 11 },
  "new_values": { "instructor_id": 27 },
  "ip_address": "10.4.2.55",
  "user_agent": "...",
  "created_at": "2026-09-26T09:14:02Z"
}
```

`action_label` and `entity_label` are localized server-side (FR-062 principle applied here too);
raw `action` keys stay stable for filtering.

**Scope**: a coordinator sees only entries whose entity resolves into their branches, plus their
own actions. `branch_id` filtering is available to a Super Admin; for a coordinator it is
implicit and cannot be widened (FR-033).

**Performance**: filtering by `user_id` plus a date range over 1,000,000 rows returns within
2 seconds (SC-004), served by the `(user_id, created_at)` index. A `PERF_SUITE` test seeds
1,000,000 rows and measures this.

**403**: caller lacks `audit.view`.

---

## GET /api/v1/audit-logs/export

Export the filtered audit log. Permission `audit.export`.

**Query**: identical to the list endpoint; the export must contain exactly the rows the list
returns for the same filters, under the same scope (FR-023, FR-032).

**200**: an `.xlsx` stream, Arabic-safe, with a header block carrying the applied filters, the
generation timestamp, and the requesting user's name.

**Audited**: `audit.exported` — exporting the audit log is itself an audited action.

---

## GET /api/v1/{entity}/{id}/activity

Per-entity activity, for `terms`, `branches`, `sections`, and `students`. Permission: the
entity's own `*.view` permission plus `audit.view`.

**Query**: `page`, `per_page`

**200**: entries filtered to `entity_type` and `entity_id`, in the same shape as the list
endpoint, limited to what the viewer may see (FR-033).

**404**: the entity is outside the caller's scope — the activity view cannot be used to probe for
records the caller cannot otherwise reach.

---

## Actions this feature must emit

Exactly one entry per occurrence (FR-028). The list is a contract: a Pest test performs each
action once and asserts the count and the action key.

**38 action keys** after Amendment 1 (34 originally, plus 4 for programs).

| Area | Action keys |
|---|---|
| Authentication | `auth.login`, `auth.logout`, `auth.login_failed`, `auth.login_blocked` |
| Accounts | `user.created`, `user.updated`, `user.deactivated`, `user.password_changed`, `user.password_reset`, `user.roles_changed`, `user.branches_changed` |
| Authorization | `access.denied`, `section.access_granted` |
| Terms | `term.created`, `term.updated`, `term.status_changed`, `term.set_current` |
| Structure | `branch.created`, `branch.updated`, `branch.deactivated`, `course.created`, `course.updated` |
| Programs *(Amendment 1)* | `program.created`, `program.updated`, `program.deactivated`, `program.courses_changed` |
| Sections | `section.created`, `section.updated`, `section.instructor_changed` |
| Students | `student.created`, `student.updated`, `enrollment.created`, `enrollment.withdrawn` |
| Imports | `import.uploaded`, `import.committed`, `import.resumed`, `import.error_report_downloaded` — each carrying the `mode`, which Amendment 1 extended to five values |
| Audit | `audit.exported` |

**Never recorded in any entry**: a password, a password hash, a session token, or a CSRF token.
A Pest test asserts that no `old_values` or `new_values` payload written by any audited path
contains a `password` key.

---

## Absent by design

There is no `POST`, `PUT`, `PATCH`, or `DELETE` on `/api/v1/audit-logs` or any sub-resource, and
no console command that mutates the table. Immutability is enforced in three layers — no update
path in code, no `updated_at` column, and the application database user denied `UPDATE` and
`DELETE` on the table (FR-031, constitution II). A test asserts each layer independently, because
any one of them alone would be a convention rather than a guarantee.
