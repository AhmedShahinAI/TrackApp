/**
 * T428 — the status page, pinned: the form, a result with replies, a result with none, and the generic
 * failure, in both locales at every project width.
 *
 * Against UiFixtureSeeder's support requests: FX00000002J (student 101, in progress, one reply) and
 * FX00000001M (student 100, new, no reply). The University IDs follow the seeder's arithmetic:
 * '44' . str_pad(1002315 + i * 37, 7, '0') for student 100 + i.
 *
 * Every project posts from localhost; the status limit (10 an hour per address) is widened on the
 * visual database by global-setup.ts, as the submission limits are.
 */
import { expect, test, type Page } from '@playwright/test';
import { stabilise } from './helpers/render-reference';

const WITH_REPLY = { reference: 'FX00000002J', universityId: '441002352' };
const WITHOUT_REPLY = { reference: 'FX00000001M', universityId: '441002315' };

function localeOf(projectName: string): 'ar' | 'en' {
    return projectName.startsWith('en') ? 'en' : 'ar';
}

async function lookUp(page: Page, locale: 'ar' | 'en', reference: string, universityId: string): Promise<void> {
    await page.goto(`/support/status?lang=${locale}`);
    await page.fill('input[name="reference"]', reference);
    await page.fill('input[name="university_id"]', universityId);
    await page.click('main form button[type="submit"]');
}

test.describe('support — status page (US11)', () => {
    test('the form', async ({ page }, testInfo) => {
        const locale = localeOf(testInfo.project.name);

        await page.goto(`/support/status?lang=${locale}`);
        await stabilise(page);

        await expect(page).toHaveScreenshot(`${locale}-support-status-form.png`, { fullPage: true });
    });

    test('a result with replies', async ({ page }, testInfo) => {
        const locale = localeOf(testInfo.project.name);

        await lookUp(page, locale, WITH_REPLY.reference, WITH_REPLY.universityId);
        await expect(page.locator('.track-public__message')).toHaveCount(1);
        await stabilise(page);

        await expect(page).toHaveScreenshot(`${locale}-support-status-replies.png`, { fullPage: true });
    });

    test('a result with no reply yet', async ({ page }, testInfo) => {
        const locale = localeOf(testInfo.project.name);

        await lookUp(page, locale, WITHOUT_REPLY.reference, WITHOUT_REPLY.universityId);
        await expect(page.locator('.track-public__message')).toHaveCount(0);
        await stabilise(page);

        await expect(page).toHaveScreenshot(`${locale}-support-status-pending.png`, { fullPage: true });
    });

    test('the generic failure', async ({ page }, testInfo) => {
        const locale = localeOf(testInfo.project.name);

        await lookUp(page, locale, WITH_REPLY.reference, '449999990');
        await expect(page.locator('.track-alert--danger')).toBeVisible();
        await stabilise(page);

        await expect(page).toHaveScreenshot(`${locale}-support-status-failed.png`, { fullPage: true });
    });
});
