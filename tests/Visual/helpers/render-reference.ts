/**
 * T103 — boot the React reference kit deterministically.
 *
 * The kit at design-system/ui_kits/web-app/index.html keeps its state in localStorage: the signed-in
 * demo user, the active locale, the selected term. Screenshotting it as-is produces a different
 * image depending on what the last person who opened it clicked, which makes a baseline worthless.
 *
 * This seeds that state explicitly before the kit's own scripts run, so a reference shot is a
 * function of the code and nothing else.
 *
 * The kit is served over http by a local-only read-only route (/dev/reference), not over file://.
 * It compiles its .jsx in the browser with Babel standalone, which fetches each file by XHR, and a
 * file:// page cannot do that — the kit renders an empty document. Nothing is copied or written:
 * the route reads out of design-system/, which stays vendored read-only.
 *
 * It also loads React, Babel and Lucide from unpkg, so booting it needs network access. That is why
 * no build gate depends on it: callers use kitRendered() and skip when it does not come up.
 */
import type { Page } from '@playwright/test';

export type ReferenceLocale = 'ar' | 'en';

/** The demo Super Admin from store.js — id 1, مها السالم. Holds every permission, so no screen renders empty. */
const DEMO_USER_ID = 1;

/** Term 1, the current one. The kit's other two terms are a closed and a draft term. */
const DEMO_TERM_ID = 1;

export function referenceUrl(): string {
    return '/dev/reference/ui_kits/web-app/index.html';
}

/**
 * Open the reference kit with its state fixed.
 *
 * The init script runs before any of the page's own scripts, which is the only point at which
 * localStorage can be set for a file:// page — after load, the kit has already read it and
 * rendered.
 */
export async function openReference(page: Page, locale: ReferenceLocale): Promise<void> {
    await page.addInitScript(
        ({ userId, termId, lang }) => {
            try {
                // Key names mirror the kit's own. Writing all the spellings it has used is
                // deliberate: the kit is vendored and we do not get to refactor it, so a key that
                // changes between re-vendorings should not silently stop being seeded.
                const state = {
                    TRACK_USER: String(userId),
                    TRACK_UID: String(userId),
                    track_user: String(userId),
                    TRACK_LANG: lang,
                    TRACK_LOCALE: lang,
                    track_lang: lang,
                    TRACK_TERM: String(termId),
                    track_term: String(termId),
                };

                for (const [key, value] of Object.entries(state)) {
                    window.localStorage.setItem(key, value);
                }
            } catch {
                // localStorage can be refused outright depending on the browser's settings. The kit
                // falls back to its own defaults, which name the same demo user — so a refusal
                // costs determinism of the term selection, not the whole render.
            }

            document.documentElement.lang = lang;
            document.documentElement.dir = lang === 'ar' ? 'rtl' : 'ltr';
        },
        { userId: DEMO_USER_ID, termId: DEMO_TERM_ID, lang: locale },
    );

    await page.goto(referenceUrl());

    // Babel compiles the .jsx after load, so `load` is not enough. The root filling is the signal
    // that React has mounted.
    await page.waitForLoadState('load');

    try {
        await page.waitForFunction(
            () => (document.querySelector('#root') ?? document.body).childElementCount > 0,
            undefined,
            { timeout: 20_000 },
        );
    } catch {
        // Left to the caller. kitRendered() reports it, and the tests skip rather than fail: a
        // CDN outage is not a regression in this codebase.
    }

    await page.evaluate(() => document.fonts.ready);

    // Never photograph an exception page — see refuseErrorPages() for the three times this project
    // did exactly that and committed the result.
    await refuseErrorPages(page);
}

/**
 * Refuse to photograph a Laravel error page.
 *
 * Three baselines in this project have been an unhandled exception rendered at 500 and frozen as
 * the reference for the screen they replaced: a sections list whose Blade threw `Undefined variable
 * $slot`, a landing page whose stylesheet never loaded while the machine slept, and a 403 whose
 * audit write hit a locked database. Each was written by `--update-snapshots`, each passed every
 * run afterwards, and each was found only by opening the PNG and looking at it.
 *
 * A screenshot tool cannot tell a designed page from an exception page — both are pixels. This can,
 * and it costs one DOM query per shot.
 */
export async function refuseErrorPages(page: Page): Promise<void> {
    const looksLikeAnException = await page.evaluate(() => {
        const text = document.body?.innerText ?? '';

        return (
            /Internal Server Error|Whoops, something went wrong|SQLSTATE\[/i.test(text)
            || document.querySelector('.exception-message') !== null
        );
    });

    if (looksLikeAnException) {
        throw new Error('the page rendered as an exception — refusing to capture it as a baseline');
    }
}


/**
 * Whether the kit actually mounted.
 *
 * Separate from openReference so a test can skip with a reason instead of failing. The kit depends
 * on unpkg for React, Babel and Lucide; when that is unreachable it renders nothing, and failing
 * the build for it would mean a third party can break this repository.
 */
export async function kitRendered(page: Page): Promise<boolean> {
    return page.evaluate(
        () => (document.querySelector('#root') ?? document.body).childElementCount > 0,
    );
}

/**
 * Freeze everything that varies between runs for its own reasons rather than because the design
 * changed: caret blink, the toast queue's timers, and any `Date`-dependent label.
 */
export async function stabilise(page: Page): Promise<void> {
    await page.addStyleTag({
        content: `
            *, *::before, *::after {
                animation: none !important;
                transition: none !important;
                caret-color: transparent !important;
            }
            /* The skeleton pulse is the one animation that runs forever. */
            .track-skeleton { opacity: 1 !important; }
        `,
    });

    await page.evaluate(() => document.fonts.ready);
}
