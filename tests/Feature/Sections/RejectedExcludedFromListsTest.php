<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| T482 — rejected sections are out of the default lists; pending and returned are in, badged
| (FR-181, FR-183)
|--------------------------------------------------------------------------
*/

/**
 * @param  array<string, mixed>  $world
 * @return array<string, App\Models\Section>
 */
function sectionsInEveryState(array $world): array
{
    $sections = [
        'active' => sectionCreatedBy($world['admin'], $world),
        'pending' => sectionCreatedBy($world['instructor'], $world),
        'returned' => sectionCreatedBy($world['instructor'], $world),
        'rejected' => sectionCreatedBy($world['instructor'], $world),
    ];
    decideSection($world['coordinator'], $sections['returned'], 'return', 'Wrong programme for this course.')->assertOk();
    decideSection($world['coordinator'], $sections['rejected'], 'reject', 'This section duplicates another one.')->assertOk();

    return $sections;
}

/**
 * @param  array<string, mixed>  $query
 * @return list<int>
 */
function listedSectionIds(App\Models\User $caller, array $query = []): array
{
    app('auth')->forgetGuards();

    return collect((array) test()->actingAs($caller)->getJson('/api/v1/sections?'.http_build_query($query + ['per_page' => 100]))->assertOk()->json('data'))->pluck('id')->all();
}

test('the default list leaves out rejected sections and keeps pending and returned ones', function (): void {
    $world = approvalWorld();
    $sections = sectionsInEveryState($world);

    $listed = listedSectionIds($world['admin']);

    expect($listed)->toContain($sections['active']->id, $sections['pending']->id, $sections['returned']->id);
    expect($listed)->not->toContain($sections['rejected']->id);
});

test('status=rejected lists the rejected ones, and only them', function (): void {
    $world = approvalWorld();
    $sections = sectionsInEveryState($world);

    expect(listedSectionIds($world['admin'], ['status' => 'rejected']))->toBe([$sections['rejected']->id]);
});

test('an unknown status matches nothing rather than everything', function (): void {
    $world = approvalWorld();
    sectionsInEveryState($world);

    expect(listedSectionIds($world['admin'], ['status' => 'nonsense']))->toBe([]);
});

test('created_by narrows to one creator', function (): void {
    $world = approvalWorld();
    $sections = sectionsInEveryState($world);

    expect(listedSectionIds($world['admin'], ['created_by' => $world['admin']->id]))->toBe([$sections['active']->id]);
});

test('the list screen badges pending and returned sections in the section vocabulary', function (): void {
    $world = approvalWorld();
    sectionsInEveryState($world);

    app('auth')->forgetGuards();
    $this->actingAs($world['admin'], 'web')->get(route('sections.index', ['per_page' => 100]))->assertOk()
        ->assertSee(__('sections.approval.status.pending'))
        ->assertSee(__('sections.approval.status.returned'))
        ->assertDontSee(__('sections.approval.status.rejected'));
});
