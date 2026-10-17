<?php

declare(strict_types=1);

use App\Modules\Settings\Settings;
use App\Modules\Support\Services\SupportLimits;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Sleep;

/*
|--------------------------------------------------------------------------
| T382 — one identifier from many addresses is refused at support_throttle_identifier (FR-127)
|--------------------------------------------------------------------------
|
| Independently of the address limit: every request here comes from a fresh address, so an IP limit
| alone — or one combined limiter — never fires, and this test fails. That is the point (R-09).
|
| Counted only once a challenge is solved (Phase 19 review), so an unsolved request naming a student
| does not spend their limit; the last test holds that through the real route.
|
*/

beforeEach(function (): void {
    Sleep::fake();
    supportWorld();
    supportSetting(Settings::SUPPORT_THROTTLE_IDENTIFIER, ['max_attempts' => 3, 'decay_minutes' => 60]);
    supportSetting(Settings::SUPPORT_THROTTLE_IP, ['max_attempts' => 5, 'decay_minutes' => 60]);
});

test('the fourth solved submission for one identifier is refused, from a fresh address each time', function (): void {
    foreach (range(1, 3) as $i) {
        $this->withServerVariables(['REMOTE_ADDR' => "192.0.2.$i"])
            ->post('/support', supportSubmission(['university_id' => '441999999']))
            ->assertStatus(422);
    }

    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.4'])
        ->post('/support', supportSubmission(['university_id' => '441999999']))
        ->assertStatus(429);
});

test('Arabic-Indic digits are the same identifier, and count against the same limit', function (): void {
    foreach (range(1, 3) as $i) {
        $this->withServerVariables(['REMOTE_ADDR' => "192.0.2.1$i"])->post('/support', supportSubmission(['university_id' => '441888888']));
    }

    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.20'])
        ->post('/support', supportSubmission(['university_id' => '٤٤١٨٨٨٨٨٨']))
        ->assertStatus(429);
});

test('requests that never solve a challenge do not spend a student\'s limit', function (): void {
    enrolledStudent('441777777');

    foreach (range(1, 10) as $i) {
        $this->withServerVariables(['REMOTE_ADDR' => "192.0.2.3$i"])
            ->post('/support', supportSubmission(['university_id' => '441777777', 'challenge_solution' => 'x']));
    }

    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.50'])
        ->post('/support', supportSubmission(['university_id' => '441777777']))
        ->assertRedirect(route('support.submitted'));
});

test('a student correcting ordinary mistakes does not spend their limit', function (): void {
    enrolledStudent('441666666');

    // Three solved attempts, each refused by a field rule: no category, an empty description, a
    // malformed identifier. None reaches the count (Phase 20 review).
    foreach ([['category' => ''], ['description' => ''], ['university_id' => '44166']] as $i => $mistake) {
        $this->withServerVariables(['REMOTE_ADDR' => "192.0.2.6$i"])
            ->post('/support', supportSubmission(array_merge(['university_id' => '441666666'], $mistake)))
            ->assertSessionHasErrors();
    }

    // The fourth, correct, is accepted — and the limit is still all there.
    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.70'])
        ->post('/support', supportSubmission(['university_id' => '441666666']))
        ->assertRedirect(route('support.submitted'));

    expect(RateLimiter::attempts((string) app(SupportLimits::class)->identifierKey('441666666')))->toBe(1)
        ->and(RateLimiter::attempts((string) app(SupportLimits::class)->identifierKey('44166')))->toBe(0);
});
