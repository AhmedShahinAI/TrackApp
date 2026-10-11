/**
 * T265 — axe-core over every in-scope screen and the states a person opens on it, in both locales,
 * requiring ZERO WCAG 2.1 AA violations (SC-014, FR-085).
 *
 * axe.spec.ts covers every component in every state on the gallery; this covers the screens, where
 * components meet each other and the data — a table whose headers a screen forgot, a dialog nested in
 * a landmark, a heading level skipped because two partials each assumed they were the first.
 *
 * ## Not only the page as it loads
 *
 * Dialogs sit in the DOM under display:none, which axe skips, so a scan of the loaded page proves
 * nothing about them. Per screen, this scans the page, then opens each of its dialogs and scans that;
 * then, once per screen that has a dialog form, sends the form back refused and scans the error
 * state the server returns. Once per locale and width it also scans a toast of each tone and — at
 * 390 — the open navigation drawer. No rule is disabled.
 *
 * ## Two widths
 *
 * 1440, and 390, where tables become stacked cards and the sidebar a drawer. The width-dependent
 * markup in this codebase is CSS only (media queries in _shell.scss and _components.scss; no
 * width-conditional Blade and no JS breakpoint), so 1280 and 1920 serve 1440's DOM with a different
 * line length — a contrast or name violation at one of them would exist at 1440 too.
 */
import AxeBuilder from '@axe-core/playwright';
import { expect, test, type Page } from '@playwright/test';
import { stabilise } from './helpers/render-reference';
import { accountFor, adminFor, signIn } from './support/session';
import { SCREENS } from './support/screens';

const WIDTHS = [1440, 390];

test.describe.configure({ timeout: 180_000 });

