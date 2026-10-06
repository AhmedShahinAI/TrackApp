<?php

declare(strict_types=1);

namespace App\Modules\Shared\Http\Middleware;

use App\Modules\Settings\Settings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * T094 — resolve the active locale for every web request.
 *
 * Precedence: the signed-in user's stored preference (users.locale, FR-074), then the
 * `default_locale` setting, then the framework default. Deliberately NOT the Accept-Language
 * header: this platform's default is Arabic by institutional decision, and a browser configured in
 * English would otherwise flip the interface for a user who never asked for it.
 *
 * The locale also decides `dir` in the layout, so getting this wrong does not merely translate the
 * page incorrectly — it lays it out backwards.
 */
final class SetLocale
{
    /**
     * The only locales this platform has. An unknown value falls back rather than being trusted:
     * app()->setLocale() accepts anything, and an unknown locale renders every key as its own name.
     *
     * @var list<string>
     */
    private const SUPPORTED = ['ar', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale($this->resolve($request));

        return $next($request);
    }

    private function resolve(Request $request): string
    {
        $user = $request->user();

        if ($user !== null && in_array($user->locale, self::SUPPORTED, true)) {
            // A signed-in person's stored preference is the authority, and the session is NOT
            // consulted for them: on a shared machine, one person switching language must not
            // silently change it for whoever signs in next.
            return $user->locale;
        }

        if ($user === null) {
            // A guest's choice, from the sign-in screen's switch. There is no record to write to
            // until they have an account.
            $chosen = $request->session()->get('locale');

            if (is_string($chosen) && in_array($chosen, self::SUPPORTED, true)) {
                return $chosen;
            }
        }

        /*
         * `?locale=` is honoured in local only, and nowhere else. The visual suite has to shoot both
         * directions of the sign-in screen without signing anyone in, and a query parameter that
         * changed rendering in production would be a way to make any screenshot or shared link
         * render in a language the recipient did not choose.
         */
        if (app()->isLocal()) {
            $query = $request->query('locale');

            if (is_string($query) && in_array($query, self::SUPPORTED, true)) {
                return $query;
            }
        }

        return $this->configuredDefault();
    }

    /**
     * The seeded `default_locale` setting, with the framework default behind it.
     *
     * Wrapped, because this runs on every request including the ones that run before the settings
     * table exists — a migration during deployment, or a fresh checkout whose database is empty.
     * A platform that cannot serve its own error page because it cannot read a setting is worse
     * than one that falls back to Arabic.
     */
    private function configuredDefault(): string
    {
        try {
            $locale = Settings::string(Settings::DEFAULT_LOCALE);

            if (in_array($locale, self::SUPPORTED, true)) {
                return $locale;
            }
        } catch (Throwable) {
            // Settings unavailable — fall through to the config default.
        }

        $fallback = (string) config('app.locale', 'ar');

        return in_array($fallback, self::SUPPORTED, true) ? $fallback : 'ar';
    }
}
