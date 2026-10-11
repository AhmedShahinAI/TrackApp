<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Typed EAV capacity spike — Phase 12 (research.md R-06)
|--------------------------------------------------------------------------
|
| `measurement_indexes` is read by migration 2026_10_09_100003. It is OFF for the application: whether
| the two indexes ship is the spike's decision (spike-eav-results.md), and an environment flag must not
| decide a production schema. The spike turns it on for its own database only, when
| `measure_with_indexes` asks it to (tests/Support/EavSpike.php, eavSpikeSeed()).
|
*/

return [

    // The application's migration switch. Leave false; the spike sets it on its own connection.
    'measurement_indexes' => false,

    // The spike run's switch: measure with the two indexes under test (EAV_SPIKE_INDEXES=false for the
    // comparison run without them).
    'measure_with_indexes' => (bool) env('EAV_SPIKE_INDEXES', true),

    // Shrinks the seed for a quick smoke run; 1.0 is the specified 1,500,000 values.
    'scale' => (float) env('EAV_SPIKE_SCALE', 1.0),

    // A MySQL / MariaDB schema to run the spike in (the deciding run); empty runs it on a SQLite file.
    // Never an application or system schema: the spike drops and reseeds it, and refuses those names.
    'mysql_database' => (string) env('EAV_SPIKE_MYSQL_DATABASE', ''),

    // Run before each measured query run to empty the caches, so a run is truly cold — on a MySQL
    // host, e.g. `sudo systemctl restart mysql`. Empty: a fresh connection per run, caches warm.
    'cold_command' => (string) env('EAV_SPIKE_COLD_COMMAND', ''),

];
