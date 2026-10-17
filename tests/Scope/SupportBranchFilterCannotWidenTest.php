<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| T435 — another branch's branch_id returns the coordinator's own scope, not an error
|--------------------------------------------------------------------------
|
| An error for "not your branch" and an empty page for "your branch, no requests" would be two answers,
| and the difference is a probe: it says which branch ids exist, and which have requests. One answer —
| the caller's own scope — says neither.
|
*/

test('a foreign branch id and a nonexistent one both answer with the coordinator\'s own queue', function (): void {
    // Whole responses are compared, and they carry a waiting time: the clock must not tick between them.
    $this->freezeTime();
    $world = supportStaffWorld();
    $mine = supportRequestFor($world['branch']);
    supportRequestFor($world['other']);

    $own = $this->actingAs($world['coordinator'])->getJson('/api/v1/support-requests')->assertOk();

    foreach ([$world['other']->id, 999999] as $branchId) {
        $probe = $this->actingAs($world['coordinator'])->getJson('/api/v1/support-requests?branch_id='.$branchId)->assertOk();

        expect($probe->json('data'))->toBe($own->json('data'))
            ->and($probe->json('meta.total'))->toBe(1)
            ->and($probe->json('data.0.reference'))->toBe($mine->display_reference);
    }
});

test('a coordinator of two branches can narrow to either of their own', function (): void {
    $world = supportStaffWorld();
    $world['coordinator']->coordinatorBranches()->sync([$world['branch']->id, $world['other']->id]);
    $a = supportRequestFor($world['branch']);
    $b = supportRequestFor($world['other']);

    $this->actingAs($world['coordinator'])->getJson('/api/v1/support-requests?branch_id='.$world['other']->id)
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.reference', $b->display_reference);

    expect($a->id)->not->toBe($b->id);
});

test('the branch column and filter appear only where there is more than one branch to tell apart (T442)', function (): void {
    $world = supportStaffWorld();
    supportRequestFor($world['branch']);

    $single = (string) $this->actingAs($world['coordinator'])->get('/support-requests')->getContent();

    // Every cell names its column (data-label, for the stacked phone layout): no cell names the branch.
    expect($single)->not->toContain('name="branch_id"')
        ->and($single)->toContain('data-label="'.__('support.staff.fields.category').'"')
        ->and($single)->not->toContain('data-label="'.__('support.staff.fields.branch').'"');
});

test('the Super Admin gets the branch column and filter', function (): void {
    $world = supportStaffWorld();
    supportRequestFor($world['branch']);

    $html = (string) $this->actingAs($world['admin'])->get('/support-requests')->getContent();

    expect($html)->toContain('name="branch_id"')
        ->and($html)->toContain('data-label="'.__('support.staff.fields.branch').'"');
});

test('a coordinator of several branches gets the column and filter, to narrow between their own (T442)', function (): void {
    $world = supportStaffWorld();
    $world['coordinator']->coordinatorBranches()->sync([$world['branch']->id, $world['other']->id]);
    supportRequestFor($world['branch']);

    $html = (string) $this->actingAs($world['coordinator'])->get('/support-requests')->getContent();

    expect($html)->toContain('name="branch_id"')
        ->and($html)->toContain('data-label="'.__('support.staff.fields.branch').'"');
});

test('the branch column names only branches that grant visibility (Phase 23 review)', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch']);

    // In the other branch: an enrollment the registrar has withdrawn, and one in a section still pending.
    App\Models\SectionEnrollment::factory()->create([
        'student_id' => $request->student_id,
        'academic_status' => 'withdrawn',
        'section_id' => App\Models\Section::factory()->create(['branch_id' => $world['other']->id, 'term_id' => $world['term']->id])->id,
    ]);
    App\Models\SectionEnrollment::factory()->create([
        'student_id' => $request->student_id,
        'section_id' => App\Models\Section::factory()->pending()->create(['branch_id' => $world['other']->id, 'term_id' => $world['term']->id])->id,
    ]);

    $branches = $this->actingAs($world['admin'])->getJson('/api/v1/support-requests')->json('data.0.branches');

    expect($branches)->toBe([$world['branch']->campus_name_ar]);
});

test('narrowing a single-branch coordinator to their own branch adds no second scope (Phase 23 review)', function (): void {
    $world = supportStaffWorld();
    supportRequestFor($world['branch']);
    $this->actingAs($world['coordinator']);

    $sqlFor = function (string $query): array {
        Illuminate\Support\Facades\DB::flushQueryLog();
        Illuminate\Support\Facades\DB::enableQueryLog();
        $this->getJson('/api/v1/support-requests'.$query)->assertOk();
        $sql = collect(Illuminate\Support\Facades\DB::getQueryLog())->pluck('query')
            ->filter(fn (string $q): bool => str_contains($q, 'from "support_requests"'))->values()->all();
        Illuminate\Support\Facades\DB::disableQueryLog();

        return $sql;
    };

    expect($sqlFor('?branch_id='.$world['branch']->id))->toBe($sqlFor(''));
});

test('a name search cannot be crowded out by other branches\' students (Phase 23 review)', function (): void {
    $world = supportStaffWorld();

    // 600 students named the same, in no branch of the coordinator's, created FIRST, so they hold the
    // lowest ids — the ones an uncapped-then-limited match would keep.
    $rows = [];
    foreach (range(1, 600) as $i) {
        $rows[] = ['university_id' => '45'.str_pad((string) $i, 7, '0', STR_PAD_LEFT), 'name_ar' => 'محمد الغريب', 'name_en' => 'Mohammed Stranger', 'created_at' => now(), 'updated_at' => now()];
    }
    App\Models\Student::query()->withoutGlobalScopes()->insert($rows);

    $mine = supportRequestFor($world['branch']);

    App\Models\Student::query()->withoutGlobalScopes()->whereKey($mine->student_id)->update(['name_en' => 'Mohammed Mine']);

    $this->actingAs($world['coordinator'])
        ->getJson('/api/v1/support-requests?q=Mohammed')
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.reference', $mine->display_reference);
});
