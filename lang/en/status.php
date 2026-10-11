<?php

declare(strict_types=1);

/*
 * T099 — the English half of the fixed status vocabulary (design-system.md §8).
 *
 * Key for key with lang/ar/status.php. A key present in one and missing from the other fails
 * LocalizationParityTest (T054) rather than rendering the key name to a user.
 */

return [
    'active' => 'Active',
    'completed' => 'Completed',
    'progress' => 'In progress',
    'late' => 'Late',
    'pending' => 'Awaiting approval',
    'notStarted' => 'Not started',
    'closed' => 'Closed',
    'draft' => 'Draft',
    'archived' => 'Archived',
    'withdrawn' => 'Withdrawn',
    'inactive' => 'Suspended',
    'failed' => 'Failed',
    'queued' => 'Queued',
    'running' => 'Running',
    'warning' => 'Warning',
    'error' => 'Error',
    'valid' => 'Valid',
    'shared' => 'Shared access',
    'current' => 'Current',

    // Section approval and import-row states the schema allows (US7 review: every enum value has a label).
    'returned' => 'Returned',
    'rejected' => 'Rejected',
    'committed' => 'Saved',
    'skipped' => 'Skipped',

    // The registrar's academic status on an enrollment (FR-110), shown beside TRACK's own status.
    'regular' => 'Regular',
    'excused' => 'Excused',

    // x-track-status-badge's fallback for a value the vocabulary does not know yet.
    'unknown' => 'Unknown status',
];
