/**
 * T103's companion — the one comparison against the React reference kit that is worth automating.
 *
 * components.spec.ts explains why per-component diffing against the kit is not: the kit ships no
 * per-component page, so a comparison would be between two different documents with two different
 * resets and the diff would be dominated by the harness.
 *
 * What IS comparable is structural: the kit and the Blade shell agree on geometry and on the
 * content that comes from the same fixture dataset. Those are facts, not pixels, and a fact that
 * changes is a real regression. design-system.md §13 keeps the pixel-level fidelity review as a
 * human gate; this covers the part a human reviewer would miss by eye.
 *
 * The kit is opened through render-reference.ts so its localStorage state is fixed — otherwise a
 * reference assertion depends on what the last person to open the kit clicked.
 */
import { expect, test } from '@playwright/test';
import { kitRendered, openReference, stabilise } from './helpers/render-reference';

/*
 * This file runs at one width and is not parallelised.
 *
 * Every kit test loads React, Babel and Lucide from unpkg and then has Babel compile fifteen .jsx
 * files fetched through /dev/reference. Running that across eight projects in parallel saturates
 * the server and the CDN, and the failures that come back are timeouts rather than findings — which
 * is how a suite earns a reputation for flakiness it does not deserve.
 *
 * Nothing here is width-dependent except the sidebar check, which asserts a computed inset rather
 * than a pixel position.
 */
const ONLY_WIDTH = 1440;

test.describe.configure({ mode: 'serial', timeout: 120_000 });

test.beforeEach(({}, testInfo) => {
    test.skip(
        testInfo.project.use.viewport?.width !== ONLY_WIDTH,
        'Reference checks are viewport-independent and expensive; they run at 1440 only.',
    );
});

/** From design-system.md §4. The numbers the shell is specified in. */
const GEOMETRY = {
    sidebarWidth: 240,
    topbarHeight: 64,
    contentPaddingTop: 28,
    contentPaddingBottom: 48,
};

test.describe('shell geometry matches the specification', () => {
    test('the tokens carry the §4 measurements', async ({ page }) => {
        await page.goto('/dev/components?locale=ar');
        await stabilise(page);

        const tokens = await page.evaluate(() => {
            const style = getComputedStyle(document.documentElement);

            return {
                sidebarWidth: style.getPropertyValue('--sidebar-width').trim(),
                topbarHeight: style.getPropertyValue('--topbar-height').trim(),
                controlMd: style.getPropertyValue('--control-h-md').trim(),
                radiusLg: style.getPropertyValue('--radius-lg').trim(),
                fontBody: style.getPropertyValue('--fs-body').trim(),
                statSize: style.getPropertyValue('--fs-stat').trim(),
            };
        });

        expect(tokens).toEqual({
            sidebarWidth: `${GEOMETRY.sidebarWidth}px`,
            topbarHeight: `${GEOMETRY.topbarHeight}px`,
            controlMd: '40px',
            radiusLg: '12px',
            fontBody: '14px',
            statSize: '26px',
        });
    });

    test('the sidebar sits on the inline-start edge in both directions', async ({ page }) => {
        for (const [locale, expected] of [['ar', 'right'], ['en', 'left']] as const) {
            await page.goto(`/dev/components?locale=${locale}`);
            await stabilise(page);

            const direction = await page.evaluate(() => document.documentElement.dir);

            expect(direction).toBe(locale === 'ar' ? 'rtl' : 'ltr');

            // Measured from the computed inset rather than from bounding boxes. The preview
            // container has a 1px border, so its bounding box is 2px wider than its padding box,
            // and comparing device-pixel-rounded edges was off by one — a test failing on a
            // rounding artifact teaches nobody anything.
            //
            // What we actually mean is that the sidebar is pinned to the inline-start edge, so the
            // computed physical inset on that side is 0 and the other side is auto.
            const insets = await page.locator('[data-gallery="sidebar"] .track-sidebar').evaluate(
                (node) => {
                    const style = getComputedStyle(node);

                    return { left: style.left, right: style.right, width: style.width };
                },
            );

            const pinned = expected === 'right' ? insets.right : insets.left;

            expect(pinned, `sidebar should hug the ${expected} edge in ${locale}`).toBe('0px');
            expect(insets.width).toBe(`${GEOMETRY.sidebarWidth}px`);
        }
    });
});

test.describe('the reference kit is reachable and deterministic', () => {
    test('the kit boots with fixed state', async ({ page }) => {
        await openReference(page, 'ar');

        // Skipped, not failed, when the kit does not mount. It pulls React, Babel and Lucide from
        // unpkg; an outage there is not a regression in this repository, and a build that a third
        // party can break is a build nobody trusts.
        test.skip(! await kitRendered(page), 'The reference kit did not mount — unpkg unreachable?');

        const root = await page.locator('#root, body').first().innerText();

        expect(root.length).toBeGreaterThan(0);
        expect(await page.evaluate(() => document.documentElement.dir)).toBe('rtl');
    });

    test('opening the kit twice produces the same DOM', async ({ page }) => {
        // This is the whole point of render-reference.ts. If two consecutive boots differ, the kit
        // is reading state we have not pinned, and every reference comparison built on it is noise.
        await openReference(page, 'ar');

        test.skip(! await kitRendered(page), 'The reference kit did not mount — unpkg unreachable?');

        const first = await page.locator('#root, body').first().innerHTML();

        await openReference(page, 'ar');
        const second = await page.locator('#root, body').first().innerHTML();

        expect(second).toBe(first);
    });
});

test.describe('the fixture dataset reaches both sides', () => {
    test('the kit and the gallery agree on the design system palette', async ({ page }) => {
        // Both read from design-system/tokens, one through a copy. A mismatch here means the copy
        // has drifted in a way TokenParityTest did not catch — which would mean the drift is in a
        // value the kit computes rather than declares.
        await page.goto('/dev/components?locale=ar');
        await stabilise(page);

        const production = await page.evaluate(() =>
            getComputedStyle(document.documentElement).getPropertyValue('--track-blue').trim().toUpperCase(),
        );

        await openReference(page, 'ar');

        test.skip(! await kitRendered(page), 'The reference kit did not mount — unpkg unreachable?');

        const reference = await page.evaluate(() =>
            getComputedStyle(document.documentElement).getPropertyValue('--track-blue').trim().toUpperCase(),
        );

        // An empty value is reported rather than silently passing: it would mean the kit stopped
        // loading the token sheet, which is the one thing both sides are supposed to share.
        expect(reference, 'the reference kit did not expose --track-blue').not.toBe('');
        expect(production).toBe(reference);
    });
});
