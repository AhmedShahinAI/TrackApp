/**
 * T102 — axe-core over the component gallery, in both locales, requiring ZERO WCAG 2.1 AA
 * violations.
 *
 * Zero, not "a short list of known issues". FR-085 and SC-014 make AA an acceptance gate, and a
 * baseline of accepted violations is how a gate becomes a formality — the list only ever grows,
 * because each addition is individually small.
 *
 * Both locales, because several of the failures this catches are direction-specific: a focus
 * outline clipped by an `overflow: hidden` that only overlaps in RTL, or a label whose `for` points
 * at an id that a mirrored layout duplicated.
 *
 * This runs against the gallery rather than a screen, because the gallery is the only surface that
 * renders every component in every state. A screen exercises the three states it happens to need.
 */
import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';
import { stabilise } from './helpers/render-reference';

/**
 * The gallery's own chrome — the `.gal` wrapper and its `h2` labels — is scaffolding, not design
 * system. Its headings are deliberately out of order relative to the specimens, which axe would
 * correctly flag as a heading-order violation of a page nobody ships.
 */
const EXCLUDE = ['.gal > section > h2'];

// An axe pass over a 27-component page is several seconds, and the per-specimen test below runs one
// per component. The default 30s is not enough for that, and raising it is honest: the test is slow
// because it is thorough, not because something is stuck.
test.describe.configure({ timeout: 180_000 });

/**
 * The per-specimen scan runs at one width only.
 *
 * Attributing a violation to the component that owns it does not depend on the viewport — a missing
 * label is missing at every width. Running 27 scans across all eight projects was 432 axe passes
 * for the same answer, and it blew the test timeout rather than finding anything. Responsive
 * behaviour is covered by the whole-page scan, which does run at every width.
 */
const PER_SPECIMEN_WIDTH = 1440;

for (const locale of ['ar', 'en'] as const) {
    test(`component gallery has no WCAG 2.1 AA violations · ${locale}`, async ({ page }) => {
        await page.goto(`/dev/components?locale=${locale}`);
        await stabilise(page);

        const results = await new AxeBuilder({ page })
            .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
            .exclude(EXCLUDE)
            .analyze();

        // Printed before the assertion so a CI log shows what failed, not just that something did.
        if (results.violations.length > 0) {
            for (const violation of results.violations) {
                const targets = violation.nodes.map((node) => node.target.join(' ')).join('\n      ');

                console.error(
                    `\n  [${violation.impact}] ${violation.id}: ${violation.help}\n` +
                    `    ${violation.helpUrl}\n` +
                    `    nodes:\n      ${targets}`,
                );
            }
        }

        expect(results.violations).toEqual([]);
    });

    test(`every component specimen is individually clean · ${locale}`, async ({ page }, testInfo) => {
        test.skip(
            testInfo.project.use.viewport?.width !== PER_SPECIMEN_WIDTH,
            'Per-specimen attribution is viewport-independent; it runs at 1440 only.',
        );

        // Running axe over the whole page can mask a violation inside one component: a duplicate id
        // between two specimens, for instance, is reported once against the page. Scoping to each
        // specimen in turn attributes the failure to the component that owns it, which is the
        // difference between a useful failure and a puzzle.
        await page.goto(`/dev/components?locale=${locale}`);
        await stabilise(page);

        const sections = await page.locator('[data-gallery]').evaluateAll(
            (nodes) => nodes.map((node) => node.getAttribute('data-gallery')!),
        );

        expect(sections.length).toBeGreaterThan(0);

        const failures: string[] = [];

        for (const section of sections) {
            const results = await new AxeBuilder({ page })
                .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
                .include(`[data-gallery="${section}"]`)
                .exclude(EXCLUDE)
                .analyze();

            for (const violation of results.violations) {
                failures.push(`${section}: [${violation.impact}] ${violation.id} — ${violation.help}`);
            }
        }

        expect(failures).toEqual([]);
    });
}

test('contrast is checked, not assumed', async ({ page }) => {
    // The §6 corrections exist because seven pairings failed contrast. This asserts that axe's
    // colour-contrast rule actually ran rather than being quietly inapplicable — a suite that
    // reports zero violations because it checked nothing is the failure mode that matters here.
    await page.goto('/dev/components?locale=ar');
    await stabilise(page);

    const results = await new AxeBuilder({ page })
        .withRules(['color-contrast'])
        .exclude(EXCLUDE)
        .analyze();

    expect(results.violations).toEqual([]);

    // `passes` and `incomplete` both mean the rule executed. An empty `passes` with no violations
    // would mean axe found nothing to measure.
    const checked = results.passes.length + results.incomplete.length;

    expect(checked).toBeGreaterThan(0);
});
