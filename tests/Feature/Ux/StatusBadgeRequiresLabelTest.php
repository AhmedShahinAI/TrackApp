<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

/*
|--------------------------------------------------------------------------
| T261 — a status badge cannot show a colour without a text label (FR-077)
|--------------------------------------------------------------------------
|
| Colour alone fails readers who cannot tell the tones apart, and screen readers announce no colour
| at all. The badge always renders its label from lang/{ar,en}/status.php — so the one way it could still
| show colour without words is a status the vocabulary does not know, which printed the raw key
| "status.something" beside a coloured dot. An unknown status with no label of its own now reads
| "Unknown status" in the neutral tone and is logged — words beside the colour, and a page that stays
| up (it was an exception, which turned a whole table into a 500 over one unlabelled row).
|
*/

/** The nineteen statuses of design-system.md §8, plus the enum values the schema allows. */
const BADGE_STATUSES = [
    'active', 'current', 'completed', 'valid', 'progress', 'running', 'shared', 'late', 'failed', 'error',
    'pending', 'warning', 'queued', 'notStarted', 'closed', 'draft', 'archived', 'withdrawn', 'inactive',
    'returned', 'rejected', 'committed', 'skipped',
];

test('every status enum value in the schema has a badge label', function (): void {
    $missing = [];

    foreach (glob(database_path('migrations/*.php')) ?: [] as $file) {
        preg_match_all("/->enum\('(?:status|from_status|to_status)',\s*\[([^\]]*)\]/", (string) file_get_contents($file), $enums);

        foreach ($enums[1] as $list) {
            preg_match_all("/'([^']+)'/", $list, $values);

            foreach ($values[1] as $value) {
                // Import jobs carry their own vocabulary (imports.status.*) and pass the badge a label.
                // Support requests likewise (support.statuses.*, via SupportRequestStatus::labelKey());
                // FoundationModelsTest asserts each of their five has a label in both languages.
                if (str_contains($file, 'import_jobs') || str_contains($file, 'support_requests')) {
                    continue;
                }

                if (! in_array($value, BADGE_STATUSES, true)) {
                    $missing[] = basename($file).": {$value}";
                }
            }
        }
    }

    expect($missing)->toBe([]);
});

test('every status renders its own label, in both languages', function (string $status): void {
    foreach (['ar', 'en'] as $locale) {
        app()->setLocale($locale);

        $html = Blade::render('<x-track-status-badge :status="$s" />', ['s' => $status]);
        $label = trim(strip_tags($html));

        expect($label)->toBe(__('status.'.$status))
            ->and($label)->not->toBe('status.'.$status)
            ->and($label)->not->toBe('');
    }
})->with(BADGE_STATUSES);

test('a badge given its own label shows that label', function (): void {
    $html = Blade::render('<x-track-status-badge status="running">قيد الاستيراد</x-track-status-badge>');

    expect(trim(strip_tags($html)))->toBe('قيد الاستيراد');
});

test('an unknown status with no label still shows words, and is logged', function (): void {
    $log = Illuminate\Support\Facades\Log::spy();

    foreach (['ar', 'en'] as $locale) {
        app()->setLocale($locale);

        $html = Blade::render('<x-track-status-badge status="not-a-status" />');

        expect(trim(strip_tags($html)))->toBe(__('status.unknown'))
            ->and($html)->toContain('track-badge--neutral');
    }

    $log->shouldHaveReceived('warning')
        ->withArgs(fn (string $message, array $context): bool => $context === ['status' => 'not-a-status'])
        ->twice();
});

test('an unknown status is allowed when it brings its own label', function (): void {
    $html = Blade::render('<x-track-status-badge status="not-a-status">نص</x-track-status-badge>');

    expect(trim(strip_tags($html)))->toBe('نص');
});
