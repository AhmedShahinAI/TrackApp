# Residual Risk Register

Security plan Phase 11 (SEC-111). Last updated 2026-10-17. **Status: awaiting owner sign-off
(SEC-113).**

This register collects every security risk the platform carries into release, in one place. It covers
risks accepted, deferred or still open from three sources:
- [security-review-001.md](../security-review-001.md) (`001 R-*`);
- [security-review-002.md](../security-review-002.md) (`002 R-*`);
- this security plan (`G-*`, [SECURITY-STRATEGY.md](./SECURITY-STRATEGY.md)).

The evidence for every closed item is in [SECURITY-EVIDENCE-LOG.md](./SECURITY-EVIDENCE-LOG.md).

**How to read it.**
- *Severity* is the impact on this deployment if the risk is realised.
- *Accepted until* is the date or event that re-opens the decision.
- Nothing here is "accepted" until the owner signs below.

## Open: decisions only the owner can take

| ID | Risk | Severity | Owner | Accepted until | Follow-up |
|---|---|---|---|---|---|
| G-01 | The registrar roster in git history (all 9 commits, both branches, `origin`) may be **real student data**. Whether it is real is **unverified**: the owner states none was intentionally uploaded, and the evidence is circumstantial (evidence log, Phase 1). The working tree is clean, and a guard test keeps it so | Critical if real; Info if synthetic | Dr. Ahmed Shahin | Owner verifies the backup against section 2298 | If real: Phase 2 (history purge and force-push), only with explicit approval; DPO decision. If synthetic: withdraw Phase 2 and correct "real" in the docs |
| G-01b | 224 leftover copies of the original export in `storage/app/private/imports/2026/10/` on the development machine (gitignored, never pushed, nothing references them) | High if real; Info if synthetic | Dr. Ahmed Shahin | Owner's deletion approval | Delete exactly the 224 verified files. The new tests no longer create real-data copies |
| D-4 / G-16 | The PRD asks for a CAPTCHA on the support form; a proof-of-work challenge, honeypot, minimum fill time and two-axis rate limits are used instead | Low | Dr. Ahmed Shahin | Owner decision | Accept proof of work as the CAPTCHA (amend PRD §8.2), or add one. Any third-party CAPTCHA script needs a CSP change |
| 002 R-02 | Staff replies on a support request are shown to whoever holds the reference, who may be an impersonator (002 R-01) | Medium | Owner + support staff | Before launch | Staff guidance: never put personal data in a reply. Consider a one-line notice by the composer |
| 002 R-03 | The `support.request_created` audit entry keeps `student_id` and the submitting IP for the audit log's 5 years, outliving anonymisation | Medium | Owner + university DPO | DPO decision | Options (a), (b) and (c) are set out in 002 R-03 |
| 002 R-06 | A support request's free-text description is kept indefinitely after anonymisation | Medium | Owner + university DPO | DPO decision | Keep for trends, or clear in the sweep (a one-line change) |
| G-04b | Accounts created before SEC-047 keep their passwords and are not forced to change them. Some predate the 12-character minimum | Medium | Owner | Go-live | One-time `password_change_required = true` for every account at go-live (recommended) |

## Accepted by design

| ID | Risk | Severity | Owner | Accepted until | Follow-up |
|---|---|---|---|---|---|
| 002 R-01 | Anyone who knows a valid University ID can raise a support request in that student's name | Low (no data disclosed) | Product | University SSO becomes available (v2) | Re-review the support channel then |
| PRD §4.2 | No two-factor authentication in v1 | Medium | Owner | v2 or university IT requirement | Mitigated by: a 12-character minimum, per-account lockout, lockout alerts and a forced change of admin-set passwords |
| PRD §4.2 | No self-service password reset; a Super Admin resets passwords | Low | Owner | v2 | Mitigated by: an admin-set password must be changed at next sign-in (SEC-047) |
| G-03b | Anyone who knows a staff email can keep that account locked for 15 minutes at a time | Low | Operations | — | `track:login-unblock`, which is audited; the lock raises an alert |
| 002 R-04 | A Redis outage closes the support channel (fail-closed challenge) | Low (availability) | Infrastructure | — | Redis monitoring (runbook §7) |
| G-14b | An instructor may create a section for a teaching colleague, who then reaches it before approval (Amendment 4) | Low | Product | — | The coordinator's approval is the control; the named person must hold a teaching role |
| G-21b | No cryptographic hash chain on the audit log; a database administrator can still alter rows | Low | Operations | University IT asks for tamper evidence | Model guard, `REVOKE`, deploy gate and hourly drift alert (SEC-082) cover the application user |

## Deferred: hosting facts (D-7) and work that needs a host

| ID | Risk | Severity | Owner | Accepted until | Follow-up |
|---|---|---|---|---|---|
| 001 R-04 | Encryption at rest, TLS configuration and backup encryption at the host | High until confirmed | University IT | Before go-live | Runbook §8.1, §8.6 |
| 001 R-05 / §8.5 | Queue payloads are serialized PHP; Redis must be private | High until confirmed | University IT | Before go-live | Runbook §8.5 |
| 002 R-05 | A distributed flood of the public support form | Medium | University IT | Before go-live | WAF or edge rate limiting (runbook §8.8) |
| G-06b | HSTS from the app depends on `TRUSTED_PROXIES` | Low | University IT | Before go-live | HSTS at the web server (runbook §8.1) |
| G-19b | **Restore drill** not yet performed (SEC-103); RPO 24 h and RTO 4 h are unproven | High | Operations | **M7, 2026-11-19** | Rehearse on staging (runbook §6); approval A-8 |
| G-19c | Backup storage location and offline key custody undecided | High | University IT + owner | Before go-live | Runbook §8.4, §8.8 |
| 001 R-02 | Error tracking (Sentry) and alert email not yet configured | Medium | Operations | Before go-live | `track:preflight` now fails a production deploy without an alert path |
| 001 R-03 | The audit `REVOKE` needs a DBA to apply once | Medium | DBA | First production deploy | `track:audit-grants --strict` gates every deploy |

## Deferred: needs the owner's approval to finish

| ID | Item | Owner | Follow-up |
|---|---|---|---|
| SEC-075 | Browser screenshots and an axe check of the new 419, 429 and 500 pages | Owner (baseline approval) | Run and record on approval |
| SEC-090, SEC-095 | The hardened CI workflow (audits, gitleaks, SHA pins) has not yet run on GitHub | Owner (push approval) | Push a branch and record the run. The full-history gitleaks scan may flag the fixture password `Track2026`, which is test-only |
| Password page | The visual baselines of `/profile/password` change (the hint now says 12) | Owner (baseline approval) | Regenerate on approval |

## Closed by this plan (for the record)

G-02, G-03, G-05, G-07, G-08, G-09, G-10, G-11 (pending its first CI run), G-12, G-13, G-15, G-17, G-18,
G-20, G-21 (foreign key), G-22, and both `LEAK?` items from the scope-bypass review. Evidence is in
the log, phase by phase.

## Sign-off (SEC-113)

| Role | Name | Decision | Date |
|---|---|---|---|
| Product owner | Dr. Ahmed Shahin | _pending_ | |
| University IT / Information Security | _TBD_ | _pending (approval due 2026-11-12)_ | |
