<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use App\Modules\Settings\Settings;
use Illuminate\Database\Seeder;

/**
 * T047 + T303 - every value that would otherwise be a literal in application code (constitution IV).
 *
 * The two lists at the bottom are why `sections.degree` and `sections.activity` are plain string
 * columns rather than database enums: adding a degree is a row here, not an ALTER TABLE on a live
 * sections table (FR-108, FR-109).
 *
 * firstOrCreate on the key, not updateOrCreate: the seeder owns the defaults, not the live values.
 * DatabaseSeeder is the production-safe seed and may run on every deploy, and re-asserting a default
 * would silently undo a Super Admin's edit - drop `master` from `section_degrees`, say, and every
 * section already stored with that degree then fails InSetting on its next edit. A changed default
 * here reaches a fresh database; an existing one moves through the settings screen or a migration.
 */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // FR-043, FR-044. Nine digits. Kept here rather than in a validation rule so a change to
            // the institution's identifier format is a settings edit, not a deployment.
            Settings::UNIVERSITY_ID_PATTERN => '^\d{9}$',

            Settings::IMPORT_MAX_FILE_MB => 20,
            Settings::IMPORT_MAX_ROWS => 50000,
            Settings::ATTACHMENT_MAX_MB => 10,
            Settings::DEFAULT_LOCALE => 'ar',
            Settings::SESSION_TIMEOUT_MINUTES => 60,

            Settings::SECTION_DEGREES => [
                ['value' => 'bachelor', 'ar' => 'البكالوريوس', 'en' => 'Bachelor'],
                ['value' => 'intermediate_diploma', 'ar' => 'الدبلوم المتوسط', 'en' => 'Intermediate Diploma'],
            ],

            Settings::SECTION_ACTIVITIES => [
                ['value' => 'lecture', 'ar' => 'محاضرة', 'en' => 'Lecture'],
            ],
        ];

        foreach ($settings as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }

        // Amendment 2's keys, kept in their own file (T367) and seeded wherever these are.
        (new SupportSettingSeeder)->run();

        Settings::forget();
    }
}
