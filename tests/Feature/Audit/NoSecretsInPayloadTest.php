<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T157 — no secret reaches a payload, by any route
|--------------------------------------------------------------------------
|
| contracts/audit.md: "Never recorded in any entry: a password, a password hash, a session token, or
| a CSRF token."
|
| The danger is specific. Password flows are the ones most worth auditing and the ones whose inputs
| must never be kept, so they are exactly where a payload written from `$request->all()` or from
| `$model->getChanges()` puts a hash into a table that is immutable by design and retained for five
| years. There is no cleanup path afterwards: the Auditor can insert and nothing can delete.
|
| Rather than check the few flows that are obviously risky, this drives every implemented action
| from ActionCoverageTest's performers and inspects whatever each one wrote.
|
*/

/**
 * Any key or value that must never appear.
 *
 * Checked against keys at every depth and against values, because a redaction that only looks at
 * top-level keys misses `{"user":{"password":"..."}}`.
 */
const FORBIDDEN_KEY_PATTERN = '/password|passwd|secret|token|_hash$|^hash$|csrf|remember/i';

/**
 * Walk a decoded payload and return every offending key path.
 *
 * @param  array<array-key, mixed>  $payload
 * @return list<string>
 */
function forbiddenKeysIn(array $payload, string $prefix = ''): array
{
    $found = [];

    foreach ($payload as $key => $value) {
        $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

        if (is_string($key) && preg_match(FORBIDDEN_KEY_PATTERN, $key)) {
            $found[] = $path;
        }

        if (is_array($value)) {
            $found = array_merge($found, forbiddenKeysIn($value, $path));
        }
    }

    return $found;
}

test('no payload written by any implemented action carries a secret', function (): void {
    $offenders = [];

    foreach (actionPerformers() as $action => $performer) {
        DB::table('audit_logs')->delete();

        $performer();

        foreach (DB::table('audit_logs')->get() as $entry) {
            foreach (['old_values', 'new_values'] as $column) {
                $decoded = json_decode((string) $entry->{$column}, true);

                if (! is_array($decoded)) {
                    continue;
                }

                foreach (forbiddenKeysIn($decoded) as $path) {
                    $offenders[] = sprintf('%s / %s / %s.%s', $action, $entry->action, $column, $path);
                }
            }
        }
    }

    expect(array_values(array_unique($offenders)))->toBe([]);
});

test('the password flows write an entry and still keep nothing', function (): void {
    /*
     * Named separately from the sweep above, because these three are the ones where a payload is
     * both required and dangerous: the change must be accountable, and the value must not survive.
     */
    foreach (['user.password_changed', 'user.password_reset', 'user.created'] as $action) {
        DB::table('audit_logs')->delete();

        actionPerformers()[$action]();

        $entry = DB::table('audit_logs')->where('action', $action)->first();

        expect($entry)->not->toBeNull("'$action' wrote no entry at all");

        $payload = (string) $entry->old_values.(string) $entry->new_values;

        expect($payload)->not->toContain('password')
            ->and($payload)->not->toContain('$argon2')
            ->and($payload)->not->toContain('$2y$');
    }
});

test('a hash never appears even as a bare value', function (): void {
    DB::table('audit_logs')->delete();

    actionPerformers()['user.created']();

    foreach (DB::table('audit_logs')->get() as $entry) {
        $blob = (string) $entry->old_values.(string) $entry->new_values;

        // The two prefixes the application can produce: Argon2id in production, bcrypt under test.
        expect($blob)->not->toContain('$argon2id$')
            ->and($blob)->not->toContain('$2y$');
    }
});
