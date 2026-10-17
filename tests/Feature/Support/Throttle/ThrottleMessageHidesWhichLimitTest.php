<?php

declare(strict_types=1);

use App\Modules\Settings\Settings;
use Illuminate\Support\Sleep;

/*
|--------------------------------------------------------------------------
| T383 — neither refusal says which limit it was (FR-130)
|--------------------------------------------------------------------------
|
| Saying "too many requests for this University ID" confirms the identifier is being processed. So the
| two refusals share one body, one status, one Retry-After (the longer configured window), and carry
| no X-RateLimit-* counter — a counter on the response is a readout of which limit is close.
|
*/

beforeEach(function (): void {
    Sleep::fake();
    supportWorld();
    supportSetting(Settings::SUPPORT_THROTTLE_IP, ['max_attempts' => 2, 'decay_minutes' => 60]);
    supportSetting(Settings::SUPPORT_THROTTLE_IDENTIFIER, ['max_attempts' => 2, 'decay_minutes' => 30]);
});

test('the address refusal and the identifier refusal are the same response', function (bool $json): void {
    $post = fn (string $ip, string $id) => $json
        ? $this->withServerVariables(['REMOTE_ADDR' => $ip])->postJson('/support', supportSubmission(['university_id' => $id]))
        : $this->withServerVariables(['REMOTE_ADDR' => $ip])->post('/support', supportSubmission(['university_id' => $id]));

    // By address: one address, three identifiers.
    $post('203.0.113.1', '441000101');
    $post('203.0.113.1', '441000102');
    $byAddress = $post('203.0.113.1', '441000103');

    // By identifier: one identifier, three addresses.
    $post('203.0.113.2', '441000200');
    $post('203.0.113.3', '441000200');
    $byIdentifier = $post('203.0.113.4', '441000200');

    $byAddress->assertStatus(429);
    $byIdentifier->assertStatus(429);

    expect($byAddress->getContent())->toBe($byIdentifier->getContent())
        ->and($byAddress->headers->get('Retry-After'))->toBe('3600')
        ->and($byIdentifier->headers->get('Retry-After'))->toBe('3600');

    foreach ([$byAddress, $byIdentifier] as $refusal) {
        $body = mb_strtolower((string) $refusal->getContent());

        expect($refusal->headers->has('X-RateLimit-Remaining'))->toBeFalse()
            ->and($refusal->headers->has('X-RateLimit-Limit'))->toBeFalse()
            ->and($body)->not->toContain('441000')
            ->and($body)->not->toContain('university')
            ->and($body)->not->toContain('الرقم الجامعي')
            ->and($body)->not->toContain('203.0.113');
    }
})->with(['a browser' => [false], 'a JSON client' => [true]]);

test('a successful response carries no counter either', function (): void {
    $this->get('/support')->assertHeaderMissing('X-RateLimit-Remaining')->assertHeaderMissing('X-RateLimit-Limit');
    $this->post('/support', supportSubmission())->assertHeaderMissing('X-RateLimit-Remaining');
});
