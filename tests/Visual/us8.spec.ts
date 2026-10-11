/**
 * T343 — the course and staff wizard entry points, against the CourseImportDialog and
 * UserImportDialog references. Both locales, every width in the project matrix.
 *
 * ## Against the references, with one deliberate difference
 *
 * The references are single dialogs that check and import at once. Here the same upload opens the
 * six-step wizard (FR-106), so what is compared is the reference's dialog body — the drop zone, the
 * statement of the columns, the template download — rendered as the wizard's first step. The
 * stepper above it is the wizard's own and has its own baseline in us5.spec.ts.
 *
 * ## Determinism
 *
 * Both steps render only fixed text and the upload limits from settings; nothing here uploads.
 */
import { expect, test, type Page } from '@playwright/test';
import { refuseErrorPages } from './helpers/render-reference';
import { adminFor, expectLocale, signIn } from './support/session';

async function stabilise(page: Page) {
    await page.waitForLoadState('networkidle');
    await page.evaluate(() => document.fonts.ready);
    await page.waitForTimeout(150);

    // Never photograph an exception page (see us4.spec.ts).
    await refuseErrorPages(page);
}

for (const locale of ['ar', 'en'] as const) {
    test.describe(`US8 · ${locale}`, () => {
        for (const mode of ['courses', 'users'] as const) {
            test(`the ${mode} upload step`, async ({ page }) => {
                await signIn(page, locale, adminFor(locale));
                await page.goto(`/imports/new/${mode}`);
                await stabilise(page);
                await expectLocale(page, locale);

                // The template is the reference's "تنزيل القالب" — the shape's own starting point.
                await expect(page.locator(`a[href$="/imports/new/${mode}/template"]`)).toBeVisible();
                await expect(page.locator('label.track-dropzone')).toBeVisible();

                await expect(page).toHaveScreenshot(`${locale}-${mode}-upload.png`, { fullPage: true });
            });
        }
    });
}
