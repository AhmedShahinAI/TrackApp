<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use App\Modules\Settings\Settings;
use Illuminate\Database\Seeder;

/**
 * T367 — the student support channel's configuration (Amendment 2, data-model.md Group 7 "Settings").
 *
 * Run from SettingSeeder rather than beside it in DatabaseSeeder. Everything that seeds settings —
 * the deploy seed, the test helpers, the screen sweeps — goes through SettingSeeder, and a database
 * holding the platform's settings without these would be a broken deployment the moment the support
 * form is opened (Settings throws on an unknown key, by design).
 *
 * firstOrCreate, for SettingSeeder's reason: the seeder owns the defaults, not the live values, and
 * re-running it on deploy must not undo a Super Admin's edit.
 *
 * ## Twelve keys, not the seven T367 lists
 *
 * `support_status_throttle_reference` (Phase 22 review) caps failed lookups against one reference from
 * any number of addresses; an address limit alone let a botnet walk a cohort's University IDs.
 *
 * `support_channel_enabled` (Phase 25, T463) puts the link to /support on the sign-in screen, off by
 * default.
 *
 * `support_min_elapsed_seconds` (Phase 20) is the contract's "configured minimum age" of a form: the
 * contract names the check and no key held its value.
 *
 * `public_throttle` is the `public` route group's flood floor, moved here from a literal in
 * AppServiceProvider by the Phase 19 review (constitution IV).
 *
 * `support_challenge_difficulty` is added. T370 says the challenge's difficulty comes "from
 * settings" and data-model.md lists no key for it; a literal in ChallengeService would be the
 * hard-coded configurable value constitution IV forbids. Default 16 leading zero bits — about 65,000
 * hashes on average, around a second on a phone — enough to make a scripted flood cost real work
 * without making a student wait (research.md R-08; recorded in tasks.md Phase 19).
 *
 * ## Rate limits are one row each
 *
 * `{max_attempts, decay_minutes}` together, read through Settings::limit(). The attempt count and
 * its window are both settings (R-09), and two separate rows could be edited out of step — "5" with
 * a window someone changed to a day is a different control from the one anyone decided on.
 */
class SupportSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // FR-120. Machine values are what a request stores; the labels are what each locale shows.
            Settings::SUPPORT_CATEGORIES => [
                ['value' => 'incorrect_data', 'ar' => 'بيانات غير صحيحة', 'en' => 'Incorrect data'],
                ['value' => 'sign_in_problem', 'ar' => 'مشكلة في تسجيل الدخول', 'en' => 'Sign-in problem'],
                ['value' => 'section_problem', 'ar' => 'مشكلة في الشعبة', 'en' => 'Section problem'],
                ['value' => 'general_enquiry', 'ar' => 'استفسار عام', 'en' => 'General enquiry'],
                ['value' => 'other', 'ar' => 'أخرى', 'en' => 'Other'],
            ],

            // FR-121.
            Settings::SUPPORT_DESCRIPTION_MAX => 2000,

            // FR-126, FR-127, FR-131. Status retrieval is tighter: it is the guessing surface.
            //
            // Phase 22 review: the lookup limits count FAILED lookups only (a successful one is refunded),
            // so they can be tight without locking out students who share an address — 4 an hour per
            // address, below submission's 5, where data-model.md first had 10, which was looser. And a
            // second counter per reference: 5 failed guesses an hour against one reference, from any
            // number of addresses.
            Settings::SUPPORT_THROTTLE_IP => ['max_attempts' => 5, 'decay_minutes' => 60],
            Settings::SUPPORT_THROTTLE_IDENTIFIER => ['max_attempts' => 3, 'decay_minutes' => 60],
            Settings::SUPPORT_STATUS_THROTTLE_IP => ['max_attempts' => 4, 'decay_minutes' => 60],
            Settings::SUPPORT_STATUS_THROTTLE_REFERENCE => ['max_attempts' => 5, 'decay_minutes' => 60],

            // R-10. Above the slowest verification path, so every response leaves at the same moment.
            Settings::SUPPORT_MIN_RESPONSE_MS => 400,

            // FR-159, R-12. Counted from creation, not closure.
            Settings::SUPPORT_RETENTION_MONTHS => 24,

            // R-08, T370. See the class docblock.
            Settings::SUPPORT_CHALLENGE_DIFFICULTY => 16,

            // Phase 20, contracts/support-public.md `_issued_at`: a form submitted sooner than this
            // after it was served was not read and typed by a person.
            Settings::SUPPORT_MIN_ELAPSED_SECONDS => 3,

            // T463, FR-114. The sign-in screen's link to /support. Off by default: the channel is opened to
            // students deliberately, once the people who answer it are ready, not by the deploy that ships it.
            Settings::SUPPORT_CHANNEL_ENABLED => false,

            // The flood floor under every `public`-group route (T355). Per IP, per minute: well above
            // what a student filling one form needs, well below what a page-minting script wants.
            Settings::PUBLIC_THROTTLE => ['max_attempts' => 60, 'decay_minutes' => 1],
        ];

        foreach ($settings as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }

        Settings::forget();
    }
}
