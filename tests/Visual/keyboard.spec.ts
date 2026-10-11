/**
 * T272 — keyboard operation on the screens, beyond what axe can see (SC-014).
 *
 * axe checks that controls have names and colours have contrast; it cannot press Tab. These do:
 * the skip link is the first stop and lands in the content, a dialog holds focus while open and
 * hands it back to its trigger when closed, and a focused control shows the --green-600 ring.
 * Measured at 1440 in each locale — the behaviour is the same at every width, and a phone has no Tab.
 */
import { expect, test, type Page } from '@playwright/test';
import { adminFor, signIn } from './support/session';

test.beforeEach(({}, testInfo) => {
    test.skip(testInfo.project.use.viewport?.width !== 1440, 'Keyboard behaviour is checked at 1440.');
});

function localeOf(project: string): 'ar' | 'en' {
    return project.startsWith('ar-') ? 'ar' : 'en';
}

async function focusedSelector(page: Page): Promise<string> {
    return page.evaluate(() => {
        const el = document.activeElement as HTMLElement | null;

        return el === null ? '' : `${el.tagName.toLowerCase()}${el.id ? '#' + el.id : ''}.${Array.from(el.classList).join('.')}`;
    });
}

test('the skip link is the first Tab stop and lands in the content', async ({ page }, testInfo) => {
    const locale = localeOf(testInfo.project.name);
    await signIn(page, locale, adminFor(locale));
    await page.goto('/branches');

    await page.keyboard.press('Tab');
    expect(await focusedSelector(page)).toContain('track-skip-link');
    await expect(page.locator('.track-skip-link')).toBeInViewport();

    await page.keyboard.press('Enter');
    await expect(page).toHaveURL(/#track-content$/);
});

test('a dialog holds focus while open and returns it to its trigger', async ({ page }, testInfo) => {
    const locale = localeOf(testInfo.project.name);
    await signIn(page, locale, adminFor(locale));
    await page.goto('/branches');

    const trigger = page.locator('.track-page-header .track-btn--primary').first();
    await trigger.focus();
    await page.keyboard.press('Enter');

    const dialog = page.locator('#branch-dialog [role="dialog"]');
    await expect(dialog).toBeVisible();

    // Focus moved into the dialog, and twenty Tabs — more than it has controls — never leave it.
    expect(await dialog.evaluate((d) => d.contains(document.activeElement))).toBe(true);

    for (let i = 0; i < 20; i++) {
        await page.keyboard.press(i % 5 === 4 ? 'Shift+Tab' : 'Tab');
        expect(await dialog.evaluate((d) => d.contains(document.activeElement)), `Tab ${i + 1} stayed inside`).toBe(true);
    }

    await page.keyboard.press('Escape');
    await expect(dialog).toBeHidden();
    await expect(trigger).toBeFocused();
});

test('a focused control shows the focus ring', async ({ page }, testInfo) => {
    const locale = localeOf(testInfo.project.name);
    await signIn(page, locale, adminFor(locale));
    await page.goto('/branches');

    // Keyboard focus, so :focus-visible applies.
    const trigger = page.locator('.track-page-header .track-btn--primary').first();
    await page.locator('body').click({ position: { x: 1, y: 1 } });
    await trigger.focus();
    await page.keyboard.press('Shift+Tab');
    await page.keyboard.press('Tab');

    // Polled: box-shadow eases in over --dur-fast (tokens/base.css), so the first read is mid-fade.
    const ring = () => page.evaluate(() => {
        const style = getComputedStyle(document.activeElement as HTMLElement);

        return `${style.boxShadow} | ${style.outlineStyle}`;
    });

    // --ring-focus is rgba(16,185,129,.25) — the --green-600 family — drawn as a box-shadow.
    await expect.poll(ring, { message: 'the --green-600 focus ring' }).toContain('rgba(16, 185, 129, 0.25)');
});

test('a dialog opened from a table row returns focus to that row\'s button', async ({ page }, testInfo) => {
    const locale = localeOf(testInfo.project.name);
    await signIn(page, locale, adminFor(locale));
    await page.goto('/sections/13');

    // The withdraw dialog is one element shared by every roster row; the row's button relays the
    // open, and focus must come back to that button — not to the wrapper the relay passed through.
    const withdraw = page.locator('td .track-btn--ghost').filter({ has: page.locator('use[href$="#user-minus"]') }).nth(1);
    await withdraw.focus();
    await page.keyboard.press('Enter');

    const dialog = page.locator('#withdraw-dialog [role="dialog"]');
    await expect(dialog).toBeVisible();

    // Focus has to have left the row for its return to mean anything.
    await expect.poll(() => dialog.evaluate((d) => d.contains(document.activeElement))).toBe(true);

    await page.keyboard.press('Escape');
    await expect(dialog).toBeHidden();
    await expect(withdraw).toBeFocused();
});
