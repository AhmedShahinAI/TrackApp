/**
 * T486 — section approval (Amendment 4), against v2's ApprovalsScreen and ApprovalDialog references:
 * the approval queue, the decision dialog, the rejection confirmation, a returned section with its
 * reason, and a badged pending section in the list. In both locales, at every project width.
 *
 * Fixtures (UiFixtureSeeder::seedApprovalSections): section 20 is instructor 5's pending request in
 * branch 1, and section 21 is the same instructor's request returned with a reason in branch 2. The
 * coordinators of both locales hold branches 1 and 2, so each has section 20 to decide.
 *
 * The dialogs are opened and photographed but never submitted. Submitting would change the fixture
 * every other project reads from.
 */
import { expect, test, type Page } from '@playwright/test';
import { refuseErrorPages } from './helpers/render-reference';
import { accountFor, signIn } from './support/session';

function localeOf(project: string): 'ar' | 'en' {
    return project.startsWith('en') ? 'en' : 'ar';
}

async function stabilise(page: Page): Promise<void> {
    await page.waitForLoadState('networkidle');
    await page.evaluate(() => document.fonts.ready);
    await page.waitForTimeout(150);
    await refuseErrorPages(page);
}

test.describe('section approval (Amendment 4)', () => {
    test('the approval queue', async ({ page }, testInfo) => {
        const locale = localeOf(testInfo.project.name);
        await signIn(page, locale, accountFor('coordinator', locale));
        await page.goto('/sections/approvals');
        await expect(page.locator('tbody tr')).toHaveCount(1);
        await stabilise(page);

        await expect(page).toHaveScreenshot(`${locale}-section-approvals.png`, { fullPage: true });
    });

    test('the decision dialog', async ({ page }, testInfo) => {
        const locale = localeOf(testInfo.project.name);
        await signIn(page, locale, accountFor('coordinator', locale));
        await page.goto('/sections/20');
        await page.locator('.track-page-header__actions .track-btn--primary').first().click();
        const dialog = page.locator('#section-decide .track-dialog');
        await expect(dialog).toBeVisible();
        await stabilise(page);

        await expect(dialog).toHaveScreenshot(`${locale}-section-decide-dialog.png`);
    });

    test('the rejection confirmation restates that rejection is final', async ({ page }, testInfo) => {
        const locale = localeOf(testInfo.project.name);
        await signIn(page, locale, accountFor('coordinator', locale));
        await page.goto('/sections/20');
        await page.locator('.track-page-header__actions .track-btn--danger').click();
        const dialog = page.locator('#section-reject .track-dialog');
        await expect(dialog.locator('.track-alert--danger')).toBeVisible();
        await stabilise(page);

        await expect(dialog).toHaveScreenshot(`${locale}-section-reject-dialog.png`);
    });

    test('a returned section shows its creator the reason and the resubmit action', async ({ page }, testInfo) => {
        test.skip(localeOf(testInfo.project.name) === 'en', 'The returned request belongs to the Arabic instructor.');
        await signIn(page, 'ar', accountFor('instructor', 'ar'));
        await page.goto('/sections/21');
        await expect(page.locator('.track-alert--warning')).toBeVisible();
        await stabilise(page);

        await expect(page).toHaveScreenshot('ar-section-returned.png', { fullPage: true });
    });

    test('a pending section is badged in the list', async ({ page }, testInfo) => {
        const locale = localeOf(testInfo.project.name);
        await signIn(page, locale, accountFor('coordinator', locale));
        await page.goto('/sections');
        await expect(page.locator('tbody .track-badge--warning').first()).toBeVisible();
        await stabilise(page);

        await expect(page).toHaveScreenshot(`${locale}-sections-with-approval.png`, { fullPage: true });
    });
});
