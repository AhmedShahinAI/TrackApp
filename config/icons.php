<?php

declare(strict_types=1);

/*
 * T067 — the single icon name map.
 *
 * design-system.md §7: the brand board shows a bespoke outline set and no files were supplied, so
 * Lucide 0.460.0 is substituted and the substitution stays flagged (ui-conflicts C-07). If the team
 * later supplies its own set, exactly two things change — the sprite built by
 * scripts/build-icons.mjs, and the `concepts` map below. No Blade template names a Lucide icon
 * directly for a navigation concept, which is what makes that true.
 */

return [
    /*
     * The sprite the x-track-icon component references. Built from the pinned lucide-static
     * package, never fetched from a CDN: the strict Content-Security-Policy admits no third-party
     * origin, and PDPL rules out a per-page-view call to someone else's server.
     */
    'sprite' => '/icons/lucide.svg',

    'default_size' => 20,

    /*
     * Navigation and domain concepts to icon names, from the §7 table.
     *
     * `programs` is `library`, not `graduation-cap`: v2 of the design system corrected this when it
     * reassigned `graduation-cap` to الطلبة. Both entries are here so the conflict cannot recur.
     */
    'concepts' => [
        'home' => 'house',
        'dashboard' => 'layout-dashboard',
        'initiatives' => 'lightbulb',
        'branches' => 'building-2',
        'programs' => 'library',
        'courses' => 'book-open',
        'sections' => 'users',
        'users' => 'user-cog',
        'terms' => 'calendar-range',
        'imports' => 'file-up',
        'audit' => 'shield-check',
        'reports' => 'chart-column',
        'settings' => 'settings',
        'students' => 'graduation-cap',
        'instructors' => 'user',
        'support' => 'headphones',
    ],

    /*
     * The icon each alert and badge tone carries. An alert is "always icon + text" per the
     * component contract, so the tone cannot be communicated by colour alone (WCAG 1.4.1).
     */
    'tones' => [
        'info' => 'info',
        'success' => 'circle-check',
        'warning' => 'triangle-alert',
        'danger' => 'circle-alert',
        'error' => 'circle-alert',
    ],
];
