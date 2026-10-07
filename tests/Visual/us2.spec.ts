/**
 * T143 — US2 made visible: the two refusal pages, the shared-access badge, and each role's sidebar.
 *
 * Scope is mostly invisible by design — a coordinator's list simply has fewer rows, and no
 * screenshot can prove the missing ones were excluded rather than absent. What a baseline *can*
 * pin is everything the person actually sees when scope bites: the full-page 403 and 404 (FR-025,
 * FR-080), the badge that distinguishes a shared section from one they teach (FR-026), and the
 * sidebar, which is the one place a role's reach is laid out in full.
 *
 * The sidebars are the reason this file shoots three different accounts rather than one. Three
 * roles, three different navigations, against the approved Shell.jsx NAV — and a regression in the
 * per-role map is otherwise only visible to someone who happens to sign in as the right person.
 */
import { expect, test, type Page } from '@playwright/test';
import { refuseErrorPages } from './helpers/render-reference';
import { accountFor, adminFor, expectLocale } from './support/session';

const INTERACTION_WIDTH = 1440;

/** The accounts this spec drives, and why each one. */

/*
 * Instructor 5, not instructor 3.
 *
 * Instructor 3 holds the fixture's other grant, and is also the never-signed-in account that
 * us1.spec.ts photographs for the first-sign-in banner. Signing in here would write their
 * first_login_at and leave that test shooting a page with no banner on it — the kind of failure
 * that reads as a styling regression in a completely different spec. Instructor 5 teaches sections
 * 13 and 16 and was granted 14, so their list carries both states at once.
 */

function skipUnlessInteractionWidth(testInfo: { project: { use: { viewport?: { width: number } | null } } }): void {
    test.skip(
        testInfo.project.use.viewport?.width !== INTERACTION_WIDTH,
        'Interaction-driven; runs at 1440 only.',
    );
}

async function signIn(page: Page, locale: 'ar' | 'en', who: { email: string; password: string }) {
    await page.goto(`/login?locale=${locale}`);
    await page.fill('input[name="email"]', who.email);
    await page.fill('input[name="password"]', who.password);

    // Scoped to the credentials form: the language switch is also a submit button and sits earlier
    // in the DOM. See the same note in us1.spec.ts, where a bare selector cost four worthless
    // baselines.
    await page.click('.track-login__fields button[type="submit"]');
    await page.waitForLoadState('load');

    await expect(page.locator('.track-login__fields')).toHaveCount(0);

    /*
     * No locale switch here: the account's own stored preference decides, and there is one admin
     * per locale. Switching after sign-in wrote a shared row and raced across parallel workers —
     * see tests/Visual/support/session.ts.
     */
    await expectLocale(page, locale);
}

async function stabilise(page: Page) {
    await page.waitForLoadState('networkidle');
    await page.evaluate(() => document.fonts.ready);
    await page.waitForTimeout(150);

    // Never photograph an exception page.
    await refuseErrorPages(page);
}

