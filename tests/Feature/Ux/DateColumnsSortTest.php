<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| US7 review — a date column sorts by date, not by its DD/MM/YYYY text
|--------------------------------------------------------------------------
|
| data-table.js sorts on `data-sort-value`. When T270 moved every date to DD/MM/YYYY, that value
| became the displayed text, which is not a number — so the sort fell back to comparing strings and
| put 01/12/2026 before 02/01/2026. Every date cell must carry a sortable number.
*/

test('every rendered date cell sorts on a timestamp', function (string $key): void {
    seedScreens();

    $html = (string) renderScreen($key, 'en')->assertOk()->getContent();

    preg_match_all('/<td[^>]*data-sort-value="([^"]*)"[^>]*>\s*\x{2066}?(\d{2}\/\d{2}\/\d{4})/u', $html, $cells, PREG_SET_ORDER);

    expect($cells)->not->toBe([], "{$key} renders no date cells");

    foreach ($cells as [, $sortValue, $shown]) {
        expect($sortValue)->toMatch('/^\d+$/', "{$key}: a cell showing {$shown} sorts on \"{$sortValue}\"");
    }
})->with(['audit', 'imports', 'users', 'terms', 'section', 'student', 'home']);

test('every column offered for sorting has something to sort on', function (string $key): void {
    seedScreens();

    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="utf-8" ?>'.(string) renderScreen($key, 'en')->assertOk()->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    $empty = [];

    foreach ($xpath->query('//table') ?: [] as $table) {
        $headers = $xpath->query('./thead//th', $table);

        foreach ($headers ?: [] as $index => $th) {
            if ($xpath->query('.//*[contains(@class,"track-table__sort")]', $th)?->length === 0) {
                continue;
            }

            // A sort button over a column whose every cell sorts on "" — the actions column — is a
            // control that does nothing.
            $values = [];

            foreach ($xpath->query('./tbody/tr[not(td[@colspan])]/td['.($index + 1).']', $table) ?: [] as $cell) {
                if ($cell instanceof DOMElement && $cell->getAttribute('data-sort-value') !== '') {
                    $values[] = $cell->getAttribute('data-sort-value');
                }
            }

            $cells = $xpath->query('./tbody/tr[not(td[@colspan])]/td['.($index + 1).']', $table);
            $rows = $cells === false ? 0 : $cells->length;

            if ($rows > 0 && $values === []) {
                $empty[] = trim((string) $th->textContent);
            }
        }
    }

    expect($empty)->toBe([]);
})->with(array_keys(screens()));
