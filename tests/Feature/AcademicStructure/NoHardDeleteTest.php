<?php

declare(strict_types=1);

use App\Models\AcademicTerm;
use App\Models\Branch;
use App\Models\Section;

/*
|--------------------------------------------------------------------------
| T178 — nothing that holds records can be destroyed (FR-049, constitution V)
|--------------------------------------------------------------------------
|
| A branch holds sections, which hold enrollments, which hold the history the platform exists to
| keep. Deactivation takes it out of use; deletion would take the history with it.
|
| Asserted as the absence of a route rather than as a refusal inside one, because the strongest form
| of "this cannot happen" is that there is nowhere to ask.
|
*/

test('no DELETE route exists for any structural entity', function (string $entity): void {
    $deleteRoutes = collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn ($route) => in_array('DELETE', $route->methods(), true))
        ->filter(fn ($route) => str_contains($route->uri(), $entity))
        ->map(fn ($route) => implode('|', $route->methods()).' '.$route->uri())
        ->values()
        ->all();

    expect($deleteRoutes)->toBe([]);
})->with(['terms', 'branches', 'courses', 'sections', 'students', 'enrollments', 'users']);

test('deactivation is the removal path for a branch, and it keeps its sections', function (): void {
    $admin = aUser('SUPER_ADMIN');

    $branch = Branch::factory()->create(['organization_id' => anOrganisation()->id, 'is_active' => true]);
    $sections = Section::factory()->count(3)->create(['branch_id' => $branch->id]);

    test()->actingAs($admin)
        ->putJson("/api/v1/branches/{$branch->id}", ['is_active' => false])
        ->assertOk();

    expect($branch->fresh()->is_active)->toBeFalse()
        ->and($branch->fresh()->exists)->toBeTrue();

    // Every section still readable, still naming the branch it belongs to.
    foreach ($sections as $section) {
        expect($section->fresh()->branch_id)->toBe($branch->id);
    }
});

test('archiving is the removal path for a term, and it keeps its sections', function (): void {
    $admin = aUser('SUPER_ADMIN');

    $term = AcademicTerm::factory()->create(['status' => 'closed']);
    $sections = Section::factory()->count(2)->create(['term_id' => $term->id]);

    test()->actingAs($admin)
        ->patchJson("/api/v1/terms/{$term->id}/status", ['status' => 'archived', 'reason' => 'انتهى'])
        ->assertOk();

    expect($term->fresh()->status)->toBe('archived')
        ->and($term->fresh()->exists)->toBeTrue();

    foreach ($sections as $section) {
        expect($section->fresh()->term_id)->toBe($term->id);
    }
});

test('an archived term leaves the default list but is still reachable by name', function (): void {
    $admin = aUser('SUPER_ADMIN');

    $live = AcademicTerm::factory()->create(['status' => 'active']);
    $archived = AcademicTerm::factory()->create(['status' => 'archived']);

    $default = collect((array) test()->actingAs($admin)
        ->getJson('/api/v1/terms')->assertOk()->json('data'))->pluck('id');

    expect($default)->toContain($live->id)
        ->and($default)->not->toContain($archived->id);

    /*
     * Out of the way, not gone. An archive that cannot be opened is a deletion with extra steps,
     * and the records inside it are what FR-049 protects.
     */
    $asked = collect((array) test()->actingAs($admin)
        ->getJson('/api/v1/terms?status=archived')->assertOk()->json('data'))->pluck('id');

    expect($asked)->toContain($archived->id);
});

test('the branches screen states that deactivation is the only removal', function (): void {
    $admin = aUser('SUPER_ADMIN');

    $html = test()->actingAs($admin)->get('/branches')->assertOk()->getContent();

    app()->setLocale('ar');

    // Said on the screen, not only enforced in the code: someone looking for a delete button needs
    // to know it is deliberately absent before they go hunting for it.
    expect($html)->toContain(__('branches.notice'));
});
