# Typed EAV capacity spike — results

**Tasks:** T290 – T293 · **Method:** [research.md](./research.md) R-06 · **Requirement:** FR-087, FR-088, SC-017
**Measured:** 2026-10-09 (after the Phase 12 review corrections)

> ## Status: PROVISIONAL — the deciding MySQL run has not been made
>
> Every threshold passes on **SQLite**, with and without the two measurement indexes. FR-087 asks for
> the design to be *demonstrated*, and the design ships on MySQL / MariaDB, whose planner, buffer pool
> and write path differ from an embedded engine's. Until the MySQL run below is recorded here, this
> result is strong evidence, not the decision: **the feature is not accepted on it**. Phase 2 planning
> may proceed on the assumption that the design holds, with this one confirmation outstanding.

## Verdict

| | |
|---|---|
| **SQLite (this run)** | **PASS** — all five thresholds, with and without the measurement indexes; tightest margin **59×** |
| **MySQL / MariaDB** | **Not yet measured** — no MySQL-class server on the measuring machine. One command; see "The deciding run" |
| **Index recommendation** | Ship **neither** measurement index unless the MySQL run shows a query that needs one (F-2, F-3) |
| **Operational note** | Refresh table statistics after bulk value loads (F-1) |

## Environment

| | |
|---|---|
| Engine | SQLite 3.53.4, file database, WAL journal |
| PHP | 8.4.26 |
| Machine | Intel Core Ultra 7 155U (12 cores / 14 threads), 15 GB RAM, NVMe SSD (Micron 1 TB), Windows 11 |
| Run kind | **Warm cache.** Each run opens a fresh connection, so nothing is cached in the connection, but the OS page cache stays warm — these are not cold-cache numbers. `EAV_SPIKE_COLD_COMMAND` (e.g. a MySQL restart) makes each run truly cold; it was not available here |
| Samples | 10 runs per query; with 10 samples the reported p95 is the slowest run (the conservative reading) |

## Schema measured

Migrations `2026_10_09_100001` (initiatives, schema versions, fields, initiative sections) and
`2026_10_09_100002` (targets, records, values) — data-model.md Group 6, with its unique keys:
`(initiative_id, version_number)`, `(schema_version_id, field_key)`, `(initiative_id, section_id)`,
`(assignment_id, student_id)` on targets and on records, `(record_id, field_id)` on values. Typed value
columns: `value_text` text (utf8mb4 on MySQL), `value_number` decimal(18,4), `value_date` date,
`value_bool` boolean, `value_json` json (text on SQLite), `attachment_id`.

| Run | Indexes beyond the unique keys |
|---|---|
| **with** | `student_initiative_values(field_id, value_bool)` · `student_initiative_records(assignment_id, is_complete)` — migration `2026_10_09_100003`, switched on for the spike's own database only |
| **without** | none — and none in the application's migrations either, until the MySQL run decides |

## Data (both runs)

| Table | Rows |
|---|---|
| sections · students · section_enrollments | 3,000 · 30,000 · 30,000 |
| initiatives · schema versions · fields | 10 · 50 · 50 |
| initiative_sections (assignments) · targets · records | 3,000 · 30,000 · 30,000 |
| **student_initiative_values** | **1,500,000** (database 214 MB) |

