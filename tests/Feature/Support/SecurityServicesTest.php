<?php

declare(strict_types=1);

use App\Models\AcademicTerm;
use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Models\Setting;
use App\Models\Student;
use App\Models\SupportRequest;
use App\Modules\Settings\Settings;
use App\Modules\Support\Http\Middleware\ConstantResponseTime;
use App\Modules\Support\Http\Middleware\SupportSubmissionThrottle;
use App\Modules\Support\Http\SupportLimitReached;
use App\Modules\Support\Services\Challenge\ChallengeService;
use App\Modules\Support\Services\Challenge\ChallengeUnavailable;
use App\Modules\Support\Services\Challenge\ProofOfWork;
use App\Modules\Support\Services\ReferenceGenerator;
use App\Modules\Support\Services\SupportLimits;
use App\Modules\Support\Services\VerifyStudentIdentity;
use Database\Seeders\SettingSeeder;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Sleep;

/*
|--------------------------------------------------------------------------
| Phase 19 — the four security services, the settings and the policy (T366–T373)
|--------------------------------------------------------------------------
|
| The checkpoint's second half: "all four security services exist and fail closed". Each service is
| driven directly or through a probe route here; the end-to-end behaviour through the real support
| routes is Phase 20's (T378–T384), which cannot be written until those routes exist.
|
*/

beforeEach(function (): void {
    (new SettingSeeder)->run();
});

function setSetting(string $key, mixed $value): void
{
    Setting::query()->where('key', $key)->update(['value' => json_encode($value)]);
    Settings::forget();
}

/** The smallest nonce for a challenge, by brute force — the test's own solver, not the app's. */
function solveChallenge(string $challenge, int $difficulty): string
{
    for ($nonce = 0; ; $nonce++) {
        if (ProofOfWork::leadingZeroBits(hash('sha256', $challenge.':'.$nonce, true)) >= $difficulty) {
            return (string) $nonce;
        }
    }
}

// ── Settings and permissions (T366, T367) ─────────────────────────────────────────────────────────

test('the support settings are seeded and readable', function (): void {
    expect(Settings::values(Settings::SUPPORT_CATEGORIES))
        ->toBe(['incorrect_data', 'sign_in_problem', 'section_problem', 'general_enquiry', 'other'])
        ->and(Settings::int(Settings::SUPPORT_DESCRIPTION_MAX))->toBe(2000)
        ->and(Settings::limit(Settings::SUPPORT_THROTTLE_IP))->toBe(['max_attempts' => 5, 'decay_minutes' => 60])
        ->and(Settings::limit(Settings::SUPPORT_THROTTLE_IDENTIFIER))->toBe(['max_attempts' => 3, 'decay_minutes' => 60])
        // Phase 22 review: 4 failed lookups an hour per address (was 10, looser than submission), and
        // its own per-reference counter.
        ->and(Settings::limit(Settings::SUPPORT_STATUS_THROTTLE_IP))->toBe(['max_attempts' => 4, 'decay_minutes' => 60])
        ->and(Settings::limit(Settings::SUPPORT_STATUS_THROTTLE_REFERENCE))->toBe(['max_attempts' => 5, 'decay_minutes' => 60])
        ->and(Settings::int(Settings::SUPPORT_MIN_RESPONSE_MS))->toBe(400)
        ->and(Settings::int(Settings::SUPPORT_RETENTION_MONTHS))->toBe(24)
        ->and(Settings::int(Settings::SUPPORT_CHALLENGE_DIFFICULTY))->toBe(16)
        ->and(Settings::limit(Settings::PUBLIC_THROTTLE))->toBe(['max_attempts' => 60, 'decay_minutes' => 1]);
});

test('a malformed rate-limit setting throws rather than read as zero or unlimited', function (mixed $value): void {
    setSetting(Settings::SUPPORT_THROTTLE_IP, $value);

    expect(fn () => Settings::limit(Settings::SUPPORT_THROTTLE_IP))->toThrow(RuntimeException::class);
})->with([[5], [['max_attempts' => 0, 'decay_minutes' => 60]], [['max_attempts' => 5]], [['max_attempts' => '5', 'decay_minutes' => 60]]]);

