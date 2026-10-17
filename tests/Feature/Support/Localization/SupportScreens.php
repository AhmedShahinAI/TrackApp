<?php

declare(strict_types=1);

use App\Models\SupportRequest;
use App\Models\SupportResponse;
use App\Modules\Settings\Settings;
use Illuminate\Support\Sleep;

/*
|--------------------------------------------------------------------------
| Phase 24 — the support channel's eight screens, rendered in one locale
|--------------------------------------------------------------------------
|
| Each screen is reached the way a person reaches it — the form by a GET, the confirmation by
| submitting, the refusal by a failed verification, the limit page by running out — so a screen that
| only breaks when it is reached for real breaks here too. All data is English-only (names, branch,
| description), so an Arabic character on an English page can only be the platform's own text.
|
*/

/**
 * @return array<string, string> screen name => HTML
 */
function supportScreensIn(string $locale): array
{
    Sleep::fake();
    supportWorld();

    $html = [];

    // ── Public, signed out: the visitor's language is chosen in the URL and kept on the session ─────
    test()->flushSession();
    $html['form'] = (string) test()->get('/support?lang='.$locale)->assertOk()->getContent();
    $html['status-form'] = (string) test()->get('/support/status?lang='.$locale)->assertOk()->getContent();

    $student = enrolledStudent('441234567');

    // A second branch, so the screens that list a student's branches print a separator to check.
    App\Models\SectionEnrollment::factory()->create([
        'student_id' => $student->id,
        'section_id' => App\Models\Section::factory()->create([
            'term_id' => App\Models\AcademicTerm::query()->where('is_current', true)->value('id'),
        ])->id,
    ]);
    test()->post('/support', supportSubmission())->assertRedirect(route('support.submitted'));
    $html['submitted'] = (string) test()->get('/support/submitted')->assertOk()->getContent();

    $html['refused'] = (string) test()->post('/support', supportSubmission(['university_id' => '449999990']))->assertStatus(422)->getContent();

    $request = SupportRequest::query()->withoutGlobalScopes()->where('student_id', $student->id)->sole();
    $request->forceFill(['created_at' => now()->subDays(3)])->saveQuietly();
    $reply = new SupportResponse(['body' => 'We have corrected your section.']);
    $reply->forceFill(['support_request_id' => $request->id, 'user_id' => aUser('SUPER_ADMIN')->id])->save();

    $html['status-result'] = (string) test()->post('/support/status', ['reference' => $request->reference, 'university_id' => '441234567'])->assertOk()->getContent();

    supportSetting(Settings::SUPPORT_STATUS_THROTTLE_IP, ['max_attempts' => 1, 'decay_minutes' => 60]);
    test()->withServerVariables(['REMOTE_ADDR' => '198.51.100.250'])->post('/support/status', ['reference' => 'K7Q2M9XVT3A', 'university_id' => '441000001']);
    $html['limit-reached'] = (string) test()->withServerVariables(['REMOTE_ADDR' => '198.51.100.250'])
        ->post('/support/status', ['reference' => 'K7Q2M9XVT3A', 'university_id' => '441000001'])
        ->assertStatus(429)->getContent();

    // ── Staff, signed in with this language as their stored preference ─────────────────────────────
    $admin = aUser('SUPER_ADMIN', ['locale' => $locale]);
    $request->forceFill(['status' => 'in_progress', 'assigned_to' => $admin->id])->saveQuietly();

    $html['queue'] = (string) test()->actingAs($admin)->get('/support-requests')->assertOk()->getContent();
    $html['detail'] = (string) test()->actingAs($admin)->get('/support-requests/'.$request->reference)->assertOk()->getContent();

    return $html;
}
