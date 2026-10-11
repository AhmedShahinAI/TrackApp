<?php

declare(strict_types=1);

namespace App\Modules\Support\Services\Challenge;

use RuntimeException;

/**
 * T370 — a challenge could not be issued: the store is down, or the difficulty setting is unusable.
 *
 * GET /support/challenge answers this with 503 and a generic message (T392), and the form then
 * refuses to submit — failing closed (FR-129). The cause is logged, never shown.
 */
final class ChallengeUnavailable extends RuntimeException {}
