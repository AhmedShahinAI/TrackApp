# CR-002 · Student Technical Support Requests

**Status**: Decisions complete · ready for design and for `/speckit-specify`
**Raised**: 2026-09-27 · **Decided**: 2026-09-27 · **Decided by**: Dr. Ahmed Ismail Shahin (Product Owner)
**Becomes**: ~~feature `002-student-support`~~ → **merged into feature 001 on 2026-09-27** at the
Product Owner's direction, as Phases 18–25 (T354–T463), FR-114 … FR-169, User Stories 9–13, and
SC-025 … SC-038. The separate feature directory no longer exists

> **Merge note (2026-09-27).** Keeping this as a separate feature was recommended and declined. The
> recommendation was based on the schedule: PRD §10 gives feature 001 the window 2026-09-27 →
> 2026-10-08, the support channel appears nowhere in the PRD's 100-row MoSCoW table because it is v1.1
> scope, and the constitution requires an explicit trade for scope added mid-phase. That trade is now
> recorded in PRD §10 and in plan.md Complexity Tracking. Containment applied during the merge: Phases
> 18–25 are strictly additive and last, they depend on Phases 1–17 rather than the reverse, they touch
> exactly one pre-existing file (T463, behind a flag defaulting to false), and they can be deferred
> wholesale without unpicking anything.
**Affects**: [PRD.md](../../PRD.md) · [constitution.md](../../.specify/memory/constitution.md) · the TRACK Design System
**Does not affect**: [specs/001-foundation-access-roster](../001-foundation-access-roster/) — see §10

---

## 1. What this adds

Students gain exactly one capability: **submitting a technical support request about the TRACK
platform**. Staff gain the other side: receiving, responding to, and closing those requests.

