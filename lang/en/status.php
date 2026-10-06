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
];