test('the support permissions are granted as data-model.md tabulates', function (): void {
    $matrix = [
        'SUPER_ADMIN' => ['support.view' => true, 'support.respond' => true, 'support.assign' => true, 'support.close' => true],
        'BRANCH_COORDINATOR' => ['support.view' => true, 'support.respond' => true, 'support.assign' => false, 'support.close' => false],
        'INSTRUCTOR' => ['support.view' => false, 'support.respond' => false, 'support.assign' => false, 'support.close' => false],
    ];

    foreach ($matrix as $role => $expected) {
        $user = aUser($role);

        foreach ($expected as $permission => $holds) {
            expect($user->can($permission))->toBe($holds, "$role / $permission");
        }
    }
});

test('the policy maps each action to its permission (T373)', function (): void {
    $request = SupportRequest::factory()->create();

    $expected = [
        'SUPER_ADMIN' => ['viewAny' => true, 'view' => true, 'respond' => true, 'transition' => true, 'assign' => true, 'close' => true],
        'BRANCH_COORDINATOR' => ['viewAny' => true, 'view' => true, 'respond' => true, 'transition' => true, 'assign' => false, 'close' => false],
        'INSTRUCTOR' => ['viewAny' => false, 'view' => false, 'respond' => false, 'transition' => false, 'assign' => false, 'close' => false],
    ];

    foreach ($expected as $role => $abilities) {
        $user = aUser($role);

        foreach ($abilities as $ability => $allowed) {
            $argument = $ability === 'viewAny' ? SupportRequest::class : $request;

            expect($user->can($ability, $argument))->toBe($allowed, "$role / $ability");
        }
    }
});

// ── VerifyStudentIdentity (T368) ──────────────────────────────────────────────────────────────────

test('verification finds an active enrollment in the current term, in one query', function (): void {
    $term = AcademicTerm::factory()->current()->create();
    $student = Student::factory()->create(['university_id' => '441234567']);
    SectionEnrollment::factory()->create([
        'student_id' => $student->id,
        'section_id' => Section::factory()->create(['term_id' => $term->id])->id,
    ]);

    DB::enableQueryLog();
    $match = app(VerifyStudentIdentity::class)->resolve('441234567');
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect($match?->studentId)->toBe($student->id)
        ->and($match?->termId)->toBe($term->id)
        ->and($queries)->toHaveCount(1)
        // Arabic-Indic digits are the same identifier (UniversityId::normalise).
        ->and(app(VerifyStudentIdentity::class)->verify('٤٤١٢٣٤٥٦٧'))->toBeTrue();
});

test('every reason for "no" is the same null', function (): void {
    $current = AcademicTerm::factory()->current()->create();
    $past = AcademicTerm::factory()->closed()->create();

    // Enrolled, but only in a past term.
    $lastTerm = Student::factory()->create(['university_id' => '441000001']);
    SectionEnrollment::factory()->create(['student_id' => $lastTerm->id, 'section_id' => Section::factory()->create(['term_id' => $past->id])->id]);

    // Enrolled this term, but withdrawn.
    $withdrawn = Student::factory()->create(['university_id' => '441000002']);
    SectionEnrollment::factory()->withdrawn()->create(['student_id' => $withdrawn->id, 'section_id' => Section::factory()->create(['term_id' => $current->id])->id]);

    // Enrolled this term, but the student record was deleted.
    $deleted = Student::factory()->create(['university_id' => '441000003']);
    SectionEnrollment::factory()->create(['student_id' => $deleted->id, 'section_id' => Section::factory()->create(['term_id' => $current->id])->id]);
    $deleted->delete();

    // On the roster with no enrollment at all.
    Student::factory()->create(['university_id' => '441000004']);

    $verify = app(VerifyStudentIdentity::class);

    foreach (['441000001', '441000002', '441000003', '441000004', '449999999', '', 'not-an-id'] as $id) {
        expect($verify->resolve($id))->toBeNull("'$id' was verified");
    }
});

