/**
 * T267 (browser half) — first contentful paint under 2 seconds on every screen (SC-015).
 *
 * PageLoadTest bounds the server's share; this measures what SC-015 names, in a browser, on one
 * fixed profile so the number means the same thing on every run: cold cache, 4× CPU slowdown, and a
 * 4G-class link (40 ms round trip, 9 Mbps down). A screen is loaded once per locale and its
 * `first-contentful-paint` entry read from the Performance API.
 *
 * Measured at 1440 only — width changes layout, not what has to arrive before the first paint.
 */
import { expect, test, type Page } from '@playwright/test';
import { accountFor, adminFor, signIn } from './support/session';
import { SCREENS } from './support/screens';

const BUDGET_MS = 2000;

// In order on one worker (`default` overrides fullyParallel for this file, and unlike `serial` does
// not skip the rest after one slow page), in the `first-paint` project that runs after every other
// one (playwright.config.ts): one page timed at a time, with nothing else loading the server.
test.describe.configure({ mode: 'default', timeout: 90_000 });

async function throttle(page: Page): Promise<void> {
    const cdp = await page.context().newCDPSession(page);

    await cdp.send('Network.enable');
    await cdp.send('Network.setCacheDisabled', { cacheDisabled: true });
    await cdp.send('Network.emulateNetworkConditions', {
        offline: false,
        latency: 40,
        downloadThroughput: (9 * 1024 * 1024) / 8,
        uploadThroughput: (1.5 * 1024 * 1024) / 8,
    });
    await cdp.send('Emulation.setCPUThrottlingRate', { rate: 4 });
}

for (const locale of ['ar', 'en'] as const) {
for (const screen of SCREENS) {
    test(`${screen.key} paints within ${BUDGET_MS} ms · ${locale}`, async ({ page }, testInfo) => {
        const path = screen.as === 'guest' ? `${screen.path}?locale=${locale}` : screen.path;

        if (screen.as !== 'guest') {
            await signIn(page, locale, screen.as === 'admin' ? adminFor(locale) : accountFor('instructor', locale));
        }

        await throttle(page);
        await page.goto(path, { waitUntil: 'load' });

        const fcp = await page.evaluate(() => {
            const entry = performance.getEntriesByName('first-contentful-paint')[0];

            return entry ? entry.startTime : null;
        });

        testInfo.annotations.push({ type: 'fcp', description: `${screen.key} · ${locale}: ${fcp?.toFixed(0)} ms` });

        expect(fcp, 'a first-contentful-paint entry').not.toBeNull();
        expect(fcp as number).toBeLessThan(BUDGET_MS);
    });
}
}
