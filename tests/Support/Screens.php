<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| US7 — every in-scope screen, and how to reach it (design-system.md §9)
|--------------------------------------------------------------------------
|
| The localisation and UX sweeps (T259–T263, T267) ask the same question of every screen, so the
| screens are listed once. Each is reached on the UI fixture dataset (UiFixtureSeeder) — the same
| data the visual baselines render — as the account that can see it.
|
| The list is closed: EveryScreenRendersBothDirectionsTest fails when a GET screen route exists that
| is neither listed here nor excluded with a reason, so a screen added next month joins the sweep or
| says why it does not.
|
*/

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\UiFixtureSeeder;
use Illuminate\Testing\TestResponse;

/** Fixture accounts: the Super Admin of each locale, and an instructor. */
const SCREEN_ACCOUNTS = [
    'admin' => ['ar' => 1, 'en' => 8],
    'instructor' => ['ar' => 5, 'en' => 10],
];

/**
 * Screen key => [route name, route parameters, account role or 'guest'].
 *
 * Fixture ids: section 13 (current term, taught by instructor 5), student 100, import jobs 11
 * (mapping), 13 (checking, queue-waiting), 14 (preview), 16 (importing) and 1 (report).
 *
 * @return array<string, array{0: string, 1: array<string, int>, 2: string}>
 */
function screens(): array
{
    return [
        'login' => ['login', [], 'guest'],
        'home' => ['home', [], 'admin'],
        'profile' => ['profile', [], 'admin'],
        'profile.preferences' => ['profile.preferences', [], 'admin'],
        'profile.password' => ['profile.password.edit', [], 'admin'],
        'users' => ['users.index', [], 'admin'],
        'audit' => ['audit.index', [], 'admin'],
        'terms' => ['terms.index', [], 'admin'],
        'branches' => ['branches.index', [], 'admin'],
        // Phase 14 (T317) — the nine fixture programmes.
        'programs' => ['programs.index', [], 'admin'],
        'courses' => ['courses.index', [], 'admin'],
        'sections' => ['sections.index', [], 'admin'],
        'sections.mine' => ['sections.index', [], 'instructor'],
        'section' => ['sections.show', ['section' => 13], 'admin'],
        'section.import' => ['sections.import', ['section' => 13], 'admin'],
        'students' => ['students.index', [], 'admin'],
        'student' => ['students.show', ['student' => 100], 'admin'],
        'imports' => ['imports.index', [], 'admin'],
        'imports.new' => ['imports.create', [], 'admin'],
        // Phase 15 (T334) — the registrar import's first step.
        'imports.new.registrar' => ['imports.create.registrar', [], 'admin'],
        'import.registrar' => ['imports.show', ['import' => 17], 'admin'],
        // Phase 16 (T348) — the course and staff uploads' first steps.
        'imports.new.courses' => ['imports.create.courses', [], 'admin'],
        'imports.new.users' => ['imports.create.users', [], 'admin'],
        'import.mapping' => ['imports.show', ['import' => 11], 'admin'],
        'import.checking' => ['imports.show', ['import' => 13], 'admin'],
        'import.preview' => ['imports.show', ['import' => 14], 'admin'],
        'import.importing' => ['imports.show', ['import' => 16], 'admin'],
        'import.report' => ['imports.show', ['import' => 1], 'admin'],
    ];
}

/**
 * GET screen routes deliberately outside the sweep, each with its reason.
 *
 * @return array<string, string>
 */
function screensExcluded(): array
{
    return [
        'dev.components' => 'local-only component gallery; swept by axe.spec.ts',
        'dev.reference' => 'serves the design kit itself, not an application screen',
        'sections.import.template' => 'a file download, not a page',
        'imports.courses.template' => 'a file download, not a page',
        'imports.users.template' => 'a file download, not a page',
        'health' => 'a JSON readiness probe for the load balancer, not a page (T276)',
    ];
}

/** Seed the fixture dataset once per test that needs screens. */
function seedScreens(): void
{
    test()->seed([DatabaseSeeder::class, UiFixtureSeeder::class]);
}

/**
 * Render one screen in one locale, as the account it is listed under.
 *
 * @return TestResponse<Symfony\Component\HttpFoundation\Response>
 */
function renderScreen(string $key, string $locale): TestResponse
{
    [$route, $params, $as] = screens()[$key];

    test()->flushSession();
    app('auth')->forgetGuards();

    // A guest's language is the one they chose on the sign-in screen, kept in the session.
    // (`?locale=` is honoured only in the local environment, by design — see SetLocale.)
    if ($as === 'guest') {
        return test()->withSession(['locale' => $locale])->get(route($route, $params));
    }

    $user = User::query()->findOrFail(SCREEN_ACCOUNTS[$as][$locale]);

    return test()->actingAs($user)->get(route($route, $params));
}

/**
 * The text a reader meets on a page: visible text nodes plus the attributes a screen reader or a
 * tooltip speaks. Scripts, styles and inline JSON are not reading matter.
 */
function readableText(string $html): string
{
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);

    foreach (['script', 'style', 'template'] as $tag) {
        foreach (iterator_to_array($document->getElementsByTagName($tag)) as $node) {
            $node->parentNode?->removeChild($node);
        }
    }

    $xpath = new DOMXPath($document);
    $parts = [$document->textContent];

    foreach ($xpath->query('//@aria-label | //@title | //@placeholder | //@alt') ?: [] as $attribute) {
        $parts[] = $attribute->nodeValue;
    }

    return (string) preg_replace('/\s+/u', ' ', implode(' ', $parts));
}
