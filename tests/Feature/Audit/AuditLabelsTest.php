<?php

declare(strict_types=1);

use App\Models\Section;
use App\Modules\Audit\Services\AuditLabels;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Audit action labels resolve (FR-062 applied to the audit log)
|--------------------------------------------------------------------------
|
| This is the assertion the landing-page screenshot used to be relied on for, and never actually
| made: the rows are masked there now, because they are live and their timestamps move.
|
| Every action key contains a dot, and a dot is Laravel's path separator. `__('audit.actions.auth.login')`
| looks for a nested array the lang file does not have, so every row on the landing page read
| "إجراء غير معروف" — eight of them, in both locales, for every role, through all of Phase 4.
|
*/

test('all 38 contract actions have a label in both locales', function (): void {
    /*
     * The contract is the source, not a harvest of the source code.
     *
     * The first version scanned app/ for emitted keys, which answered a different question and
     * answered it wrongly: it demanded labels for `course.deleted` and `section.deleted`, keys the
     * Auditable trait *could* emit but that contracts/audit.md does not list and no implemented
     * flow produces. Whether the system emits anything outside the 38 is ActionCoverageTest's
     * question; whether each of the 38 can be displayed is this one's.
     */
    foreach (['ar', 'en'] as $locale) {
        app()->setLocale($locale);

        $unknown = __('audit.actions.unknown');

        foreach (contractActionKeys() as $action) {
            expect(AuditLabels::for($action))
                ->not->toBe($unknown, "[$locale] no label for the contract action '$action'");
        }
    }
});

test('the label file carries the 43 contract keys and nothing else', function (): void {
    foreach (['ar', 'en'] as $locale) {
        app()->setLocale($locale);

        /** @var array<string, string> $labels */
        $labels = (array) __('audit.actions');

        $declared = array_values(array_diff(array_keys($labels), ['unknown']));

        sort($declared);
        $contract = contractActionKeys();
        sort($contract);

        // A label for a key the contract does not define is a key somebody is about to emit.
        expect($declared)->toBe($contract, "[$locale] the label file has drifted from the contract");
    }
});

test('an action nobody has a label for falls back rather than printing the key', function (): void {
    app()->setLocale('ar');

    expect(AuditLabels::for('nonsense.action'))->toBe(__('audit.actions.unknown'))
        ->and(AuditLabels::for('nonsense.action'))->not->toContain('audit.actions');
});

test('the landing page renders labels, not raw keys', function (): void {
    $admin = aUser('SUPER_ADMIN');
    Section::factory()->count(2)->create();

    DB::table('audit_logs')->insert([
        'user_id' => $admin->id,
        'action' => 'auth.login',
        'ip_address' => '10.0.0.1',
        'created_at' => now(),
    ]);

    $html = $this->actingAs($admin)->get('/home')->assertOk()->getContent();

    app()->setLocale('ar');

    /*
     * Through AuditLabels, not __(). The first draft asserted against
     * `__('audit.actions.auth.login')`, which returns the key itself — the exact failure this class
     * exists to work around, reproduced inside its own test.
     */
    expect($html)->toContain(AuditLabels::for('auth.login'))
        ->and($html)->not->toContain('audit.actions.');
});