for (const locale of ['ar', 'en'] as const) {
    test.describe(`US2 · ${locale}`, () => {
        test('403 — a permission the caller does not hold', async ({ page }) => {
            await signIn(page, locale, accountFor('instructor', locale));

            // An instructor holds no user.view, and the record (the accounts screen) is not scoped
            // away from them — so this is the refusal, not the absence.
            await page.goto('/users');
            await stabilise(page);

            await expect(page).toHaveScreenshot(`${locale}-refusal-403.png`, { fullPage: true });
        });

        test('404 — nothing there, or nothing they may know about', async ({ page }) => {
            await signIn(page, locale, accountFor('instructor', locale));

            await page.goto('/sections/999999');
            await stabilise(page);

            /*
             * The same page a coordinator gets for another branch's section id. That the two are
             * indistinguishable is FR-024, and a baseline is how it stays that way: a future
             * "helpful" message naming what was missed would change these pixels.
             */
            await expect(page).toHaveScreenshot(`${locale}-refusal-404.png`, { fullPage: true });
        });

        test('the shared-access badge in the sections list', async ({ page }) => {
            /*
             * Arabic only, and deliberately.
             *
             * The badge needs an instructor who teaches two sections and was granted a third, and
             * the fixture gives that shape to one account (r.alanazi). Duplicating the sections and
             * the grant for an English twin would change the section counts every other baseline
             * renders, to prove a label that components.spec.ts already shoots in both locales —
             * status-badge is one of the twenty-seven specimens there.
             */
            test.skip(locale !== 'ar', 'Needs the granted instructor; the badge label is covered in components.spec.ts.');

            await signIn(page, locale, accountFor('instructor', locale));

            await page.goto('/sections');
            await stabilise(page);

            await expect(page).toHaveScreenshot(`${locale}-sections-shared.png`, { fullPage: true });
        });

        test('sidebar — Super Admin', async ({ page }, testInfo) => {
            skipUnlessInteractionWidth(testInfo);

            await signIn(page, locale, adminFor(locale));
            await page.goto('/home');
            await stabilise(page);

            await expect(page.locator('.track-sidebar')).toHaveScreenshot(`${locale}-sidebar-admin.png`);
        });

        test('sidebar — branch coordinator', async ({ page }, testInfo) => {
            skipUnlessInteractionWidth(testInfo);

            await signIn(page, locale, accountFor('coordinator', locale));
            await page.goto('/home');
            await stabilise(page);

            await expect(page.locator('.track-sidebar')).toHaveScreenshot(`${locale}-sidebar-coordinator.png`);
        });

        test('sidebar — instructor', async ({ page }, testInfo) => {
            skipUnlessInteractionWidth(testInfo);

            await signIn(page, locale, accountFor('instructor', locale));
            await page.goto('/home');
            await stabilise(page);

            await expect(page.locator('.track-sidebar')).toHaveScreenshot(`${locale}-sidebar-instructor.png`);
        });
    });
}

test.describe('US2 behaviour the pixels cannot pin', () => {
    test('the three sidebars differ from one another', async ({ page }, testInfo) => {
        skipUnlessInteractionWidth(testInfo);

        /*
         * Three sign-ins in one test, which is three times the work of any other, and the default
         * 30s budget is not enough once four workers are competing for the PHP server. It passed
         * alone and timed out in the full run — the classic shape of a budget that was set for a
         * single-request test and inherited by one that makes nine.
         */
        test.setTimeout(120_000);

        const labels: Record<string, string[]> = {};

        for (const [role, who] of Object.entries({ admin: adminFor('ar'), coordinator: accountFor('coordinator', 'ar'), instructor: accountFor('instructor', 'ar') })) {
            await signIn(page, 'ar', who);
            await page.goto('/home');
            await stabilise(page);

            labels[role] = await page.locator('.track-sidebar__nav a span:last-child').allTextContents();

            // Cookies only. /logout is a POST — navigating to it would 405 and leave the session
            // intact, which is worse than not trying.
            await page.context().clearCookies();
        }

        /*
         * Asserted as a relation rather than against a fixed list. The counts come from Shell.jsx's
         * NAV — ten for a Super Admin minus the omitted `programs`, five for a coordinator, three
         * for an instructor — but what matters is that the three are genuinely different: a
         * navigation map that silently fell back to "every entry" for everyone would still produce
         * three screenshots, and all three would be wrong in the same way.
         */
        expect(labels.admin.length).toBeGreaterThan(labels.coordinator.length);
        expect(labels.coordinator.length).toBeGreaterThan(labels.instructor.length);
        expect(labels.instructor.length).toBe(3);
    });

    test('a refusal page names no record and no branch', async ({ page }) => {
        await signIn(page, 'ar', accountFor('instructor', 'ar'));
        await page.goto('/sections/999999');
        await stabilise(page);

        const body = await page.locator('body').innerText();

        // FR-024: the prose must not disclose what was asked for.
        expect(body).not.toContain('999999');
        expect(body).not.toContain('Section');
        expect(body).not.toContain('No query results');
    });
});
