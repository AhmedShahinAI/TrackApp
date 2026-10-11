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

## 2. Deploy

Zero-downtime release directories (Deployer or Envoy, PRD §9.5). In order:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build          # prebuild: fonts → icons → brand assets (see §2.1), then vite build
php artisan migrate --force
php artisan track:audit-grants --strict   # fails the deploy if the app user can UPDATE or DELETE audit_logs (§4)
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
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

It runs `track:monitor` every five minutes (§7). `onOneServer()` keeps a multi-server deployment from
alerting twice; that needs the shared Redis cache.

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
  entries about those requests are not (T459 appends the details here).

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
| Workers not polling / backlog age | `/health` → `checks.queue` (for the load balancer) | 120 s without a heartbeat; a job waiting > 5 min |

`php artisan track:monitor` prints what it measured, so running it by hand is the quickest way to see
the current numbers. Error tracking: set `SENTRY_LARAVEL_DSN` to the university-approved instance;
personal data is not sent (`send_default_pii` false).
