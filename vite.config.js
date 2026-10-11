import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import rtlcss from 'rtlcss';

/**
 * T013 / T063 — two entry points, one per writing direction.
 *
 * Bootstrap 5.3 was authored for separate RTL and LTR builds and the design system follows it
 * (constitution: Technology constraints). Two stylesheets are simpler and faster than one build
 * flipping direction at runtime, and they keep third-party components mirroring consistently.
 *
 * Tailwind, which the Laravel skeleton ships, is removed: the constitution mandates Bootstrap, and
 * running both would mean two competing utility layers over one token set.
 *
 * The skeleton's Bunny-hosted font is removed too. Cairo and Inter are self-hosted under
 * public/fonts (T058, T059) because the strict Content-Security-Policy and the PDPL constraint on
 * third-party origins rule out fetching fonts per page load.
 *
 * ── How the RTL build actually works ─────────────────────────────────────────────────────────────
 *
 * tasks.md T063 says app-rtl.scss should import "the matching Bootstrap build (`bootstrap.rtl` for
 * Arabic)". **There is no such Sass entry.** Bootstrap 5.3 ships exactly one Sass entry,
 * `bootstrap.scss`; `bootstrap.rtl.css` in its dist folder is that entry compiled and then run
 * through RTLCSS. Importing `bootstrap/scss/bootstrap.rtl` fails with "Can't find stylesheet to
 * import", which is how this was found.
 *
 * Using the shipped `bootstrap.rtl.css` is not an option either: our Sass variables have to be
 * applied at compile time, and a pre-compiled file has Bootstrap's own palette baked in.
 *
 * So this build does what Bootstrap does — compiles from source, then mirrors the result with
 * RTLCSS, applied only to the Arabic entry. The plugin below keys on the file PostCSS was handed,
 * so the LTR sheet passes through untouched. It runs identically in dev and in build, because a
 * mirroring step that only happened at build time would make the dev server a different product.
 *
 * Our own CSS barely needs it: _shell.scss and _components.scss use logical properties throughout.
 * Bootstrap's spacing and float utilities are physical (`.ms-3`, `.text-start` resolve to
 * left/right), and those are what the mirroring is for.
 */
const mirrorArabicEntry = {
    postcssPlugin: 'track-rtl',
    prepare(result) {
        const from = result.opts.from ?? '';

        // Only the Arabic entry. Vite hands PostCSS the originating file path, which for a Sass
        // entry is the .scss file, so this matches before any import is inlined.
        if (!from.replace(/\\/g, '/').includes('resources/css/app-rtl')) {
            return {};
        }

        return rtlcss({
            // Keep `background-position` alone. The brand gradient and the sidebar gradient are
            // directional by design, and mirroring them inverts the brand.
            processUrls: false,
            useCalc: true,
        });
    },
};

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app-rtl.scss',
                'resources/css/app-ltr.scss',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    css: {
        postcss: {
            plugins: [mirrorArabicEntry],
        },
        preprocessorOptions: {
            scss: {
                loadPaths: ['node_modules', 'resources/css'],
                // Bootstrap 5.3 still emits legacy-API deprecations under Dart Sass 1.8x.
                silenceDeprecations: ['import', 'global-builtin', 'color-functions'],
            },
        },
    },
    /*
     * T358 (review) — the support form's proof-of-work worker (resources/js/pow/worker.js) is a module
     * worker, started with `new Worker(new URL('./worker.js', import.meta.url), { type: 'module' })`.
     * Vite emits it as its own hashed file beside the bundle: same origin, so the CSP's
     * `script-src 'self'` covers it with no exemption, and every deploy ships a fresh name. 'es' to
     * match `type: 'module'`. Never `?worker&inline`, which would become a blob: URL the CSP refuses.
     */
    worker: {
        format: 'es',
    },
    server: {
        watch: {
            ignored: [
                '**/storage/framework/views/**',
                '**/design-system/**',
                '**/design-system-v1/**',
            ],
        },
    },
});