test('a signed-in staff member on the same browser does not narrow verification', function (): void {
    $term = AcademicTerm::factory()->current()->create();
    $student = Student::factory()->create(['university_id' => '441234568']);
    SectionEnrollment::factory()->create(['student_id' => $student->id, 'section_id' => Section::factory()->create(['term_id' => $term->id])->id]);

    // A coordinator of no branch sees no student through the scopes; verification must not care.
    $this->actingAs(aUser('BRANCH_COORDINATOR'));

    expect(app(VerifyStudentIdentity::class)->verify('441234568'))->toBeTrue();
});

// ── ReferenceGenerator (T369) ─────────────────────────────────────────────────────────────────────

test('a generated reference is unused and well-formed', function (): void {
    $existing = SupportRequest::factory()->count(3)->create()->pluck('reference')->all();

    $reference = app(ReferenceGenerator::class)->generate();

    expect($existing)->not->toContain($reference)
        ->and(ReferenceGenerator::normalise($reference))->toBe($reference);
});

// ── ChallengeService (T370) ───────────────────────────────────────────────────────────────────────

test('an issued challenge has the contract\'s shape and the configured difficulty', function (): void {
    setSetting(Settings::SUPPORT_CHALLENGE_DIFFICULTY, 9);

    $issued = app(ChallengeService::class)->issue();

    expect($issued->toArray())->toHaveKeys(['challenge', 'difficulty', 'expires_in'])
        ->and($issued->difficulty)->toBe(9)
        ->and($issued->expiresIn)->toBe(600)
        ->and(ProofOfWork::isValidPuzzle($issued->challenge, $issued->difficulty))->toBeTrue();
});

test('a correct answer passes once, and only once', function (): void {
    setSetting(Settings::SUPPORT_CHALLENGE_DIFFICULTY, 8);
    $service = app(ChallengeService::class);

    $issued = $service->issue();
    $nonce = solveChallenge($issued->challenge, 8);

    expect($service->verify($issued->challenge, $nonce))->toBeTrue()
        ->and($service->verify($issued->challenge, $nonce))->toBeFalse();
});

test('a wrong answer fails and still consumes the challenge', function (): void {
    setSetting(Settings::SUPPORT_CHALLENGE_DIFFICULTY, 8);
    $service = app(ChallengeService::class);

    $issued = $service->issue();
    $right = solveChallenge($issued->challenge, 8);

    $wrong = (int) $right + 1;
    while (ProofOfWork::satisfies($issued->challenge, 8, (string) $wrong)) {
        $wrong++;
    }

    expect($service->verify($issued->challenge, (string) $wrong))->toBeFalse()
        // Consumed by the wrong attempt: the right answer is now too late.
        ->and($service->verify($issued->challenge, $right))->toBeFalse();
});

test('an expired challenge is refused', function (): void {
    setSetting(Settings::SUPPORT_CHALLENGE_DIFFICULTY, 8);
    $service = app(ChallengeService::class);

    $issued = $service->issue();
    $nonce = solveChallenge($issued->challenge, 8);

    $this->travel(ChallengeService::TTL_SECONDS + 1)->seconds();

    expect($service->verify($issued->challenge, $nonce))->toBeFalse();
});

test('the difficulty is the stored one, not one a client might claim', function (): void {
    setSetting(Settings::SUPPORT_CHALLENGE_DIFFICULTY, 20);
    $service = app(ChallengeService::class);
    $issued = $service->issue();

    // Easier settings after issue change nothing for a challenge already out.
    setSetting(Settings::SUPPORT_CHALLENGE_DIFFICULTY, 1);

    $easy = solveChallenge($issued->challenge, 1);
    $hashBits = ProofOfWork::leadingZeroBits(hash('sha256', $issued->challenge.':'.$easy, true));

    expect($service->verify($issued->challenge, $easy))->toBe($hashBits >= 20);
});

test('a forged or malformed challenge is refused without touching the store', function (mixed $challenge, mixed $nonce): void {
    $store = Mockery::mock(Repository::class);
    $store->shouldNotReceive('get', 'forget');
    Cache::partialMock()->shouldReceive('store')->andReturn($store);

    expect(app(ChallengeService::class)->verify($challenge, $nonce))->toBeFalse();
})->with([
    'unsigned, right shape' => [str_repeat('A', 48), '0'],
    'too short' => ['AAAA', '0'],
    'not a string' => [['x'], '0'],
    'nonce not a string' => [str_repeat('A', 48), 7],
]);

