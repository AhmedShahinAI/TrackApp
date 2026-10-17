<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| SEC-064 — the owner's decision D-5: addresses and browsers are the Super Admin's to see (G-13)
|--------------------------------------------------------------------------
|
| A coordinator reads their branch's audit trail, which records other people's network address and
| browser on every entry. Those are personal data about colleagues, not about the branch's work.
|
| The entries are written directly with a known address: under test the Auditor records its
| no-request placeholder (the app runs in the console), which would make "not shown" pass vacuously.
|
*/

function entryWithAddress(User $author, string $ip): void
{
    DB::table('audit_logs')->insert([
        'user_id' => $author->id,
        'action' => 'auth.login',
        'ip_address' => $ip,
        'user_agent' => 'Mozilla/5.0 (TestBrowser '.$ip.')',
        'created_at' => now(),
    ]);
}

test('a coordinator sees the entry but not the address or browser, on the API and the screen', function (): void {
    $coordinator = aUser('BRANCH_COORDINATOR');
    entryWithAddress($coordinator, '10.77.66.55');

    $entries = $this->actingAs($coordinator)->getJson('/api/v1/audit-logs')->assertOk()->collect('data');

    expect($entries->pluck('action')->all())->toContain('auth.login')
        ->and($entries->pluck('ip_address')->filter()->all())->toBe([])
        ->and($entries->pluck('user_agent')->filter()->all())->toBe([]);

    $this->actingAs($coordinator)->get('/audit')->assertOk()
        ->assertDontSee('10.77.66.55')
        ->assertDontSee('TestBrowser');
});

test('the Super Admin still sees addresses and browsers', function (): void {
    $admin = aUser('SUPER_ADMIN');
    entryWithAddress($admin, '10.88.77.66');

    $entries = $this->actingAs($admin)->getJson('/api/v1/audit-logs')->assertOk()->collect('data');

    expect($entries->pluck('ip_address')->all())->toContain('10.88.77.66')
        ->and($entries->pluck('user_agent')->filter()->implode(' '))->toContain('TestBrowser');

    $this->actingAs($admin)->get('/audit')->assertOk()->assertSee('10.88.77.66');
});
