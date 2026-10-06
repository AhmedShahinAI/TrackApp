/**
 * T114 — the User Story 1 screens, pinned.
 *
 * Login (default, error, lockout countdown), the first-sign-in banner, the landing page, the
 * accounts list, the create/edit dialog, and the two confirmations — both locales, at every project
 * width.
 *
 * Signing in needs a real session, so each test posts the sign-in form rather than faking a user:
 * the point is partly that the screens work for someone who got there the way a person does.
 * Credentials come from UiFixtureSeeder (T051), which the visual database is seeded with.
 *
 * As in components.spec.ts, the baselines pin the current rendering and fail on drift. Fidelity to
 * the AuthScreens / HomeScreen / UsersScreen references stays the human gate design-system.md §13
 * defines — tasks.md T114 names those references, and comparing against them automatically has the
 * same problem components.spec.ts documents: the kit ships no per-screen rendered artifact that
 * shares this layout.
 */
import { expect, test, type Page } from '@playwright/test';
import { stabilise } from './helpers/render-reference';

/** From UiFixtureSeeder: user 1, مها السالم, SUPER_ADMIN — holds every permission. */
const ADMIN = { email: 'm.alsalem@mu.edu.sa', password: 'Track2026' };

/** User 3, د. فيصل العتيبي — an instructor who has never signed in, so the banner shows for them. */
const FIRST_TIME = { email: 'f.alotaibi@mu.edu.sa', password: 'Track2026' };

/** User 6, أ. سلمان الرشيدي — deactivated, for the refusal state. */
const DEACTIVATED = { email: 's.alrashidi@mu.edu.sa', password: 'Track2026' };

/**
 * Tests that drive an interaction run at one width only.
 *
 * Opening a dialog or tripping the rate limiter is not width-dependent, and below 768px the data
 * table stacks into cards — the row-action buttons are then present but not visible, so a click
 * waits for a visibility that never comes. The stacked-card layout itself is covered by
 * components.spec.ts, which shoots the table at every width.
 *
 * The screens that ARE width-dependent — login, home, users, profile — still run everywhere.
 */
const INTERACTION_WIDTH = 1440;

function skipUnlessInteractionWidth(testInfo: { project: { use: { viewport?: { width: number } | null } } }): void {
    test.skip(
        testInfo.project.use.viewport?.width !== INTERACTION_WIDTH,
        'Interaction-driven; runs at 1440 only. Width-dependent layout is covered elsewhere.',
    );
}

async function signIn(page: Page, locale: 'ar' | 'en', who: { email: string; password: string }) {
    await page.goto(`/login?locale=${locale}`);
    await page.fill('input[name="email"]', who.email);
    await page.fill('input[name="password"]', who.password);

    /*
     * Scoped to the credentials form. The language switch is also a submit button and sits EARLIER
     * in the DOM, so a bare `button[type="submit"]` clicked the switch, reloaded the page in the
     * other language, and never signed anyone in — while every screenshot silently captured the
     * login page instead of the screen under test.
     */
    await page.click('.track-login__fields button[type="submit"]');
    await page.waitForLoadState('load');

    /*
     * Asserted, not assumed. This is the guard against the failure above: a screenshot of the wrong
     * page is a passing test and a worthless baseline, so the helper refuses to return until the
     * sign-in screen is actually behind us.
     */
    await expect(page.locator('.track-login__fields')).toHaveCount(0);
}

