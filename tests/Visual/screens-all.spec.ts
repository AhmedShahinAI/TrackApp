/**
 * T264 — the full screen regression: every in-scope screen (design-system.md §9 rows 1–20), both
 * locales, at 1280 / 1440 / 1920 / 390, at ≤ 1% pixel difference.
 *
 * ## Against which reference
 *
 * The Blade baselines — reviewed against the React kit when each screen was built — not the kit
 * itself. components.spec.ts and reference.spec.ts record why: the kit renders in its own harness,
 * with its own reset and its own fonts loaded from a CDN, so a pixel diff between the two measures
 * the harness. The kit comparison stays a human review; this is the regression that holds the
 * reviewed result in place.
 *
 * ## One registry, no duplicate baselines
 *
 * Most screens are already photographed by their own story's spec (us1 … us6). Photographing them
 * again here would double the suite's run time for the same assertion. So this file photographs only
 * the screens no story spec covers in both locales at every width — the section and student detail
 * pages, which us4 shoots at 1440 in Arabic for its interactions, and the preferences page US7 adds —
 * and then asserts, per project, that every screen in the registry has a baseline. A screen added
 * without one fails here rather than going unwatched.
 */
import { existsSync } from 'node:fs';
import { join } from 'node:path';
import { expect, test, type Page } from '@playwright/test';
import { refuseErrorPages } from './helpers/render-reference';
import { adminFor, expectLocale, signIn } from './support/session';

test.describe.configure({ mode: 'serial' });

async function stabilise(page: Page) {
    await page.waitForLoadState('networkidle');
    await page.evaluate(() => document.fonts.ready);
    await page.waitForTimeout(150);
    await refuseErrorPages(page);
}

/** Dates seeded relative to now() by the fixture: they look fixed and are not. */
function volatileDates(page: Page) {
    return [page.locator('td[data-label*="تاريخ"], td[data-label*="date" i], td[data-label*="From"], td[data-label*="To"]')];
}

/** The screens this file photographs itself. */
const OWN = [
    { name: 'section-detail', path: '/sections/13' },
    { name: 'student-detail', path: '/students/100' },
    { name: 'preferences', path: '/profile/preferences' },
];

for (const locale of ['ar', 'en'] as const) {
    test.describe(`screens · ${locale}`, () => {
        for (const screen of OWN) {
            test(screen.name, async ({ page }) => {
                await signIn(page, locale, adminFor(locale));
                await page.goto(screen.path);
                await stabilise(page);
                await expectLocale(page, locale);

                await expect(page).toHaveScreenshot(`${locale}-${screen.name}.png`, {
                    fullPage: true,
                    mask: volatileDates(page),
                });
            });
        }
    });
}

/**
 * design-system.md §9 rows 1–20, each with the spec and screenshot that hold its baseline. Rows 8
 * and 10 are parts of other screens (the shared badge, the activity tab) and are listed with the
 * shot that isolates them.
 */
const REGISTRY: Array<{ row: number; screen: string; spec: string; shot: string; only?: { projects: Record<string, string[]>; why: string } }> = [
    { row: 1, screen: 'Login', spec: 'us1.spec.ts', shot: 'login-default' },
    { row: 2, screen: 'Profile', spec: 'us1.spec.ts', shot: 'profile' },
    { row: 2, screen: 'Preferences', spec: 'screens-all.spec.ts', shot: 'preferences' },
    {
        row: 3, screen: 'First sign-in banner', spec: 'us1.spec.ts', shot: 'first-login-banner',
        // Signing in stamps first_login_at, so each locale's never-signed-in account (users 3 and 12)
        // is photographed by one project only.
        only: { projects: { 'ar-1440': ['ar'], 'en-1440': ['en'] }, why: 'consumes a one-time first-sign-in account' },
    },
    { row: 4, screen: 'Home', spec: 'us1.spec.ts', shot: 'home' },
    { row: 5, screen: 'Users', spec: 'us1.spec.ts', shot: 'users' },
    { row: 6, screen: 'Refusal 403', spec: 'us2.spec.ts', shot: 'refusal-403' },
    { row: 7, screen: 'Refusal 404', spec: 'us2.spec.ts', shot: 'refusal-404' },
    { row: 8, screen: 'Shared-access badge', spec: 'us2.spec.ts', shot: 'sections-shared' },
    { row: 9, screen: 'Audit', spec: 'us3.spec.ts', shot: 'audit-filtered' },
    { row: 10, screen: 'Activity tab', spec: 'us3.spec.ts', shot: 'activity-panel' },
    { row: 11, screen: 'Terms', spec: 'us4.spec.ts', shot: 'terms' },
    { row: 12, screen: 'Branches', spec: 'us4.spec.ts', shot: 'branches' },
    { row: 13, screen: 'Courses', spec: 'us4.spec.ts', shot: 'courses' },
    { row: 14, screen: 'Sections', spec: 'us4.spec.ts', shot: 'sections-admin' },
    { row: 15, screen: 'Section detail', spec: 'screens-all.spec.ts', shot: 'section-detail' },
    { row: 16, screen: 'Students', spec: 'us4.spec.ts', shot: 'students' },
    { row: 17, screen: 'Student detail', spec: 'screens-all.spec.ts', shot: 'student-detail' },
    { row: 18, screen: 'Imports', spec: 'us5.spec.ts', shot: 'imports' },
    { row: 19, screen: 'Import wizard', spec: 'us5.spec.ts', shot: 'step-preview' },
    { row: 20, screen: 'Section import', spec: 'us6.spec.ts', shot: 'section-import' },
];

test('every in-scope screen has a baseline in this project, in both locales', async ({}, testInfo) => {
    // A row photographed in one place only says where, and why; everywhere else it is required.
    const missing: string[] = [];

    for (const entry of REGISTRY) {
        const locales = entry.only ? (entry.only.projects[testInfo.project.name] ?? []) : ['ar', 'en'];

        for (const locale of locales) {
            const file = join(testInfo.project.testDir, 'baselines', process.platform, testInfo.project.name, entry.spec, `${locale}-${entry.shot}.png`);

            if (! existsSync(file)) {
                missing.push(`row ${entry.row} ${entry.screen} · ${locale} — ${entry.spec} ${locale}-${entry.shot}.png`);
            }
        }
    }

    expect(missing, `screens without a baseline in ${testInfo.project.name}`).toEqual([]);
});