test.beforeEach(({}, testInfo) => {
    test.skip(
        ! WIDTHS.includes(testInfo.project.use.viewport?.width ?? 0),
        'Scanned at 1440 and 390; the other widths render the same markup.',
    );
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

/** Scan, report each violation with its nodes, and return the rule ids. */
async function scan(page: Page, label: string, include?: string): Promise<string[]> {
    let builder = new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']);

    if (include) {
        builder = builder.include(include);
    }

    const results = await builder.analyze();

    for (const violation of results.violations) {
        const targets = violation.nodes.map((node) => node.target.join(' ')).join('\n      ');

        console.error(
            `\n  [${label}] [${violation.impact}] ${violation.id}: ${violation.help}\n` +
            `    ${violation.helpUrl}\n    nodes:\n      ${targets}`,
        );
    }

    return results.violations.map((v) => `${label}: ${v.id}`);
}

/** The ids of the overlay dialogs on the page (the inline wizard confirmation is part of the page). */
async function dialogIds(page: Page): Promise<string[]> {
    return page.locator('.track-dialog-scrim[id]').evaluateAll((nodes) => nodes.map((n) => n.id));
}

async function openDialog(page: Page, id: string) {
    await page.evaluate((dialog) => window.dispatchEvent(new CustomEvent('track-dialog-open', { detail: { id: dialog } })), id);
    await expect(page.locator(`#${id} [role="dialog"]`)).toBeVisible();
}

async function closeDialog(page: Page, id: string) {
    await page.evaluate((dialog) => window.dispatchEvent(new CustomEvent('track-dialog-close', { detail: { id: dialog } })), id);
    await expect(page.locator(`#${id} [role="dialog"]`)).toBeHidden();
}

for (const screen of SCREENS) {
    test(`${screen.key} and its dialogs have no WCAG 2.1 AA violations`, async ({ page }, testInfo) => {
        const locale = projectLocale(testInfo.project.name);
        await open(page, locale, screen);

        const violations = await scan(page, `${screen.key} · ${locale}`);
        const dialogs = await dialogIds(page);

        // One dialog per id pattern: a terms page renders a status dialog per term and transition,
        // all from one partial, and scanning forty copies finds nothing the first did not.
        const distinct = [...new Map(dialogs.map((id) => [id.replace(/\d+/g, '#'), id])).values()];

        for (const id of distinct) {
            await openDialog(page, id);
            violations.push(...await scan(page, `${screen.key} · ${locale} · #${id}`, `#${id}`));
            await closeDialog(page, id);
        }

        // The refused state: the first dialog form with a required field, submitted empty past the
        // browser's own checks, comes back from the server open with its errors (x-track-dialog).
        const refusable = await page.evaluate(() => {
            const form = Array.from(document.querySelectorAll<HTMLFormElement>('form'))
                .find((f) => f.querySelector('input[name="_dialog"]') && f.querySelector('[required]') && f.getAttribute('action'));

            return form ? (form.querySelector<HTMLInputElement>('input[name="_dialog"]')?.value ?? null) : null;
        });

        if (refusable !== null) {
            await openDialog(page, refusable);
            await Promise.all([
                page.waitForNavigation({ waitUntil: 'load' }),
                page.evaluate((dialog) => {
                    const form = document.querySelector<HTMLFormElement>(`form:has(input[name="_dialog"][value="${dialog}"])`);

                    if (! form) {
                        return;
                    }

                    form.noValidate = true;
                    form.querySelectorAll<HTMLInputElement | HTMLTextAreaElement>('input[required], textarea[required]')
                        .forEach((field) => { field.value = ''; });
                    form.submit();
                }, refusable),
            ]);
            await stabilise(page);
            await expect(page.locator(`#${refusable} [role="dialog"]`)).toBeVisible();
            violations.push(...await scan(page, `${screen.key} · ${locale} · #${refusable} refused`, `#${refusable}`));
        }

        expect(violations).toEqual([]);
    });
}

test('the toasts and the navigation drawer have no WCAG 2.1 AA violations', async ({ page }, testInfo) => {
    const locale = projectLocale(testInfo.project.name);
    await open(page, locale, SCREENS.find((s) => s.key === 'home')!);

    const violations: string[] = [];

    for (const tone of ['success', 'error', 'info']) {
        await page.evaluate((t) => window.dispatchEvent(new CustomEvent('track-toast', {
            detail: { tone: t, message: document.documentElement.lang === 'ar' ? 'رسالة تجريبية' : 'A test message', retry: t === 'error' ? () => {} : undefined },
        })), tone);
    }

    await expect(page.locator('#track-toasts .track-toast')).toHaveCount(3);
    violations.push(...await scan(page, `toasts · ${locale}`, '#track-toasts'));

    if ((testInfo.project.use.viewport?.width ?? 0) < 768) {
        await page.locator('[aria-controls="track-sidebar"]').first().click();
        await expect(page.locator('#track-sidebar')).toBeInViewport();
        await stabilise(page);
        violations.push(...await scan(page, `drawer · ${locale}`));
    }

    expect(violations).toEqual([]);
});

/**
 * Text on the sidebar's gradient, which axe cannot judge: it cannot pick one background colour from a
 * gradient, so it files every such pair as "needs review" rather than as a pass or a violation. The
 * sign-out button sat at about 1.3:1 there, unreported. Each visible text colour in the sidebar is
 * measured against both ends of --surface-sidebar.
 */
test("the sidebar's text meets AA against both ends of its gradient", async ({ page }, testInfo) => {
    test.skip((testInfo.project.use.viewport?.width ?? 0) < 768, 'Docked sidebar only; the drawer is scanned above.');

    const locale = projectLocale(testInfo.project.name);
    await open(page, locale, SCREENS.find((s) => s.key === 'home')!);

    const failures = await page.evaluate(() => {
        const luminance = ([r, g, b]: number[]) => {
            const c = [r, g, b].map((v) => {
                const s = v / 255;

                return s <= 0.03928 ? s / 12.92 : ((s + 0.055) / 1.055) ** 2.4;
            });

            return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
        };
        const parse = (css: string) => (css.match(/[\d.]+/g) ?? []).map(Number);
        const ratio = (a: number[], b: number[]) => {
            const [hi, lo] = [luminance(a), luminance(b)].sort((x, y) => y - x);

            return (hi + 0.05) / (lo + 0.05);
        };

        const sidebar = document.querySelector('.track-sidebar') as HTMLElement;
        const stops = (getComputedStyle(sidebar).backgroundImage.match(/rgba?\([^)]+\)/g) ?? []).map(parse);
        const out: string[] = [];

        sidebar.querySelectorAll<HTMLElement>('a, button, span, p').forEach((el) => {
            const text = Array.from(el.childNodes).some((n) => n.nodeType === Node.TEXT_NODE && (n.textContent ?? '').trim() !== '');

            if (! text || el.offsetParent === null) {
                return;
            }

            // An element on its own filled background (the active item) is axe's to judge, not ours.
            if (el.closest('[aria-current="page"]')) {
                return;
            }

            const [r, g, b, a = 1] = parse(getComputedStyle(el).color);

            for (const stop of stops) {
                // Composite a translucent colour over the gradient stop it sits on.
                const fg = [r, g, b].map((v, i) => v * a + stop[i] * (1 - a));
                const contrast = ratio(fg, stop);

                if (contrast < 4.5) {
                    out.push(`${(el.textContent ?? '').trim()} — ${contrast.toFixed(2)}:1 on rgb(${stop.slice(0, 3).join(',')})`);
                }
            }
        });

        return out;
    });

    expect(failures).toEqual([]);
});