This fills the one gap the PRD already acknowledged — [PRD.md:1505](../../PRD.md#L1505) carries the
metric "Support requests · < 1 per 10 active users per month · **Support channel log [TBD]**". This
change request is that channel. It is not a new product direction; it is the closing of a stated TBD.

**Students do not get accounts.** That is the central decision, and §3 explains why.

---

## 2. Decisions recorded

| # | Question | Decision |
|---|---|---|
| 1 | How does a student authenticate? | **They do not.** No account, no password, no session. A public form verifies their university ID against the roster — see §3 |
| 2 | Who creates student accounts? | **Nobody.** No student account exists |
| 3 | What can a student do? | **Submit a technical support request.** Nothing else — no initiative data, no enrollment data, no data entry |
| 4 | What can a student see? | Their own request's status, retrieved by reference number + university ID. Nothing belonging to anyone else |
| 5 | Who receives and responds? | **Super Admin** and **branch coordinator** (the coordinator sees only their own branch's students — see §7) |
| 6 | Expected volume? | **Low.** The PRD's own target is < 1 request per 10 active users per month |
| 7 | Mobile app? | **No.** Responsive web only; PRD P3-F001 stays in v2 |

---

## 3. Why students get no accounts

The original proposal was university ID + password with first-time activation. It was rejected for a
specific, evidenced reason.

**The university ID is guessable, and the roster holds no second factor.**

The `students` table carries `university_id`, `name_ar`, `name_en` and nothing else
([data-model.md](../001-foundation-access-roster/data-model.md)). The ID pattern is `^\d{9}$`, and
the registration unit's real export shows IDs that are structurally sequential within a cohort:

```
471105041 · 471105043 · 471105048 · 471105050 · 471105056 · 471105058
```

Anyone holding one ID can guess dozens. "Enter your university ID, then choose a password" therefore
means **anyone can claim another student's account**. With no date of birth, national ID, phone, or
email in the roster, there is no factor available to prove the claimant is that student.

Three ways out were considered and all rejected:

| Option | Rejected because |
|---|---|
| Add a verification field to the import | The registration unit's actual export has four columns and does not include one ([ui-conflicts.md C-09](../001-foundation-access-roster/ui-conflicts.md)) |
| Instructor issues an activation code | If the student can reach their instructor, they already have support — the feature's purpose evaporates |
| University SSO | PRD defers SSO to v2 (P3-F002) |

**Since the only capability is submitting a support request, authentication buys nothing here.** It
would not prevent impersonation without a second factor, and it would cost: a `student_accounts`
table, a password store under PDPL, an activation flow, session handling, rate limiting, and a
reversal of the constitution's no-self-registration rule.

**What replaces it**: a public form that verifies the university ID **exists in the roster and has an
active enrollment in the current term**, behind a rate limit and a CAPTCHA. A request that fails
verification is never created. Impersonation risk is unchanged — it was never solved by the password
either — but the blast radius stays at "a false request attributed to a real student", and the audit
trail records the originating IP and the verification result.

**Residual risk, accepted**: a person knowing a valid university ID can submit a request in that
student's name. Mitigated by the rate limit, the CAPTCHA, IP logging, and the fact that a request
exposes no data. Revisit if SSO arrives in v2, which would make real student authentication cheap.

---

## 4. Scope

### In scope

- A public, unauthenticated support-request form, verified against the roster
- A public status check by reference number + university ID
- A staff-side queue: list, filter, open, respond, close
- Notification to the assigned staff when a request arrives (in-app; see §11 on email)
- Audit entries for request created, responded, status changed, and reassigned
- Arabic-first, RTL, with English parity, per constitution VI
- Rate limiting and CAPTCHA on the public endpoints

### Out of scope

- Student accounts, passwords, sessions, or any student sign-in
- Students viewing initiative data, enrollments, grades, or completion
- Students entering or editing any academic data
- File attachments on a request *(first version — see §11)*
- A general-purpose helpdesk: no SLAs, no escalation tiers, no canned replies
- Mobile application

---

## 5. The two-sided flow

**Student side** (public, no account)

1. Opens the support page from a link on the TRACK login screen.
2. Enters university ID, selects a request category, writes the description, optionally gives a
   contact (email or mobile they type themselves — nothing is read from the roster).
3. The platform verifies the ID exists and has an active enrollment in the current term. Failure
   returns one generic message that does not reveal whether the ID exists — the same
   no-enumeration discipline the staff sign-in uses (FR-009).
4. On success the request is created and a **reference number** is shown, which the student is told
   to keep.
5. Later, the student checks status with reference number + university ID on the same page.

**Staff side** (authenticated, existing RBAC)

6. Super Admin or branch coordinator sees a queue, scoped per §7.
7. Opens a request, writes a response, sets status.
8. The student sees the response on the status page.

**Status machine**

```
new ──► in_progress ──► resolved ──► closed
 │                          │
 └──────────► rejected ◄────┘
```

`rejected` requires a reason, matching the PRD's existing return-for-correction pattern.

---

## 6. Data model sketch

**No change to `students`.** No auth columns, no contact columns.

`support_requests`

| Column | Type | Notes |
|---|---|---|
| `reference` | varchar(20) unique | Shown to the student; random, not sequential, so it cannot be enumerated |
| `student_id` | FK → `students.id` | Resolved during verification |
| `term_id` | FK → `academic_terms.id` | The term the verification matched |
| `category` | varchar(40) | Validated against a `support_categories` setting — not a DB enum, so adding a category needs no migration (constitution IV) |
| `description` | text | The student's message |
| `contact` | varchar(255) nullable | Typed by the student; **unverified**, and labelled as such in the staff view |
| `status` | enum(`new`,`in_progress`,`resolved`,`closed`,`rejected`) | §5 |
| `assigned_to` | FK → `users.id` nullable | |
| `submitted_ip` | varchar(45) | For the rate limit and the audit trail |
| `created_at`, `updated_at` | timestamp | |

`support_responses`

| Column | Type |
|---|---|
| `support_request_id` | FK cascade |
| `user_id` | FK → `users.id` — the responding staff member |
| `body` | text |
| `created_at` | timestamp |

**New permissions**: `support.view`, `support.respond`, `support.assign`, `support.close`.
Granted to `SUPER_ADMIN` in full; `support.view` and `support.respond` to `BRANCH_COORDINATOR`.

**New settings**: `support_categories`, `support_rate_limit_per_hour`.

---

## 7. Authorization

The staff side **reuses the existing branch scope mechanism unchanged** — no new scope type is
introduced.

| Role | Sees |
|---|---|
| `SUPER_ADMIN` | Every request |
| `BRANCH_COORDINATOR` | A request whose student has an **active enrollment in one of that coordinator's branches** |
| `INSTRUCTOR` | Nothing — no support permission |

`support_requests` therefore carries the `BelongsToBranchScope` trait resolved through the student's
enrollments, exactly as `Student` does. An out-of-scope request id returns **404, not 403**, per
constitution I.

**Edge case needing a rule**: a student with active enrollments in more than one branch. The rule is
that the request is visible to a coordinator of **any** branch the student is enrolled in, plus the
Super Admin. This is deliberate: a support request is about the platform, not about a section, so
narrowing it further would hide requests from the only people able to help.

**Public endpoints** are outside the authenticated stack entirely and carry their own protections:
rate limit per IP and per university ID, CAPTCHA, and no enumeration in any response.

---

## 8. What changes in the PRD

| Location | Current | Change |
|---|---|---|
| [§1 line 13](../../PRD.md#L13) | "Internal Institutional Tool (authenticated staff only)" | "…(authenticated staff; plus an unauthenticated student support form)" |
| [§3.4 line 86](../../PRD.md#L86) | Non-Goal: "does not provide self-registration for any user type" | **Keep as written** — still true. No student account is created, so nothing is registered |
| [§4.1](../../PRD.md) | In-scope list | Add the student support channel and the staff-side queue |
| [§8.2](../../PRD.md) | Security | Add the public-endpoint rules: rate limit, CAPTCHA, no enumeration |
| [§8.4 lines 1096–1098](../../PRD.md#L1096) | "N/A for public traffic" | Amend: public traffic now exists on two endpoints, at low volume |
| [§12.4 line 1505](../../PRD.md#L1505) | "Support channel log **[TBD]**" | Resolve the TBD: the channel is TRACK's own `support_requests` table |

The §3.4 non-goal surviving intact is the clearest evidence that option B was the right choice.

---

## 9. What changes in the constitution

**Version 1.1.0 — MINOR, additive.** No principle is removed or redefined.

The one line that needs attention is [constitution.md:266](../../.specify/memory/constitution.md#L266):

> "No self-registration. Accounts are created only by the SUPER_ADMIN, and `/register` MUST return 404."

This stays **literally true** and is not amended. What is added is a clarifying clause to the
security floor:

> Public, unauthenticated endpoints are permitted only where they create no account and expose no
> record. The student support form is the only such endpoint: it verifies a university ID against the
> roster, is rate-limited per IP and per identifier, carries a CAPTCHA, and reveals nothing about
> whether an identifier exists.

Had option A been chosen, this would have been a **2.0.0** amendment reversing the rule. It is not.

---

## 10. What changes in feature 001

**Superseded 2026-09-27 by the merge.** The support channel is now *part of* feature 001, so this
section's question no longer applies as written. What it got right and wrong:

- **Wrong on one point.** The pre-merge analysis found that FR-114 (then FR-001) requires the platform
  to "link to it from the sign-in screen", and no task covered it. That is **one** edit to a file built
  by Phases 1–17, so the claim "nothing changes in 001" was too absolute. It is now **T463**, behind a
  `support_channel_enabled` flag defaulting to false.
- **Right on everything else.** The four points below all still hold, and they are why the merge was
  containable at all:

- T111 asserts `/register`, `/password/forgot`, `/password/reset`, and 2FA paths all return 404. All
  still true — the support form is at a different path and registers nobody.
- The `students` table is unchanged, so no migration in 001 moves.
- The three roles are unchanged; the support permissions are seeded by 002.
- The scope mechanism is reused, not modified.

002 depends on 001 (it needs `students`, `section_enrollments`, `academic_terms`, the branch scope,
and the `Auditor`), but 001 needs no edit. This is why the decision matters: option A would have
touched 001's auth module, its `/register` test, and its data model.

---

## 11. Open items — small, and none blocking design

1. **How the response reaches the student.** No email exists in the roster and SMTP is a Phase 3
   dependency (PRD §9.6). **Proposed default**: the status page is the channel — the student returns
   with their reference number. If they typed a contact, staff may use it manually. Email delivery
   becomes available when SMTP lands and needs no schema change, since `contact` already exists.
   *Confirm this is acceptable.*
2. **Attachments.** A student may want to attach a screenshot. Excluded from the first version to
   keep the public surface small, since accepting uploads from unauthenticated callers is the riskiest
   thing this feature could do. Revisit after launch.
3. **Category list.** The initial `support_categories` values need naming — suggest: بيانات خاطئة ·
   مشكلة دخول · مشكلة في الشعبة · استفسار عام · أخرى.
4. **Retention.** Support requests contain free text a student wrote and fall under PDPL. The
   constitution sets 5 years for audit entries and indefinite for term data; requests need their own
   period. Suggest 2 years, then anonymise.

---

## 12. Screens to commission for the design system

Add to the TRACK Design System as **v2**, keeping v1 intact (§13).

**Public** — no sidebar, no topbar, standalone layout on the brand's dusk-campus imagery:

1. Support request form — university ID, category select, description, optional contact, CAPTCHA
2. Submission confirmation — the reference number, prominent, with "keep this" guidance
3. Status check form — reference + university ID
4. Status result — the request, its status badge, and any staff responses
5. Generic verification failure — one message, no enumeration

**Staff** — inside the existing shell:

6. Support queue — `x-track-data-table` with status badges, category, student, branch, age; filters
7. Request detail — the message, the response thread, assign and status controls
8. Response composer — with a confirmation on `rejected`, which requires a reason

**Reusing existing components**: every one of these is buildable from the 24 Blade components in
[design-system.md §5](../001-foundation-access-roster/design-system.md) — the public pages need no
new component, only a standalone layout variant. Please confirm rather than introducing new
primitives.

**Still outstanding from v1** (unrelated to this change, both already logged): vector logo originals
(C-06) and the dynamic-field editor screens (C-18, needed for Phase 2).

---

## 13. Design-system re-vendoring checklist

The design system is a generated bundle. When v2 arrives:

1. Ship it as `TRACK Design System v2.zip` — **do not overwrite v1**; the visual-regression suite
   compares against a named reference
2. Re-vendor to `design-system/` and re-apply the **7 accessibility corrections** from
   `design-system/ACCESSIBILITY-DELTA.md`
3. Run `tests/Architecture/TokenParityTest.php` — it fails on any silent token drift
4. Re-baseline `tests/Visual/baselines/` only for screens that actually changed
5. Confirm no new CDN dependency entered the bundle — fonts, icons, and scripts stay self-hosted
   under the CSP

---

## 14. Next steps

| # | Step | Command / owner |
|---|---|---|
| 1 | Amend the PRD per §8 | Product Owner |
| 2 | Amend the constitution to 1.1.0 per §9 | `/speckit-constitution` |
| 3 | Commission the 8 screens per §12 | Designer |
| 4 | Create the feature | `/speckit-specify` → `/speckit-plan` → `/speckit-tasks` |
| 5 | Check consistency | `/speckit-analyze` |

Steps 1–3 can run in parallel. Step 4 should wait for step 2, so the spec is written against the
amended constitution rather than needing rework.

**Estimated size**: ~18–22 tasks, against ~45–60 for the rejected authenticated-portal design.