test('a challenge from another deployment\'s key is refused', function (): void {
    $issued = app(ChallengeService::class)->issue();

    config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);

    expect(app(ChallengeService::class)->verify($issued->challenge, '0'))->toBeFalse();
});

test('the store being down fails closed: no challenge issued, no answer accepted (FR-129)', function (): void {
    setSetting(Settings::SUPPORT_CHALLENGE_DIFFICULTY, 8);
    $issued = app(ChallengeService::class)->issue();
    $nonce = solveChallenge($issued->challenge, 8);

    $down = Mockery::mock(Repository::class);
    $down->shouldReceive('add', 'get', 'forget')->andThrow(new RuntimeException('Connection refused'));
    Cache::partialMock()->shouldReceive('store')->andReturn($down);

    expect(fn () => app(ChallengeService::class)->issue())->toThrow(ChallengeUnavailable::class)
        ->and(app(ChallengeService::class)->verify($issued->challenge, $nonce))->toBeFalse();
});

test('an out-of-range difficulty setting refuses to issue rather than clamp', function (mixed $difficulty): void {
    setSetting(Settings::SUPPORT_CHALLENGE_DIFFICULTY, $difficulty);

    expect(fn () => app(ChallengeService::class)->issue())->toThrow(ChallengeUnavailable::class);
})->with([0, 33, -1, '16']);

// ── SupportSubmissionThrottle and SupportLimits (T371) ────────────────────────────────────────────

/**
 * The submission's limits as T394 will wire them: the throttle outside the padding. The closure
 * stands in for T391, which counts the identifier once the challenge is verified.
 */
function throttleProbe(): void
{
    Route::middleware([SupportSubmissionThrottle::class, ConstantResponseTime::class])
        ->post('/__probe/support-throttle', function (Illuminate\Http\Request $request) {
            if ($request->boolean('challenge_ok')) {
                app(SupportLimits::class)->consumeIdentifier($request->input('university_id'));
            }

            return response('ok');
        });
}

test('the address limit refuses one IP walking many identifiers', function (): void {
    Sleep::fake();
    setSetting(Settings::SUPPORT_THROTTLE_IP, ['max_attempts' => 3, 'decay_minutes' => 60]);
    throttleProbe();

    foreach (['441000001', '441000002', '441000003'] as $id) {
        $this->postJson('/__probe/support-throttle', ['university_id' => $id])->assertOk();
    }

    $this->postJson('/__probe/support-throttle', ['university_id' => '441000004'])->assertStatus(429);
});

test('the address limit decides on the shared atomic count, not a read taken before it', function (): void {
    setSetting(Settings::SUPPORT_THROTTLE_IP, ['max_attempts' => 3, 'decay_minutes' => 60]);
    $request = Illuminate\Http\Request::create('/', 'POST', server: ['REMOTE_ADDR' => '10.9.0.1']);

    // The race, frozen: every read says nobody has counted yet (what fifty simultaneous requests all
    // saw under the first version's check-then-count), while the atomic counter, shared by them all,
    // returns the fourth hit. A decision taken on the read lets this request through; only one taken
    // on the count refuses it.
    RateLimiter::partialMock()->shouldReceive('attempts')->andReturn(0);
    RateLimiter::partialMock()->shouldReceive('tooManyAttempts')->andReturn(false);
    RateLimiter::partialMock()->shouldReceive('increment')->andReturn(4);

    expect(fn () => app(SupportLimits::class)->hitAddress($request))->toThrow(SupportLimitReached::class);
});

