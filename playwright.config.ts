import { defineConfig, devices } from '@playwright/test';

import { VISUAL_DATABASE } from './tests/Visual/global-setup';

/**
 * T009 — visual regression and accessibility.
 *
 * The matrix is locale x width, because the platform ships Arabic right-to-left and English
 * left-to-right as equal interfaces (constitution principle VI), and a layout that passes in one
 * direction routinely fails in the other. Running both is the only way the suite means anything.
 *
 * Widths come from the design system: 1280 and 1920 are the verification widths, 1440 is the
 * baseline capture width for component diffs, and 390 proves the stacked-card behaviour below
 * 768px (FR-085).
 *
 * The 1% threshold is not tolerance for drift. It absorbs anti-aliasing and font-hinting
 * differences between the React reference and the Blade implementation; anything larger is a real
 * difference and must be either fixed or recorded with its reason (design-system.md section 13).
 */
const WIDTHS = [1280, 1440, 1920, 390] as const;
const LOCALES = [
    { id: 'ar', locale: 'ar-SA' },
    { id: 'en', locale: 'en-US' },
] as const;

export default defineConfig({
    testDir: './tests/Visual',

    /*
     * Builds and seeds the visual database before anything runs, so the suite does not depend on
     * a file that looks like a build artifact and gets cleaned up. Deleting it mid-run once produced
     * sixty failures in components unrelated to the change under test.
     */
    globalSetup: './tests/Visual/global-setup.ts',
    outputDir: './test-results',
    // {platform} is load-bearing and was missing (F-07 from the Phase 1 review). Font rasterisation
    // differs between Windows and the Linux CI runner by more than the 1% threshold, so one shared
    // baseline means either the suite fails on every CI run or the threshold has to be widened far
    // enough to stop detecting anything. Separate baselines per platform is the only honest answer;
    // the Linux set is generated in CI with `--update-snapshots` and committed from there.
    snapshotPathTemplate: '{testDir}/baselines/{platform}/{projectName}/{testFilePath}/{arg}{ext}',

    // A visual diff is never flaky in a useful way: a retry that passes hides a real difference.
    retries: 0,
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    /*
     * Capped locally too, not just in CI. Eight projects against one `artisan serve` saturate it:
     * a full local run produced eight failures that all passed when re-run serially, and chasing
     * those is worse than taking a little longer. PHP_CLI_SERVER_WORKERS raises the server side;
     * this caps the client side.
     */
    workers: process.env.CI ? 2 : 4,

    reporter: process.env.CI
        ? [['html', { open: 'never' }], ['github']]
        : [['html', { open: 'never' }], ['list']],

    use: {
        baseURL: process.env.APP_URL ?? 'http://127.0.0.1:8000',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'off',
    },

    expect: {
        toHaveScreenshot: {
            // Two settings doing two different jobs, which the original pairing conflated.
            //
            // maxDiffPixelRatio is how MANY pixels may differ: 1%, which absorbs the anti-aliased
            // edges of Arabic glyph shaping without admitting a layout change.
            //
            // threshold is how DIFFERENT a single pixel may be before it counts, on pixelmatch's
            // YIQ scale. This was 0.2, which admits any colour delta below 1409 — and a brand-blue
            // shift of 16 steps per channel is 491, while the §6 accent-green correction itself is
            // 1335. The gate could not detect either. Planting a visible colour change and watching
            // the suite pass is how that was found.
            //
            // 0.05 admits deltas below 88. Five grey levels of text anti-aliasing is 13, so the
            // noise this is meant to absorb still passes, and both colour changes above now fail.
            maxDiffPixelRatio: 0.01,
            threshold: 0.05,
            animations: 'disabled',
            caret: 'hide',
            scale: 'css',
        },
    },

    projects: [
        ...LOCALES.flatMap(({ id, locale }) =>
        WIDTHS.map((width) => ({
            name: `${id}-${width}`,
            // First paint is measured on its own, below: a timing taken while another worker loads
            // the same single-process dev server measures the queue, not the page.
            testIgnore: /fcp\.spec\.ts/,
            use: {
                ...devices['Desktop Chrome'],
                locale,
                viewport: { width, height: width === 390 ? 844 : 900 },
                // Honour prefers-reduced-motion so the 120-280ms transitions cannot make a
                // screenshot race the animation (design-system.md section 10).
                contextOptions: { reducedMotion: 'reduce' },
                // The harness injects a style tag to freeze animations before each screenshot, which
                // the application's CSP (T277) rightly refuses. Screenshots and axe measure layout and
                // markup, not the policy; tests/Visual/csp.spec.ts turns this off and checks the
                // policy against every screen on its own.
                bypassCSP: true,
            },
        })),
        ),

        /*
         * SC-015's first-paint budget (fcp.spec.ts), after every other project has finished, in one
         * project, so nothing else is in flight on the server while a page is timed. The spec covers
         * both locales itself, in order on one worker.
         *
         * A dependent project is skipped when a dependency fails, so a screenshot failure would hide
         * the first-paint result. The gate therefore also runs it on its own:
         *     npx playwright test --project=first-paint --no-deps          (quickstart.md, Full gate)
         */
        {
            name: 'first-paint',
            testMatch: /fcp\.spec\.ts/,
            dependencies: LOCALES.flatMap(({ id }) => WIDTHS.map((width) => `${id}-${width}`)),
            use: {
                ...devices['Desktop Chrome'],
                viewport: { width: 1440, height: 900 },
            },
        },
    ],

    // Boot the application for the suite. Reuses a running server locally; always starts a fresh
    // one in CI so a stale build can never be measured.
    //
    // The env overrides matter. The visual suite renders pages, not infrastructure: it has no use
    // for Redis sessions or a queue, and depending on them means the suite fails for reasons that
    // have nothing to do with the design. Without these the gallery returns Laravel's error page,
    // and axe dutifully reports violations in Laravel's error page — which is how this was found.
    //
    // APP_ENV stays `local` because the gallery route is registered only in local (T100), and
    // SQLite because the suite needs a database it can always reach.
    webServer: {
        command: 'php artisan serve --host=127.0.0.1 --port=8000',
        url: 'http://127.0.0.1:8000/up',
        reuseExistingServer: !process.env.CI,
        timeout: 120_000,
        env: {
            // PHP's built-in server is single-threaded. With fullyParallel across eight projects it
            // serves one request at a time and the rest queue until they time out, which looks
            // exactly like a flaky test suite. This is in ServeCommand's own passthrough list.
            PHP_CLI_SERVER_WORKERS: '8',

            /*
             * Cheap hashing. This suite measures rendering, not hash strength, and the lockout test
             * signs in six times — at Argon2id's production cost (19 MiB, 2 iterations, ~32 ms each)
             * that test alone cannot fit a sensible budget. The strength that ships is asserted in
             * CreateUserTest against .env.example, where it belongs.
             */
            HASH_DRIVER: 'bcrypt',
            BCRYPT_ROUNDS: '4',

            APP_ENV: 'local',
            APP_DEBUG: 'true',
            SESSION_DRIVER: 'file',
            CACHE_STORE: 'file',
            QUEUE_CONNECTION: 'sync',
            DB_CONNECTION: process.env.CI ? 'mysql' : 'sqlite',
            /*
             * The same path globalSetup builds, imported rather than repeated — two spellings of
             * one file is how a suite ends up seeding one database and reading another.
             */
            DB_DATABASE: process.env.CI ? 'track_test' : VISUAL_DATABASE,
        },
    },
});
