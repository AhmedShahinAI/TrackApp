# Runbook — deploying and operating TRACK (feature 001)

**Task:** T280 · **Audience:** whoever deploys, restores or is on call · **Last checked:** 2026-10-09

Target platform (plan.md, research R-07): Ubuntu 24.04 LTS, Nginx, PHP-FPM **8.4** (Pest 5 and the
locked dependencies require 8.4; plan.md and PRD §9.5 still say 8.3 — 8.4 is correct), MySQL 8.0+ or
MariaDB 10.11+ (InnoDB, `utf8mb4_unicode_ci`), Redis for cache, sessions and queues, Supervisor for the
workers, hosted in the Kingdom of Saudi Arabia. Environments: local, staging, production.

---

## 1. Pre-flight — every deploy

| Check | How |
|---|---|
| The gate is green on the commit | CI: Pint, PHPStan, `php artisan test`, the Playwright visual and axe suites (quickstart.md "Full gate"). |
| Production `.env` | `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…`, `SESSION_SECURE_COOKIE=true`, `HASH_DRIVER=argon2id`, `LOG_STACK=json`, `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis`, `APP_DISPLAY_TIMEZONE=Asia/Riyadh`, `SENTRY_LARAVEL_DSN=<approved instance>`, `ALERT_EMAIL=<on-call address>`. |
| Redis is private | Password set (`REDIS_PASSWORD`), bound to the private network, not reachable from the internet. Queue payloads are serialized PHP (security review R-05). |
| Disk | Under 80 % on the volume holding `storage/` (PRD 8.8 alert threshold). |
| Production `.env` is the production template | Copied from `.env.production.example`, never from `.env.example`. The application refuses to boot on production/staging with `APP_DEBUG=true`, `SESSION_SECURE_COOKIE=false` or `LOG_LEVEL=debug` (SEC-073), and `track:preflight` fails the deploy when the alert path is dead (SEC-083). |

## 2. Deploy

