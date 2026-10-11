/**
 * T253 — the instructor's section list: the section page's import entry, the section import page
 * with its two-column template, and the empty state of an instructor with no section.
 *
 * ## Against which reference
 *
 * tasks.md names `SectionFilePicker`, but in the design kit that is the initiatives screen's section
 * picker (InitiativesScreen.jsx), not a roster import. The approved entry for this story is the
 * section page's "استيراد كشف" action opening the import wizard in section mode
 * (SectionsScreens.jsx, ImportScreens.jsx), and these baselines are taken against that.
 *
 * ## Nothing here writes
 *
 * The import page is photographed before a file is chosen, and the template is a download the
 * suite does not trigger. The empty state belongs to instructors the fixture seeds with no section
 * and no grant (UiFixtureSeeder users 10 and 11), one per locale.
 */
import { expect, test, type Page } from '@playwright/test';
import { refuseErrorPages } from './helpers/render-reference';
import { accountFor, adminFor, expectLocale, signIn } from './support/session';

/** A section in the fixture's current term, taught by instructor 5 and holding enrollments. */
const SECTION = 13;

async function stabilise(page: Page) {
    await page.waitForLoadState('networkidle');
    await page.evaluate(() => document.fonts.ready);
    await page.waitForTimeout(150);

    // Never photograph an exception page (US3).
    await refuseErrorPages(page);
}

for (const locale of ['ar', 'en'] as const) {
    test.describe(`US6 · ${locale}`, () => {
        test('the section page offers the list import', async ({ page }) => {
            await signIn(page, locale, adminFor(locale));
            await page.goto(`/sections/${SECTION}`);
            await stabilise(page);
            await expectLocale(page, locale);

            const entry = page.locator(`a[href$="/sections/${SECTION}/import"]`);
            await expect(entry).toBeVisible();

            await expect(page.locator('.track-page-header')).toHaveScreenshot(`${locale}-section-import-entry.png`);
        });

        test('the section import page: the section fixed, two columns, and the template', async ({ page }) => {
            await signIn(page, locale, adminFor(locale));
            await page.goto(`/sections/${SECTION}/import`);
            await stabilise(page);

            // The term select is the central import's; a section list's term is its section's.
            await expect(page.locator('select[name="term_id"]')).toHaveCount(0);
            await expect(page.locator(`a[href$="/sections/${SECTION}/import/template"]`)).toBeVisible();

            await expect(page).toHaveScreenshot(`${locale}-section-import.png`, { fullPage: true });
        });

        test('an instructor with no section is told whom to contact', async ({ page }) => {
            await signIn(page, locale, accountFor('unassigned', locale));
            await page.goto('/sections');
            await stabilise(page);
            await expectLocale(page, locale);

            await expect(page).toHaveScreenshot(`${locale}-instructor-no-sections.png`, { fullPage: true });
        });
    });
}
