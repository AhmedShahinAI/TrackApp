<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T159 — the audit log is scoped, and the filter cannot widen it (FR-033)
|--------------------------------------------------------------------------
|
| contracts/audit.md states the rule exactly: "a coordinator sees only entries whose entity resolves
| into their branches, plus their own actions. `branch_id` filtering is available to a Super Admin;
| for a coordinator it is implicit and cannot be widened."
|
| The second clause is the one worth testing hardest. A filter is a parameter the caller controls,
| and the natural implementation — `when($request->filled('branch_id'), ...)` — reads as a
| narrowing but becomes a widening the moment it is applied before the scope rather than inside it.
| Passing another branch's id must change nothing.
|
*/

/**
 * @return array{mine: Branch, theirs: Branch, coordinator: User}
 */
function twoBranchesForAudit(): array
{
    $organisation = anOrganisation();

    $mine = Branch::factory()->create(['organization_id' => $organisation->id]);
    $theirs = Branch::factory()->create(['organization_id' => $organisation->id]);

    $coordinator = aUser('BRANCH_COORDINATOR', ['organization_id' => $organisation->id]);
    $coordinator->coordinatorBranches()->sync([$mine->id]);
    $coordinator->forgetScopeCache();

    return ['mine' => $mine, 'theirs' => $theirs, 'coordinator' => $coordinator];
}

function anEntryAbout(string $type, int $id, ?int $actorId = null): void
{
    DB::table('audit_logs')->insert([
        'user_id' => $actorId,
        'action' => 'section.updated',
        'entity_type' => $type,
        'entity_id' => $id,
        'ip_address' => '10.0.0.1',
        'created_at' => now(),
    ]);
}

test('a coordinator sees entries about their own branch and not another', function (): void {
    ['mine' => $mine, 'theirs' => $theirs, 'coordinator' => $coordinator] = twoBranchesForAudit();

    $visible = Section::factory()->create(['branch_id' => $mine->id]);
    $hidden = Section::factory()->create(['branch_id' => $theirs->id]);

    $admin = aUser('SUPER_ADMIN');
    DB::table('audit_logs')->delete();

    anEntryAbout('Section', $visible->id, $admin->id);
    anEntryAbout('Section', $hidden->id, $admin->id);

    $ids = collect((array) test()->actingAs($coordinator)->getJson('/api/v1/audit-logs')->json('data'))
        ->pluck('entity_id');

    expect($ids)->toContain($visible->id)
        ->and($ids)->not->toContain($hidden->id);
});

test('a coordinator sees their own actions even when the entity is out of scope', function (): void {
    ['theirs' => $theirs, 'coordinator' => $coordinator] = twoBranchesForAudit();

    $elsewhere = Section::factory()->create(['branch_id' => $theirs->id]);

    DB::table('audit_logs')->delete();
    anEntryAbout('Section', $elsewhere->id, $coordinator->id);

    /*
     * "Plus their own actions" is not a convenience. Without it a person loses sight of what they
     * themselves did the moment it touched something outside their branches, and a trail its own
     * actor cannot read is not accountability.
     */
    $entries = (array) test()->actingAs($coordinator)->getJson('/api/v1/audit-logs')->json('data');

    expect($entries)->toHaveCount(1);
});

test('entries about students and enrollments in scope are visible too', function (): void {
    ['mine' => $mine, 'coordinator' => $coordinator] = twoBranchesForAudit();

    $section = Section::factory()->create(['branch_id' => $mine->id]);
    $student = Student::factory()->create();
    $enrollment = SectionEnrollment::factory()->create([
        'section_id' => $section->id, 'student_id' => $student->id, 'status' => 'active',
    ]);

    $admin = aUser('SUPER_ADMIN');
    DB::table('audit_logs')->delete();
    anEntryAbout('SectionEnrollment', $enrollment->id, $admin->id);

    $ids = collect((array) test()->actingAs($coordinator)->getJson('/api/v1/audit-logs')->json('data'))
        ->pluck('entity_id');

    expect($ids)->toContain($enrollment->id);
});

