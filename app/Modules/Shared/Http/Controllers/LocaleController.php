<?php

declare(strict_types=1);

namespace App\Modules\Shared\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The AR/EN switch (FR-074).
 *
 * POST, not GET: for a signed-in person this writes `users.locale`, and a GET that changes stored
 * state is a GET a prefetcher or a crawler can trigger.
 *
 * Two storage places, deliberately:
 *
 *   • A signed-in person's choice goes on their record, so it follows them to another device.
 *   • A guest's choice goes on the session, because there is no record to write to — and the
 *     sign-in screen needs a working switch. Without this, the login screen's language button was
 *     pointing at a route that did not exist, which the visual suite found.
 *
 * The session value is deliberately NOT consulted for a signed-in user: their stored preference is
 * the authority, or switching on a shared machine would silently change the next person's language.
 */
final class LocaleController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(['ar', 'en'])],
        ]);

        $locale = (string) $validated['locale'];
        $user = $request->user();

        if ($user !== null) {
            $user->forceFill(['locale' => $locale])->save();
        } else {
            $request->session()->put('locale', $locale);
        }

        // T279 — back to the page the switch was on, but only on this host. `back()` follows the
        // Referer header, which is caller input; the CSRF check already stops a cross-site post, and
        // this stops the redirect from ever leaving the application if something else gets through.
        $previous = url()->previous();
        $sameHost = parse_url($previous, PHP_URL_HOST) === $request->getHost();

        return redirect()->to($sameHost ? $previous : ($user !== null ? route('home') : route('login')));
    }
}
