<?php

declare(strict_types=1);

use App\Models\Section;

/*
|--------------------------------------------------------------------------
| T476 — a coordinator's queue holds instructors' requests in their branches; a Super Admin's holds all
| (FR-177, FR-184)
|--------------------------------------------------------------------------
*/

/**
 * One pending section from each kind of creator, plus one from another branch's instructor.
 *
 * @param  array<string, mixed>  $world
 * @return array<string, Section>
 */
function queueFixtures(array $world): array
{
    $otherInstructor = aUser('INSTRUCTOR');
    Section::factory()->create(['branch_id' => $world['other']->id, 'term_id' => $world['term']->id, 'instructor_id' => $otherInstructor->id]);

    return [
        'instructor' => sectionCreatedBy($world['instructor'], $world),
        'own' => sectionCreatedBy($world['coordinator'], $world),
        'peer' => sectionCreatedBy($world['peer'], $world),
        'elsewhere' => sectionCreatedBy($otherInstructor, $world, ['branch_id' => $world['other']->id]),
    ];
}

/** @return list<int> */
function queueIds(App\Models\User $caller): array
{
    app('auth')->forgetGuards();

    return collect((array) test()->actingAs($caller)->getJson('/api/v1/sections/approvals')->assertOk()->json('data'))->pluck('id')->all();
}

test('a coordinator sees only instructors\' pending sections in their own branches', function (): void {
    $world = approvalWorld();
    $sections = queueFixtures($world);

    expect(queueIds($world['coordinator']))->toBe([$sections['instructor']->id]);
});

test('a Super Admin sees every pending section, oldest waiting first', function (): void {
    $world = approvalWorld();
    $sections = queueFixtures($world);
    $sections['peer']->forceFill(['created_at' => now()->subDays(3)])->saveQuietly();

    expect(queueIds($world['admin']))->toBe([
        $sections['peer']->id, $sections['instructor']->id, $sections['own']->id, $sections['elsewhere']->id,
    ]);
});

test('a decided or returned section leaves the queue', function (): void {
    $world = approvalWorld();
    $approved = sectionCreatedBy($world['instructor'], $world);
    $returned = sectionCreatedBy($world['instructor'], $world);
    decideSection($world['coordinator'], $approved, 'approve')->assertOk();
    decideSection($world['coordinator'], $returned, 'return', 'Wrong programme for this course.')->assertOk();

    expect(queueIds($world['coordinator']))->toBe([]);
});

test('each row names the creator with their role, the enrollment count and the waiting time', function (): void {
    $world = approvalWorld();
    sectionCreatedBy($world['instructor'], $world);

    app('auth')->forgetGuards();
    $row = $this->actingAs($world['coordinator'])->getJson('/api/v1/sections/approvals')->json('data.0');

    expect($row['creator'])->toMatchArray(['id' => $world['instructor']->id, 'role' => 'INSTRUCTOR'])
        ->and($row['active_enrollments'])->toBe(0)
        ->and($row['waiting_since'])->not->toBeNull()
        ->and($row['status'])->toBe('pending');
});

test('the queue screen renders for a coordinator, with the request on it', function (): void {
    $world = approvalWorld();
    $section = sectionCreatedBy($world['instructor'], $world);

    app('auth')->forgetGuards();
    $this->actingAs($world['coordinator'], 'web')->get(route('sections.approvals'))->assertOk()
        ->assertSee(__('sections.approval.title'))
        // Row links are written into Alpine's visit('…') with escaped slashes, so the number is asserted.
        ->assertSee($section->section_number)
        ->assertSee(__('sections.approval.status.pending'));
});

test('a page of the queue is a fixed number of queries', function (): void {
    $world = approvalWorld();
    $admin = $world['admin'];

    $count = function () use ($admin): int {
        app('auth')->forgetGuards();
        test()->actingAs($admin)->getJson('/api/v1/sections/approvals')->assertOk();
        Illuminate\Support\Facades\DB::flushQueryLog();
        Illuminate\Support\Facades\DB::enableQueryLog();
        test()->actingAs($admin)->getJson('/api/v1/sections/approvals')->assertOk();
        Illuminate\Support\Facades\DB::disableQueryLog();

        return count(Illuminate\Support\Facades\DB::getQueryLog());
    };

    sectionCreatedBy($world['instructor'], $world);
    $one = $count();

    foreach (range(1, 4) as $i) {
        sectionCreatedBy($world['instructor'], $world);
    }

    expect($count())->toBe($one);
});