Zero-downtime release directories (Deployer or Envoy, PRD §9.5). In order:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build          # prebuild: fonts → icons → brand assets (see §2.1), then vite build
php artisan migrate --force
php artisan track:audit-grants --strict   # fails the deploy if the app user can UPDATE or DELETE audit_logs (§4)
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
php artisan track:preflight              # fails the deploy if alerts would reach no one: ALERT_EMAIL unset or MAIL_MAILER=log (SEC-083)
# switch the `current` symlink to the new release, then:
php artisan queue:restart   # workers finish their current job and restart on the new code
sudo systemctl reload php8.4-fpm
```

Then: §3 smoke check. (§4's grant check already ran as a deploy step; a failure there stops the
release before the symlink switches.)

### 2.3 Scheduler

One cron entry per server, as the application user:

```cron
* * * * * cd /var/www/track/current && php artisan schedule:run >> /dev/null 2>&1
```

It runs `track:monitor` every five minutes (§7). Once a day at 00:00 in the application timezone, which
is **UTC** (`config/app.php`, so 03:00 in Riyadh), it queues the support retention sweep,
`support:anonymise` (§5a). `onOneServer()` keeps a multi-server deployment from
alerting or sweeping twice; that needs the shared Redis cache.

### 2.1 Asset build — `design-system/assets/`

`npm run build` runs `prebuild` first:

| Step | Script | Produces |
|---|---|---|
| `build:fonts` | `scripts/build-fonts.mjs` | `public/fonts/` — Cairo and Inter, self-hosted |
| `build:icons` | `scripts/build-icons.mjs` | `public/icons/lucide.svg` sprite |
| `build:assets` | `scripts/copy-brand-assets.mjs` | copies `design-system/assets/` → `public/img/brand/` |

`public/img/brand/` is git-ignored and exists only after a build. `copy-brand-assets.mjs` exits 1 with
instructions if `design-system/assets/` is missing — it is vendored read-only; restore it from the
design-system archive rather than editing it. Nothing is fetched from a CDN at runtime; the CSP
(`ContentSecurityPolicy`) would refuse it.

### 2.2 Supervisor — the queue workers

`/etc/supervisor/conf.d/track-worker.conf`:

```ini
[program:track-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/track/current/artisan queue:work redis --queue=default --sleep=3 --tries=1 --timeout=870 --max-time=3600
directory=/var/www/track/current
user=www-data
numprocs=2
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
stopwaitsecs=900
redirect_stderr=true
stdout_logfile=/var/www/track/shared/storage/logs/worker.log
```

Why these numbers — they are coupled, and changing one without the others reintroduces a defect:

- `ValidateImportJob::$timeout` is **840 s**; `CommitImportChunkJob::$timeout` is **80 s**.
- `--timeout=870` sits above the longest job, so a worker is never killed mid-validation.
- `retry_after` (`config/queue.php`, `REDIS_QUEUE_RETRY_AFTER`) is **900 s**, above `--timeout`, so a
  slow job is never handed to a second worker while the first still runs it.
- `stopwaitsecs=900` lets a deploy's `queue:restart` wait for a validation to finish.
- `--tries=1`: both import jobs are single-attempt by design; a failed chunk is resumed by a person
  from the import screen, not retried blindly.

```bash
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start track-worker:*
```

A running worker writes a heartbeat every 30 s; `/health` reports `queue: fail` after 120 s without one,
or when a job has waited more than 5 minutes.

## 3. Smoke check

```bash
curl -fsS https://<host>/health | jq
```

Expect `"status": "ok"` and `ok` for `database`, `redis`, `queue`, `storage` (HTTP 200). Any `fail`
answers 503; the reason is in `storage/logs/track.json-<date>.log` under the same request id as the
response's `X-Request-Id` header. `/up` is the bare liveness probe; `/health` is readiness.

Then sign in as a Super Admin, open the audit log, and confirm the sign-in was recorded.

## 4. Audit table — grant verification (staging and production)

The audit log is append-only in three layers; the third is a database privilege. Migration
`2026_01_01_000016_revoke_audit_log_mutation_grants.php` runs
`REVOKE UPDATE, DELETE ON <db>.audit_logs FROM '<app user>'@'%'` and **silently does nothing** if the
migrating user lacks `GRANT OPTION`. So the grant must be checked, not assumed — after the first deploy
and after any change to database users.

As a DBA:

```sql
SHOW GRANTS FOR '<app user>'@'%';
-- Expected: no UPDATE and no DELETE on <db>.audit_logs (table-level or inherited from <db>.*).
-- If missing, apply it by hand:
REVOKE UPDATE, DELETE ON `<db>`.`audit_logs` FROM '<app user>'@'%';
```

Note that a database-wide `GRANT ALL ON <db>.*` makes the table-level revoke ineffective; grant the
application user explicit privileges per table, or `SELECT, INSERT` only on `audit_logs`.

Then prove it **as the application user** — every deploy does this already (§2):

```bash
php artisan track:audit-grants --strict
# audit_logs is append-only for this user: UPDATE and DELETE are refused.   (exit 0)
```

The command issues an `UPDATE` and a `DELETE` on `audit_logs` that match no row (`WHERE 1 = 0`):
privileges are checked before the `WHERE` runs, so a user who lacks them is refused and a user who
holds them changes nothing. Exit 1 names what is allowed. The migration also logs a CRITICAL line on
staging and production when its own `REVOKE` could not be applied.

Record the date and result in the deploy log. (Security review R-03.)

## 5. Audit retention — 5 years

Constitution ("Audit entries are retained for at least 5 years"), PRD 8.7.

- **Nothing in the application deletes audit entries**, and the database user cannot (§4). There is no
  pruning job, by design.
- Backups (§6) carry the audit table; a restore never drops entries newer than the backup.
- After five years, entries may be archived — exported (the audit export, or `mysqldump --where`) to
  the university's records store — and then removed **by a DBA**, under a recorded decision. This is
  the only deletion path, and it is outside the application.
- Personal data in support requests (Amendment 2, research R-12) is anonymised after 2 years; the audit
  entries about those requests are not (§5a).

## 5a. Support request retention — anonymised at 24 months (Amendment 2, T451–T452)

Constitution v1.1.0, FR-159, research.md R-12. A support request holds free text a student wrote, which
is personal data under PDPL.

| Table | Kept | What happens at the end |
|---|---|---|
| `support_requests` | `support_retention_months` (default **24**), counted from **creation**, not closure | `student_id`, `contact` and `submitted_ip` are set to null and `anonymised_at` is stamped. **The row stays**, with its reference, category, status and **description**. The description is the student's own free text and is kept indefinitely (FR-159 as written; an open decision, security review R-06) |
| `support_responses` | Indefinitely, with their request | Nothing. They hold staff text, though a reply may quote the student (security review R-02) |
| `audit_logs` about a request | 5 years (§5) | Nothing. The **creation** entry still holds the student and the submitting address: `student_id` and `submitted_ip` in its payload, and the address and user agent in its `ip_address` and `user_agent` columns. So for those 5 years an anonymised request can be linked back to its student by anyone who can read the audit log (security review R-03, an open decision). The sweep adds one `support.anonymised` entry (actor null, `channel: system`) that carries no student, contact or address |

- **The sweep.** `AnonymiseExpiredRequestsJob` runs on the queue, so the queue workers (§2.2) must be
  running, as well as the scheduler (§2.3). It is unique, so a slow sweep is never joined by a second
  one, and retried up to 3 times. Each attempt is bounded by the worker's `--timeout=870`. Every request
  it finishes stays finished, so a first run over a long backlog completes across attempts, or across
  days.
- **A request that fails is skipped, not fatal.** Its transaction rolls back, the error is reported, and
  the sweep continues with the rest. The run then ends as a **failed job**, which alerts (§7). Find the
  cause in the error log (`The retention sweep anonymised N requests; M could not be anonymised`) and
  the reported exception above it. That request is retried every day until the cause is fixed.
- **If the sweep stops running at all**, because the scheduler or the workers are down, `track:monitor`
  raises `support_retention_overdue` once any request is more than `ALERT_RETENTION_GRACE_DAYS`
  (default 2) past its retention (§7).
- **Nothing is deleted.** The application never deletes a request or a response. A hard delete would
  lose the trend data the PRD §12.4 metric needs.
- **Visibility after the sweep.** An anonymised request has no student, so no branch: only a Super
  Admin sees it, with the student shown as removed. A status lookup with its old reference fails like a
  reference that never existed.
- **Changing the period.** Edit the `support_retention_months` setting (see "Changing a support
  setting" in §6a), and then clear the settings cache. It must be at least 1; a sweep that reads 0 or less
  refuses to run and fails as a job, which alerts, rather than anonymising everything. Shortening
  the period anonymises the newly expired requests on the next run. Lengthening it cannot restore a request
  that has already been anonymised.
- **Run it by hand** (after a long outage of the scheduler, for instance):
  `php artisan tinker --execute="dispatch(new App\Modules\Support\Jobs\AnonymiseExpiredRequestsJob);"`
- **Backups** (§6) still hold the cleared columns until they age out of the 30-day backup retention. A
  restore older than the last sweep must be followed by a sweep run.

## 6. Backup and restore

Constitution and PRD 8.3: daily backups, 30-day retention, plus a snapshot on every term closure;
RPO 24 h, RTO 4 h; AES-256 at rest; stored in a separate location.

**Daily (02:00 Asia/Riyadh):**

```bash
mysqldump --single-transaction --routines --triggers --hex-blob \
  -u backup -p <db> | gzip | openssl enc -aes-256-cbc -pbkdf2 -pass file:/etc/track/backup.key \
  > /backups/track-$(date +%F).sql.gz.enc
rsync -a /var/www/track/shared/storage/app/private/ backup-host:/track/files/$(date +%F)/
# prune: keep 30 days, keep every term-closure snapshot
```

`storage/app/private/` holds uploaded import files. Without it, a restored database lists imports
whose files are gone — the error report still works (it reads `import_rows`), the original file does not.

**Restore (rehearse quarterly on staging):**

1. Put the application in maintenance: `php artisan down`; stop workers: `supervisorctl stop track-worker:*`.
2. Decrypt and load: `openssl enc -d -aes-256-cbc -pbkdf2 -pass file:… -in <file> | gunzip | mysql <db>`.
3. Restore `storage/app/private/` from the same date.
4. `php artisan migrate --force` (a no-op if the backup is from the same release).
5. Re-verify the audit grant (§4) — a restore into a fresh database starts with default grants.
6. `php artisan up`; start workers; §3 smoke check.
7. Imports that were mid-commit at backup time show as interrupted and can be resumed from the screen.

Record the restore duration against the 4-hour RTO.

## 6a. Support channel — proxies and the PHP pool (Amendment 2, Phase 19 review)

**Trusted proxies.** Set `TRUSTED_PROXIES` to the load balancer's or reverse proxy's addresses or CIDRs
(`config/trustedproxy.php`). Without it every visitor has the proxy's address, and the per-IP limits on
the public support form — five submissions an hour — refuse the whole institution after the fifth. Never
`*`: the config refuses it, because it lets any client choose its own `X-Forwarded-For` and reset every
per-IP limit.

**Cache store.** `CACHE_STORE=redis`. The support form's proof-of-work challenges are single-use, and
`ChallengeService` lets exactly one submission spend a challenge by trusting the store's delete to report
success to one caller only. Redis `DEL` does; the database cache store does not, and two simultaneous
submissions of one solution would both pass. The rate limiters count with Redis `INCR` for the same reason.

**PHP pool sizing.** Every support submission and every status lookup (Phase 22) that reaches the
roster is held to
`support_min_response_ms` (400 ms) so that its timing reveals nothing (research.md R-10); refusals (429)
are not held. A worker therefore serves at most `1000 / support_min_response_ms` such responses a
second — 2.5 at the default. The page views (the form, the challenge, the confirmation) are not held.
Size the pool, or give `/support` its own PHP-FPM pool, for the expected peak of submissions and status
checks times 0.4 s — a results day, when many students check at once, is the peak to plan for — so a burst on the public form cannot take workers
from the staff screens.

**Changing a support setting**, including **opening the channel to students**
(`support_channel_enabled`, off by default). There is no settings screen in this phase. Settings are
read through a cache that never expires (`settings.all`), so editing the row **does nothing until the
cache is cleared**. Do both in one command:

```sh
php artisan tinker --execute="App\Models\Setting::query()->where('key', 'support_channel_enabled')->update(['value' => json_encode(true)]); App\Modules\Settings\Settings::forget();"
```

Only a stored JSON `true` turns the sign-in link on. `"true"`, `1` and a missing row all read as off, and
so does a settings store that cannot be read: the sign-in page never fails because of this switch. To
close the channel again, store `false` the same way.

**The padding cap.** `SUPPORT_PADDING_MAX_CONCURRENT` (config/support.php, default 40) is how many padded
support responses may sleep at once; past it, a new one gets an immediate generic 503 instead of a
worker. Set it to about a third of the pool's workers. A warning "took longer than its response-time
floor" in the log means the floor is below real latency and is no longer hiding anything: raise
`support_min_response_ms` above the p99 of `POST /support` and `POST /support/status`.

## 7. Monitoring and alerts (PRD 8.8)

Alerts are raised by the application itself and written to `storage/logs/track.alerts-<date>.log`
(JSON lines, CRITICAL) — which Sentry also receives once `SENTRY_LARAVEL_DSN` is set — and mailed to
`ALERT_EMAIL` when it is set. Each condition alerts at most once per 30 minutes
(`ALERT_REPEAT_AFTER_MINUTES`). Thresholds are in `config/monitoring.php`, each overridable by env.

| Condition | Raised by | Default threshold |
|---|---|---|
| Server error rate | `track:monitor`, from per-minute request counts (`RequestOutcomes` middleware) | > 1 % over 5 minutes, with ≥ 50 requests in the window |
| A queued job fails | the `JobFailed` event, immediately | any |
| Queue backlog | `track:monitor` | > 1,000 jobs |
| Queue store unreachable | `track:monitor` | any |
| Disk usage of the `storage/` volume | `track:monitor` | > 80 % |
| Support retention overdue: the sweep has stopped (§5a) | `track:monitor`, one indexed EXISTS | a request > `ALERT_RETENTION_GRACE_DAYS` (2) days past `support_retention_months` |
| Support retention cannot be checked | `track:monitor` | settings or database unreadable |
| Workers not polling / backlog age | `/health` → `checks.queue` (for the load balancer) | 120 s without a heartbeat; a job waiting > 5 min |
| Any account or address locked out of sign-in (SEC-082) | `track:monitor`, from `audit_logs` | any `auth.login_blocked` in the window |
| Failed sign-ins (SEC-082) | `track:monitor`, from `audit_logs` | > `ALERT_LOGIN_FAILURES` (50) in the window |
| One account refused unusually often (SEC-082) | `track:monitor`, from `audit_logs` | > `ALERT_DENIED_PER_USER` (20) `access.denied` for one user; the alert carries user ids only |
| Audit log writable again (SEC-082) | `track:monitor`, the §4 check on every run (MySQL/MariaDB) | the app user can UPDATE or DELETE `audit_logs` |
| Backup older than 26 hours (SEC-102) | `track:monitor` | no successful `track:backup` in 26 h |

`php artisan track:monitor` prints what it measured, so running it by hand is the quickest way to see
the current numbers. Error tracking: set `SENTRY_LARAVEL_DSN` to the university-approved instance;
personal data is not sent (`send_default_pii` false).

## 8. Hardening — TLS, web server, files, secrets, rollback (security plan Phase 9)

The application sets its own headers and refuses an unsafe configuration (SEC-070…073). What it cannot
do is configure the server around it. These are the host's half; the items marked **(D-7)** wait for
university IT to say how the hosting is arranged, and are recorded as deferred until then.

### 8.1 TLS and HSTS (G-06, 001 R-04)

- TLS 1.2 minimum, 1.3 preferred; no SSLv3, TLS 1.0/1.1, or RC4/3DES/CBC-SHA1 suites. Certificate
  renewal automated, with expiry monitored by the host **(D-7)**.
- HSTS set **at the web server** as the primary control: `Strict-Transport-Security: max-age=31536000;
  includeSubDomains`. The application also sends it, but only when it can see the request was HTTPS —
  which behind a TLS terminator depends on `TRUSTED_PROXIES` being right **(D-7)**.
- HTTP on port 80 only redirects to HTTPS; nothing is served over plain HTTP.

### 8.2 Web server (nginx shown)

```nginx
server {
    listen 443 ssl http2;
    server_name track.example.edu.sa;
    root /var/www/track/current/public;          # never the release root: .env, storage/, vendor/ stay outside
    index index.php;

    client_max_body_size 21m;                     # import_max_file_mb (20) plus form overhead; keep upload_max_filesize/post_max_size in php.ini in step

    location ~ /\.(?!well-known) { deny all; }   # .env, .git, .htaccess and every other dotfile
    location ~* \.(sql|bak|log|zip|xlsx|csv)$ { deny all; }

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    server_tokens off;
}
```

`/health` answers a single status to anyone and its component detail only with the `X-Health-Token`
header matching `HEALTH_TOKEN` (SEC-091); give that token to the load balancer and monitoring only.

### 8.3 Ownership and permissions

| Path | Owner | Mode | Why |
|---|---|---|---|
| release code (`/var/www/track/releases/*`) | `deploy:deploy` | `0755` dirs, `0644` files | PHP-FPM (`www-data`) reads code and cannot change it |
| `shared/storage/`, `bootstrap/cache/` | `www-data:www-data` | `0750` / `0640` | the only paths the application writes |
| `shared/.env` | `deploy:www-data` | `0640` | readable by PHP, not world-readable, not writable by PHP |
| `/etc/track/backup.key` | `root:backup` | `0640` | the backup job only (§6, §8.4) |

No `storage` symlink into `public/` (`php artisan storage:link` is never run: the private disk is not
served, security review 001 F-01).

### 8.4 Secrets: provisioning and rotation

| Secret | Where it lives | Rotation | Side effect of rotating |
|---|---|---|---|
| `APP_KEY` | `shared/.env` | only on suspected compromise | every session and encrypted cookie is invalidated (everyone signs in again); anything stored with `Crypt` must be re-encrypted first. The support channel keys its HMACs on it too (challenge signatures, form stamps, the hashed University-ID and reference rate-limit buckets), so outstanding challenges are refused once and those buckets reset |
| `DB_PASSWORD` | `shared/.env` + MySQL | yearly, or when staff with access leave | none if changed in both places in one deploy |
| `REDIS_PASSWORD` | `shared/.env` + Redis | yearly | queued jobs survive; sessions survive |
| backup key (`/etc/track/backup.key`) | host, **plus an offline copy held by the owner** | never silently: a new key starts a new backup chain; keep the old key until its backups age out (30 days) | a lost key makes every backup unreadable — the offline copy is the restore path **(D-7: who holds it)** |
| `SENTRY_LARAVEL_DSN`, `ALERT_EMAIL`, mail relay credentials | `shared/.env` | per provider | none |

Secrets are typed into the server, never committed (CI's gitleaks job, SEC-090, checks every commit),
and never pasted into a ticket or chat.

### 8.5 Redis and the queue (001 R-05)

Bound to the private interface only (`bind 10.x.x.x`), `requirepass` set (`REDIS_PASSWORD`), not
reachable from the internet, `protected-mode yes`. Queue payloads are serialized PHP: whoever can write
to this Redis can run code in the workers, which is why it is private.

### 8.6 Encryption at rest (001 R-04)

Database and backup volumes are encrypted at the disk level (AES-256), and backups additionally with
the backup key (§6). Confirm with the hosting provider before go-live **(D-7)**.

### 8.7 Rollback of a release

Releases are directories under `releases/` with `current` as a symlink (§2), so a rollback is a symlink
switch, not a redeploy:

1. `php artisan down` on the current release.
2. Decide on the database: if the bad release ran migrations, read each one's `down()`. Roll back
   (`php artisan migrate:rollback --step=<n> --force`) **only** when every one is safe to reverse —
   never one that drops a column holding data written since. When in doubt, keep the schema: the
   previous release must tolerate the newer one (expand-then-contract migrations).
3. Point `current` at the previous release: `ln -sfn /var/www/track/releases/<previous> /var/www/track/current`.
4. `php artisan config:cache && php artisan route:cache && php artisan view:cache` from the restored
   release; `supervisorctl restart track-worker:*`; reload PHP-FPM so opcache drops the old code.
5. `php artisan track:audit-grants --strict` and `php artisan track:preflight`; `php artisan up`; §3 smoke check.
6. Record what was rolled back and why. Keep at least the last 3 releases on disk so step 3 is possible.

### 8.8 Deferred until university IT answers (D-7)

| Item | Needed to decide |
|---|---|
| Edge rate limiting / WAF in front of `/support` (002 R-05) | whether a proxy or WAF exists |
| `TRUSTED_PROXIES` values | the load balancer's addresses |
| Certificate issuance and expiry monitoring | who terminates TLS |
| Backup storage location and key custody | where backups go, who holds the offline key |
| Incident contact | who is called for an alert out of hours |
