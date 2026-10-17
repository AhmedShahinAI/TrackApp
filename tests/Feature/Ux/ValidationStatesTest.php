<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| T263 — every client-side rule is also a server-side rule, reported beside its field (FR-078)
|--------------------------------------------------------------------------
|
| field-validation.js checks `required`, `minlength`, `maxlength` and `type="email"` on blur, which
| is a courtesy: anything the browser checks can be skipped by anyone who posts directly. So this
| reads every form on every screen, takes each rule the markup declares, posts a value that breaks
| it, and asserts the server refuses that same field — in the language of the page.
|
| Then the browser half: the refusal comes back to the page it was made from, and the message is
| rendered as the field's own error (role="alert"), not as a banner somewhere else.
|
*/

/**
 * Every POST form with a fixed action, and the rules its fields declare.
 *
 * Forms whose action is bound by Alpine (`:action`) are built per row at runtime — the withdraw and
 * deactivate dialogs — and are covered by their own feature tests. GET forms are filters, which
 * validate nothing by design.
 *
 * @return list<array{action: string, method: string, hidden: array<string, string>, rules: list<array{field: string, rule: string, value: mixed}>}>
 */
function formsWithRules(string $html): array
{
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);

    $forms = [];

    foreach ($xpath->query("//form[@method='POST' or @method='post'][@action]") ?: [] as $form) {
        if (! $form instanceof DOMElement) {
            continue;
        }

        $action = $form->getAttribute('action');
        $path = (string) parse_url($action, PHP_URL_PATH);

        // The session's own forms, not data entry.
        if (in_array($path, ['/logout', '/locale'], true) || $action === '') {
            continue;
        }

        $method = 'POST';
        $spoofed = $xpath->query(".//input[@name='_method']", $form)?->item(0);

        if ($spoofed instanceof DOMElement) {
            $method = strtoupper($spoofed->getAttribute('value'));
        }

        $rules = [];

        // What the browser sends regardless of what the user typed — a term status form's target
        // status, say. Without it the server refuses the missing hidden field first and never gets
        // as far as the field under test.
        $hidden = [];

        foreach ($xpath->query(".//input[@type='hidden']", $form) ?: [] as $input) {
            if ($input instanceof DOMElement && $input->getAttribute('name') !== '' && ! str_starts_with($input->getAttribute('name'), '_')) {
                $hidden[$input->getAttribute('name')] = $input->getAttribute('value');
            }
        }

        foreach ($xpath->query('.//input | .//textarea | .//select', $form) ?: [] as $control) {
            if (! $control instanceof DOMElement) {
                continue;
            }

            $name = $control->getAttribute('name');
            $type = strtolower($control->getAttribute('type'));

            if ($name === '' || str_starts_with($name, '_') || in_array($type, ['hidden', 'checkbox', 'radio', 'submit'], true)) {
                continue;
            }

            $field = (string) preg_replace(['/\]\[/', '/\[/', '/\]/'], ['.', '.', ''], $name);

            if ($control->hasAttribute('required')) {
                $rules[] = ['field' => $field, 'rule' => 'required', 'value' => ''];
            }

            if ($control->hasAttribute('maxlength')) {
                $rules[] = ['field' => $field, 'rule' => 'maxlength', 'value' => str_repeat('ب', (int) $control->getAttribute('maxlength') + 1)];
            }

            if ($control->hasAttribute('minlength') && (int) $control->getAttribute('minlength') > 1) {
                $rules[] = ['field' => $field, 'rule' => 'minlength', 'value' => str_repeat('ب', (int) $control->getAttribute('minlength') - 1)];
            }

            if ($type === 'email') {
                $rules[] = ['field' => $field, 'rule' => 'email', 'value' => 'not-an-email'];
            }
        }

        if ($rules !== []) {
            $forms[] = ['action' => $action, 'method' => $method, 'hidden' => $hidden, 'rules' => $rules];
        }
    }

    return $forms;
}

/*
 * One locale per test case. Switching the acting user part-way through a test, after requests have
 * gone through the API's Sanctum guard, leaves the first user authenticated in the test harness — an
 * artifact of reusing one application across requests, which a real request, authenticated afresh,
 * never sees.
 */
test('the server refuses every value the form\'s own markup refuses, in the page\'s language', function (string $key, string $locale): void {
    seedScreens();

    $missing = [];
    $forms = formsWithRules((string) renderScreen($key, $locale)->assertOk()->getContent());

    foreach ($forms as $form) {
        foreach ($form['rules'] as $rule) {
            // Nested, as the browser's `mapping[term]` arrives: a literal "mapping.term" key is a
            // different field.
            $payload = $form['hidden'];

            // Phase 20 — the public support form refuses anything without a solved proof-of-work and
            // an aged form stamp BEFORE any field rule runs (contracts/support-public.md, order of
            // evaluation). The browser supplies both; here a solved, aged, otherwise-valid submission
            // stands in for them, so the one bad value below is what the server answers.
            if ($form['action'] === route('support.store')) {
                supportWorld();
                $payload = array_merge($payload, supportSubmission());
            }

            data_set($payload, $rule['field'], $rule['value']);

            $response = test()->json($form['method'], $form['action'], $payload);
            $errors = (array) $response->json('errors');
            $message = $errors[$rule['field']][0] ?? null;

            if ($response->status() !== 422 || $message === null) {
                $missing[] = "{$form['method']} {$form['action']} {$rule['field']} ({$rule['rule']}) → {$response->status()}";

                continue;
            }

            $arabic = preg_match('/\p{Arabic}/u', $message) === 1;

            if ($arabic !== ($locale === 'ar')) {
                $missing[] = "{$form['action']} {$rule['field']}: message in the wrong language — {$message}";
            }
        }
    }

    expect($missing)->toBe([]);
})->with(array_keys(screens()))->with(['ar', 'en']);

test('a refused form comes back with the error beside its field', function (): void {
    seedScreens();

    renderScreen('courses', 'ar')->assertOk();

    test()->from(route('courses.index'))
        ->post(route('api.v1.courses.store'), ['code' => '', 'name_ar' => '', 'name_en' => ''])
        ->assertRedirect(route('courses.index'));

    $html = (string) test()->get(route('courses.index'))->assertOk()->getContent();

    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);

    // The code field's own error, inside the code field's wrapper, announced as an alert.
    $error = $xpath->query("//*[@id='f-code']/ancestor::*[contains(@class,'track-field')][1]//*[@role='alert']")?->item(0);

    expect($error?->textContent)->not->toBeNull()
        ->and(preg_match('/\p{Arabic}/u', (string) $error?->textContent))->toBe(1);
});
