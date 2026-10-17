<?php

declare(strict_types=1);

use App\Modules\Shared\Logging\RedactingProcessor;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Monolog\Level;
use Monolog\LogRecord;

/*
|--------------------------------------------------------------------------
| SEC-080, SEC-081 — no log line carries a secret or a query's personal data (security plan G-10)
|--------------------------------------------------------------------------
*/

/** @param  array<string, mixed>  $context */
function record(array $context): LogRecord
{
    return new LogRecord(new DateTimeImmutable, 'test', Level::Error, 'm', $context);
}

test('sensitive keys are replaced, at any depth', function (): void {
    $out = (new RedactingProcessor)(record([
        'password' => 'Hunter2-Hunter2',
        'nested' => ['api_token' => 'abc', 'password_hash' => '$argon2id$x', 'keep' => 'visible'],
        'headers' => ['Authorization' => 'Bearer x', 'Cookie' => 'track_session=y'],
    ]))->context;

    expect($out['password'])->toBe('[redacted]')
        ->and($out['nested']['api_token'])->toBe('[redacted]')
        ->and($out['nested']['password_hash'])->toBe('[redacted]')
        ->and($out['nested']['keep'])->toBe('visible')
        ->and($out['headers']['Authorization'])->toBe('[redacted]')
        ->and($out['headers']['Cookie'])->toBe('[redacted]');
});

test('a query exception keeps its statement but loses the values bound into it', function (): void {
    $user = aUser('INSTRUCTOR', ['email' => 'leaky.person@mu.edu.sa']);

    try {
        // The unique email index refuses a second row — the driver's message names the value.
        DB::table('users')->insert(['organization_id' => $user->organization_id, 'name_ar' => 'x', 'name_en' => 'x',
            'email' => 'leaky.person@mu.edu.sa', 'password' => 'x', 'locale' => 'ar', 'is_active' => true]);
        $this->fail('expected a unique-constraint violation');
    } catch (QueryException $e) {
        expect($e->getMessage())->toContain('leaky.person@mu.edu.sa');  // the leak being closed

        $out = (new RedactingProcessor)(record(['exception' => $e]))->context['exception'];

        expect($out['message'])->not->toContain('leaky.person')
            ->and($out['message'])->toContain('insert into')
            ->and($out['message'])->toContain('?')
            ->and($out['class'])->toBe(QueryException::class)
            ->and($out['trace'])->not->toBeEmpty();
    }
});

test('the json and alerts channels both scrub', function (string $channel): void {
    $file = storage_path('logs/test-redaction-'.uniqid().'.log');
    config(["logging.channels.{$channel}.path" => $file, "logging.channels.{$channel}.driver" => 'single', "logging.channels.{$channel}.level" => 'debug']);
    Illuminate\Support\Facades\Log::forgetChannel($channel);

    Illuminate\Support\Facades\Log::channel($channel)->error('probe', ['password' => 'Hunter2-Hunter2']);

    $written = (string) file_get_contents($file);
    @unlink($file);

    expect($written)->toContain('[redacted]');
    expect($written)->not->toContain('Hunter2');
})->with(['json', 'alerts']);

test('password fields are never flashed back after a failed validation', function (): void {
    $this->from('/login')->post('/login', ['email' => 'not-an-email', 'password' => 'Secret-value-123']);

    expect(session()->getOldInput('password'))->toBeNull()
        ->and(session()->getOldInput('email'))->toBe('not-an-email');
});