test('the identifier limit refuses one identifier from many addresses, independently', function (): void {
    Sleep::fake();
    setSetting(Settings::SUPPORT_THROTTLE_IDENTIFIER, ['max_attempts' => 2, 'decay_minutes' => 60]);
    throttleProbe();

    foreach (['10.0.0.1', '10.0.0.2'] as $ip) {
        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/__probe/support-throttle', ['university_id' => '441000009', 'challenge_ok' => 1])->assertOk();
    }

    // A third address, well under its own limit — refused for the identifier alone, on arrival. The
    // same identifier in Arabic-Indic digits is the same identifier.
    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.3'])
        ->postJson('/__probe/support-throttle', ['university_id' => '٤٤١٠٠٠٠٠٩', 'challenge_ok' => 1])->assertStatus(429);

    // A different identifier from that address is fine: the limits are separate.
    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.3'])
        ->postJson('/__probe/support-throttle', ['university_id' => '441000010', 'challenge_ok' => 1])->assertOk();
});

test('a student cannot be locked out by requests that never solved a challenge', function (): void {
    Sleep::fake();
    setSetting(Settings::SUPPORT_THROTTLE_IDENTIFIER, ['max_attempts' => 3, 'decay_minutes' => 60]);
    throttleProbe();

    // An attacker names the victim ten times, from ten addresses, with nothing solved.
    foreach (range(1, 10) as $i) {
        $this->withServerVariables(['REMOTE_ADDR' => "10.3.0.$i"])
            ->postJson('/__probe/support-throttle', ['university_id' => '441555555']);
    }

    // The student, solving their challenge, is still let in.
    $this->withServerVariables(['REMOTE_ADDR' => '10.4.0.1'])
        ->postJson('/__probe/support-throttle', ['university_id' => '441555555', 'challenge_ok' => 1])->assertOk();
});

test('both refusals are identical — body, status and Retry-After — and never name the limit (FR-130)', function (): void {
    setSetting(Settings::SUPPORT_THROTTLE_IP, ['max_attempts' => 1, 'decay_minutes' => 60]);
    setSetting(Settings::SUPPORT_THROTTLE_IDENTIFIER, ['max_attempts' => 1, 'decay_minutes' => 30]);
    throttleProbe();

    // By identifier: one identifier, two addresses — its counter started now.
    $this->withServerVariables(['REMOTE_ADDR' => '10.2.0.1'])->postJson('/__probe/support-throttle', ['university_id' => '441000013', 'challenge_ok' => 1]);
    $byIdentifier = $this->withServerVariables(['REMOTE_ADDR' => '10.2.0.2'])->postJson('/__probe/support-throttle', ['university_id' => '441000013']);

    // By address, with its counter started twenty minutes earlier. An honest remaining time would
    // differ between the two by exactly that, and say which limit it was.
    $this->travel(-20)->minutes();
    $this->withServerVariables(['REMOTE_ADDR' => '10.1.0.1'])->postJson('/__probe/support-throttle', ['university_id' => '441000011']);
    $this->travelBack();
    $byAddress = $this->withServerVariables(['REMOTE_ADDR' => '10.1.0.1'])->postJson('/__probe/support-throttle', ['university_id' => '441000012']);

    $byAddress->assertStatus(429);
    $byIdentifier->assertStatus(429);

    // The longer of the two configured windows, for either.
    expect($byAddress->headers->get('Retry-After'))->toBe('3600')
        ->and($byIdentifier->headers->get('Retry-After'))->toBe('3600')
        ->and($byAddress->getContent())->toBe($byIdentifier->getContent())
        ->and($byAddress->headers->has('X-RateLimit-Remaining'))->toBeFalse()
        ->and(strtolower((string) $byAddress->getContent()))->not->toContain('university')
        ->and(strtolower((string) $byAddress->getContent()))->not->toContain('ip');
});

