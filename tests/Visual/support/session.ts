/**
 * Signing in, with the locale the account actually prefers.
 *
 * ## Two defects produced this file
 *
 * **The first:** every signed-in `en-*` baseline across us1, us2 and us3 was a byte-for-byte copy
 * of its `ar-*` counterpart — half the shell screenshots testing nothing, and no baseline at all
 * for the LTR rendering FR-074 requires. The cause is the application working as specified:
 * SetLocale resolves user → session → `?locale=`, so `/login?locale=en` sets the guest session and
 * the moment the sign-in succeeds the account's own stored preference wins.
 *
 * **The second, caused by the first fix:** switching the locale after signing in, through the real
 * /locale endpoint, writes `users.locale` — a row shared by every worker. Under `fullyParallel`
 * with four workers, one test set it to `ar` while another set it to `en`, and a baseline named
 * `ar-sections-shared.png` was captured in English and committed. A screenshot of the wrong
 * language is a passing test and a worthless reference.
 *
 * ## So: one account per locale, and nothing is written
 *
 * The fixture seeds two Super Admins, identical in permissions and differing only in `locale`.
 * Signing in as the right one needs no switch, mutates no shared row, and cannot race. The locale
 * is then asserted rather than arranged, so a fixture whose preference drifts fails loudly instead
 * of producing a quietly mislabelled baseline.
 */
import { expect, type Page } from '@playwright/test';

export type Credentials = { email: string; password: string };

const PASSWORD = 'Track2026';

/**
 * The Super Admin whose stored preference is `locale`.
 *
 * Two rows, not one row switched: see the note above. Both are seeded by UiFixtureSeeder with the
 * same role and no branches, so any difference a baseline shows between them is the language.
 */
export function adminFor(locale: 'ar' | 'en'): Credentials {
    return accountFor('admin', locale);
}

/**
 * The fixture account for a role and a locale.
 *
 * Six rows, three roles times two languages, identical within each role apart from `locale`. The
 * duplication is the point: it is what lets an `en-*` baseline be English without any test writing
 * to a row another worker is reading.
 */
export function accountFor(role: 'admin' | 'coordinator' | 'instructor' | 'unassigned', locale: 'ar' | 'en'): Credentials {
    const accounts = {
        admin: { ar: 'm.alsalem@mu.edu.sa', en: 'm.alharbi@mu.edu.sa' },
        coordinator: { ar: 'k.alshammari@mu.edu.sa', en: 's.alqahtani@mu.edu.sa' },
        instructor: { ar: 'r.alanazi@mu.edu.sa', en: 'o.alzahrani@mu.edu.sa' },

        // Instructors with no section and no grant (US6). The English one is the same account as
        // `instructor.en`, which has never had a section either.
        unassigned: { ar: 'l.aldosari@mu.edu.sa', en: 'o.alzahrani@mu.edu.sa' },
    } as const;

    return { email: accounts[role][locale], password: PASSWORD };
}

export async function signIn(page: Page, locale: 'ar' | 'en', who: Credentials): Promise<void> {
    await page.goto(`/login?locale=${locale}`);
    await page.fill('input[name="email"]', who.email);
    await page.fill('input[name="password"]', who.password);

    /*
     * Scoped to the credentials form. The language switch is also a submit button and sits earlier
     * in the DOM, so a bare `button[type="submit"]` clicked the switch, reloaded the page in the
     * other language, and signed nobody in — while every screenshot silently captured the login
     * page instead of the screen under test.
     */
    await page.click('.track-login__fields button[type="submit"]');
    await page.waitForLoadState('load');

    // Asserted, not assumed: a screenshot of the wrong page is a passing test and a worthless
    // baseline, so the helper refuses to return until the sign-in screen is behind us.
    await expect(page.locator('.track-login__fields')).toHaveCount(0);
}

/**
 * Fail if the page is not in the language this baseline claims to be.
 *
 * Asserts; never switches. The switch is what raced, and an assertion catches the same mistake —
 * an account seeded with the wrong preference, a test passing the wrong credentials — at the point
 * where it would otherwise be baked into a reference image.
 */
export async function expectLocale(page: Page, locale: 'ar' | 'en'): Promise<void> {
    await expect(page.locator('html')).toHaveAttribute('lang', locale);
    await expect(page.locator('html')).toHaveAttribute('dir', locale === 'ar' ? 'rtl' : 'ltr');
}

/**
 * Sign in as the Super Admin for this locale, and confirm the page came back in it.
 */
export async function signInAsAdmin(page: Page, locale: 'ar' | 'en'): Promise<void> {
    await signIn(page, locale, adminFor(locale));
    await expectLocale(page, locale);
}

/**
 * Refuse to photograph a Laravel error page.
 *
 * Three baselines in this project have been an unhandled exception rendered at 500 and frozen as
 * the reference for the screen they replaced: a sections list whose Blade threw, a landing page
 * whose stylesheet never loaded, and a 403 whose audit write hit a locked database. Each one was
 * written by `--update-snapshots`, each passed every run afterwards, and each was found only by
 * opening the PNG.
 *
 * A screenshot tool cannot tell a designed page from an exception page — they are both pixels. This
 * can, and it costs one DOM query per shot.
 */
export async function refuseErrorPages(page: Page): Promise<void> {
    const looksLikeAnException = await page.evaluate(() => {
        const text = document.body?.innerText ?? '';

        return (
            // Ignition's heading and Laravel's plain-text fallback.
            /Internal Server Error|Whoops, something went wrong|SQLSTATE\[/i.test(text)
            || document.querySelector('.exception-message, #app[data-page*="Exception"]') !== null
        );
    });

    expect(
        looksLikeAnException,
        'the page rendered as an exception — refusing to capture it as a baseline',
    ).toBe(false);
}
