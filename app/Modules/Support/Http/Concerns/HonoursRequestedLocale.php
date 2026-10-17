<?php

declare(strict_types=1);

namespace App\Modules\Support\Http\Concerns;

use Illuminate\Http\Request;

/**
 * `?lang=` on a public support page (FR-161): a visitor has no account to store a preference on, so the
 * language is chosen in the URL and remembered on the guest session, as the language switch does. One
 * copy for every public page (Phase 22 review), so the two cannot drift into accepting different values.
 */
trait HonoursRequestedLocale
{
    private function honourRequestedLocale(Request $request): void
    {
        $lang = $request->query('lang');

        if (is_string($lang) && in_array($lang, ['ar', 'en'], true)) {
            $request->session()->put('locale', $lang);
            app()->setLocale($lang);
        }
    }
}