test('a refusal is never padded, wherever it is raised, so a flood cannot hold the workers', function (): void {
    Sleep::fake();
    setSetting(Settings::SUPPORT_THROTTLE_IP, ['max_attempts' => 1, 'decay_minutes' => 60]);
    setSetting(Settings::SUPPORT_THROTTLE_IDENTIFIER, ['max_attempts' => 1, 'decay_minutes' => 60]);
    throttleProbe();

    $this->withServerVariables(['REMOTE_ADDR' => '10.5.0.1'])->postJson('/__probe/support-throttle', ['university_id' => '441000030']);
    Sleep::fake();

    // Refused by the address limit, outside the padding.
    $this->withServerVariables(['REMOTE_ADDR' => '10.5.0.1'])->postJson('/__probe/support-throttle', ['university_id' => '441000031'])->assertStatus(429);

    // Refused by the identifier count, raised inside the padding (where T391 will raise it).
    $this->withServerVariables(['REMOTE_ADDR' => '10.5.0.2'])->postJson('/__probe/support-throttle', ['university_id' => '441000032', 'challenge_ok' => 1])->assertOk();
    Sleep::fake();
    $this->withServerVariables(['REMOTE_ADDR' => '10.5.0.3'])->postJson('/__probe/support-throttle', ['university_id' => '441000032', 'challenge_ok' => 1])->assertStatus(429);

    Sleep::assertNeverSlept();
});

test('the identifier never appears in a limiter key, nor any unkeyed hash of it', function (): void {
    throttleProbe();
    Sleep::fake();
    $this->postJson('/__probe/support-throttle', ['university_id' => '441777777', 'challenge_ok' => 1]);

    $hashed = app(SupportLimits::class)->identifierKey('441777777');

    expect($hashed)->not->toContain('441777777')
        ->and(RateLimiter::attempts((string) $hashed))->toBe(1)
        ->and(RateLimiter::attempts('support:submit:id:441777777'))->toBe(0)
        // A plain SHA-256 of nine digits is reversible by enumeration; the key must not be one.
        ->and(RateLimiter::attempts('support:submit:id:'.hash('sha256', '441777777')))->toBe(0);
});

test('a browser gets the refusal as a page on the public layout, with no way into the staff side', function (): void {
    setSetting(Settings::SUPPORT_THROTTLE_IP, ['max_attempts' => 1, 'decay_minutes' => 60]);
    throttleProbe();
    Sleep::fake();

    $this->post('/__probe/support-throttle', ['university_id' => '441000020']);
    $page = $this->post('/__probe/support-throttle', ['university_id' => '441000021']);

    $page->assertStatus(429)
        ->assertSee('lang="ar"', false)
        ->assertSee('dir="rtl"', false)
        ->assertSee(__('support.limit_title'));

    // FR-163: the only links are the skip link; the language switch is a form posting to /locale.
    preg_match_all('/<a\s[^>]*href="([^"]*)"/i', (string) $page->getContent(), $links);

    expect($links[1])->toBe(['#track-public-content'])
        ->and((string) $page->getContent())->not->toContain('track-sidebar')
        ->and((string) $page->getContent())->not->toContain('/logout');
});

test('the public floor reads its setting and refuses in the same shape, without X-RateLimit headers', function (): void {
    setSetting(Settings::PUBLIC_THROTTLE, ['max_attempts' => 2, 'decay_minutes' => 1]);
    Route::middleware('public')->get('/__probe/public-floor', fn () => response('ok'));

    $this->getJson('/__probe/public-floor')->assertOk();
    $this->getJson('/__probe/public-floor')->assertOk();
    $refused = $this->getJson('/__probe/public-floor');

    // The whole window, never a countdown of this caller's counter (Phase 20 review).
    $refused->assertStatus(429)
        ->assertHeader('Retry-After', '60')
        ->assertJsonStructure(['message']);

    expect($refused->headers->has('X-RateLimit-Limit'))->toBeFalse()
        ->and($refused->headers->has('X-RateLimit-Remaining'))->toBeFalse()
        ->and($refused->json('message'))->toBe(trans_choice('support.limit_reached', 1, ['minutes' => 1]));
});

// ── Trusted proxies (review) ──────────────────────────────────────────────────────────────────────

test('behind a trusted proxy the client address is the forwarded one', function (): void {
    config(['trustedproxy.proxies' => ['10.200.0.1']]);
    Route::get('/__probe/ip', fn (Illuminate\Http\Request $request) => response((string) $request->ip()));

    $this->withServerVariables(['REMOTE_ADDR' => '10.200.0.1'])
        ->withHeader('X-Forwarded-For', '203.0.113.7')
        ->get('/__probe/ip')->assertSeeText('203.0.113.7');

    // Anyone else's X-Forwarded-For is ignored: a client cannot choose its own address.
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.9'])
        ->withHeader('X-Forwarded-For', '203.0.113.8')
        ->get('/__probe/ip')->assertSeeText('198.51.100.9');
});