test('a coordinator cannot widen the scope with branch_id', function (): void {
    ['mine' => $mine, 'theirs' => $theirs, 'coordinator' => $coordinator] = twoBranchesForAudit();

    $visible = Section::factory()->create(['branch_id' => $mine->id]);
    $hidden = Section::factory()->create(['branch_id' => $theirs->id]);

    $admin = aUser('SUPER_ADMIN');
    DB::table('audit_logs')->delete();
    anEntryAbout('Section', $visible->id, $admin->id);
    anEntryAbout('Section', $hidden->id, $admin->id);

    // Asking for the other branch by name returns nothing from it — not an error, not a refusal.
    $ids = collect((array) test()->actingAs($coordinator)
        ->getJson('/api/v1/audit-logs?branch_id='.$theirs->id)->json('data'))
        ->pluck('entity_id');

    expect($ids)->not->toContain($hidden->id);
});

test('branch_id narrows for a Super Admin', function (): void {
    ['mine' => $mine, 'theirs' => $theirs] = twoBranchesForAudit();

    $here = Section::factory()->create(['branch_id' => $mine->id]);
    $there = Section::factory()->create(['branch_id' => $theirs->id]);

    $admin = aUser('SUPER_ADMIN');
    DB::table('audit_logs')->delete();
    anEntryAbout('Section', $here->id, $admin->id);
    anEntryAbout('Section', $there->id, $admin->id);

    $unfiltered = (array) test()->actingAs($admin)->getJson('/api/v1/audit-logs')->json('data');
    expect($unfiltered)->toHaveCount(2);

    $filtered = collect((array) test()->actingAs($admin)
        ->getJson('/api/v1/audit-logs?branch_id='.$mine->id)->json('data'))
        ->pluck('entity_id');

    expect($filtered)->toContain($here->id)
        ->and($filtered)->not->toContain($there->id);
});

/**
 * The text inside an .xlsx, as a single string.
 *
 * An xlsx is a zip, so a substring check against the streamed bytes is not an assertion — it either
 * matches compressed noise or it matches nothing, and either way it says nothing about the sheet.
 * This unpacks it and returns the parts that hold values.
 */
function textInsideXlsx(string $bytes): string
{
    $path = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
    file_put_contents($path, $bytes);

    $zip = new ZipArchive;
    expect($zip->open($path))->toBeTrue('the export is not a readable xlsx');

    $text = '';

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = (string) $zip->getNameIndex($i);

        if (str_contains($name, 'sharedStrings') || str_contains($name, 'worksheets/')) {
            $text .= (string) $zip->getFromIndex($i);
        }
    }

    $zip->close();
    @unlink($path);

    return $text;
}

test('the export carries exactly what the list returns, for the same caller', function (): void {
    ['mine' => $mine, 'theirs' => $theirs, 'coordinator' => $coordinator] = twoBranchesForAudit();

    $visible = Section::factory()->create(['branch_id' => $mine->id]);
    $hidden = Section::factory()->create(['branch_id' => $theirs->id]);

    $admin = aUser('SUPER_ADMIN');
    DB::table('audit_logs')->delete();

    anEntryAbout('Section', $visible->id, $admin->id);
    anEntryAbout('Section', $hidden->id, $admin->id);

    $coordinator->givePermissionTo('audit.export');
    $coordinator->forgetScopeCache();

    /*
     * The list is read first, deliberately. Exporting is itself an audited action recorded against
     * the caller, and a coordinator sees their own actions — so asking afterwards returns one more
     * row than the export contains, and the test would be comparing two different moments.
     */
    $listed = (array) test()->actingAs($coordinator->fresh())
        ->getJson('/api/v1/audit-logs')->json('data');

    $response = test()->actingAs($coordinator->fresh())->get('/api/v1/audit-logs/export')->assertOk();

    expect($response->headers->get('content-type'))
        ->toContain('spreadsheetml.sheet');

    $sheet = textInsideXlsx($response->streamedContent());

    /*
     * Counted, not searched. A bare id is a short digit string that turns up inside a zip's own
     * bookkeeping, so the first version of this test passed without reading the sheet at all.
     * Counting the rows is unambiguous: the list returns one entry for this caller, so the sheet
     * holds one data row.
     */
    $rows = substr_count($sheet, '<row ');

    // Four header lines, a blank, the column headings, and exactly one entry.
    expect($rows)->toBe(7, "the sheet holds $rows rows; the list returns one entry");

    expect($listed)->toHaveCount(1)
        ->and($listed[0]['entity_id'])->toBe($visible->id);

    expect($hidden->id)->not->toBe($visible->id);
});
