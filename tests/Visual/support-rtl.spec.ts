/**
 * T446 — every support screen mirrors: the page direction, breadcrumb chevrons pointing the reading
 * way, leading button icons on the label's inline-start side, and the toast region anchored to the
 * inline-end corner — the checks rtl-mirroring.spec.ts makes for the platform's own screens.
 *
 * The public pages have no sidebar and no breadcrumbs (FR-163); for them the direction, the icon side
 * and the reference staying left-to-right inside Arabic are what mirroring means.
 */
import { expect, test } from '@playwright/test';
import { openSupportScreen, SUPPORT_SCREENS } from './support-screens';

test.beforeEach(({}, testInfo) => {
    test.skip(testInfo.project.use.viewport?.width !== 1440, 'Mirroring is checked at 1440.');
});

function localeOf(project: string): 'ar' | 'en' {
    return project.startsWith('en') ? 'en' : 'ar';
}

for (const screen of SUPPORT_SCREENS) {
    test(`support · ${screen} mirrors`, async ({ page }, testInfo) => {
        const locale = localeOf(testInfo.project.name);
        const rtl = locale === 'ar';

        await openSupportScreen(page, locale, screen);

        const facts = await page.evaluate(() => {
            const chevrons = Array.from(document.querySelectorAll('.track-breadcrumbs__sep use'))
                .map((use) => (use.getAttribute('href') ?? '').split('#').pop());

            const iconSides = Array.from(document.querySelectorAll('.track-btn'))
                .filter((button) => button.querySelector('.track-btn__icon') && button.querySelector('.track-btn__label'))
                .map((button) => {
                    const icon = button.querySelector('.track-btn__icon') as Element;
                    const label = button.querySelector('.track-btn__label') as Element;

                    return icon.getBoundingClientRect().left > label.getBoundingClientRect().left ? 'right' : 'left';
                });

            const reference = document.querySelector('.track-public__reference, .track-code');
            const toast = document.querySelector('#track-toasts');
            const toastStyle = toast ? getComputedStyle(toast) : null;

            return {
                dir: document.documentElement.getAttribute('dir'),
                chevrons,
                iconSides,
                referenceDir: reference ? getComputedStyle(reference).direction : null,
                toast: toastStyle === null ? null : { left: toastStyle.left, right: toastStyle.right },
            };
        });

        expect(facts.dir).toBe(rtl ? 'rtl' : 'ltr');

        for (const chevron of facts.chevrons) {
            expect(chevron, 'breadcrumb chevron').toBe(rtl ? 'chevron-left' : 'chevron-right');
        }

        for (const side of facts.iconSides) {
            expect(side, 'button icon side').toBe(rtl ? 'right' : 'left');
        }

        // A reference or an identifier reads left to right inside Arabic, or its groups swap.
        if (facts.referenceDir !== null) {
            expect(facts.referenceDir, 'reference direction').toBe('ltr');
        }

        if (facts.toast !== null) {
            const anchored = rtl ? facts.toast.left : facts.toast.right;

            expect(anchored, 'toast anchored to the inline-end corner').not.toBe('auto');
        }
    });
}
