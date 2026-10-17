/**
 * T410 — the support channel's staff screens, pinned: the queue, a request with its response thread,
 * and the rejection confirmation, in both locales at every project width.
 *
 * Against UiFixtureSeeder's three support requests (FX00000001…3): one waiting, one in progress with a
 * reply, one rejected. The request opened here is the in-progress one, so opening it changes nothing
 * (a `new` request would move to in progress on first open, and the baseline would depend on which
 * project ran first).
 *
 * Waiting times are relative to "now", so every cell that shows one is masked; the column's place and
 * alignment are what the baseline pins.
 */
import { expect, test } from '@playwright/test';
import { adminFor, signIn } from './support/session';
import { stabilise } from './helpers/render-reference';

/**
 * The in-progress fixture request: FX00000002 and its Luhn mod 32 check character, as
 * ReferenceGenerator::checkCharacter computes it (UiFixtureSeeder::seedSupportRequests).
 */
const IN_PROGRESS = 'FX00000002J';

function localeOf(projectName: string): 'ar' | 'en' {
    return projectName.startsWith('en') ? 'en' : 'ar';
}

test.describe('support — staff screens (US10)', () => {
    test('the queue', async ({ page }, testInfo) => {
        const locale = localeOf(testInfo.project.name);

        await signIn(page, locale, adminFor(locale));
        await page.goto('/support-requests');
        await stabilise(page);

        await expect(page).toHaveScreenshot(`${locale}-support-queue.png`, {
            fullPage: true,
            // The waiting-time cells: the seventh column (SupportQueueController / queue.blade.php).
            mask: [page.locator('tbody tr td:nth-child(7)')],
        });
    });

    test('a request with its response thread', async ({ page }, testInfo) => {
        const locale = localeOf(testInfo.project.name);

        await signIn(page, locale, adminFor(locale));
        await page.goto(`/support-requests/${IN_PROGRESS}`);
        await stabilise(page);

        await expect(page).toHaveScreenshot(`${locale}-support-request.png`, {
            fullPage: true,
            mask: [page.locator('.track-support__facts dd').nth(6)],
        });
    });

    test('the rejection confirmation', async ({ page }, testInfo) => {
        const locale = localeOf(testInfo.project.name);

        await signIn(page, locale, adminFor(locale));
        await page.goto(`/support-requests/${IN_PROGRESS}`);
        await page.click('.track-support__reject button');
        await expect(page.locator('#support-reject [role="dialog"]')).toBeVisible();
        await stabilise(page);

        await expect(page.locator('#support-reject [role="dialog"]')).toHaveScreenshot(`${locale}-support-reject.png`);
    });
});
