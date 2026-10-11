<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| US7 review — a column labelled "in Arabic" holds Arabic, "in English" holds English
|--------------------------------------------------------------------------
|
| The roster and the accounts list show each name in the reader's language, and both labelled the
| column with the Arabic-name label — so an English reader saw "Name in Arabic" above English names.
| Each table cell carries its column label (data-label), so the claim can be read off the page.
*/

test('a column that names a language holds that language', function (string $key, string $locale): void {
    seedScreens();
    app()->setLocale($locale);

    $arabicLabels = [__('students.fields.name_ar'), __('users.fields.name_ar'), __('courses.fields.name_ar')];
    $englishLabels = [__('students.fields.name_en'), __('users.fields.name_en'), __('courses.fields.name_en')];

    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="utf-8" ?>'.(string) renderScreen($key, $locale)->assertOk()->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);

    $wrong = [];

    foreach ((new DOMXPath($document))->query('//td[@data-label]') ?: [] as $cell) {
        if (! $cell instanceof DOMElement) {
            continue;
        }

        $label = $cell->getAttribute('data-label');
        $text = trim($cell->textContent);

        if ($text === '' || $text === __('common.labels.none')) {
            continue;
        }

        if (in_array($label, $arabicLabels, true) && preg_match('/\p{Arabic}/u', $text) !== 1) {
            $wrong[] = "{$label}: {$text}";
        }

        if (in_array($label, $englishLabels, true) && preg_match('/\p{Arabic}/u', $text) === 1) {
            $wrong[] = "{$label}: {$text}";
        }
    }

    expect($wrong)->toBe([]);
})->with(['users', 'courses', 'students', 'section', 'student'])->with(['ar', 'en']);