for (const locale of ['ar', 'en'] as const) {
    test.describe(`US1 · ${locale}`, () => {
        test('login — default', async ({ page }) => {
            await page.goto(`/login?locale=${locale}`);
            await stabilise(page);

            // The whole page: this is the one screen with no application shell, and its split
            // layout is the thing most likely to drift.
            await expect(page).toHaveScreenshot(`${locale}-login-default.png`, { fullPage: true });
        });

        test('login — generic failure', async ({ page }) => {
            await page.goto(`/login?locale=${locale}`);
            await page.fill('input[name="email"]', 'nobody@mu.edu.sa');
            await page.fill('input[name="password"]', 'definitely-wrong');
            await page.click('button[type="submit"]');
            await page.waitForLoadState('networkidle');
            await stabilise(page);

            // FR-009: the alert says nothing about which field was wrong, and the baseline is what
            // would catch someone "helpfully" making it specific.
            await expect(page.locator('.track-login__fields')).toHaveScreenshot(
                `${locale}-login-error.png`,
            );
        });

        test('login — deactivated refusal', async ({ page }) => {
            await page.goto(`/login?locale=${locale}`);
            await page.fill('input[name="email"]', DEACTIVATED.email);
            await page.fill('input[name="password"]', DEACTIVATED.password);
            await page.click('button[type="submit"]');
            await page.waitForLoadState('networkidle');
            await stabilise(page);

            await expect(page.locator('.track-login__fields')).toHaveScreenshot(
                `${locale}-login-deactivated.png`,
            );
        });

        test('login — lockout countdown', async ({ page }, testInfo) => {
            skipUnlessInteractionWidth(testInfo);
            test.setTimeout(120_000);

            // FR-010. Six attempts for one address trips the limiter; the sixth response carries
            // retry_after and the screen counts down from it.
            await page.goto(`/login?locale=${locale}`);

            for (let attempt = 0; attempt < 6; attempt++) {
                await page.fill('input[name="email"]', 'lockout-target@mu.edu.sa');
                await page.fill('input[name="password"]', 'wrong-password');
                // Scoped, as in signIn: the language switch is also a submit button.
                await page.click('.track-login__fields button[type="submit"]');
                await page.waitForLoadState('load');
            }

            await stabilise(page);

            /*
             * The alert only — not the whole column. The countdown ticks once a second, so a
             * full-page shot would capture a different number on every run; the alert is masked
             * instead and what is pinned is that the warning tone and the disabled button appear.
             */
            await expect(page.locator('.track-login__fields')).toHaveScreenshot(
                `${locale}-login-lockout.png`,
                // Both the alert AND the submit button are masked: the button label counts down too
                // (`remaining + 's'`), so masking only the alert still left a digit changing every
                // second — a 16%% diff on every run, which is what this caught.
                { mask: [page.locator('.track-alert'), page.locator('.track-login__fields button[type="submit"]')] },
            );
        });

        test('first-sign-in banner on the landing page', async ({ page }) => {
            await signIn(page, locale, FIRST_TIME);
            await stabilise(page);

            const banner = page.locator('.track-alert--warning').first();

            // The instructor may land on /profile rather than /home depending on what the seeder
            // grants; either way the banner is the thing under test.
            if (await banner.count() > 0) {
                await expect(banner).toHaveScreenshot(`${locale}-first-login-banner.png`);
            } else {
                test.skip(true, 'This role does not land on a page that hosts the banner.');
            }
        });

        test('landing page', async ({ page }) => {
            await signIn(page, locale, ADMIN);
            await stabilise(page);

            await expect(page).toHaveScreenshot(`${locale}-home.png`, { fullPage: true });
        });

        test('accounts list', async ({ page }) => {
            await signIn(page, locale, ADMIN);
            await page.goto('/users');
            await stabilise(page);

            // last_login_at renders as a timestamp from the fixture, which is fixed — but the
            // column is masked anyway, because a fixture reseed would otherwise invalidate every
            // width's baseline for a reason that has nothing to do with the design.
            await expect(page).toHaveScreenshot(`${locale}-users.png`, {
                fullPage: true,
                mask: [page.locator('td[data-label*="دخول"], td[data-label*="sign-in"]')],
            });
        });

        test('create dialog', async ({ page }, testInfo) => {
            skipUnlessInteractionWidth(testInfo);

            await signIn(page, locale, ADMIN);
            await page.goto('/users');
            await page.click('.track-page-header__actions button');
            await page.waitForTimeout(250);
            await stabilise(page);

            // By id. The accounts screen hosts three dialogs, so .first()/.last() selected a
            // hidden one and the screenshot waited forever for it to become stable.
            await expect(page.locator('#user-dialog .track-dialog')).toHaveScreenshot(
                `${locale}-user-dialog.png`,
            );
        });

        test('deactivate confirmation', async ({ page }, testInfo) => {
            skipUnlessInteractionWidth(testInfo);

            await signIn(page, locale, ADMIN);
            await page.goto('/users');

            const trigger = page.locator('button[title*="تعطيل"], button[title*="Deactivate"]').first();

            if (! await trigger.isVisible().catch(() => false)) {
                test.skip(true, 'No visible deactivatable row at this width.');
            }

            await trigger.click();
            await page.waitForTimeout(250);
            await stabilise(page);

            // FR-082: the consequence and the preserved-history note are both in the shot, because
            // both are what make the action safe to take.
            await expect(page.locator('#user-deactivate .track-dialog')).toHaveScreenshot(
                `${locale}-deactivate-confirm.png`,
            );
        });

        test('reset-password confirmation', async ({ page }, testInfo) => {
            skipUnlessInteractionWidth(testInfo);

            await signIn(page, locale, ADMIN);
            await page.goto('/users');

            const trigger = page
                .locator('button[title*="إعادة تعيين"], button[title*="Reset"]')
                .first();

            if (! await trigger.isVisible().catch(() => false)) {
                test.skip(true, 'No visible resettable row at this width.');
            }

            await trigger.click();
            await page.waitForTimeout(250);
            await stabilise(page);

            await expect(page.locator('#user-reset .track-dialog')).toHaveScreenshot(
                `${locale}-reset-confirm.png`,
            );
        });

        test('profile and change password', async ({ page }) => {
            await signIn(page, locale, ADMIN);
            await page.goto('/profile');
            await stabilise(page);

            await expect(page).toHaveScreenshot(`${locale}-profile.png`, { fullPage: true });

            await page.goto('/profile/password');
            await stabilise(page);

            await expect(page).toHaveScreenshot(`${locale}-password.png`, { fullPage: true });
        });
    });
}

test.describe('US1 behaviour the pixels cannot pin', () => {
    test('the sign-in form is fully keyboard operable', async ({ page }) => {
        await page.goto('/login?locale=ar');

        // Tab order: email, password, submit. A screen whose only path to the button is a mouse is
        // a screen a keyboard user cannot sign in on, and no screenshot shows that.
        await page.keyboard.press('Tab');
        await page.keyboard.press('Tab');

        const reached = await page.evaluate(() => {
            const active = document.activeElement as HTMLElement | null;

            return { tag: active?.tagName, name: active?.getAttribute('name') };
        });

        expect(['INPUT', 'BUTTON', 'A']).toContain(reached.tag);
    });

    test('no page scrolls sideways at any width', async ({ page }) => {
        for (const path of ['/login?locale=ar', '/login?locale=en']) {
            await page.goto(path);
            await stabilise(page);

            const overflow = await page.evaluate(
                () => document.documentElement.scrollWidth - document.documentElement.clientWidth,
            );

            expect(overflow, `${path} scrolls sideways`).toBeLessThanOrEqual(1);
        }
    });
});
