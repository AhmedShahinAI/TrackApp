# Contracts — Public endpoints (unauthenticated)

Conventions and the binding **anti-enumeration contract**: [README.md](./README.md).

These five routes are the entire public surface of the TRACK platform — two that act and three that
serve them. They live in `routes/public.php`, outside the `auth:sanctum` stack, and are bounded by
`tests/Architecture/PublicSurfaceBoundedTest.php`.

Every route here:

- creates no account, credential, or session (FR-124);
- exposes no record belonging to anyone other than the caller's own request (FR-134);
- is padded to `support_min_response_ms` (FR / [research.md](../research.md) R-10);
- carries CSRF protection — Laravel's guest session cookie supplies the token, and a public form is not
  an excuse to disable it.

---

## GET /support

Serve the support request form. **Unauthenticated.**

**Query**: `lang` (optional, `ar`|`en`) — a visitor has no stored preference, so the language choice must
be available without a sign-in (FR-161).

**200**: the form, in a standalone layout carrying **no navigation to any authenticated area**
(FR-163). Includes the category list from `support_categories`, the description limit from
`support_description_max`, a challenge token from `GET /support/challenge`, a hidden honeypot field, and
the form-issued timestamp used for the minimum-elapsed-time check.

Rate-limited per IP to prevent the page itself being used to mint challenges in bulk.

---

## GET /support/challenge

Issue a single-use proof-of-work challenge. **Unauthenticated.**

**200**: `{ "challenge": "...", "difficulty": N, "expires_in": 600 }`

The challenge is stored server-side, single-use, and expires in 10 minutes. Difficulty is read from
settings.

**503**: when the challenge store is unavailable. The form then refuses submission rather than allowing
it through — **failing closed** (FR-129). A generic message tells the visitor to try again shortly.

---

## POST /support

Submit a support request. **Unauthenticated.** The primary endpoint of this feature.

**Request** (form-encoded):

| Field | Required | Rule |
|---|---|---|
| `university_id` | yes | Matches the configured pattern (FR-117) |
| `category` | yes | One of `support_categories` (FR-120) |
| `description` | yes | Non-empty, at most `support_description_max` characters (FR-115, FR-121) |
| `contact` | no | Free text the student types. **Never** read from any stored record (FR-116) |
| `challenge_solution` | yes | A valid, unconsumed, unexpired nonce (FR-128). Any nonce meeting the rule in research.md R-08 is accepted, not only the smallest; the difficulty is the one stored with the challenge, never read from the request |
| `_honeypot` | must be empty | Completion rejects the submission |
| `_issued_at` | yes | Must be at least the configured minimum age |

**Order of evaluation matters and is contractual:**

1. Rate limits — both axes, independently (FR-126, FR-127). Refusing first means an attacker cannot use
   the later steps as an oracle.
2. Challenge, honeypot, elapsed time.
3. **Format** validation of `university_id` → a **specific** message naming the expected format. This is
   the deliberate exception to the generic-failure rule (FR-117).
4. Other field validation → ordinary per-field messages.
5. **Verification** — student exists **and** holds an active enrollment in the current term → the
   **generic** failure path.

**302** to `GET /support/submitted` on success, carrying the reference number for one render.

**422** for a verification failure, with **one body, identical across all three failure modes**:

```json
{ "message": "تعذّر إرسال الطلب. تحقّق من رقمك الجامعي وحاول مرة أخرى." }
```

This body MUST be byte-identical whether the identifier does not exist, exists but is not enrolled in
the current term, or has only a withdrawn enrollment. No header and no timing may distinguish them.

**422** for a format or field failure, with the specific field message.

**429** when either limit is reached: the body states a limit was reached and when to retry, and
**never which limit** (FR-130).

**Audited**: `support.request_created` — **null actor**, `channel: public`, with the originating IP and
the resolved student and term in `new_values`. Written inside the same transaction as the request
(constitution II).

**Never recorded in the audit payload**: the challenge solution, the honeypot value, or the raw
description — the description lives in the request row, and duplicating free-text personal data into a
5-year audit payload would defeat the 2-year retention.

---

## GET /support/submitted

Show the confirmation. **Unauthenticated.**

**200**: the reference number displayed prominently with guidance to retain it (FR-122), and a link to
the status page. The reference is read from a single-render flash, not from the URL, so it does not land
in browser history, a proxy log, or a shared link.

---

## POST /support/status

Retrieve a request's state and responses. **Unauthenticated.**

**Request**: `reference` (required) and `university_id` (required) — **both** (FR-132).

**200**: the request's reference, category, submitted date, current state with its text label, the
rejection reason where present, and every staff response with its date.

**Explicitly absent from the response** (FR-134): the assignee, any internal note, the student's own
identifier echoed back, the originating IP, and anything belonging to any other student.

**422** with **one body, identical** whether the reference does not exist or the University ID does not
match it (FR-135):

```json
{ "message": "لم يتم العثور على طلب بهذا الرقم المرجعي." }
```

**429** per `support_status_throttle_ip`, which is tighter than submission because this endpoint is the
guessing surface: an attacker holding a stolen reference would otherwise brute-force the University ID
against it (FR-131).

**Not audited.** A read that exposes only the caller's own request, with no state change, does not meet
the constitution's definition of a sensitive action. Recording every status poll would fill the audit log
with noise and — because the entry would carry the identifier — would itself become a disclosure surface.
The rate limiter is the control here, not the audit log.

---

## Anonymised requests

A request past its retention period has no student (see [data-model.md](../data-model.md) and
[research.md](../research.md) R-12). Its reference therefore matches no University ID, and status
retrieval for it returns the **same generic failure** as a non-existent reference. No special case, no
separate message — the student cannot tell their old request was anonymised rather than never existing,
which is the correct outcome.
