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
 * ## Eight keys, not the seven T367 lists
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
            Settings::SUPPORT_THROTTLE_IP => ['max_attempts' => 5, 'decay_minutes' => 60],
            Settings::SUPPORT_THROTTLE_IDENTIFIER => ['max_attempts' => 3, 'decay_minutes' => 60],
            Settings::SUPPORT_STATUS_THROTTLE_IP => ['max_attempts' => 10, 'decay_minutes' => 60],

            // R-10. Above the slowest verification path, so every response leaves at the same moment.
            Settings::SUPPORT_MIN_RESPONSE_MS => 400,

            // FR-159, R-12. Counted from creation, not closure.
            Settings::SUPPORT_RETENTION_MONTHS => 24,

            // R-08, T370. See the class docblock.
            Settings::SUPPORT_CHALLENGE_DIFFICULTY => 16,
        ];

        foreach ($settings as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }

        Settings::forget();
    }
}
