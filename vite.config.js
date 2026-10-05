import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

/**
 * T013 — two entry points, one per writing direction.
 *
 * Bootstrap 5.3 ships separate RTL and LTR builds, and the design system was authored against them
 * (constitution: Technology constraints). Two stylesheets are simpler and faster than one build
 * flipping direction at runtime, and they keep third-party components mirroring consistently.
 *
 * Tailwind, which the Laravel skeleton ships, is removed: the constitution mandates Bootstrap, and
 * running both would mean two competing utility layers over one token set.
 *
 * The skeleton's Bunny-hosted font is removed too. Cairo and Inter are self-hosted under
 * public/fonts (T058, T059) because the strict Content-Security-Policy and the PDPL constraint on
 * third-party origins rule out fetching fonts per page load.
 */
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
        preprocessorOptions: {
            scss: {
                // Bootstrap 5.3 still emits legacy-API deprecations under Dart Sass 1.8x.
                silenceDeprecations: ['import', 'global-builtin', 'color-functions'],
            },
        },
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
