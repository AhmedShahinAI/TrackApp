/**
 * T328 — the registrar import's upload step and its parsed-metadata confirmation panel.
 *
 * ## No React reference exists
 *
 * The registrar mode was accepted (C-09) after the design system was produced, so there is no
 * ImportScreens counterpart to diff against. The baseline is taken from the Blade implementation and
 * reviewed by the design author, as the queue-waiting state's is (design-system.md §9 row 26). That
 * review is the gate: `--update-snapshots` records whatever renders, so a first capture that has not
 * been looked at certifies nothing.
 *
 * ## Determinism
 *
 * Both states are fixture-only. The upload step reads the terms and instructors UiFixtureSeeder
 * seeds; the confirmation panel is jobs 17 (the Arabic admin's) and 18 (the English admin's), each
 * the reference file's block resolved to section 19. Nothing here uploads a file — an upload would
 * add a row to the imports list while us5.spec.ts photographs it.
 *
 * ## Every width
 *
 * The panel's label/value grid stacks below 768px (.track-registrar__fact), and the upload step's
 * term and instructor selects share a row only above it (.track-form-grid). Both are width
 * behaviours, so both are shot in every project.
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

const PANEL_JOB = { ar: 17, en: 18 } as const;

for (const locale of ['ar', 'en'] as const) {
    test.describe(`US5 · registrar · ${locale}`, () => {
        test('the registrar upload step', async ({ page }) => {
            await signIn(page, locale, adminFor(locale));
            await page.goto('/imports/new/registrar');
            await stabilise(page);
            await expectLocale(page, locale);

            // The two values the export cannot carry (FR-101).
            await expect(page.locator('select[name="term_id"]')).toBeVisible();
            await expect(page.locator('select[name="instructor_id"]')).toBeVisible();

            await expect(page).toHaveScreenshot(`${locale}-registrar-upload.png`, { fullPage: true });
        });

        test('the parsed-metadata confirmation panel', async ({ page }) => {
            await signIn(page, locale, adminFor(locale));
            await page.goto(`/imports/${PANEL_JOB[locale]}`);
            await stabilise(page);

            const panel = page.locator('.track-registrar');

            // The block as the file wrote it, whatever the reader's language.
            await expect(panel).toContainText('المجمعة (طلاب)');
            await expect(panel).toContainText('2298');

            // Its own shot as well as the page: the panel is the step, and a regression inside it
            // would be a small part of a full-page diff.
            await expect(panel).toHaveScreenshot(`${locale}-registrar-panel.png`);
            await expect(page).toHaveScreenshot(`${locale}-registrar-confirm.png`, { fullPage: true });
        });
    });
}
