<?php

declare(strict_types=1);

use App\Models\AcademicTerm;
use App\Models\Branch;
use App\Models\Course;
use App\Models\ImportJob;
use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T135 — out of scope is indistinguishable from absent (FR-024)
|--------------------------------------------------------------------------
|
| 404, never 403. The distinction is the whole of constitution principle I: a 403 confirms the
| record exists, and a coordinator who can tell "forbidden" from "absent" can enumerate another
| branch's section ids one request at a time until the numbering of the entire college is known.
|
| Every access path is asserted separately, because they fail independently. A global scope covers
| the list and the export; route-model binding covers the typed address; and it is entirely possible
| to get one right and leave the other open — which is why FR-023 names all of them.
|
*/

/**
 * Two branches that share nothing, and a coordinator who owns exactly one of them.
 *
 * @return array{mine: Branch, theirs: Branch, coordinator: User}
 */
function twoBranches(): array
{
    $organisation = anOrganisation();

    $mine = Branch::factory()->create(['organization_id' => $organisation->id]);
    $theirs = Branch::factory()->create(['organization_id' => $organisation->id]);

    $coordinator = aUser('BRANCH_COORDINATOR', ['organization_id' => $organisation->id]);
    $coordinator->coordinatorBranches()->sync([$mine->id]);
    $coordinator->forgetScopeCache();

    return ['mine' => $mine, 'theirs' => $theirs, 'coordinator' => $coordinator];
}

/**
 * A section in a given branch, with one enrolled student.
 *
 * @return array{section: Section, student: Student, enrollment: SectionEnrollment}
 */
function sectionIn(Branch $branch): array
{
    $term = AcademicTerm::query()->first() ?? AcademicTerm::factory()->create();
    $course = Course::factory()->create(['organization_id' => $branch->organization_id]);

    $section = Section::factory()->create([
        'branch_id' => $branch->id,
        'term_id' => $term->id,
        'course_id' => $course->id,
    ]);

    $student = Student::factory()->create();
    $enrollment = SectionEnrollment::factory()->create([
        'section_id' => $section->id,
        'student_id' => $student->id,
        'status' => 'active',
    ]);

    return ['section' => $section, 'student' => $student, 'enrollment' => $enrollment];
}

test('a section in another branch is absent through every path', function (): void {
    ['theirs' => $theirs, 'coordinator' => $coordinator] = twoBranches();
    $foreign = sectionIn($theirs)['section'];

    // The typed address, as JSON and as a screen.
    $this->actingAs($coordinator)->getJson("/api/v1/sections/{$foreign->id}")->assertNotFound();
    $this->actingAs($coordinator)->get("/sections/{$foreign->id}")->assertNotFound();

    // The list.
    $listed = (array) $this->actingAs($coordinator)->getJson('/api/v1/sections')->assertOk()->json('data');
    expect(collect($listed)->pluck('id'))->not->toContain($foreign->id);

    // The export.
    $export = $this->actingAs($coordinator)->get('/api/v1/sections/export')->assertOk();
    expect($export->streamedContent())->not->toContain((string) $foreign->section_number);
});

test('a student enrolled only in another branch is absent', function (): void {
    ['theirs' => $theirs, 'coordinator' => $coordinator] = twoBranches();
    $foreign = sectionIn($theirs)['student'];

    $this->actingAs($coordinator)->getJson("/api/v1/students/{$foreign->id}")->assertNotFound();

    $listed = (array) $this->actingAs($coordinator)->getJson('/api/v1/students')->assertOk()->json('data');
    expect(collect($listed)->pluck('id'))->not->toContain($foreign->id);
});

test('an enrollment in another branch is absent', function (): void {
    ['theirs' => $theirs, 'coordinator' => $coordinator] = twoBranches();
    $foreign = sectionIn($theirs)['enrollment'];

    expect(SectionEnrollment::query()->whereKey($foreign->id)->exists())->toBeTrue();

    $this->actingAs($coordinator)->get('/home');

    // Through the scoped query the row is simply not there.
    $visible = $this->actingAs($coordinator)->getJson('/api/v1/students')->assertOk();
    expect($visible->json('data'))->not->toContain($foreign->student_id);
});

