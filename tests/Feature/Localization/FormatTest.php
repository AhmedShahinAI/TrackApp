<?php

declare(strict_types=1);

use App\Models\AcademicTerm;
use App\Modules\Shared\Support\Format;

/*
|--------------------------------------------------------------------------
| T270 — Western digits and DD/MM/YYYY in both languages, terms as "2026/2027 - الأول" (FR-084)
|--------------------------------------------------------------------------
*/

test('dates read DD/MM/YYYY with Western digits whatever the language', function (string $locale): void {
    app()->setLocale($locale);

    expect(Format::date('2026-09-05'))->toBe('05/09/2026')
        ->and(Format::dateTime('2026-09-05 11:07:00'))->toBe("\u{2066}05/09/2026 14:07\u{2069}")
        ->and(Format::date(null))->toBe('—');
})->with(['ar', 'en']);

test('a stored UTC instant reads on the Riyadh clock and calendar', function (): void {
    // 22:30 UTC is 01:30 the next morning in Riyadh: both the hour and the day move.
    expect(Format::dateTime('2026-09-05 22:30:00'))->toBe("\u{2066}06/09/2026 01:30\u{2069}")
        ->and(Format::date('2026-09-05 22:30:00'))->toBe('06/09/2026')
        ->and(Format::dateTime(now('UTC')->setDate(2026, 1, 1)->setTime(6, 0)))->toBe("\u{2066}01/01/2026 09:00\u{2069}");
});

test('a date and time read date-first in an Arabic paragraph, isolated left-to-right', function (): void {
    app()->setLocale('ar');

    // LRI … PDI around the whole value: the space between the two digit runs cannot take the
    // paragraph's right-to-left direction and swap them.
    expect(Format::dateTime('2026-10-09 02:25:00'))->toStartWith("\u{2066}09/10/2026")
        ->and(Format::dateTime('2026-10-09 02:25:00'))->toEndWith("05:25\u{2069}");
});

test('a calendar date names a day, so no zone moves it', function (): void {
    config(['app.display_timezone' => 'America/Los_Angeles']);

    expect(Format::date('2026-09-05'))->toBe('05/09/2026')
        ->and(Format::date(Illuminate\Support\Carbon::parse('2026-09-05')))->toBe('05/09/2026');
});

test('numbers use Western digits and comma grouping whatever the language', function (string $locale): void {
    app()->setLocale($locale);

    expect(Format::number(50000))->toBe('50,000')
        ->and(Format::number('1234'))->toBe('1,234')
        ->and(Format::number(null))->toBe('0');
})->with(['ar', 'en']);

test('a term reads as its academic year and the semester in the reader\'s language', function (): void {
    $term = AcademicTerm::factory()->make(['academic_year' => '2026/2027', 'semester' => 'first']);

    app()->setLocale('ar');
    expect(Format::term($term))->toBe('2026/2027 - الأول');

    app()->setLocale('en');
    expect(Format::term($term))->toBe('2026/2027 - First');

    expect(Format::term(null))->toBe('—');
});

test('no rendered date anywhere still uses a month name', function (): void {
    seedScreens();

    foreach (['section', 'student', 'terms', 'users', 'audit'] as $key) {
        $text = readableText((string) renderScreen($key, 'ar')->assertOk()->getContent());

        // isoFormat('LL') wrote Arabic month names; none should survive the sweep.
        expect($text)->not->toMatch('/\d{1,2} (يناير|فبراير|مارس|أبريل|مايو|يونيو|يوليو|أغسطس|سبتمبر|أكتوبر|نوفمبر|ديسمبر) \d{4}/u', $key);
    }
});
