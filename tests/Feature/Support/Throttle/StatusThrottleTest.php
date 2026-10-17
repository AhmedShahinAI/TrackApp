<?php

declare(strict_types=1);

use App\Modules\Settings\Settings;
use Illuminate\Support\Sleep;

/*
|--------------------------------------------------------------------------
| T426 — status lookups are limited per address at support_status_throttle_ip (FR-131)
|--------------------------------------------------------------------------
|
| The lookup is the guessing surface: someone holding a stolen reference would otherwise try University
| IDs against it until one matched. Its own key, its own limit, separate from the submission's.
|
*/

beforeEach(function (): void {
    Sleep::fake();
    supportWorld();
    supportSetting(Settings::SUPPORT_STATUS_THROTTLE_IP, ['max_attempts' => 4, 'decay_minutes' => 60]);
    supportSetting(Settings::SUPPORT_STATUS_THROTTLE_REFERENCE, ['max_attempts' => 5, 'decay_minutes' => 60]);
});

test('guessing identifiers against one reference stops at the limit', function (): void {
    foreach (range(1, 4) as $i) {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.40'])
            ->postJson('/support/status', ['reference' => 'K7Q2M9XVT3A', 'university_id' => '44100000'.$i])
            ->assertStatus(422);
    }

    $refused = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.40'])
        ->postJson('/support/status', ['reference' => 'K7Q2M9XVT3A', 'university_id' => '441000005']);

    $refused->assertStatus(429)->assertHeader('Retry-After', '3600');

    expect($refused->headers->has('X-RateLimit-Remaining'))->toBeFalse();

    // Another address, another reference, is unaffected.
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.41'])
        ->postJson('/support/status', ['reference' => 'ZZZZ0000001', 'university_id' => '441000006'])
        ->assertStatus(422);
});

test('the status limit and the submission limit are separate counters', function (): void {
    foreach (range(1, 4) as $i) {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.42'])->postJson('/support/status', ['reference' => 'K7Q2M9XVT3A', 'university_id' => '441000001']);
    }

    // Out of lookups — and still able to submit.
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.42'])
        ->postJson('/support/status', ['reference' => 'ABCD0000001', 'university_id' => '441000001'])
        ->assertStatus(429);

    enrolledStudent('441234567');
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.42'])->post('/support', supportSubmission())->assertRedirect(route('support.submitted'));
});

test('as seeded, the lookup limit is TIGHTER than the submission limit (T426, Phase 22 review)', function (): void {
    App\Models\Setting::query()->whereIn('key', [Settings::SUPPORT_STATUS_THROTTLE_IP, Settings::SUPPORT_STATUS_THROTTLE_REFERENCE])->delete();
    (new Database\Seeders\SupportSettingSeeder)->run();

    // The seeded values, compared — not one number pinned. data-model.md first said 10, which was looser.
    expect(Settings::limit(Settings::SUPPORT_STATUS_THROTTLE_IP)['max_attempts'])
        ->toBeLessThan(Settings::limit(Settings::SUPPORT_THROTTLE_IP)['max_attempts'])
        ->and(Settings::limit(Settings::SUPPORT_STATUS_THROTTLE_REFERENCE))->toBe(['max_attempts' => 5, 'decay_minutes' => 60]);
});

test('a refused lookup is not padded', function (): void {
    foreach (range(1, 4) as $i) {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.43'])->postJson('/support/status', ['reference' => 'x', 'university_id' => 'y']);
    }

    Sleep::fake();
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.43'])->postJson('/support/status', ['reference' => 'x', 'university_id' => 'y'])->assertStatus(429);

    Sleep::assertNeverSlept();
});

test('guessing against one reference from many addresses stops at the reference counter', function (): void {
    foreach (range(1, 5) as $i) {
        $this->withServerVariables(['REMOTE_ADDR' => "198.51.100.5$i"])
            ->postJson('/support/status', ['reference' => 'K7Q2M9XVT3A', 'university_id' => '44100010'.$i])
            ->assertStatus(422);
    }

    // A sixth address, well under its own limit.
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.60'])
        ->postJson('/support/status', ['reference' => 'k7q2-m9xv-t3a', 'university_id' => '441000106'])
        ->assertStatus(429)
        ->assertHeader('Retry-After', '3600');
});

test('the reference counter counts references that do not exist exactly like ones that do', function (): void {
    $student = enrolledStudent('441234567');
    $real = App\Models\SupportRequest::factory()->create(['student_id' => $student->id]);

    $refusedAfter = function (string $reference, string $prefix): int {
        foreach (range(1, 10) as $i) {
            $status = $this->withServerVariables(['REMOTE_ADDR' => "$prefix.$i"])
                ->postJson('/support/status', ['reference' => $reference, 'university_id' => '449999999'])
                ->status();

            if ($status === 429) {
                return $i;
            }
        }

        return 0;
    };

    // Same attempt count for a live reference and an invented one: the counter cannot tell them apart.
    expect($refusedAfter($real->reference, '10.70.0'))->toBe($refusedAfter('K7Q2M9XVT3A', '10.71.0'))
        ->and($refusedAfter('K7Q2M9XVT3A', '10.72.0'))->toBe(1);
});

test('a lookup that finds its request costs nothing: success is not guessing', function (): void {
    supportSetting(Settings::SUPPORT_STATUS_THROTTLE_IP, ['max_attempts' => 2, 'decay_minutes' => 60]);
    $student = enrolledStudent('441234567');
    $mine = App\Models\SupportRequest::factory()->create(['student_id' => $student->id]);

    // A student checking again and again, or a campus behind one address.
    foreach (range(1, 6) as $i) {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.80'])
            ->postJson('/support/status', ['reference' => $mine->reference, 'university_id' => '441234567'])
            ->assertOk();
    }

    // The budget is still whole: two failures pass, the third is refused.
    foreach (range(1, 2) as $i) {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.80'])
            ->postJson('/support/status', ['reference' => 'BBBB000000'.$i, 'university_id' => '441000001'])
            ->assertStatus(422);
    }

    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.80'])
        ->postJson('/support/status', ['reference' => 'BBBB0000009', 'university_id' => '441000001'])
        ->assertStatus(429);
});

test('an IPv6 /64 is one address: rotating within it buys nothing', function (): void {
    foreach (range(1, 4) as $i) {
        $this->withServerVariables(['REMOTE_ADDR' => "2001:db8:abcd:12::$i"])
            ->postJson('/support/status', ['reference' => 'CCCC000000'.$i, 'university_id' => '441000001'])
            ->assertStatus(422);
    }

    $this->withServerVariables(['REMOTE_ADDR' => '2001:db8:abcd:12:ffff:ffff:ffff:fffe'])
        ->postJson('/support/status', ['reference' => 'CCCC0000009', 'university_id' => '441000001'])
        ->assertStatus(429);

    // The next /64 is a different address.
    $this->withServerVariables(['REMOTE_ADDR' => '2001:db8:abcd:13::1'])
        ->postJson('/support/status', ['reference' => 'CCCC0000008', 'university_id' => '441000001'])
        ->assertStatus(422);

    expect(App\Modules\Support\Services\SupportLimits::addressBucket('2001:db8:abcd:12::1'))
        ->toBe(App\Modules\Support\Services\SupportLimits::addressBucket('2001:0db8:abcd:0012:9999::1'))
        ->and(App\Modules\Support\Services\SupportLimits::addressBucket('203.0.113.5'))->toBe('203.0.113.5');
});
