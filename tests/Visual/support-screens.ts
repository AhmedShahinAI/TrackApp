/**
 * Phase 24 — the support channel's eight screens, each reached the way a person reaches it, for the
 * axe (T445) and mirroring (T446) specs.
 *
 * Fixtures: UiFixtureSeeder's support requests and roster. FX00000002J belongs to student 101
 * (441002352) and is in progress with a reply; 441002315 (student 100) is enrolled in the current
 * term. global-setup.ts widens the support limits on the visual database, so these flows can repeat
 * across projects from localhost — except where a screen needs a limit reached, which uses its own
 * address-free path: the limit page is rendered by the status lookup after the per-reference counter
 * is spent on an invented reference.
 */
import { expect, type Page } from '@playwright/test';
import { adminFor, signIn } from './support/session';

export type SupportScreen =
    | 'form' | 'submitted' | 'refused' | 'status-form' | 'status-result' | 'limit-reached'
    | 'queue' | 'detail';

export const SUPPORT_SCREENS: SupportScreen[] = [
    'form', 'submitted', 'refused', 'status-form', 'status-result', 'limit-reached', 'queue', 'detail',
];

const ENROLLED = '441002315';
const WITH_REPLY = { reference: 'FX00000002J', universityId: '441002352' };

async function submitForm(page: Page, locale: 'ar' | 'en', universityId: string): Promise<void> {
    await page.goto(`/support?lang=${locale}`);
    await expect(page.locator('main form button[type="submit"]')).toBeEnabled({ timeout: 30_000 });
    await page.fill('input[name="university_id"]', universityId);
    await page.selectOption('select[name="category"]', 'incorrect_data');
    await page.fill('textarea[name="description"]', 'لا تظهر شعبتي في الجدول الدراسي.');
    await page.waitForTimeout(3_100);
    await page.click('main form button[type="submit"]');
}

async function lookUp(page: Page, locale: 'ar' | 'en', reference: string, universityId: string): Promise<void> {
    await page.goto(`/support/status?lang=${locale}`);
    await page.fill('input[name="reference"]', reference);
    await page.fill('input[name="university_id"]', universityId);
    await page.click('main form button[type="submit"]');
}

/** Open one screen in one locale, as the person who sees it. */
export async function openSupportScreen(page: Page, locale: 'ar' | 'en', screen: SupportScreen): Promise<void> {
    switch (screen) {
        case 'form':
            await page.goto(`/support?lang=${locale}`);
            await expect(page.locator('main form button[type="submit"]')).toBeEnabled({ timeout: 30_000 });
            return;
        case 'submitted':
            await submitForm(page, locale, ENROLLED);
            await expect(page).toHaveURL(/\/support\/submitted$/);
            return;
        case 'refused':
            await submitForm(page, locale, '449999990');
            await expect(page.locator('.track-alert--danger')).toBeVisible();
            return;
        case 'status-form':
            await page.goto(`/support/status?lang=${locale}`);
            return;
        case 'status-result':
            await lookUp(page, locale, WITH_REPLY.reference, WITH_REPLY.universityId);
            await expect(page.locator('.track-public__message')).toHaveCount(1);
            return;
        case 'limit-reached': {
            // Spend the per-reference counter on a reference nobody holds (5 an hour, seeded).
            const invented = `ZZZZ${Date.now().toString().slice(-6)}A`;
            for (let i = 0; i < 6; i++) {
                await lookUp(page, locale, invented, '441000001');
            }
            await expect(page.locator('h1')).toBeVisible();
            return;
        }
        case 'queue':
            await signIn(page, locale, adminFor(locale));
            await page.goto('/support-requests');
            return;
        case 'detail':
            await signIn(page, locale, adminFor(locale));
            await page.goto(`/support-requests/${WITH_REPLY.reference}`);
            return;
    }
}
