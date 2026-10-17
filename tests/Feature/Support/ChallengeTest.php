<?php

declare(strict_types=1);

use App\Models\SupportRequest;
use App\Modules\Support\Services\FormStamp;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Sleep;

/*
|--------------------------------------------------------------------------
| T384 — no submission without a person behind it, and none when that cannot be checked (FR-128/129)
|--------------------------------------------------------------------------
|
| Each guard refuses through the real POST /support, with the same message under the same key — a
| script is not told which guard caught it — and nothing is recorded. The last case is the one that
| matters most: with the challenge store down, the form refuses rather than lets everything through.
|
*/

beforeEach(function (): void {
    Sleep::fake();
    supportWorld();
    enrolledStudent('441234567');
});

/**
 * @param  Illuminate\Testing\TestResponse<Symfony\Component\HttpFoundation\Response>  $response
 */
function assertRefusedByGuard(Illuminate\Testing\TestResponse $response, int $requestsBefore = 0): void
{
    $response->assertRedirect(route('support.create'))
        ->assertSessionHasErrors(['challenge' => __('support.failures.challenge')]);

    expect(SupportRequest::query()->count())->toBe($requestsBefore);
}

test('a submission is refused when the challenge is', function (string $case, Closure $payload): void {
    assertRefusedByGuard($this->post('/support', $payload()));
})->with([
    'no solution' => ['no solution', fn (): array => supportSubmission(['challenge_solution' => null])],
    'no challenge' => ['no challenge', fn (): array => supportSubmission(['challenge' => null])],
    'a wrong solution' => ['wrong', fn (): array => supportSubmission(['challenge_solution' => '99999999999'])],
    'a forged challenge' => ['forged', fn (): array => supportSubmission(['challenge' => str_repeat('A', 48)])],
    'the honeypot completed' => ['honeypot', fn (): array => supportSubmission(['_honeypot' => 'http://spam.example'])],
    'no form stamp' => ['stamp', fn (): array => supportSubmission(['_issued_at' => null])],
    'a forged form stamp' => ['forged stamp', fn (): array => supportSubmission(['_issued_at' => '1000000000.'.str_repeat('A', 43)])],
]);

test('a replayed solution is refused the second time', function (): void {
    $payload = supportSubmission();

    $this->post('/support', $payload)->assertRedirect(route('support.submitted'));

    // The first submission's request is the only one.
    assertRefusedByGuard($this->post('/support', array_merge($payload, ['_issued_at' => agedFormStamp()])), 1);
});

test('an expired solution is refused', function (): void {
    $payload = supportSubmission();

    $this->travel(11)->minutes();

    assertRefusedByGuard($this->post('/support', $payload));
});

test('a form sent back faster than a person could read it is refused', function (): void {
    assertRefusedByGuard($this->post('/support', supportSubmission(['_issued_at' => app(FormStamp::class)->issue()])));
});

test('with the challenge store down, the form fails closed (FR-129)', function (): void {
    $payload = supportSubmission();

    $down = Mockery::mock(Repository::class);
    $down->shouldReceive('add', 'get', 'forget')->andThrow(new RuntimeException('Connection refused'));
    // Settings read through the same facade; let them reach the database so this tests the challenge
    // store, not a configuration outage.
    $down->shouldReceive('rememberForever')->andReturnUsing(fn (string $key, Closure $value): mixed => $value());
    Cache::partialMock()->shouldReceive('store')->andReturn($down);

    // No challenge can be issued — the page's widget gets a 503 and keeps submit disabled …
    $this->getJson('/support/challenge')
        ->assertStatus(503)
        ->assertExactJson(['message' => __('support.failures.challenge_unavailable')])
        ->assertHeader('Cache-Control', 'no-store, private');

    // … and an answer to one issued before the outage is not taken on trust.
    assertRefusedByGuard($this->post('/support', $payload));
});

test('a challenge is issued with its difficulty and expiry, and never cached', function (): void {
    $response = $this->getJson('/support/challenge')->assertOk();

    expect($response->json())->toHaveKeys(['challenge', 'difficulty', 'expires_in'])
        ->and($response->json('expires_in'))->toBe(600)
        ->and((string) $response->headers->get('Cache-Control'))->toContain('no-store');
});

test('a refused submission keeps what the student typed, so a solved retry is one click', function (): void {
    $this->post('/support', supportSubmission(['challenge_solution' => null, 'description' => 'kept text']));

    expect(session()->getOldInput('description'))->toBe('kept text');
});

test('a harvested form stamp expires with the session it was served in', function (): void {
    $payload = supportSubmission();

    $this->travel(config('session.lifetime') + 1)->minutes();

    // A fresh challenge, so only the stamp is old.
    assertRefusedByGuard($this->post('/support', array_merge($payload, solvedChallenge())));
});

test('an unusable minimum-age setting keeps the documented three seconds, and says so', function (mixed $setting): void {
    supportSetting(App\Modules\Settings\Settings::SUPPORT_MIN_ELAPSED_SECONDS, $setting);
    $log = Illuminate\Support\Facades\Log::spy();

    // Issued one second ago: inside the three-second default, so refused — not waved through by a
    // minimum the bad row turned into zero.
    assertRefusedByGuard($this->post('/support', supportSubmission(['_issued_at' => agedFormStamp(1)])));

    $log->shouldHaveReceived('warning')->withArgs(fn (string $message): bool => str_contains($message, 'minimum form age'));
})->with([-1, 0, '', 'three', 100000]);

test('two submissions racing with one solution: exactly one passes the challenge', function (): void {
    $service = app(App\Modules\Support\Services\Challenge\ChallengeService::class);
    $solved = solvedChallenge();

    // The race, frozen: both requests read the record before either removes it. Only the store's
    // removal — which reports success to exactly one caller (Redis DEL) — tells them apart.
    $real = Cache::store();
    $stale = Mockery::mock($real)->makePartial();
    $stale->shouldReceive('get')->andReturn(['difficulty' => App\Modules\Settings\Settings::int(App\Modules\Settings\Settings::SUPPORT_CHALLENGE_DIFFICULTY)]);
    Cache::partialMock()->shouldReceive('store')->andReturn($stale);

    $results = [
        $service->verify($solved['challenge'], $solved['challenge_solution']),
        $service->verify($solved['challenge'], $solved['challenge_solution']),
    ];

    expect(array_values(array_filter($results)))->toHaveCount(1);
});

test('the deployment\'s challenge store is Redis, whose removal is atomic', function (): void {
    // ChallengeService relies on `forget` reporting success to exactly one caller. Redis DEL does;
    // the database store does not. Pinned where a deployment reads it (Phase 20 review).
    expect((string) file_get_contents(base_path('.env.example')))->toContain("\nCACHE_STORE=redis")
        ->and((string) file_get_contents(base_path('docs/runbook-deploy.md')))->toContain('CACHE_STORE=redis');
});
