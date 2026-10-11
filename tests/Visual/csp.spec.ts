/**
 * T277 — the strict Content Security Policy breaks nothing: every screen, in both locales, loads and
 * runs with zero CSP violations.
 *
 * ContentSecurityPolicyTest pins the policy on the server; only a browser can say whether the pages
 * live within it — an Alpine expression needing eval, a Bootstrap data: image, a style attribute.
 * Every `securitypolicyviolation` event is recorded from the first byte, and each screen's dialogs
 * are opened so the scripts behind them run too.
 */
import { expect, test, type Page } from '@playwright/test';
import { accountFor, adminFor, signIn } from './support/session';
import { SCREENS } from './support/screens';

test.describe.configure({ timeout: 90_000 });

// The suite bypasses CSP so its screenshot harness can inject styles (playwright.config.ts); this
// spec is the one that must not.
test.use({ bypassCSP: false });

/** Settle without injecting anything — a style tag here would itself be a violation. */
async function settle(page: Page): Promise<void> {
    await page.waitForLoadState('networkidle');
    await page.evaluate(() => document.fonts.ready);
}

test.beforeEach(({}, testInfo) => {
    test.skip(testInfo.project.use.viewport?.width !== 1440, 'Checked at 1440; the policy does not vary by width.');
});

function projectLocale(project: string): 'ar' | 'en' {
    return project.startsWith('ar-') ? 'ar' : 'en';
}

async function recordViolations(page: Page): Promise<() => Promise<string[]>> {
    await page.addInitScript(() => {
        (window as unknown as { __csp: string[] }).__csp = [];
        document.addEventListener('securitypolicyviolation', (e) => {
            (window as unknown as { __csp: string[] }).__csp.push(`${e.violatedDirective} ← ${e.blockedURI || 'inline'} (${e.sourceFile}:${e.lineNumber})`);
        });
    });

    return () => page.evaluate(() => (window as unknown as { __csp?: string[] }).__csp ?? []);
}

for (const screen of SCREENS) {
    test(`${screen.key} runs within the policy`, async ({ page }, testInfo) => {
        const locale = projectLocale(testInfo.project.name);
        const violations = await recordViolations(page);

        // Alpine's CSP build cannot run every expression the standard build can; one it cannot parse
        // is reported as a console error or a page error, not a CSP violation. Both count.
        const errors: string[] = [];
        page.on('pageerror', (e) => errors.push(`pageerror: ${e.message}`));
        page.on('console', (m) => {
            // The refusal screen is a 404 by design; the browser logs its own document's status.
            if (screen.key.startsWith('refusal-') && /status of 40[34]/.test(m.text())) {
                return;
            }

            if (m.type() === 'error' || (m.type() === 'warning' && /alpine/i.test(m.text()))) {
                errors.push(`console.${m.type()}: ${m.text()}`);
            }
        });

        if (screen.as === 'guest') {
            await page.goto(`${screen.path}?locale=${locale}`);
        } else {
            await signIn(page, locale, screen.as === 'admin' ? adminFor(locale) : accountFor('instructor', locale));
            await page.goto(screen.path);
        }

        await settle(page);

        const header = (await page.request.get(page.url())).headers()['content-security-policy'] ?? '';
        expect(header, 'the page is served under the policy').toContain("default-src 'self'");

        // Run the scripts behind each dialog, too.
        const dialogs = await page.locator('.track-dialog-scrim[id]').evaluateAll((nodes) => nodes.map((n) => n.id));

        for (const id of dialogs.slice(0, 5)) {
            await page.evaluate((d) => window.dispatchEvent(new CustomEvent('track-dialog-open', { detail: { id: d } })), id);
            await expect(page.locator(`#${id} [role="dialog"]`)).toBeVisible();
            await page.evaluate((d) => window.dispatchEvent(new CustomEvent('track-dialog-close', { detail: { id: d } })), id);
        }

        expect(await violations()).toEqual([]);
        expect(errors).toEqual([]);
    });
}

/**
 * The CSP build of Alpine evaluates a handler when it fires, not when the page loads, so a click
 * handler it cannot parse is silent until someone clicks. Each rewritten handler (forms.js,
 * dialog.js, data-table.js) is exercised here, under the policy, with errors captured.
 */
test('the rewritten handlers work under the policy', async ({ page }, testInfo) => {
    const locale = projectLocale(testInfo.project.name);
    const violations = await recordViolations(page);
    const errors: string[] = [];
    page.on('pageerror', (e) => errors.push(`pageerror: ${e.message}`));
    page.on('console', (m) => {
        if (m.type() === 'error' || (m.type() === 'warning' && /alpine/i.test(m.text()))) {
            errors.push(`console.${m.type()}: ${m.text()}`);
        }
    });

    await signIn(page, locale, adminFor(locale));

    // Accounts: edit fills the dialog from the row; deactivate and reset name the account.
    await page.goto('/users');
    await settle(page);
    const row = page.locator('tbody tr').filter({ has: page.locator('.track-row-actions') }).first();
    const email = (await row.locator('td[data-label]').nth(1).innerText()).trim();

    await row.locator('.track-row-actions button').first().click();
    await expect(page.locator('#user-dialog [role="dialog"]')).toBeVisible();
    await expect(page.locator('#user-dialog input[name="email"]')).toHaveValue(email);
    await page.keyboard.press('Escape');

    const deactivate = page.locator('.track-row-actions button[data-user-name]').last();
    const name = (await deactivate.getAttribute('data-user-name')) ?? '';
    await deactivate.click();
    const target = page.locator('#user-deactivate, #user-reset').locator('[role="dialog"]:visible');
    await expect(target).toBeVisible();
    await expect(target).toContainText(name);
    await page.keyboard.press('Escape');

    // The roster: withdraw names the student; a column sorts.
    await page.goto('/sections/13');
    await settle(page);
    const withdraw = page.locator('td button[data-student]').first();
    const student = (await withdraw.getAttribute('data-student')) ?? '';
    await withdraw.click();
    await expect(page.locator('#withdraw-dialog [role="dialog"]')).toBeVisible();
    await expect(page.locator('#withdraw-dialog .track-dialog__subject')).toHaveText(student);
    await expect(page.locator('#withdraw-dialog-form')).toHaveAttribute('action', /\/enrollments\/\d+\/withdraw$/);
    await page.keyboard.press('Escape');

    const sortButton = page.locator('.track-table__sort').first();
    await sortButton.click();
    await expect(page.locator('th[aria-sort="ascending"]')).toHaveCount(1);
    await expect(sortButton.locator('use')).toHaveAttribute('href', /#arrow-up$/);

    // A clickable row navigates.
    await page.goto('/sections');
    await settle(page);
    await page.locator('tbody tr[role="link"]').first().click();
    await expect(page).toHaveURL(/\/sections\/\d+$/);

    // Import mapping: emptying a required column disables the check. Job 11 is the Arabic admin's;
    // anyone else sees it read-only, with no form to exercise.
    if (locale === 'ar') {
        await page.goto('/imports/11');
        await settle(page);
        const submit = page.locator('form[x-data="trackMappingForm"] button[type="submit"]');
        await expect(submit).toBeEnabled();
        await page.locator('select[data-target][required]').first().selectOption('');
        await expect(submit).toBeDisabled();
    }

    expect(await violations()).toEqual([]);
    expect(errors).toEqual([]);
});
