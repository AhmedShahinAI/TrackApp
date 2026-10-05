<?php

declare(strict_types=1);

// Placeholder so the Performance testsuite resolves before its first real test lands.
// Remove when the first Performance test is written.

// The performance group is excluded from the default run (phpunit.xml). Every real test in this
// directory must carry this group, or it will run on every build.
test('Performance suite is registered', function (): void {
    expect(1 + 1)->toBe(2);
})->group('performance');