test('an import job for another branch is absent', function (): void {
    ['theirs' => $theirs, 'coordinator' => $coordinator] = twoBranches();
    $foreignSection = sectionIn($theirs)['section'];

    $job = ImportJob::query()->create([
        'user_id' => aUser('SUPER_ADMIN')->id,
        'mode' => 'section',
        'section_id' => $foreignSection->id,
        'original_filename' => 'roster.xlsx',
        'file_path' => 'imports/roster.xlsx',
        'file_hash' => str_repeat('a', 64),
        'status' => 'completed',
    ]);

    $this->actingAs($coordinator)->getJson("/api/v1/imports/{$job->id}")->assertNotFound();
});

test('an audit entry about another branch is absent', function (): void {
    ['theirs' => $theirs, 'coordinator' => $coordinator] = twoBranches();
    $foreignSection = sectionIn($theirs)['section'];

    $admin = aUser('SUPER_ADMIN');
    DB::table('audit_logs')->insert([
        'user_id' => $admin->id,
        'action' => 'section.updated',
        'entity_type' => 'Section',
        'entity_id' => $foreignSection->id,
        'ip_address' => '10.0.0.1',
        'created_at' => now(),
    ]);

    $entries = (array) $this->actingAs($coordinator)->getJson('/api/v1/audit-logs')->assertOk()->json('data');

    expect(collect($entries)->where('entity_id', $foreignSection->id))->toBeEmpty();
});

test('the refusal is 404 and not 403, and says nothing about what exists', function (): void {
    ['theirs' => $theirs, 'coordinator' => $coordinator] = twoBranches();
    $foreign = sectionIn($theirs)['section'];

    $response = $this->actingAs($coordinator)->getJson("/api/v1/sections/{$foreign->id}");

    $response->assertNotFound();
    expect($response->status())->not->toBe(403);

    /*
     * The body carries the generic refusal and nothing else. Asserted as an exact shape rather
     * than as "does not contain the id", which was the first attempt and a false positive: the
     * Arabic message JSON-escapes to ا...0629, and a substring check for the id "2" matched
     * a digit inside an escape sequence.
     */
    $body = json_decode((string) $response->getContent(), true);

    expect(array_keys($body))->toBe(['message'])
        ->and($body['message'])->toBe(__('authorization.refusal.missing_title'))
        ->and($body['message'])->not->toContain('Section')
        ->and($body['message'])->not->toContain('No query results');
});

test('a Super Admin reaches every one of them', function (): void {
    ['theirs' => $theirs] = twoBranches();
    ['section' => $section, 'student' => $student] = sectionIn($theirs);

    $admin = aUser('SUPER_ADMIN');

    $this->actingAs($admin)->getJson("/api/v1/sections/{$section->id}")->assertOk();
    $this->actingAs($admin)->getJson("/api/v1/students/{$student->id}")->assertOk();
});

test('an import job naming a section the caller lost is absent, even though they ran it', function (): void {
    $instructor = aUser('INSTRUCTOR');
    $section = Section::factory()->create(['instructor_id' => $instructor->id]);

    $job = ImportJob::query()->create([
        'user_id' => $instructor->id,
        'mode' => 'section',
        'section_id' => $section->id,
        'original_filename' => 'roster.xlsx',
        'file_path' => 'imports/roster.xlsx',
        'file_hash' => str_repeat('c', 64),
        'status' => 'completed',
    ]);

    $this->actingAs($instructor->fresh())->getJson("/api/v1/imports/{$job->id}")->assertOk();

    // The section moves to someone else; the import that names it goes with it.
    $section->forceFill(['instructor_id' => aUser('INSTRUCTOR')->id])->saveQuietly();

    /*
     * The "own jobs" allowance exists for registrar and course imports, which target a whole term
     * and name no section. Left ungated it re-admitted this one — the row counts of a section the
     * caller can no longer open.
     */
    $this->actingAs($instructor->fresh())->getJson("/api/v1/imports/{$job->id}")->assertNotFound();
});

test('a section-less import stays visible to whoever ran it', function (): void {
    $instructor = aUser('INSTRUCTOR');

    $job = ImportJob::query()->create([
        'user_id' => $instructor->id,
        'mode' => 'registrar',
        'original_filename' => 'term.xlsx',
        'file_path' => 'imports/term.xlsx',
        'file_hash' => str_repeat('d', 64),
        'status' => 'completed',
    ]);

    // The other half of the rule: without this, the person who started an import cannot watch it.
    $this->actingAs($instructor->fresh())->getJson("/api/v1/imports/{$job->id}")->assertOk();
});
