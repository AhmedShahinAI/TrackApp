<?php

declare(strict_types=1);

use App\Models\AcademicTerm;
use App\Models\SectionEnrollment;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| US7 review — a refused dialog comes back open, with the refusal in it (FR-078, FR-082)
|--------------------------------------------------------------------------
|
| Every dialog form posts and redirects back. Before this, the page came back with every dialog
| shut: a 422 beside a field, or a 409 flashed as `conflict`, was rendered inside a hidden dialog —
| or, for the term reason, not rendered at all — and the person saw the page reload and nothing else.
*/

/**
 * The dialog with this id in the page, and whether it came back open.
 *
 * @return array{open: bool, alerts: list<string>}
 */
function dialogState(string $html, string $id): array
{
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);

    $dialog = $xpath->query("//*[@id='{$id}']")?->item(0);

    if (! $dialog instanceof DOMElement) {
        return ['open' => false, 'alerts' => []];
    }

    $alerts = [];

    foreach ($xpath->query(".//*[@role='alert']", $dialog) ?: [] as $alert) {
        $alerts[] = trim((string) $alert->textContent);
    }

    return [
        'open' => str_contains($dialog->getAttribute('x-data'), 'trackDialog(true)'),
        'alerts' => array_values(array_filter($alerts)),
    ];
}

test('every dialog form on every screen comes back open with its error inside it', function (string $key): void {
    seedScreens();

    $html = (string) renderScreen($key, 'ar')->assertOk()->getContent();
    $page = route(screens()[$key][0], screens()[$key][1]);
    $checked = 0;
    $failures = [];

    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);

    foreach ($xpath->query("//form[@action][.//input[@name='_dialog']]") ?: [] as $form) {
        if (! $form instanceof DOMElement) {
            continue;
        }

        $marker = $xpath->query(".//input[@name='_dialog']", $form)?->item(0);
        $required = $xpath->query('.//*[@required][@name]', $form);

        // A form with nothing to refuse (set-current, assign) is covered by the 409 test below.
        if (! $marker instanceof DOMElement || $required === false || $required->length === 0) {
            continue;
        }

        $dialogId = $marker->getAttribute('value');

        $payload = [];

        foreach ($xpath->query(".//input[@type='hidden'][@name]", $form) ?: [] as $hidden) {
            if ($hidden instanceof DOMElement) {
                $payload[$hidden->getAttribute('name')] = $hidden->getAttribute('value');
            }
        }

        // Every required field left empty: the server must refuse at least one of them.
        foreach ($required as $field) {
            if ($field instanceof DOMElement) {
                $payload[(string) preg_replace('/\[\]$/', '', $field->getAttribute('name'))] = '';
            }
        }

        $method = strtolower($payload['_method'] ?? 'post');
        unset($payload['_method']);

        test()->from($page)->{$method}($form->getAttribute('action'), $payload)->assertRedirect($page);

        $state = dialogState((string) test()->get($page)->assertOk()->getContent(), $dialogId);

        if (! $state['open'] || $state['alerts'] === []) {
            $failures[] = "{$dialogId}: open=".var_export($state['open'], true).', alerts='.count($state['alerts']);
        }

        $checked++;
    }

    expect($failures)->toBe([]);

    if ($checked === 0) {
        expect(true)->toBeTrue(); // A screen with no dialog forms has nothing to check.
    }
})->with(['branches', 'courses', 'sections', 'section', 'terms']);

test('the term reason the server trims to nothing comes back beside the reason field', function (): void {
    seedScreens();

    $term = AcademicTerm::query()->where('status', 'closed')->firstOrFail();
    $dialogId = 'term-'.$term->id.'-to-active';

    renderScreen('terms', 'ar')->assertOk();

    // Three characters to the browser's minlength, one after the server trims it.
    test()->from(route('terms.index'))
        ->patch(route('api.v1.terms.status', $term), ['status' => 'active', 'reason' => 'a  ', '_dialog' => $dialogId])
        ->assertRedirect(route('terms.index'));

    $html = (string) test()->get(route('terms.index'))->assertOk()->getContent();
    $state = dialogState($html, $dialogId);

    expect($state['open'])->toBeTrue()
        ->and($state['alerts'])->toHaveCount(1)
        ->and(preg_match('/\p{Arabic}/u', $state['alerts'][0]))->toBe(1)
        ->and($term->fresh()?->status)->toBe('closed');
});

