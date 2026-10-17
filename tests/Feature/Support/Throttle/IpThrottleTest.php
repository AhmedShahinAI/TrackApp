<?php

declare(strict_types=1);

use App\Modules\Settings\Settings;
use Illuminate\Support\Sleep;

/*
|--------------------------------------------------------------------------
| T381 — one address walking many identifiers is refused at support_throttle_ip (FR-126)
|--------------------------------------------------------------------------
|
| Through the real POST /support. The address is counted on arrival, before the challenge, so even
| submissions that solve nothing spend it — they come from the caller, and spend only the caller's.
|
*/

beforeEach(function (): void {
    Sleep::fake();
    supportWorld();
    supportSetting(Settings::SUPPORT_THROTTLE_IP, ['max_attempts' => 5, 'decay_minutes' => 60]);
});

test('the sixth submission from one address in the window is refused, whatever the identifier', function (): void {
    foreach (range(1, 5) as $i) {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
            ->post('/support', supportSubmission(['university_id' => '44100000'.$i]))
            ->assertStatus(422);
    }

    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
        ->post('/support', supportSubmission(['university_id' => '441000006']))
        ->assertStatus(429);

    // Another address is unaffected.
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.11'])
        ->post('/support', supportSubmission(['university_id' => '441000007']))
        ->assertStatus(422);
});

test('the window passes and the address may submit again', function (): void {
    foreach (range(1, 5) as $i) {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.12'])->post('/support', supportSubmission(['university_id' => '44100001'.$i]));
    }

    $this->travel(61)->minutes();

    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.12'])
        ->post('/support', supportSubmission(['university_id' => '441000019']))
        ->assertStatus(422);
});
