<?php

declare(strict_types=1);

use App\Models\ImportJob;
use App\Models\Section;
use App\Models\SectionAccessGrant;
use App\Models\SectionEnrollment;
use App\Modules\Imports\Jobs\CommitImportChunkJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

/*
|--------------------------------------------------------------------------
| T251 — an instructor cannot import into a section they neither teach nor hold a grant for (FR-054)
|--------------------------------------------------------------------------
|
| 404, not 403: a 403 would confirm the section exists, and an instructor who could enumerate other
| instructors' section ids by status code has learned something FR-024 says they may not. The attempt
| is still recorded, as `access.denied`, because a refusal nobody can see is a refusal nobody reviews.
|
*/

test('a foreign section is a 404 on every import route, and the attempt is audited', function (): void {
    $world = sectionWorld();
    $outsider = aUser('INSTRUCTOR');
    $foreign = $world['section'];

    DB::table('audit_logs')->delete();

    $this->actingAs($outsider)
        ->post(route('api.v1.sections.imports.store', $foreign), ['file' => rosterFile(sectionRows(1), 'list.xlsx', SECTION_HEADERS)], ['Accept' => 'application/json'])
        ->assertNotFound();

    $this->actingAs($outsider)->get(route('sections.import', $foreign))->assertNotFound();
    $this->actingAs($outsider)->get(route('sections.import.template', $foreign))->assertNotFound();

    expect(DB::table('audit_logs')->where('action', 'access.denied')->where('user_id', $outsider->id)->count())->toBe(3)
        ->and(ImportJob::query()->withoutGlobalScopes()->count())->toBe(0);
});

test('the 404 says nothing about what exists', function (): void {
    $world = sectionWorld();

    $foreign = $this->actingAs(aUser('INSTRUCTOR'))
        ->post(route('api.v1.sections.imports.store', $world['section']), ['file' => rosterFile(sectionRows(1), 'list.xlsx', SECTION_HEADERS)], ['Accept' => 'application/json']);

    $missing = $this->actingAs(aUser('INSTRUCTOR'))
        ->post(route('api.v1.sections.imports.store', 999999), ['file' => rosterFile(sectionRows(1), 'list.xlsx', SECTION_HEADERS)], ['Accept' => 'application/json']);

    expect($foreign->status())->toBe(404)
        ->and($foreign->getContent())->toBe($missing->getContent());
});

test('a section held through an access grant may be imported into', function (): void {
    $world = sectionWorld();
    $granted = aUser('INSTRUCTOR');

    SectionAccessGrant::query()->create([
        'section_id' => $world['section']->id,
        'user_id' => $granted->id,
        'granted_by' => $world['admin']->id,
        'granted_at' => now(),
    ]);

    $job = importSectionList($granted->fresh(), $world['section'], sectionRows(2));

    expect($job->imported)->toBe(2);
});

test('a coordinator who can see the section, but teaches none, is refused with 403', function (): void {
    $world = sectionWorld();
    $coordinator = aUser('BRANCH_COORDINATOR');
    $coordinator->coordinatorBranches()->sync([$world['branch']->id]);

    // The section is in their scope (the GET works), so the refusal is a permission, not a 404.
    $this->actingAs($coordinator->fresh())->getJson(route('api.v1.sections.show', $world['section']))->assertOk();

    $this->actingAs($coordinator->fresh())
        ->post(route('api.v1.sections.imports.store', $world['section']), ['file' => rosterFile(sectionRows(1), 'list.xlsx', SECTION_HEADERS)], ['Accept' => 'application/json'])
        ->assertForbidden();
});

test('a Super Admin may import into any section, as the reference allows', function (): void {
    $world = sectionWorld();

    expect(importSectionList($world['admin'], $world['section'], sectionRows(1))->imported)->toBe(1);
});

test('an instructor whose section moves to someone else mid-import stops writing to it', function (): void {
    $world = sectionWorld();

    $job = uploadSectionList($world['instructor'], $world['section'], rosterFile(sectionRows(600), 'list.xlsx', SECTION_HEADERS));
    $job = mapAndValidate($world['instructor'], $job);
    $this->actingAs($world['instructor'])->getJson(route('api.v1.imports.preview', $job))->assertOk();

    $queue = Queue::fake();
    $this->actingAs($world['instructor'])->postJson(route('api.v1.imports.commit', $job), ['confirm' => true])->assertAccepted();
    $queue->pushed(CommitImportChunkJob::class)->first()->handle(); // chunk 1, while they still teach it

    Section::query()->withoutGlobalScopes()->whereKey($world['section']->id)->update(['instructor_id' => aUser('INSTRUCTOR')->id]);

    $queue->pushed(CommitImportChunkJob::class)[1]->handle();

    expect(ImportJob::query()->withoutGlobalScopes()->find($job->id)?->status)->toBe('failed')
        ->and(SectionEnrollment::query()->withoutGlobalScopes()->where('section_id', $world['section']->id)->count())->toBe(500);
});
