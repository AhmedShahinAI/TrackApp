<?php

declare(strict_types=1);

use App\Models\Section;

/*
|--------------------------------------------------------------------------
| A refusal is rendered in the reader's language (FR-074)
|--------------------------------------------------------------------------
|
| The 404 raised by route-model binding is thrown from inside the web group, before SetLocale has
| run — it is appended to the group so `$request->user()` resolves, and SubstituteBindings sits
| earlier. So the one page a lost person reads was rendered in the default locale: measured as
| `ar/rtl` for a user whose every other page was `en/ltr`.
|
| It went unnoticed because both locales' 404 baselines were byte-identical, which looks like
| correct RTL/LTR parity and was in fact the same Arabic page photographed twice.
|
*/

/**
 * @return array{lang: string, dir: string}
 */
function documentLanguageOf(string $html): array
{
    preg_match('/<html lang="([a-z]{2})" dir="(\w+)"/', $html, $m);

    return ['lang' => $m[1] ?? '', 'dir' => $m[2] ?? ''];
}

test('a 404 from route-model binding speaks the reader language', function (string $locale, string $dir): void {
    $user = aUser('INSTRUCTOR', ['locale' => $locale]);
    $foreign = Section::factory()->create();

    $html = $this->actingAs($user)->get("/sections/{$foreign->id}")->assertNotFound()->getContent();

    expect(documentLanguageOf($html))->toBe(['lang' => $locale, 'dir' => $dir]);
})->with([
    'arabic' => ['ar', 'rtl'],
    'english' => ['en', 'ltr'],
]);

test('a 403 speaks the reader language too', function (string $locale, string $dir): void {
    $user = aUser('INSTRUCTOR', ['locale' => $locale]);

    $html = $this->actingAs($user)->get('/users')->assertForbidden()->getContent();

    expect(documentLanguageOf($html))->toBe(['lang' => $locale, 'dir' => $dir]);
})->with([
    'arabic' => ['ar', 'rtl'],
    'english' => ['en', 'ltr'],
]);

test('the two refusal pages differ from one another in the same language', function (): void {
    $user = aUser('INSTRUCTOR', ['locale' => 'en']);
    $foreign = Section::factory()->create();

    $missing = $this->actingAs($user)->get("/sections/{$foreign->id}")->getContent();
    $forbidden = $this->actingAs($user)->get('/users')->getContent();

    /*
     * Different pages, deliberately: someone who mistyped a URL needs different wording from
     * someone who lacks a permission. What must stay identical is the answer for a record that is
     * out of scope versus one that never existed — asserted in tests/Scope.
     */
    expect($missing)->not->toBe($forbidden);

    app()->setLocale('en');

    expect($missing)->toContain(__('authorization.refusal.missing_title'))
        ->and($forbidden)->toContain(__('authorization.refusal.forbidden_title'));
});
