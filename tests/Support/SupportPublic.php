<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Amendment 2, US9 — the public support form's world (Phase 20)
|--------------------------------------------------------------------------
|
| A real submission needs a solved challenge and a form stamp old enough to pass the elapsed-time
| guard. Both are produced here through the application's own services, so a test submits exactly what
| the browser would — and the puzzle is solved by the test's own loop, not the code under test.
|
*/

use App\Models\AcademicTerm;
use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Models\Setting;
use App\Models\Student;
use App\Modules\Settings\Settings;
use App\Modules\Support\Services\Challenge\ChallengeService;
use App\Modules\Support\Services\Challenge\ProofOfWork;
use App\Modules\Support\Services\FormStamp;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Carbon;

/**
 * Settings seeded, the puzzle made cheap enough to solve in a test, and the limits wide enough that a
 * test not about them never meets them.
 */
function supportWorld(): void
{
    (new SettingSeeder)->run();

    supportSetting(Settings::SUPPORT_CHALLENGE_DIFFICULTY, 4);
    supportSetting(Settings::SUPPORT_THROTTLE_IP, ['max_attempts' => 1000, 'decay_minutes' => 60]);
    supportSetting(Settings::SUPPORT_THROTTLE_IDENTIFIER, ['max_attempts' => 1000, 'decay_minutes' => 60]);
    supportSetting(Settings::PUBLIC_THROTTLE, ['max_attempts' => 1000, 'decay_minutes' => 1]);
    supportSetting(Settings::SUPPORT_STATUS_THROTTLE_IP, ['max_attempts' => 1000, 'decay_minutes' => 60]);
    supportSetting(Settings::SUPPORT_STATUS_THROTTLE_REFERENCE, ['max_attempts' => 1000, 'decay_minutes' => 60]);
}

function supportSetting(string $key, mixed $value): void
{
    Setting::query()->where('key', $key)->update(['value' => json_encode($value)]);
    Settings::forget();
}

/**
 * A student holding an active enrollment, in an active section, in the current term.
 */
function enrolledStudent(string $universityId = '441234567'): Student
{
    $term = AcademicTerm::query()->where('is_current', true)->first() ?? AcademicTerm::factory()->current()->create();
    $student = Student::factory()->create(['university_id' => $universityId]);

    SectionEnrollment::factory()->create([
        'student_id' => $student->id,
        'section_id' => Section::factory()->create(['term_id' => $term->id])->id,
    ]);

    return $student;
}

/**
 * @return array{challenge: string, challenge_solution: string}
 */
function solvedChallenge(): array
{
    $issued = app(ChallengeService::class)->issue();

    for ($nonce = 0; ; $nonce++) {
        if (ProofOfWork::leadingZeroBits(hash('sha256', $issued->challenge.':'.$nonce, true)) >= $issued->difficulty) {
            return ['challenge' => $issued->challenge, 'challenge_solution' => (string) $nonce];
        }
    }
}

/**
 * A form stamp issued long enough ago to pass the elapsed-time guard.
 */
function agedFormStamp(int $secondsAgo = 60): string
{
    $now = Carbon::now();
    Carbon::setTestNow($now->copy()->subSeconds($secondsAgo));

    try {
        return app(FormStamp::class)->issue();
    } finally {
        Carbon::setTestNow($now);
    }
}

/**
 * Everything a browser would POST to /support, solved and aged, with overrides.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function supportSubmission(array $overrides = []): array
{
    return array_merge([
        'university_id' => '441234567',
        'category' => 'incorrect_data',
        'description' => 'My section is missing from my schedule.',
        'contact' => '',
        '_honeypot' => '',
        '_issued_at' => agedFormStamp(),
    ], solvedChallenge(), $overrides);
}
