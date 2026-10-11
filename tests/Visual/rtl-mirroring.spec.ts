/**
 * T266 — every screen mirrors: the sidebar on the inline-start edge, breadcrumb chevrons pointing the
 * reading way, leading button icons on the label's inline-start side, and the toast anchored to the
 * inline-end corner (FR-076, design-system.md §10 "RTL/LTR").
 *
 * Facts, not pixels. A screenshot shows a mirroring defect only if someone looks at the right corner
 * of the right screen; these assertions name the defect. Measured at 1440, where the sidebar is
 * docked — below 768px it becomes a drawer and is checked by the narrow-layout baselines instead.
 */
import { expect, test, type Page } from '@playwright/test';
import { stabilise } from './helpers/render-reference';
import { accountFor, adminFor, signIn } from './support/session';
import { SCREENS } from './support/screens';

const ONLY_WIDTH = 1440;

test.describe.configure({ timeout: 90_000 });

test.beforeEach(({}, testInfo) => {
    test.skip(testInfo.project.use.viewport?.width !== ONLY_WIDTH, 'Mirroring facts are measured at 1440.');
});

function projectLocale(project: string): 'ar' | 'en' {
    return project.startsWith('ar-') ? 'ar' : 'en';
}

async function open(page: Page, locale: 'ar' | 'en', screen: (typeof SCREENS)[number]) {
    if (screen.as === 'guest') {
        await page.goto(`${screen.path}?locale=${locale}`);
    } else {
        await signIn(page, locale, screen.as === 'admin' ? adminFor(locale) : accountFor('instructor', locale));
        await page.goto(screen.path);
    }

    await stabilise(page);
}

for (const screen of SCREENS) {
    test(`${screen.key} mirrors`, async ({ page }, testInfo) => {
        const locale = projectLocale(testInfo.project.name);
        const rtl = locale === 'ar';
        await open(page, locale, screen);

        const facts = await page.evaluate(() => {
            const width = document.documentElement.clientWidth;

            const sidebar = document.querySelector('.track-sidebar');
            const sidebarBox = sidebar?.getBoundingClientRect() ?? null;

            const chevrons = Array.from(document.querySelectorAll('.track-breadcrumbs__sep use'))
                .map((use) => use.getAttribute('href') ?? '')
                .map((href) => href.slice(href.lastIndexOf('#') + 1));

            // Buttons with a leading icon and a label: on which side of the label does the icon sit?
            // x-track-button renders `icon` first and `iconEnd` last, so a button whose first
            // child is an icon has a leading icon.
            const iconSides = Array.from(document.querySelectorAll('.track-btn'))
                .filter((button) => (button as HTMLElement).offsetParent !== null)
                .map((button) => {
                    const first = button.querySelector(':scope > :not(.track-btn__spinner)');
                    const label = button.querySelector(':scope > .track-btn__label');

                    if (! first?.classList.contains('track-btn__icon') || ! label || (label.textContent ?? '').trim() === '') {
                        return null;
                    }

                    return first.getBoundingClientRect().left > label.getBoundingClientRect().left ? 'right' : 'left';
                })
                .filter((side): side is 'left' | 'right' => side !== null);

            const toast = document.querySelector('#track-toasts');
            const toastStyle = toast ? getComputedStyle(toast) : null;

            return {
                width,
                sidebar: sidebarBox === null ? null : { left: sidebarBox.left, right: sidebarBox.right },
                chevrons,
                iconSides,
                toast: toastStyle === null ? null : { left: toastStyle.left, right: toastStyle.right },
            };
        });

        // The sidebar sits on the inline-start edge: right in Arabic, left in English.
        if (facts.sidebar !== null) {
            if (rtl) {
                expect(Math.round(facts.sidebar.right), 'sidebar on the right').toBe(facts.width);
            } else {
                expect(Math.round(facts.sidebar.left), 'sidebar on the left').toBe(0);
            }
        }

        // Breadcrumb chevrons point the way the trail reads.
        for (const chevron of facts.chevrons) {
            expect(chevron, 'breadcrumb chevron').toBe(rtl ? 'chevron-left' : 'chevron-right');
        }

        // A button's leading icon sits on the inline-start side of its label.
        for (const side of facts.iconSides) {
            expect(side, 'button icon side').toBe(rtl ? 'right' : 'left');
        }

        // The toast region sits in the bottom-end corner — bottom-left in Arabic, clear of the
        // sidebar (design-system.md §5 row 11).
        if (facts.toast !== null) {
            const anchored = rtl ? facts.toast.left : facts.toast.right;
            const free = rtl ? facts.toast.right : facts.toast.left;

            expect(anchored, 'toast anchored to the inline-end corner').not.toBe('auto');
            expect(parseFloat(anchored), 'toast anchored to the inline-end corner').toBeLessThan(parseFloat(free) || Infinity);
        }
    });
}
