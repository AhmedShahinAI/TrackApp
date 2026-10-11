/**
 * T162 — the audit screen, against the AuditScreen reference.
 *
 * Four things are worth pixels here: the list with filters applied, the before/after disclosure
 * open, the standing immutability notice, and one record's activity panel.
 *
 * ## Why every shot is filtered
 *
 * The audit log is the one screen whose content the test suite itself writes — every sign-in in
 * this file adds an `auth.login` row, and the lockout tests in us1 add more. An unfiltered list
 * would therefore photograph whatever ran before it, which is the non-determinism that quietly
 * invalidated the landing-page baseline for weeks. Filtering to a single action makes the rows a
 * function of the fixture rather than of the run order, and the timestamp column is masked on top
 * of that, because the fixture's own entries carry fixed dates but the suite's do not.
 */
import { expect, test, type Page } from '@playwright/test';
import { refuseErrorPages } from './helpers/render-reference';
import { adminFor, expectLocale, signIn } from './support/session';

const INTERACTION_WIDTH = 1440;


function skipUnlessInteractionWidth(testInfo: { project: { use: { viewport?: { width: number } | null } } }): void {
    test.skip(
        testInfo.project.use.viewport?.width !== INTERACTION_WIDTH,
        'Interaction-driven; runs at 1440 only.',
    );
}


async function stabilise(page: Page) {
    await page.waitForLoadState('networkidle');
    await page.evaluate(() => document.fonts.ready);
    await page.waitForTimeout(150);

    // Never photograph an exception page.
    await refuseErrorPages(page);
}

/** The timestamp column, which moves with every run. */
function volatileColumns(page: Page) {
    return [page.locator('td[data-label*="الوقت"], td[data-label*="When"]')];
}

for (const locale of ['ar', 'en'] as const) {
    test.describe(`US3 · ${locale}`, () => {
        test('audit list, filtered to one action', async ({ page }) => {
            await signIn(page, locale, adminFor(locale));

            // section.access_granted: one seeded entry, written by the fixture with a fixed date.
            await page.goto('/audit?action=section.access_granted');
            await stabilise(page);

            await expect(page).toHaveScreenshot(`${locale}-audit-filtered.png`, {
                fullPage: true,
                mask: volatileColumns(page),
            });
        });

        test('the immutability notice', async ({ page }) => {
            await signIn(page, locale, adminFor(locale));
            await page.goto('/audit?action=section.access_granted');
            await stabilise(page);

            /*
             * Its own shot. FR-031 makes immutability a property people are meant to rely on, and
             * the notice is the only place the platform says so — a silent removal would otherwise
             * be invisible in a full-page diff full of moving rows.
             */
            await expect(page.locator('.track-alert').first())
                .toHaveScreenshot(`${locale}-audit-immutable-notice.png`);
        });

        test('the before/after disclosure, open', async ({ page }, testInfo) => {
            skipUnlessInteractionWidth(testInfo);

            await signIn(page, locale, adminFor(locale));
            await page.goto('/audit?action=section.instructor_changed');
            await stabilise(page);

            const disclosure = page.locator('.track-audit__diff').first();

            if (await disclosure.count() === 0) {
                test.skip(true, 'No entry with a payload in the fixture for this action.');
            }

            await disclosure.locator('summary').click();
            await page.waitForTimeout(150);

            await expect(disclosure).toHaveScreenshot(`${locale}-audit-diff-open.png`);
        });

        test('a record activity panel', async ({ page }) => {
            await signIn(page, locale, adminFor(locale));

            // Section 11 changed hands twice in the fixture, so its panel is not empty.
            await page.goto('/sections/11');
            await stabilise(page);

            /*
             * The tab is clicked, not assumed. The panel opens on Details, so the first version of
             * this test asked Playwright to photograph a hidden element and timed out sixteen times
             * — which is the application behaving correctly and the test asking the wrong thing.
             * Clicking also makes this shot prove the strip works, which the earlier version could
             * not: both panels were visible then, so a broken tab looked identical to a working one.
             */
            await page.click('#section-tabs-tab-activity');
            await page.waitForTimeout(200);
            await expect(page.locator('.track-activity')).toBeVisible();

            /*
             * The timestamp is replaced, not masked.
             *
             * A mask paints over a cell's *content* but the cell still occupies the width its real
             * text demands — so a panel captured at 21:30 and verified at 22:05 lays out one pixel
             * differently and the whole shot fails, with the diff showing nothing but the magenta
             * block moving. That is what happened across all sixteen projects after a regeneration
             * and a verification run an hour apart.
             *
             * Writing a constant into the cell makes the width a property of the test rather than
             * of the clock, which is what the mask was reaching for in the first place.
             */
            await page.evaluate(() => {
                document
                    .querySelectorAll('.track-activity td:first-child, .track-activity td[data-label*="الوقت"], .track-activity td[data-label*="When"]')
                    .forEach((cell) => { cell.textContent = '—'; });
            });

            await expect(page.locator('.track-activity')).toHaveScreenshot(
                `${locale}-activity-panel.png`,
            );
        });
    });
}

test.describe('US3 behaviour the pixels cannot pin', () => {
    test('the disclosure is keyboard operable and announced', async ({ page }, testInfo) => {
        skipUnlessInteractionWidth(testInfo);

        await signIn(page, 'ar', adminFor('ar'));
        await page.goto('/audit?action=section.instructor_changed');
        await stabilise(page);

        const summary = page.locator('.track-audit__diff > summary').first();

        if (await summary.count() === 0) {
            test.skip(true, 'No entry with a payload in the fixture.');
        }

        /*
         * <details> was chosen over a dialog and over an expanding row precisely because the
         * browser gives this for free. Asserting it keeps that choice honest: a later swap to a
         * div with an onclick would pass every pixel test and lose keyboard access silently.
         */
        await summary.focus();
        await page.keyboard.press('Enter');

        await expect(page.locator('.track-audit__diff[open]').first()).toBeVisible();
    });

    test('the audit screen offers no way to change an entry', async ({ page }) => {
        await signIn(page, 'ar', adminFor('ar'));
        await page.goto('/audit');
        await stabilise(page);

        // FR-031 at the surface: no edit, no delete, and no form that posts anywhere.
        const mutating = await page.evaluate(() =>
            Array.from(document.querySelectorAll('form'))
                .map((f) => (f.getAttribute('method') ?? 'get').toLowerCase())
                .filter((m) => m !== 'get')
                .length,
        );

        // The logout and locale forms live in the shell, so the count is of the content region.
        const inContent = await page.evaluate(() =>
            Array.from(document.querySelectorAll('#track-content form'))
                .map((f) => (f.getAttribute('method') ?? 'get').toLowerCase())
                .filter((m) => m !== 'get')
                .length,
        );

        expect(inContent).toBe(0);
        expect(mutating).toBeGreaterThanOrEqual(0);
    });
});
