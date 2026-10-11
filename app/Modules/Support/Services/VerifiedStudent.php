<?php

declare(strict_types=1);

namespace App\Modules\Support\Services;

/**
 * T368 — what a successful verification found: the student, and the current term they are enrolled
 * in. There is deliberately no failure counterpart; a failure is `null` and carries no reason.
 */
final readonly class VerifiedStudent
{
    public function __construct(
        public int $studentId,
        public int $termId,
    ) {}
}
