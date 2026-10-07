<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Section;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T168 — the audit screen renders, in both locales, with and without entries
|--------------------------------------------------------------------------
|
| A render test rather than only a baseline, because a Blade error produces a 500 whose screenshot
| a visual suite will happily record as the new reference — which has happened twice in this
| codebase. This fails loudly instead.
|
*/

test('the screen renders for a Super Admin in both locales', function (string $locale): void {
    $admin = aUser('SUPER_ADMIN', ['locale' => $locale]);
    $branch = Branch::factory()->create(['organization_id' => anOrganisation()->id]);
    $branch->update(['campus_name_ar' => 'الزلفي']);

    $html = $this->actingAs($admin)->get('/audit')->assertOk()->getContent();

    expect($html)->toContain(__('nav.audit'))
        ->and($html)->toContain(__('audit.immutable_notice'))
        // The before/after disclosure, which only appears when an entry has a payload.
        ->and($html)->toContain('track-audit__diff')
        ->and($html)->not->toContain('audit.actions.');
})->with(['ar', 'en']);

test('the empty state appears when nothing matches', function (): void {
    $admin = aUser('SUPER_ADMIN');
    DB::table('audit_logs')->delete();

    $html = $this->actingAs($admin)->get('/audit?action=term.set_current')->assertOk()->getContent();

    expect($html)->toContain(__('audit.empty'));
});

test('the immutability notice is not dismissible', function (): void {
    $admin = aUser('SUPER_ADMIN');

    $html = $this->actingAs($admin)->get('/audit')->assertOk()->getContent();

    /*
     * FR-031. A notice someone can close is a notice most people will have closed by the time it
     * matters, and this one has to be true on the screen where somebody is deciding what to do
     * about an entry they think is wrong.
     */
    $notice = substr($html, (int) strpos($html, (string) __('audit.immutable_notice')) - 600, 700);

    expect($notice)->not->toContain('track-alert__dismiss');
});

test('the activity panel renders scoped entries for one record', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $section = Section::factory()->create();

    DB::table('audit_logs')->delete();
    DB::table('audit_logs')->insert([
        'user_id' => $admin->id, 'action' => 'section.updated',
        'entity_type' => 'Section', 'entity_id' => $section->id,
        'ip_address' => '10.0.0.1', 'created_at' => now(),
    ]);

    $html = $this->actingAs($admin)->get("/sections/{$section->id}")->assertOk()->getContent();

    expect($html)->toContain(App\Modules\Audit\Services\AuditLabels::for('section.updated'));
});