- **Mix:** 50 fields — 23 text, 12 number, 7 Yes/No, 5 date, 3 JSON — so values fall ≈46 / 24 / 14 / 10 / 6 % across the typed columns (R-06: ≈45 / 25 / 15 / 10 / 5).
- **Values:** Arabic free text averaging 76 characters (up to 483; 70 % short answers, 5 % paragraphs); JSON averaging 143 characters; Yes/No about 70 % Yes; a required text answer empty 8 % of the time. About 22 % of records complete (6,667).
- **Shape — a deliberate worst case for one initiative:** initiative 1 holds all 30,000 records, each with all 50 values (dense), so every per-initiative query reads the whole 1.5M; real terms spread values over several initiatives and leave fields blank. One assignment holds 200 students (the grid's case), the others 9–10.
- **Integrity**, checked on every run: no value has two typed columns set; every value's field belongs to its record's initiative's current schema version.

## Measured p95, after the review corrections

| Query (R-06) | Threshold | **with** | **without** | Result |
|---|---|---|---|---|
| 1 · Grid: 200 students in roster order × 15 fields, pivoted | < 2 s | **0.011 s** | **0.014 s** | PASS |
| 2 · Completion per section over active targets, 3,000 sections | < 3 s | **0.051 s** | **0.044 s** | PASS |
| 3 · Initiative-wide student-weighted completion, 30,000 targets | < 3 s | **0.026 s** | **0.031 s** | PASS |
| 4 · Recalculation sweep, 30,000 records, written back | < 2 min | **0.549 s** | **0.536 s** | PASS |
| 5 · Single value upsert + completion + audit entry | < 500 ms | **0.005 s** | **0.004 s** | PASS |

Medians (with / without): grid 0.010 / 0.010 · per-section 0.043 / 0.033 · weighted 0.024 / 0.026 ·
sweep 0.52 / 0.44 · upsert 0.003 / 0.004 s. Tightest margin: the per-section count with the indexes,
3 s ÷ 0.051 s ≈ **59×**.

**The sweep agrees.** The set of records it re-derives as complete equals, id for id, the set the stored
flags called complete before it ran — on every one of the ten runs, in both configurations.

### What each query does

1. **Grid** — the assignment's records joined to their students and their active targets, ordered by name (the roster the instructor sees); then the 15 visible fields' values for exactly those records; pivoted student × field.
2. **Per-section** — `initiative_sections ⋈ sections ⋈ initiative_targets (active) ⟕ student_initiative_records`, grouped by section with its number and branch: total and complete.
3. **Weighted** — `initiative_targets (active) ⋈ initiative_sections ⟕ student_initiative_records`: targets and complete, initiative-wide.
4. **Sweep** — per 1,000-record range, the records whose required fields are all satisfied (Yes/No = Yes, others non-empty), then `is_complete` rewritten for the range in one transaction.
5. **Upsert** — one required Yes/No answer: `INSERT … ON CONFLICT (record_id, field_id) DO UPDATE` (MySQL: `ON DUPLICATE KEY UPDATE`), the record's completion re-derived with `completed_at` and `updated_by`, and an audit entry — one transaction, as constitution II requires.

Not measured, by design: authorization scoping. Branch or section scoping narrows queries 1–3 to a
subset of what is measured here (the grid is already one assignment), so the unscoped Super Admin case
is the upper bound.

## Plans (SQLite `EXPLAIN QUERY PLAN`, with the measurement indexes)

```
1 roster   SEARCH r USING INDEX sir_assignment_complete_index (assignment_id=?)   [without: …records_assignment_id_student_id_unique]
           SEARCH t USING INDEX initiative_targets_assignment_id_student_id_unique (assignment_id=? AND student_id=?)
           SEARCH s USING INTEGER PRIMARY KEY (rowid=?)
           USE TEMP B-TREE FOR ORDER BY
1 values   SEARCH student_initiative_values USING INDEX student_initiative_values_record_id_field_id_unique (record_id=? AND field_id=?)
2          SEARCH a USING COVERING INDEX initiative_sections_initiative_id_section_id_unique (initiative_id=?)
           SEARCH s USING INTEGER PRIMARY KEY (rowid=?)
           SEARCH t USING INDEX initiative_targets_assignment_id_student_id_unique (assignment_id=?)
           SEARCH r USING INDEX student_initiative_records_assignment_id_student_id_unique (assignment_id=? AND student_id=?) LEFT-JOIN
3          SEARCH a USING COVERING INDEX initiative_sections_initiative_id_section_id_unique (initiative_id=?)
           SEARCH t USING INDEX initiative_targets_assignment_id_student_id_unique (assignment_id=?)
           SEARCH r USING INDEX student_initiative_records_assignment_id_student_id_unique (assignment_id=? AND student_id=?) LEFT-JOIN
4, 5       SEARCH student_initiative_values USING INDEX student_initiative_values_record_id_field_id_unique (record_id>? AND record_id<?)
```

Without the measurement indexes the plans are identical except the roster's first step. **No plan uses
`siv_field_bool_index`**, and with the realistic per-section query (over targets) query 2 no longer uses
the `(assignment_id, is_complete)` index at all.

## Findings

**F-1 · The `(field_id, value_bool)` index misleads the planner when statistics are stale.** The first
full run measured with no planner statistics: SQLite chose `siv_field_bool_index` for the one-record
completion check and walked every value of the five required fields (~150,000 rows) — upsert p95 0.737 s
(**FAIL**), sweep 35 s. With statistics refreshed the same query reads the record's five values in 2 ms.

*Does it apply to MySQL?* InnoDB keeps persistent statistics and recalculates them automatically once
about 10 % of a table changes (`innodb_stats_auto_recalc`), but in the background — so a bulk import can
be read before its statistics catch up, which is the window F-1 lives in. `ANALYZE TABLE` on the four
Group 6 tables samples index pages and takes seconds at 1.5M rows. **Owner:** Phase 2's bulk value writer
(import or mass update), as the last step after its final commit; the deploy runbook gains the same step
only if Phase 2 adopts it.

**F-2 · `(field_id, value_bool)` earns nothing measured.** No measured query uses it once statistics are
current; it is maintained on every value written (each grid save, every imported row) on a 1.5M-row
table, and it is the cause of F-1.

**F-3 · `(assignment_id, is_complete)` earns nothing measured once the counts use targets.** The first
version of query 2 counted records, and this index made it covering (3× faster). The corrected query
counts active targets and joins records on their `(assignment_id, student_id)` key, and no longer uses it.

**F-4 · Review corrections that changed the numbers.** The first results overstated the method:
- the sweep's agreement check compared the sweep with flags it had just written (tautological);
- the runs were called cold but were warm-cache;
- the grid and per-section queries omitted the student join, roster order, active-target filter and section identity the screens need;
- the upsert omitted the audit entry and the record's author and completion time, and wrote a Yes/No answer onto whichever required field came first — once the text field, leaving two typed columns on one value (caught by the new integrity check);
- text values were a few characters long, where real notes run to sentences (database 110 → 214 MB once lengthened).

All corrected; the table above is measured with the corrections.

## Open question for Phase 2 — Q-1: integrity the schema leaves to the application

data-model.md Group 6 gives a record no schema-version link and the value table no type check, so
nothing in the database stops (a) two typed columns being set on one value, or (b) a value pointing at a
field from another initiative or version than its record's. The spike verifies both on its data on every
run (`eavSpikeIntegrity()`), but the write path must enforce them. Phase 2 decides: application-level
enforcement in the single value writer, a `schema_version_id` on records, or a CHECK constraint (MySQL
8.0.16+ / MariaDB 10.2+). Not decided here — designing initiatives is Phase 2's.

## Remedy (T293 / FR-088)

No threshold fails in the measured configuration, so no remedy is required. R-06's ranked candidates,
considered in order:
1. the composite `(record_id, field_id)` index — already the unique key, and every completion query uses it; its companion `(field_id, value_bool)` was measured and is not needed (F-2);
2. the `is_complete` denormalisation — already in the schema and used by queries 2 and 3;
3. aggregate tables and 4. a JSON read column — unnecessary at this volume.

Recommendation for Phase 2, **to be confirmed or reversed by the MySQL run**: ship neither measurement
index; refresh statistics after bulk value loads (F-1); settle Q-1.

## The deciding run (MySQL / MariaDB)

Needs a MySQL 8.0+ or MariaDB 10.11+ server and a user that may `CREATE DATABASE` — a dedicated spike
user on a non-shared server, never the application's. The spike refuses any schema that matches a
configured connection's database or a system schema (case-insensitive), creates its own, and drops and
reseeds only that.

```bash
EAV_SPIKE_MYSQL_DATABASE=track_eav_spike php artisan test --group=performance tests/Performance/EavSpikeTest.php
EAV_SPIKE_MYSQL_DATABASE=track_eav_spike EAV_SPIKE_INDEXES=false php artisan test --group=performance tests/Performance/EavSpikeTest.php
# Truly cold runs, on a host where the user may restart the server:
EAV_SPIKE_MYSQL_DATABASE=track_eav_spike EAV_SPIKE_COLD_COMMAND="sudo systemctl restart mysql" php artisan test --group=performance tests/Performance/EavSpikeTest.php
```

Then copy each run's `storage/app/eav-spike-results.json` figures into a MySQL table here, with the server
version and machine, decide the two indexes, and change the status above.

## Re-running on SQLite

```bash
php artisan test --group=performance tests/Performance/EavSpikeTest.php                         # seeds storage/app/eav-spike.sqlite once (~20 s)
EAV_SPIKE_INDEXES=false php artisan test --group=performance tests/Performance/EavSpikeTest.php
rm storage/app/eav-spike.sqlite*   # 214 MB, git-ignored, not deployed or backed up; delete when done
```
