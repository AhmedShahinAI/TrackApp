<?php

declare(strict_types=1);

namespace App\Modules\Shared\Http\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * One write endpoint, two callers: the API client and the browser form.
 *
 * ## The gap this closes
 *
 * Every Phase 7 dialog is a plain HTML form posting to its `api.v1.*` route. That part is sound —
 * the /api/v1 group runs on the `web` stack with the session and CSRF (bootstrap/app.php), so the
 * token, the cookie and `old()` all work. What did not work was the *response*: the controllers
 * returned `response()->json(...)` unconditionally, so submitting a dialog navigated the browser to
 * a raw JSON blob. The screen never came back, the flash never appeared, and every
 * `$errors->first(...)` binding in the dialogs was unreachable code.
 *
 * Validation already behaved correctly, because `ValidationException` redirects back with the bag
 * for a non-JSON request. Only the success path, and the business refusals, had no browser half.
 *
 * ## Why `expectsJson()` and not a separate route
 *
 * A second set of web routes would mean two URLs, two names, two authorization tests and two places
 * to forget something. The content negotiation the client already performs is enough: an API caller
 * sends `Accept: application/json` and a form post does not.
 */
trait RespondsToBoth
{
    /**
     * @param  array<string, mixed>  $payload  the JSON body for an API caller
     * @param  array<string, mixed>  $flash  session keys for the browser, e.g. warnings
     */
    protected function respond(
        Request $request,
        array $payload,
        int $status = 200,
        array $flash = [],
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json($payload, $status);
        }

        /*
         * back(), so the dialog's own page re-renders with the new data. A redirect to a canonical
         * index would lose the section or student the person was working inside, which for the
         * roster dialogs is the whole context of what they just did.
         */
        return back()->with($flash + ['status' => __('common.messages.saved')]);
    }
}
