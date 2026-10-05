# Contracts — Authentication, Accounts, Roles & Scope (P0-F001, P0-F002)

Conventions, status-code meanings, and the mandatory test matrix: [README.md](./README.md).

---

## POST /api/v1/auth/login

Sign in. **Unauthenticated.** Throttled at 5 attempts/minute per `email` + IP.

**Request**: `email` (required, email), `password` (required, string)

**200**: `{ "data": { "id", "name_ar", "name_en", "email", "locale", "roles": [...], "permissions": [...], "scope": { "branch_ids": [...], "section_ids": [...] }, "first_login": true, "landing_route": "/dashboard/my-work" } }`

`first_login` is `true` only when `first_login_at` was null *before* this request; the server
sets it during the request (FR-012). `last_login_at` is updated on every successful sign-in
(Amendment 1, FR-113). The session id is regenerated before the response.

**422**: generic `"Invalid email or password."` — the response never indicates which field was
wrong (FR-009). Failed attempt audited as `auth.login_failed`.

**403**: `"Your account is inactive. Contact the administrator."` for `is_active = false` (FR-011).

**429**: `{ "message": "Too many attempts. Try again in 60 seconds.", "retry_after": 60 }` (FR-010).

**Audited**: `auth.login`, `auth.login_failed`, `auth.login_blocked`.

---

## POST /api/v1/auth/logout

End the session. Requires authentication. **200** with an empty `data`. Session invalidated and
the CSRF token regenerated. **Audited**: `auth.logout`.

---

## GET /api/v1/auth/me

Current user, role, scope, and locale. Requires authentication.

**200**: the same payload shape as login's `data`, minus `first_login` and `landing_route`.

Reflects role and scope changes on the very next request after they are made (FR-027) — no
cached permission set survives a scope change.

---

## PUT /api/v1/auth/password

Change own password. Requires authentication.

**Request**: `current_password` (required, must match), `password` (required, ≥ 8 chars, ≥ 1
letter, ≥ 1 digit, must differ from current), `password_confirmation` (required, matches)

**200**: `{ "data": { "changed_at": "..." } }`. All the caller's **other** sessions are
invalidated; the current session survives (FR-015).

**422**: wrong current password, policy failure, mismatch, or a new password equal to the
current one (FR-014) — each with its own localized field error.

**Audited**: `user.password_changed`.

---

## GET /api/v1/users

List accounts. Permission `user.view` (SUPER_ADMIN only in this feature).

**Query**: `role`, `branch_id`, `is_active`, `q` (name or email), `page`, `per_page`

**200**: paginated users with roles, coordinator branches, taught-section count, `is_active`,
`first_login_at`, and `last_login_at` (Amendment 1, FR-113 — displayed as "آخر دخول"). Never
includes any password field.

---

## POST /api/v1/users

Create an account. Permission `user.create`.

**Request**: `name_ar` (required), `name_en` (required), `email` (required, unique across
active **and** soft-deleted rows), `password` (required, policy), `role` (required, one of the
seeded role names), `branch_ids` (required array when role is `BRANCH_COORDINATOR`),
`section_ids` (optional array when role is `INSTRUCTOR`), `locale` (optional, default from the
`default_locale` setting)

**201**: the created user. `password` is hashed with Argon2id and stored nowhere else;
`first_login_at` is null (FR-003, FR-004, FR-007).

**422**: duplicate email (FR-005), policy failure, missing `branch_ids` for a coordinator.

**Audited**: `user.created` — `new_values` excludes the password entirely.

---

## PUT /api/v1/users/{id}

Edit an account. Permission `user.edit`.

**Request**: any of `name_ar`, `name_en`, `email`, `locale`. Password is **not** editable here —
use the reset endpoint.

**200**: the updated user. **422**: duplicate email. **404**: no such user.

**Audited**: `user.updated` with old and new values per changed field.

---

## PATCH /api/v1/users/{id}/deactivate

Soft-deactivate. Permission `user.deactivate`.

**Request**: `reason` (optional, string)

**200**: the user with `is_active: false`. All of that user's sessions are invalidated. Their
authorship on every existing record is untouched (FR-018). The row is **not** erased (FR-049).

**409**: the caller attempting to deactivate their own account.

**Audited**: `user.deactivated`.

---

## POST /api/v1/users/{id}/reset-password

Super Admin password reset. Permission `user.reset_password`.

**Request**: `password` (required, policy)

**200**: `{ "data": { "reset_at": "..." } }`. **All** of that user's sessions are invalidated
(FR-015). No email is sent — the Super Admin communicates the password out of band (no SMTP
dependency in this feature).

**Audited**: `user.password_reset` — the password appears in neither `old_values` nor
`new_values`.

---

## PUT /api/v1/users/{id}/roles

Assign roles. Permission `user.assign_role`.

**Request**: `roles` (required array of seeded role names)

**200**: the user with its new roles. Effective on the target user's next request (FR-027).

**Audited**: `user.roles_changed` with the previous and new role sets.

---

## PUT /api/v1/users/{id}/branches

Assign coordinator branches. Permission `user.assign_branch`.

**Request**: `branch_ids` (required array of existing branch ids)

**200**: the user with its new branches. **422**: the target user does not hold the
`BRANCH_COORDINATOR` role, or a branch id does not exist.

**Audited**: `user.branches_changed` with previous and new id sets.

---

## POST /api/v1/sections/{id}/access-grants

Grant an instructor access to one additional section. Permission `section.access_override`.

**Request**: `user_id` (required, must hold the `INSTRUCTOR` role)

**201**: the grant. The section then appears in that instructor's list labelled as shared
access (FR-026).

**404**: the section is not visible to the caller — though in practice only SUPER_ADMIN holds
this permission. **422**: the grant already exists, or the target is not an instructor.

**Audited**: `section.access_granted`.

---

## Absent by design

`/register`, `/password/forgot`, `/password/reset`, and any 2FA route do **not** exist and must
404 (FR-001). A Pest test asserts each path returns 404 — their absence is a requirement, so it
is tested rather than assumed.
