/**
 * T445 — axe-core over all eight support screens in both locales, requiring ZERO WCAG 2.1 AA
 * violations (SC-036). The rejection dialog on the request detail is opened and scanned too: axe skips
 * what is display:none, so a scan of the page as it loads says nothing about it.
 *
 * At 1440 and 390 only, as axe-all.spec.ts: the other widths serve the same markup.
 */
import AxeBuilder from '@axe-core/playwright';
import { expect, test, type Page } from '@playwright/test';
import { stabilise } from './helpers/render-reference';
import { openSupportScreen, SUPPORT_SCREENS } from './support-screens';

const WIDTHS = [1440, 390];

test.describe.configure({ timeout: 180_000 });

test.beforeEach(({}, testInfo) => {
    test.skip(! WIDTHS.includes(testInfo.project.use.viewport?.width ?? 0), 'Scanned at 1440 and 390.');
});

function localeOf(project: string): 'ar' | 'en' {
    return project.startsWith('en') ? 'en' : 'ar';
}

async function violations(page: Page, include?: string): Promise<string[]> {
    let builder = new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']);

    if (include) {
        builder = builder.include(include);
    }

    const result = await builder.analyze();

    return result.violations.map((v) => `${v.id}: ${v.nodes.map((n) => n.target.join(' ')).join(', ')}`);
}

for (const screen of SUPPORT_SCREENS) {
    test(`support · ${screen} has no WCAG 2.1 AA violation`, async ({ page }, testInfo) => {
        const locale = localeOf(testInfo.project.name);

        await openSupportScreen(page, locale, screen);
        await stabilise(page);

        expect(await violations(page), `${screen} (${locale})`).toEqual([]);

        if (screen === 'detail') {
            await page.click('.track-support__reject button');
            await expect(page.locator('#support-reject [role="dialog"]')).toBeVisible();

            expect(await violations(page, '#support-reject'), 'the rejection dialog').toEqual([]);
        }
    });
}
