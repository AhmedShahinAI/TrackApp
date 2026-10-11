<?php

declare(strict_types=1);

// Placeholder so the Enumeration testsuite resolves before its first real test lands (T357).
// A todo, not an always-true assertion: the suite reports "1 todo" until real tests exist, rather
// than a green run that proves nothing. Remove when the first enumeration test is written
// (Phase 21, FR-117 / SC-026).
//
// Run alone with: php artisan test --testsuite=Enumeration

test('the support channel does not reveal whether a University ID exists')
    ->todo('Phase 21 — FR-117 / SC-026: identical responses for known and unknown identifiers.');
