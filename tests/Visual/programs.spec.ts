/**
 * T313 — the programmes screen and its create/edit dialog, against the ProgramsScreen reference.
 * Both locales, every width in the project matrix (playwright.config.ts).
 *
 * ## Three shots, and why the dialog is shot twice
 *
 * The list, the create dialog, and the edit dialog. Create and edit are one component, and the only
 * visible difference is the one that matters: the edit case carries the FR-092 notice that a
 * programme linked to sections cannot be deleted. A single create shot would pass while the notice
 * vanished from the place someone comes looking for a delete button.
 *
 * The dialogs are shot at every width rather than at 1440 only, because the two-column grid
 * collapses to one below the tablet breakpoint (.track-form-grid) and a long Arabic programme name
 * is exactly what overflows a half-width field at 390.
 *
 * ## Determinism
 *
 * The nine programmes and their course and section counts are fixture-only (UiFixtureSeeder): no
 * other spec writes a programme, so nothing is masked.
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
    test.describe(`Programs · ${locale}`, () => {
        test('the programmes list', async ({ page }) => {
            await signIn(page, locale, adminFor(locale));
            await page.goto('/programs');
            await stabilise(page);
            await expectLocale(page, locale);

            // The nine fixture programmes from store.js, and their code reads LTR in both locales.
            await expect(page.locator('b.track-code[dir="ltr"]')).toHaveCount(9);

            await expect(page).toHaveScreenshot(`${locale}-programs.png`, { fullPage: true });
        });

        test('the create dialog', async ({ page }) => {
            await signIn(page, locale, adminFor(locale));
            await page.goto('/programs');
            await stabilise(page);

            await page.getByRole('button', { name: locale === 'ar' ? 'برنامج جديد' : 'New program' }).click();
            await page.waitForTimeout(200);

            const dialog = page.locator('#program-dialog .track-dialog');
            await expect(dialog).toBeVisible();

            // No cannot-delete notice on a create: nothing exists yet to be deleted.
            await expect(dialog.locator('.track-alert')).toHaveCount(0);

            await expect(dialog).toHaveScreenshot(`${locale}-program-dialog-create.png`);
        });

        test('the edit dialog carries the cannot-delete notice', async ({ page }) => {
            await signIn(page, locale, adminFor(locale));
            await page.goto('/programs');
            await stabilise(page);

            await page.locator('.track-row-actions button').first().click();
            await page.waitForTimeout(200);

            const dialog = page.locator('#program-dialog .track-dialog');
            await expect(dialog).toBeVisible();
            await expect(dialog.locator('input[name="code"]')).toHaveValue(/^[A-Z]+$/);
            await expect(dialog.locator('.track-alert')).toHaveCount(1);

            await expect(dialog).toHaveScreenshot(`${locale}-program-dialog-edit.png`);
        });
    });
}