test('a wildcard TRUSTED_PROXIES is refused rather than trusting every caller', function (string $value, ?array $expected): void {
    $_SERVER['TRUSTED_PROXIES'] = $value;
    $_ENV['TRUSTED_PROXIES'] = $value;

    try {
        $config = require config_path('trustedproxy.php');
    } finally {
        unset($_SERVER['TRUSTED_PROXIES'], $_ENV['TRUSTED_PROXIES']);
    }

    expect($config['proxies'])->toBe($expected);
})->with([
    'wildcard' => ['*', null],
    'double wildcard' => ['**', null],
    'empty' => ['', null],
    'list' => ['10.0.0.0/8, 172.16.0.5', ['10.0.0.0/8', '172.16.0.5']],
]);

// ── ConstantResponseTime (T372) ───────────────────────────────────────────────────────────────────

test('a fast response is held to the floor', function (): void {
    Sleep::fake();
    // Far above any render time, even on a loaded machine (the clock is faked, so it costs nothing):
    // the assertion is about the padding, not about this machine.
    setSetting(Settings::SUPPORT_MIN_RESPONSE_MS, 60000);
    Route::middleware(ConstantResponseTime::class)->get('/__probe/support-floor', fn () => response('ok'));

    $this->get('/__probe/support-floor')->assertOk();

    Sleep::assertSleptTimes(1);
    Sleep::assertSlept(fn (Carbon\CarbonInterval $pause): bool => $pause->totalMilliseconds > 50000 && $pause->totalMilliseconds <= 60000);
});

test('a refusal from inside is padded exactly like a success', function (): void {
    Sleep::fake();
    // An error page's first render passed even 5,000 ms on a loaded machine; the clock is faked, so a
    // floor of a minute costs nothing and leaves no race.
    setSetting(Settings::SUPPORT_MIN_RESPONSE_MS, 60000);
    Route::middleware(ConstantResponseTime::class)->get('/__probe/support-floor-abort', fn () => abort(422));

    $this->get('/__probe/support-floor-abort')->assertStatus(422);

    Sleep::assertSleptTimes(1);
});

test('a response already slower than the floor is not slowed further', function (): void {
    Sleep::fake();
    setSetting(Settings::SUPPORT_MIN_RESPONSE_MS, 1);
    Route::middleware(ConstantResponseTime::class)->get('/__probe/support-floor-slow', function () {
        usleep(5000);

        return response('ok');
    });

    $this->get('/__probe/support-floor-slow')->assertOk();

    Sleep::assertNeverSlept();
});

test('settings being unreadable keeps the documented floor rather than none', function (): void {
    Sleep::fake();
    Setting::query()->where('key', Settings::SUPPORT_MIN_RESPONSE_MS)->delete();
    Settings::forget();
    Route::middleware(ConstantResponseTime::class)->get('/__probe/support-floor-default', fn () => response('ok'));

    $this->get('/__probe/support-floor-default')->assertOk();

    Sleep::assertSlept(fn (Carbon\CarbonInterval $pause): bool => $pause->totalMilliseconds > 300);
});

test('a 429 from anywhere inside is returned at once', function (): void {
    Sleep::fake();
    setSetting(Settings::SUPPORT_MIN_RESPONSE_MS, 60000);
    Route::middleware(ConstantResponseTime::class)->get('/__probe/support-floor-429', fn () => abort(429));

    $this->get('/__probe/support-floor-429')->assertStatus(429);

    Sleep::assertNeverSlept();
});

test('a floor of zero or less is a broken setting, and the documented floor holds', function (mixed $floor): void {
    Sleep::fake();
    setSetting(Settings::SUPPORT_MIN_RESPONSE_MS, $floor);
    Route::middleware(ConstantResponseTime::class)->get('/__probe/support-floor-zero', fn () => response('ok'));

    $this->get('/__probe/support-floor-zero')->assertOk();

    Sleep::assertSlept(fn (Carbon\CarbonInterval $pause): bool => $pause->totalMilliseconds > 300);
})->with([0, -5, '400']);

