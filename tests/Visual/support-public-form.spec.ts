/**
 * T388 — the public support pages, pinned: the form, the confirmation and the generic refusal, in both
 * locales at every project width (desktop and phone among them).
 *
 * Driven the way a student drives them. The form fetches and solves its own proof-of-work in the
 * browser — the screenshot of the form is taken once the check reports ready, so the status line is
 * stable — and the elapsed-time guard means a person cannot submit within its minimum, so neither does
 * this test. global-setup.ts widens the per-address and per-student limits on the visual database:
 * every project posts from localhost.
 *
 * The reference on the confirmation is random by design, so it is masked; its typography and
 * placement are what the baseline pins.
 *
 * Fixture student 441002315 is enrolled in the current term (UiFixtureSeeder's generated roster);
 * 449999990 is on no roster.
 */
import { expect, test, type Page } from '@playwright/test';
import { stabilise } from './helpers/render-reference';

const ENROLLED = '441002315';
const UNKNOWN = '449999990';

function localeOf(projectName: string): 'ar' | 'en' {
    return projectName.startsWith('en') ? 'en' : 'ar';
}

async function openForm(page: Page, locale: 'ar' | 'en'): Promise<void> {
    await page.goto(`/support?lang=${locale}`);
    await expect(page.locator('html')).toHaveAttribute('lang', locale);

    // The check is done when the submit button is enabled.
    await expect(page.locator('main form button[type="submit"]')).toBeEnabled({ timeout: 30_000 });
}

async function fillAndSend(page: Page, universityId: string): Promise<void> {
    await page.fill('input[name="university_id"]', universityId);
    await page.selectOption('select[name="category"]', 'incorrect_data');
    await page.fill('textarea[name="description"]', 'لا تظهر شعبتي في الجدول الدراسي.');

    // The elapsed-time guard: a form returned within its minimum was not filled by a person.
    await page.waitForTimeout(3_100);

    await page.click('main form button[type="submit"]');
}

test.describe('support — public pages (US9)', () => {
    test('the form', async ({ page }, testInfo) => {
        const locale = localeOf(testInfo.project.name);

        await openForm(page, locale);
        await stabilise(page);

        await expect(page).toHaveScreenshot(`${locale}-support-form.png`, { fullPage: true });
    });

    test('the confirmation', async ({ page }, testInfo) => {
        const locale = localeOf(testInfo.project.name);

        await openForm(page, locale);
        await fillAndSend(page, ENROLLED);

        await expect(page).toHaveURL(/\/support\/submitted$/);
        await stabilise(page);

        await expect(page).toHaveScreenshot(`${locale}-support-submitted.png`, {
            fullPage: true,
            mask: [page.locator('.track-public__reference')],
        });
    });

    test('the generic refusal', async ({ page }, testInfo) => {
        const locale = localeOf(testInfo.project.name);

        await openForm(page, locale);
        await fillAndSend(page, UNKNOWN);

        await expect(page.locator('.track-alert--danger')).toBeVisible();
        await stabilise(page);

        await expect(page).toHaveScreenshot(`${locale}-support-refused.png`, { fullPage: true });
    });
});
