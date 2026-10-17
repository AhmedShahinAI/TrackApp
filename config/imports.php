<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Uploaded import files (security plan G-05, SEC-062, SEC-063)
    |--------------------------------------------------------------------------
    |
    | An uploaded roster is personal data, and once its rows are staged in `import_rows` the file
    | itself is needed only by validation, which re-reads it if the mapping changes. So:
    |
    |   - a completed import's file is deleted when the import completes;
    |   - `track:prune-import-files` (daily) deletes the files of imports that finished or failed
    |     more than `file_retention_days` ago, and files no import refers to at all.
    |
    | 30 days by default, matching backup retention (the owner's decision D-6 defaulted). Backups
    | keep a deleted file until they age out themselves (docs/runbook-deploy.md §5a).
    |
    */

    'file_retention_days' => (int) env('IMPORT_FILE_RETENTION_DAYS', 30),
];
