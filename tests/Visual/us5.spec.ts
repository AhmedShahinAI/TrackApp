/**
 * T220 — the import wizard, against the ImportScreens reference: the imports list, all six steps,
 * the re-upload warning, the row-reason table, the report, a stopped commit, and the queue-waiting
 * state.
 *
 * ## Nothing here writes
 *
 * Every step is photographed on a job UiFixtureSeeder already left at that step (seedImports), so
 * the suite never uploads, maps or commits. A wizard driven live by four parallel workers would
 * change the imports list, the home screen's import tile and each other's jobs mid-run — the
 * shared-state race us3 had to be rebuilt around. The jobs that carry controls come in pairs, one
 * per locale's Super Admin, because the controls are the owner's alone.
 *
 * ## The queue-waiting baseline has no React counterpart (C-13)
 *
 * The reference is a prototype with no queue, so "no worker has picked this up" is a state it never
 * reaches. That baseline is taken from the Blade implementation and goes to the design author for
 * review, as ui-conflicts.md C-13 records.
 *
 * ## The poller is live
 *
 * Steps 3 and 5 poll the job every five seconds. The fixture jobs never change, so a poll finds the
 * same status and the page does not reload under the camera; the waiting alert is shown because the
 * job's heartbeat really is older than its queueing, not because the test arranged the DOM.
 */
import { expect, test, type Page } from '@playwright/test';
import { refuseErrorPages } from './helpers/render-reference';
import { adminFor, expectLocale, signIn } from './support/session';

const INTERACTION_WIDTH = 1440;

/** Fixture job ids, per locale: the owner's copy is the one whose controls render. */
const JOBS = {
    mapped: { ar: 11, en: 12 },
    validated: { ar: 14, en: 15 },
    failed: { ar: 3, en: 4 },
    waiting: 13,
    committing: 16,
    completed: 1,
} as const;

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

    // Never photograph an exception page (US3): --update-snapshots would freeze it as the baseline.
    await refuseErrorPages(page);
}

for (const locale of ['ar', 'en'] as const) {
    test.describe(`US5 · ${locale}`, () => {
        test.beforeEach(async ({ page }) => {
            await signIn(page, locale, adminFor(locale));
        });

        test('the imports list', async ({ page }) => {
            await page.goto('/imports');
            await stabilise(page);
            await expectLocale(page, locale);

            await expect(page).toHaveScreenshot(`${locale}-imports.png`, { fullPage: true });
        });

        test('step 1 — upload, with the limits stated before a file is chosen', async ({ page }) => {
            await page.goto('/imports/new');
            await stabilise(page);

            await expect(page).toHaveScreenshot(`${locale}-step-upload.png`, { fullPage: true });
        });

        test('step 2 — mapping, carrying the re-upload warning', async ({ page }) => {
            await page.goto(`/imports/${JOBS.mapped[locale]}`);
            await stabilise(page);

            await expect(page).toHaveScreenshot(`${locale}-step-mapping.png`, { fullPage: true });

            /*
             * Its own shot. FR-067's warning is one line on a long form, and a full-page diff would
             * not notice it gone — which is exactly how an operator re-imports a roster twice.
             */
            await expect(page.locator('.track-alert--warning').first())
                .toHaveScreenshot(`${locale}-reupload-warning.png`);
        });

        test('step 3 — checking, with no worker: the queue-waiting state', async ({ page }) => {
            await page.goto(`/imports/${JOBS.waiting}`);
            await stabilise(page);

            const waiting = page.locator('.track-import__progress .track-alert');
            await expect(waiting).toBeVisible();

            await expect(page).toHaveScreenshot(`${locale}-step-checking-waiting.png`, { fullPage: true });
            await expect(waiting).toHaveScreenshot(`${locale}-queue-waiting.png`);
        });

        test('step 4 — the preview and its row-reason table', async ({ page }) => {
            await page.goto(`/imports/${JOBS.validated[locale]}`);
            await stabilise(page);

            await expect(page).toHaveScreenshot(`${locale}-step-preview.png`, { fullPage: true });

            // The table is where every defect category is read, in the reader's language (FR-062).
            await expect(page.locator('.track-table').first()).toHaveScreenshot(`${locale}-row-reasons.png`);
        });

        test('step 5 — importing, with the chunk counter', async ({ page }) => {
            await page.goto(`/imports/${JOBS.committing}`);
            await stabilise(page);

            await expect(page).toHaveScreenshot(`${locale}-step-importing.png`, { fullPage: true });
        });

        test('step 6 — the report', async ({ page }) => {
            await page.goto(`/imports/${JOBS.completed}`);
            await stabilise(page);

            await expect(page).toHaveScreenshot(`${locale}-step-report.png`, { fullPage: true });
        });

        test('a stopped commit says what survived and offers Resume', async ({ page }) => {
            await page.goto(`/imports/${JOBS.failed[locale]}`);
            await stabilise(page);

            await expect(page.getByRole('button', { name: locale === 'ar' ? /استئناف/ : /Resume/ })).toBeVisible();
            await expect(page).toHaveScreenshot(`${locale}-commit-failed.png`, { fullPage: true });
        });
    });
}

test.describe('US5 · interactions', () => {
    test.beforeEach(({}, testInfo) => skipUnlessInteractionWidth(testInfo));

    test('the confirmation restates the counts before anything is written', async ({ page }) => {
        await signIn(page, 'ar', adminFor('ar'));
        await page.goto(`/imports/${JOBS.validated.ar}`);
        await stabilise(page);

        await page.getByRole('button', { name: /تأكيد الاستيراد/ }).first().click();
        await page.waitForTimeout(200);

        /*
         * FR-082. Not pressed: the dialog is photographed and closed, so the fixture job stays
         * validated for every other worker.
         */
        await expect(page.locator('#import-confirm .track-dialog')).toHaveScreenshot('ar-confirm-dialog.png');
    });

    test('choosing a file shows its name and size, and refuses the wrong type at once', async ({ page }) => {
        await signIn(page, 'ar', adminFor('ar'));
        await page.goto('/imports/new');
        await stabilise(page);

        await page.setInputFiles('#import-file', {
            name: 'roster.pdf',
            mimeType: 'application/pdf',
            buffer: Buffer.from('%PDF-1.4'),
        });

        await expect(page.locator('.track-alert--danger')).toBeVisible();
        await expect(page.locator('.track-import__upload')).toHaveScreenshot('ar-upload-refused.png');
    });
});