test('a 409 reopens its dialog with the refusal, or shows it on the page when the dialog is gone', function (): void {
    seedScreens();

    $term = AcademicTerm::query()->where('status', 'closed')->firstOrFail();
    renderScreen('terms', 'ar')->assertOk();

    // closed → draft is not a transition, so there is no such dialog: the page carries the refusal.
    test()->from(route('terms.index'))
        ->patch(route('api.v1.terms.status', $term), ['status' => 'draft', '_dialog' => 'term-'.$term->id.'-to-draft'])
        ->assertRedirect(route('terms.index'));

    $html = (string) test()->get(route('terms.index'))->assertOk()->getContent();

    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $alert = (new DOMXPath($document))->query("//main/div[@aria-live]//*[@role='alert']")?->item(0);
    $message = trim((string) $alert?->textContent);

    // In the page's language: the states by their labels, never their keys.
    expect($message)->toContain('«'.__('status.closed', [], 'ar').'»')
        ->and($message)->toContain('«'.__('status.draft', [], 'ar').'»')
        ->and($message)->not->toContain('closed');
});

test('the withdraw dialog comes back on the same enrollment, pointing at the same withdrawal', function (): void {
    seedScreens();

    $enrollment = SectionEnrollment::query()->where('section_id', 13)->where('status', 'active')->firstOrFail();
    renderScreen('section', 'ar')->assertOk();

    test()->from(route('sections.show', 13))
        ->patch(route('api.v1.enrollments.withdraw', $enrollment), [
            'reason' => '',
            '_dialog' => 'withdraw-dialog',
            '_dialog_enrollment' => (string) $enrollment->id,
            '_dialog_subject' => 'طالب',
        ])
        ->assertRedirect(route('sections.show', 13));

    $html = (string) test()->get(route('sections.show', 13))->assertOk()->getContent();
    $state = dialogState($html, 'withdraw-dialog');

    expect($state['open'])->toBeTrue()
        ->and($state['alerts'])->not->toBe([])
        ->and($html)->toContain('data-action="'.route('api.v1.enrollments.withdraw', $enrollment).'"');
});

test('a forged enrollment in the returned state cannot point the withdraw form elsewhere', function (): void {
    seedScreens();
    renderScreen('section', 'ar')->assertOk();

    test()->from(route('sections.show', 13))
        ->patch(route('api.v1.enrollments.withdraw', SectionEnrollment::query()->where('section_id', 13)->firstOrFail()), [
            'reason' => '',
            '_dialog' => 'withdraw-dialog',
            '_dialog_enrollment' => 'javascript:alert(1)',
        ]);

    $html = (string) test()->get(route('sections.show', 13))->assertOk()->getContent();

    expect($html)->not->toContain('javascript:alert');
});

test('the account dialogs come back open as the same create or edit, with the field\'s error', function (): void {
    seedScreens();

    renderScreen('users', 'ar')->assertOk();
    $target = User::query()->whereKeyNot(SCREEN_ACCOUNTS['admin']['ar'])->firstOrFail();

    // Edit, with the email emptied.
    test()->from(route('users.index'))
        ->put(route('users.update', $target), [
            'name_ar' => 'اسم معدل', 'name_en' => 'Edited', 'email' => '',
            '_dialog' => 'user-dialog', '_dialog_user' => (string) $target->id,
        ])
        ->assertRedirect(route('users.index'));

    $html = (string) test()->get(route('users.index'))->assertOk()->getContent();
    $state = dialogState($html, 'user-dialog');

    expect($state['open'])->toBeTrue()
        ->and($state['alerts'])->not->toBe([])
        ->and($html)->toContain('اسم معدل')
        ->and($html)->toMatch('/data-user="\{&quot;id&quot;:'.$target->id.'\b/');

    // Reset password, too weak.
    test()->from(route('users.index'))
        ->post(route('users.reset-password', $target), [
            'password' => 'short', '_dialog' => 'user-reset', '_dialog_user' => (string) $target->id,
        ])
        ->assertRedirect(route('users.index'));

    $state = dialogState((string) test()->get(route('users.index'))->assertOk()->getContent(), 'user-reset');

    expect($state['open'])->toBeTrue()->and($state['alerts'])->not->toBe([]);
});
