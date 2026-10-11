<?php

declare(strict_types=1);

use App\Modules\Shared\Http\Middleware\AssignRequestId;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| T275 — one JSON line per log record, with the request id, the user id and the route (PRD 8.8)
|--------------------------------------------------------------------------
*/

/** This test's log file, one per test. */
function jsonLogFile(bool $fresh = false): string
{
    static $file = null;

    if ($fresh || $file === null) {
        $file = storage_path('logs/test-json-'.uniqid().'.log');
    }

    return $file;
}

beforeEach(function (): void {
    config([
        'logging.channels.json.driver' => 'single',
        'logging.channels.json.path' => jsonLogFile(fresh: true),
    ]);
    Log::forgetChannel('json');

    Route::middleware('web')->get('/__log-probe', function () {
        Log::channel('json')->info('probe', ['detail' => 'kept']);

        return response('ok');
    })->name('log.probe');
});

afterEach(function (): void {
    @unlink(jsonLogFile());
});

/** @return list<array<string, mixed>> */
function jsonLogLines(string $file): array
{
    $lines = array_filter(explode("\n", (string) @file_get_contents($file)));

    return array_values(array_map(static fn (string $line): array => json_decode($line, true, flags: JSON_THROW_ON_ERROR), $lines));
}

test('a log line is one JSON object carrying the request id, the user and the route', function (): void {
    $user = aUser('SUPER_ADMIN');

    $response = $this->actingAs($user)->get('/__log-probe')->assertOk();
    $id = $response->headers->get(AssignRequestId::HEADER);

    $lines = jsonLogLines(jsonLogFile());

    expect($id)->toMatch('/^[0-9a-f-]{36}$/')
        ->and($lines)->toHaveCount(1)
        ->and($lines[0]['message'])->toBe('probe')
        ->and($lines[0]['level_name'])->toBe('INFO')
        ->and($lines[0]['context']['detail'])->toBe('kept')
        ->and($lines[0]['extra']['request_id'])->toBe($id)
        ->and($lines[0]['extra']['user_id'])->toBe($user->id)
        ->and($lines[0]['extra']['route'])->toBe('log.probe')
        ->and($lines[0]['extra']['method'])->toBe('GET');
});

test('a guest\'s line has no user, and still has its request id', function (): void {
    $response = $this->get('/__log-probe')->assertOk();
    $line = jsonLogLines(jsonLogFile())[0];

    expect($line['extra']['user_id'])->toBeNull()
        ->and($line['extra']['request_id'])->toBe($response->headers->get(AssignRequestId::HEADER));
});

test('an upstream request id is kept when it is an id, and replaced when it is not', function (): void {
    $this->withHeader(AssignRequestId::HEADER, 'lb-7f3a9c21-0001')->get('/__log-probe')
        ->assertHeader(AssignRequestId::HEADER, 'lb-7f3a9c21-0001');

    // A newline and a JSON fragment: kept verbatim, it would forge a second log line.
    $forged = "x\n{\"level_name\":\"EMERGENCY\"}";
    $response = $this->withHeader(AssignRequestId::HEADER, $forged)->get('/__log-probe');

    expect($response->headers->get(AssignRequestId::HEADER))->not->toBe($forged)
        ->and($response->headers->get(AssignRequestId::HEADER))->toMatch('/^[0-9a-f-]{36}$/');
});

test('the request id travels into the jobs the request dispatches', function (): void {
    Route::middleware('web')->get('/__queue-probe', function () {
        dispatch(static function (): void {})->onConnection('database');

        return response('queued');
    });

    $id = $this->get('/__queue-probe')->assertOk()->headers->get(AssignRequestId::HEADER);

    // Context is dehydrated into the queued job's payload, and restored around the job when a worker
    // runs it — so the job's own log lines carry the id of the request that queued it.
    $payload = (string) Illuminate\Support\Facades\DB::table('jobs')->latest('id')->value('payload');

    expect($payload)->toContain('request_id')->toContain((string) $id);
});

test('the shipped default stack writes the JSON channel', function (): void {
    // The shipped defaults, read from source: a developer's own .env may choose a readable log.
    expect((string) file_get_contents(config_path('logging.php')))->toContain("env('LOG_STACK', 'json')")
        ->and((string) file_get_contents(base_path('.env.example')))->toContain('LOG_STACK=json')
        ->and(config('logging.channels.json.tap'))->toContain(App\Modules\Shared\Logging\JsonLogFormatter::class);
});
