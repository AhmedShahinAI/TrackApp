<?php

declare(strict_types=1);

use App\Models\AcademicTerm;
use App\Modules\AcademicTerms\Actions\ChangeTermStatus;

/*
|--------------------------------------------------------------------------
| T262 — the four consequential actions go through a confirmation that restates the impact (FR-082)
|--------------------------------------------------------------------------
|
| Student withdrawal, account deactivation, instructor change and term status change. For each:
| the screen offers no direct way to do it — its control opens a dialog — and that dialog says what
| will happen in words, not just "Are you sure?". The last test makes sure the confirmation can
| actually be confirmed: a dialog whose submit button sits outside its form confirms nothing.
|
*/

function dialogNode(string $html, string $id): ?DOMElement
{
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);

    $node = (new DOMXPath($document))->query("//*[@id='{$id}']")?->item(0);

    return $node instanceof DOMElement ? $node : null;
}

test('withdrawing a student is confirmed, and the confirmation says the record survives', function (): void {
    seedScreens();

    $html = (string) renderScreen('section', 'ar')->assertOk()->getContent();
    $dialog = dialogNode($html, 'withdraw-dialog');

    expect($dialog)->not->toBeNull()
        ->and($dialog?->textContent)->toContain(__('students.confirm.withdraw_body'))
        // The row action opens the dialog; it is not itself a form that submits.
        ->and($html)->toContain('track-withdraw-open');
});

test('deactivating an account is confirmed, and the confirmation says history is kept', function (): void {
    seedScreens();

    $html = (string) renderScreen('users', 'ar')->assertOk()->getContent();
    $dialog = dialogNode($html, 'user-deactivate');

    expect($dialog)->not->toBeNull()
        ->and($dialog?->textContent)->toContain(__('users.deactivate.message'))
        ->and($dialog?->textContent)->toContain(__('users.deactivate.preserved'));
});

test('changing a section\'s instructor is confirmed, and the confirmation says authorship stays', function (): void {
    seedScreens();

    $dialog = dialogNode((string) renderScreen('section', 'ar')->assertOk()->getContent(), 'assign-dialog');

    expect($dialog)->not->toBeNull()
        ->and($dialog?->textContent)->toContain(__('sections.confirm.change_instructor_body'));
});

test('every term status change is confirmed, naming the term and both states', function (): void {
    seedScreens();

    $html = (string) renderScreen('terms', 'ar')->assertOk()->getContent();

    foreach (AcademicTerm::query()->get() as $term) {
        foreach (ChangeTermStatus::TRANSITIONS[$term->status] ?? [] as $to) {
            $dialog = dialogNode($html, 'term-'.$term->id.'-to-'.$to);

            expect($dialog)->not->toBeNull("term {$term->id} → {$to}")
                ->and($dialog?->textContent)->toContain(__('status.'.$term->status))
                ->and($dialog?->textContent)->toContain(__('status.'.$to))
                ->and($dialog?->textContent)->toContain($term->name_ar);
        }
    }
});

test('every dialog\'s submit button belongs to a form, on every screen', function (string $key): void {
    seedScreens();

    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="utf-8" ?>'.renderScreen($key, 'ar')->assertOk()->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);

    $stranded = [];

    foreach ($xpath->query("//*[@role='dialog']//button[@type='submit']") ?: [] as $button) {
        if (! $button instanceof DOMElement) {
            continue;
        }

        $inForm = $xpath->query('ancestor::form', $button)?->length > 0;
        $formId = $button->getAttribute('form');
        $linked = $formId !== '' && $xpath->query("//form[@id='{$formId}']")?->length > 0;

        if (! $inForm && ! $linked) {
            $dialog = $xpath->query("ancestor::*[@role='dialog'][1]", $button)?->item(0);
            $stranded[] = $dialog instanceof DOMElement ? ($dialog->getAttribute('aria-labelledby') ?: 'dialog') : 'dialog';
        }
    }

    // A submit button outside any form submits nothing: the dialog opens, the user confirms, and
    // nothing happens. Found by this sweep in nine Phase 7 dialogs.
    expect($stranded)->toBe([], "{$key}: dialogs whose submit button is outside its form");
})->with(array_keys(screens()));
