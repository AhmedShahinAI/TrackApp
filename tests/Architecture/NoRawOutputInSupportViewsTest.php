<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

/*
|--------------------------------------------------------------------------
| T457 — no unescaped output anywhere in the support views (FR-153)
|--------------------------------------------------------------------------
|
| A description and a response are text a stranger typed. `{!! !!}` would hand that text to the browser
| as markup. NoMarkupInterpretedTest proves today's screens escape it; this keeps a later edit from
| undoing that with one pair of braces. A cell that needs markup builds it from escaped parts in an
| HtmlString, as the queue does, where the escaping is visible beside it.
|
*/

/**
 * @return list<string> "path:line" of every raw echo under the directory
 */
function rawEchoesUnder(string $directory): array
{
    $offenders = [];

    foreach ((new Finder)->files()->in($directory)->name('*.blade.php') as $file) {
        foreach (preg_split('/\R/', $file->getContents()) ?: [] as $number => $line) {
            if (str_contains($line, '{!!')) {
                $offenders[] = $file->getRelativePathname().':'.($number + 1);
            }
        }
    }

    return $offenders;
}

test('no view under resources/views/support echoes raw', function (): void {
    expect(rawEchoesUnder(resource_path('views/support')))->toBe([]);
});

/**
 * The other ways a view can print markup, which `{!!` alone does not find (Phase 25 review, L-3):
 * `echo` in a @php block, `->toHtml()`, and `HtmlString`. Blade comments are removed first, because
 * prose about echoing is not an echo.
 *
 * @return array{echoes: list<string>, html_strings: array<string, int>}
 */
function otherRawOutputUnder(string $directory): array
{
    $echoes = [];
    $htmlStrings = [];

    foreach ((new Finder)->files()->in($directory)->name('*.blade.php') as $file) {
        // Each comment becomes its own line breaks, so the line numbers reported stay the file's.
        $code = (string) preg_replace_callback(
            '/\{\{--.*?--\}\}/s',
            fn (array $comment): string => str_repeat("\n", substr_count($comment[0], "\n")),
            $file->getContents(),
        );

        foreach (preg_split('/\R/', $code) ?: [] as $number => $line) {
            if (preg_match('/(?<![\w$>-])(echo|print)\s|->toHtml\(\)/', $line) === 1) {
                $echoes[] = $file->getRelativePathname().':'.($number + 1);
            }
        }

        $count = substr_count($code, 'HtmlString(');

        if ($count > 0) {
            $htmlStrings[str_replace('\\', '/', $file->getRelativePathname())] = $count;
        }
    }

    ksort($htmlStrings);

    return ['echoes' => $echoes, 'html_strings' => $htmlStrings];
}

test('no support view echoes, prints or calls toHtml()', function (): void {
    expect(otherRawOutputUnder(resource_path('views/support'))['echoes'])->toBe([]);
});

test('every HtmlString in a support view is one that has been reviewed', function (): void {
    /*
     * An HtmlString is printed as it is, so whatever it wraps must already be escaped. Each one is
     * listed here after its escaping was read. Adding one fails this test until it is read and added.
     *
     *   staff/queue.blade.php — 3: the reference cell and the student cell, each value through e(); the
     *   status cell, through Blade::render with {{ }}.
     */
    expect(otherRawOutputUnder(resource_path('views/support'))['html_strings'])->toBe([
        'staff/queue.blade.php' => 3,
    ]);
});

test('the wider scan finds an echo, a toHtml() and a new HtmlString, but not a comment about them', function (): void {
    $directory = storage_path('framework/testing/raw-output-probe');
    @mkdir($directory, 0777, true);
    file_put_contents($directory.'/probe.blade.php', implode("\n", [
        '{{-- this comment says echo',
        'and toHtml() over two lines --}}',
        '@php echo $description; @endphp',
        '{{ $markdown->toHtml() }}',
        '{{ new Illuminate\Support\HtmlString($description) }}',
    ]));

    try {
        expect(otherRawOutputUnder($directory))->toBe([
            'echoes' => ['probe.blade.php:3', 'probe.blade.php:4'],
            'html_strings' => ['probe.blade.php' => 1],
        ]);
    } finally {
        @unlink($directory.'/probe.blade.php');
        @rmdir($directory);
    }
});

test('the scan finds a raw echo when there is one', function (): void {
    $directory = storage_path('framework/testing/raw-echo-probe');
    @mkdir($directory, 0777, true);
    file_put_contents($directory.'/probe.blade.php', "<p>{{ \$safe }}</p>\n<p>{!! \$description !!}</p>\n");

    try {
        expect(rawEchoesUnder($directory))->toBe(['probe.blade.php:2']);
    } finally {
        @unlink($directory.'/probe.blade.php');
        @rmdir($directory);
    }
});
