<?php

declare(strict_types=1);

use App\Modules\Shared\Rules\UniversityId;

/*
|--------------------------------------------------------------------------
| T181 — the university id rule (FR-044)
|--------------------------------------------------------------------------
|
| In the Unit suite, which has no application booted. That is deliberate and it constrains the
| test: it can assert the rule's own logic and its default, but not the settings lookup, which
| needs a container and a database. The settings half is covered in the Feature suite, where it
| belongs.
|
*/

test('the default is the institution rule, not a permissive one', function (): void {
    /*
     * A database with no settings row is a broken deployment. The honest fallback is the
     * institution's actual rule — `.*` would quietly accept a name in an id column, which is the
     * failure a registrar discovers four hundred rows later.
     */
    expect((new UniversityId)->pattern())->toBe('^\d{9}$');
});

test('a nine-digit id passes', function (string $value): void {
    expect((new UniversityId)->matches($value))->toBeTrue();
})->with([
    'western digits' => '441234567',
    'all zeroes' => '000000000',

    // A pasted spreadsheet cell carries whitespace.
    'padded' => ' 441234567 ',
]);

test('arabic-indic digits are accepted and mean the same id', function (): void {
    /*
     * Measured before this was handled: `\d` under the /u modifier matches ٤٤١٢٣٤٥٦٧, so the value
     * passed validation and was stored verbatim — a second student record for a person who already
     * existed under Western digits, which is the duplicate FR-043 exists to prevent.
     *
     * Rejecting them instead would be defensible and worse: the digits are right, the person is
     * right, and nothing on screen says which numerals the field wants.
     */
    expect((new UniversityId)->matches('٤٤١٢٣٤٥٦٧'))->toBeTrue();

    expect(UniversityId::normalise('٤٤١٢٣٤٥٦٧'))->toBe('441234567')
        ->and(UniversityId::normalise('۴۴۱۲۳۴۵۶۷'))->toBe('441234567')
        ->and(UniversityId::normalise(' 441234567 '))->toBe('441234567');
});

test('anything else is refused', function (mixed $value): void {
    expect((new UniversityId)->matches($value))->toBeFalse();
})->with([
    'eight digits' => '44123456',
    'ten digits' => '4412345678',
    'letters' => '44123456a',
    'a name in an id column' => 'فيصل',
    'empty' => '',
    'an array' => [[['441234567']]],
    'null' => null,
]);
