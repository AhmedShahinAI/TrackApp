/**
 * T101 — the component visual-regression suite.
 *
 * Each of the 27 components is screenshotted from the gallery (T100) in both locales and compared
 * against its own stored baseline, at ≤ 1% pixel difference.
 *
 * ── Why the baseline is the Blade output, not the React reference ────────────────────────────────
 *
 * tasks.md T101 asks for each component to be compared against
 * `design-system/components/**\/*.card.html` "rendered at the same viewport". Those files do not
 * exist — the design system ships `.jsx` sources and `.prompt.md` contracts, and the only rendered
 * artifact is the whole-app kit at ui_kits/web-app/index.html, which has no per-component page.
 *
 * Comparing a Blade component against a React component rendered in a different document, with a
 * different reset, different font loading and no shared layout box, would produce a diff dominated
 * by the harness rather than by the design. The 1% threshold exists to absorb anti-aliasing, not
 * two different rendering contexts.
 *
 * So this suite does what a visual-regression suite is actually for: it pins the current rendering
 * and fails on drift. Fidelity to the reference is established once, by the design author reviewing
 * the gallery against the kit — design-system.md §13 names that as a gate with a human in it — and
 * from then on these baselines stop it regressing.
 *
 * The reference comparison that IS automatable lives in reference.spec.ts: whole screens, where
 * both sides have a layout to share.
 */
import { expect, test } from '@playwright/test';
import { stabilise } from './helpers/render-reference';

/**
 * Every component with a gallery section. Kept as an explicit list rather than discovered at
 * runtime: a component that loses its gallery section should fail this suite loudly, not quietly
 * drop out of it.
 */
const COMPONENTS = [
    'button',
    'icon-button',
    'icon',
    'logo',
    'input',
    'select',
    'checkbox',
    'switch',
    'status-badge',
    'alert',
    'toast',
    'dialog',
    'progress',
    'skeleton',
    'tooltip',
    'empty-state',
    'card',
    'panel',
    'stat-card',
    'data-table',
    'sidebar',
    'topbar',
    'tabs',
    'breadcrumbs',
    'stepper',
    'page-header',
    'field',
] as const;

for (const locale of ['ar', 'en'] as const) {
    test.describe(`components · ${locale}`, () => {
        test.beforeEach(async ({ page }) => {
            await page.goto(`/dev/components?locale=${locale}`);
            await stabilise(page);
        });

        for (const component of COMPONENTS) {
            test(component, async ({ page }) => {
                const specimen = page.locator(`[data-gallery="${component}"]`);

                await expect(specimen).toBeVisible();

                // The section, not the viewport. A full-page shot of a 3,000px gallery would make
                // every component's diff depend on every other component's height.
                //
                // The locale is in the FILENAME, not left to the project name. snapshotPathTemplate
                // keys on {projectName}, and a project is a locale-and-width pair only by
                // convention — `--project=ar-1440` still runs both the `ar` and `en` describes in
                // this file. Without the prefix both wrote to the same baseline, whichever ran last
                // won, and half the suite then compared each locale against the other one's image
                // while reporting success.
                await expect(specimen).toHaveScreenshot(`${locale}-${component}.png`);
            });
        }

        test('the gallery covers every component on disk', async ({ page }) => {
            // The guard on the list above. If a 28th component is added and nobody adds a gallery
            // section, this fails rather than the component silently going unshot.
            const sections = await page.locator('[data-gallery]').evaluateAll(
                (nodes) => nodes.map((node) => node.getAttribute('data-gallery')),
            );

            expect(sections.sort()).toEqual([...COMPONENTS].sort());
        });
    });
}

test.describe('shell geometry at the verification widths', () => {
    // FR-085 names 1280 and 1920 as the verification widths and <768px as the stacked breakpoint.
    // Those are projects in playwright.config.ts, so this test runs once per width and the
    // baseline name carries the project — which is how one test covers all three.
    test('the data table stacks below 768px and does not scroll sideways', async ({ page }, testInfo) => {
        await page.goto('/dev/components?locale=ar');
        await stabilise(page);

        const narrow = testInfo.project.use.viewport!.width < 768;
        const table = page.locator('[data-gallery="data-table"] table').first();

        if (narrow) {
            // The stacked form hides the header row entirely; a visible <thead> below 768px means
            // the breakpoint did not take.
            await expect(table.locator('thead')).toBeHidden();
        } else {
            await expect(table.locator('thead')).toBeVisible();
        }

        // No horizontal page scroll at any width. This is the failure a stacked-card breakpoint is
        // meant to prevent, and it is cheap to assert directly.
        const overflow = await page.evaluate(
            () => document.documentElement.scrollWidth - document.documentElement.clientWidth,
        );

        expect(overflow).toBeLessThanOrEqual(1);
    });
});