// ── Review additions: verification and seeding ────────────────────────────────────────────────────

test('an enrollment in a section not yet approved, or withdrawn by the registrar, does not verify', function (string $case): void {
    $term = AcademicTerm::factory()->current()->create();
    $student = Student::factory()->create(['university_id' => '441808080']);

    $section = match ($case) {
        'pending' => Section::factory()->pending()->create(['term_id' => $term->id]),
        'rejected' => Section::factory()->rejected()->create(['term_id' => $term->id]),
        default => Section::factory()->create(['term_id' => $term->id]),
    };

    SectionEnrollment::factory()->create([
        'student_id' => $student->id,
        'section_id' => $section->id,
        'academic_status' => $case === 'registrar_withdrawn' ? 'withdrawn' : 'regular',
    ]);

    expect(app(VerifyStudentIdentity::class)->resolve('441808080'))->toBeNull();
})->with(['pending', 'rejected', 'registrar_withdrawn']);

test('seeding twice changes nothing, and keeps a Super Admin\'s edit', function (): void {
    setSetting(Settings::SUPPORT_DESCRIPTION_MAX, 1500);
    $before = Setting::query()->count();

    (new SettingSeeder)->run();
    (new Database\Seeders\PermissionSeeder)->run();
    (new Database\Seeders\RoleSeeder)->run();
    (new Database\Seeders\PermissionSeeder)->run();

    expect(Setting::query()->count())->toBe($before)
        ->and(Settings::int(Settings::SUPPORT_DESCRIPTION_MAX))->toBe(1500)
        ->and(Spatie\Permission\Models\Permission::query()->count())->toBe(47)
        ->and(Spatie\Permission\Models\Permission::query()->where('name', 'like', 'support.%')->count())->toBe(4)
        ->and(Spatie\Permission\Models\Role::findByName('INSTRUCTOR')->permissions->pluck('name')->filter(fn (string $n): bool => str_starts_with($n, 'support.'))->all())->toBe([]);
});

// ── Phase 22 review: the floor's own health, and the cap on sleeping workers ───────────────────────

test('work that outruns the floor is logged, at most once a minute', function (): void {
    Sleep::fake();
    $log = Illuminate\Support\Facades\Log::spy();
    setSetting(Settings::SUPPORT_MIN_RESPONSE_MS, 1);
    Route::middleware(ConstantResponseTime::class)->get('/__probe/support-floor-outrun', function () {
        usleep(5000);

        return response('ok');
    });

    $this->get('/__probe/support-floor-outrun')->assertOk();
    $this->get('/__probe/support-floor-outrun')->assertOk();

    $log->shouldHaveReceived('warning')
        ->withArgs(fn (string $message): bool => str_contains($message, 'longer than its response-time floor'))
        ->once();
});

test('past the cap, a padded response is refused at once, before any work, and holds no worker', function (): void {
    Sleep::fake();
    config(['support.padding_max_concurrent' => 2]);
    $ran = false;
    Route::middleware(ConstantResponseTime::class)->get('/__probe/support-floor-cap', function () use (&$ran) {
        $ran = true;

        return response('ok');
    });

    // Two requests already sleeping in this minute's bucket.
    Cache::put('support:padding:'.now()->format('YmdHi'), 2, 120);

    $this->getJson('/__probe/support-floor-cap')
        ->assertStatus(503)
        ->assertHeader('Retry-After', '30')
        ->assertExactJson(['message' => __('support.failures.challenge_unavailable')]);

    expect($ran)->toBeFalse()
        ->and(Cache::get('support:padding:'.now()->format('YmdHi')))->toBe(2);

    Sleep::assertNeverSlept();
});

test('under the cap, a padded response takes a slot and gives it back', function (): void {
    Sleep::fake();
    config(['support.padding_max_concurrent' => 5]);
    Route::middleware(ConstantResponseTime::class)->get('/__probe/support-floor-slot', fn () => response('ok'));

    $this->get('/__probe/support-floor-slot')->assertOk();

    expect((int) Cache::get('support:padding:'.now()->format('YmdHi')))->toBe(0);
});
