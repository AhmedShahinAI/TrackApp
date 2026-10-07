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

test('every action the application records has a label in both locales', function (): void {
    /*
     * Harvested from the source rather than hand-listed, so an action added next month without a
     * translation fails here instead of rendering as "unknown" to a user.
     */
    $recorded = [];

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path()));

    foreach ($files as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        preg_match_all(
            "/record\(\s*'([a-z_]+\.[a-z_]+)'/",
            (string) file_get_contents($file->getPathname()),
            $matches,
        );

        $recorded = array_merge($recorded, $matches[1]);
    }

    /*
     * The trait-generated half. Auditable turns every created/updated/deleted event on an audited
     * model into `{snake(class_basename)}.{event}` without any literal appearing in the source, so
     * harvesting `record(...)` alone found 1 missing label where there were 23.
     */
    foreach (glob(app_path('Models/*.php')) ?: [] as $model) {
        $source = (string) file_get_contents($model);

        if (! str_contains($source, 'use App\Modules\Audit\Concerns\Auditable;')) {
            continue;
        }

        $key = str(basename($model, '.php'))->snake()->toString();

        foreach (['created', 'updated', 'deleted'] as $event) {
            $recorded[] = $key.'.'.$event;
        }
    }

    $recorded = array_values(array_unique($recorded));
    expect($recorded)->not->toBeEmpty();

    foreach (['ar', 'en'] as $locale) {
        app()->setLocale($locale);

        $unknown = __('audit.actions.unknown');

        foreach ($recorded as $action) {
            expect(AuditLabels::for($action))
                ->not->toBe($unknown, "[$locale] no label for the recorded action '$action'");
        }
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
