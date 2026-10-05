import { defineConfig, devices } from '@playwright/test';

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
    outputDir: './test-results',
    snapshotPathTemplate: '{testDir}/baselines/{projectName}/{testFilePath}/{arg}{ext}',

    // A visual diff is never flaky in a useful way: a retry that passes hides a real difference.
    retries: 0,
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    workers: process.env.CI ? 2 : undefined,

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
            maxDiffPixelRatio: 0.01,
            // Per-pixel tolerance for sub-pixel text rendering. Without it, Arabic shaping
            // differences of one grey level would fail every screenshot.
            threshold: 0.2,
            animations: 'disabled',
            caret: 'hide',
            scale: 'css',
        },
    },

    projects: LOCALES.flatMap(({ id, locale }) =>
        WIDTHS.map((width) => ({
            name: `${id}-${width}`,
            use: {
                ...devices['Desktop Chrome'],
                locale,
                viewport: { width, height: width === 390 ? 844 : 900 },
                // Honour prefers-reduced-motion so the 120-280ms transitions cannot make a
                // screenshot race the animation (design-system.md section 10).
                contextOptions: { reducedMotion: 'reduce' },
            },
        })),
    ),

    // Boot the application for the suite. Reuses a running server locally; always starts a fresh
    // one in CI so a stale build can never be measured.
    webServer: {
        command: 'php artisan serve --host=127.0.0.1 --port=8000',
        url: 'http://127.0.0.1:8000/up',
        reuseExistingServer: !process.env.CI,
        timeout: 120_000,
    },
});
