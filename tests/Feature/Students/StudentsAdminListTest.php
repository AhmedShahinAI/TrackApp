<?php

declare(strict_types=1);

use App\Models\AcademicTerm;
use App\Models\Branch;
use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Models\Student;
use App\Models\SupportRequest;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T468 — the students list, extended to v2's StudentsAdminScreen (Phase 26)
|--------------------------------------------------------------------------
|
| What is adopted: the programme, degree and activity columns (Amendment 1), the sections held this
| term, open requests, and the search, branch, section and programme filters. What is not: anything
| that needs a student account or contact (C-19 … C-21 retired, C-23 blocked) and the student import
| (C-24).
|
*/

/**
 * Two branches, a current term, a section in each, and a student enrolled in each section.
 *
 * @return array{term: AcademicTerm, branch: Branch, other: Branch, mine: Section, theirs: Section, student: Student, stranger: Student}
 */
function studentsAdminWorld(): array
{
    (new SettingSeeder)->run();

    $term = AcademicTerm::query()->where('is_current', true)->first() ?? AcademicTerm::factory()->current()->create();
    $branch = Branch::factory()->create();
    $other = Branch::factory()->create();

    $mine = Section::factory()->create(['branch_id' => $branch->id, 'term_id' => $term->id, 'degree' => 'intermediate_diploma', 'activity' => 'lecture']);
    $theirs = Section::factory()->create(['branch_id' => $other->id, 'term_id' => $term->id]);

    $student = Student::factory()->create(['university_id' => '441100001', 'name_ar' => 'طالب الفرع']);
    $stranger = Student::factory()->create(['university_id' => '442200002', 'name_ar' => 'طالب فرع آخر']);

    SectionEnrollment::factory()->create(['student_id' => $student->id, 'section_id' => $mine->id]);
    SectionEnrollment::factory()->create(['student_id' => $stranger->id, 'section_id' => $theirs->id]);

    return compact('term', 'branch', 'other', 'mine', 'theirs', 'student', 'stranger');
}

function coordinatorOf(Branch $branch): User
{
    $coordinator = aUser('BRANCH_COORDINATOR');
    $coordinator->coordinatorBranches()->sync([$branch->id]);

    return $coordinator;
}

test('each row carries the programme, degree, activity and sections held this term', function (): void {
    $world = studentsAdminWorld();
    $program = $world['mine']->program;

    $html = (string) $this->actingAs(aUser('SUPER_ADMIN'))->get(route('students.index'))->assertOk()->getContent();

    $degree = collect(App\Modules\Settings\Settings::options(App\Modules\Settings\Settings::SECTION_DEGREES))->firstWhere('value', 'intermediate_diploma');

    expect($html)->toContain(e($program->name_ar))
        ->toContain(e($degree['ar']))
        ->toContain(__('students.fields.program'))
        ->toContain(__('students.fields.degree'))
        ->toContain(__('students.fields.activity'))
        ->toContain(__('students.fields.sections'));
});

test('the search takes a name or the start of a University ID', function (): void {
    $world = studentsAdminWorld();
    $admin = aUser('SUPER_ADMIN');

    $this->actingAs($admin)->get(route('students.index', ['q' => '4411']))->assertOk()
        ->assertSee($world['student']->name_ar)->assertDontSee($world['stranger']->name_ar);

    $this->actingAs($admin)->get(route('students.index', ['q' => 'فرع آخر']))->assertOk()
        ->assertSee($world['stranger']->name_ar)->assertDontSee('طالب الفرع<', false);
});

test('the branch, section and programme filters narrow to students holding a matching section', function (): void {
    $world = studentsAdminWorld();
    $admin = aUser('SUPER_ADMIN');

    foreach ([
        ['branch_id' => $world['branch']->id],
        ['section_id' => $world['mine']->id],
        ['program_id' => $world['mine']->program_id],
    ] as $filter) {
        $this->actingAs($admin)->get(route('students.index', $filter))->assertOk()
            ->assertSee($world['student']->name_ar)
            ->assertDontSee($world['stranger']->name_ar);
    }
});

test('a coordinator naming another branch gets nothing from it, not a way into it', function (): void {
    $world = studentsAdminWorld();
    $coordinator = coordinatorOf($world['branch']);

    $this->actingAs($coordinator)->get(route('students.index'))->assertOk()
        ->assertSee($world['student']->name_ar)->assertDontSee($world['stranger']->name_ar);

    $this->actingAs($coordinator)->get(route('students.index', ['branch_id' => $world['other']->id]))->assertOk()
        ->assertDontSee($world['stranger']->name_ar)
        ->assertDontSee($world['student']->name_ar);

    // Their branch list offers only what they can see: one branch, so no branch filter at all.
    $this->actingAs($coordinator)->get(route('students.index'))->assertDontSee('name="branch_id"', false);
});

test('open requests are counted for those who may see support, and never shown to an instructor (FR-142)', function (): void {
    $world = studentsAdminWorld();
    SupportRequest::factory()->create(['student_id' => $world['student']->id, 'term_id' => $world['term']->id]);
    $closed = SupportRequest::factory()->create(['student_id' => $world['student']->id, 'term_id' => $world['term']->id]);
    $closed->forceFill(['status' => 'closed'])->saveQuietly();

    $html = (string) $this->actingAs(aUser('SUPER_ADMIN'))->get(route('students.index'))->assertOk()->getContent();

    expect($html)->toContain(__('students.fields.open_requests'))
        ->toContain('<span class="track-students__open">1</span>');
});

test('an instructor never sees the open-requests column, though their student has one (FR-142)', function (): void {
    $world = studentsAdminWorld();
    SupportRequest::factory()->create(['student_id' => $world['student']->id, 'term_id' => $world['term']->id]);

    $instructor = aUser('INSTRUCTOR');
    $world['mine']->forceFill(['instructor_id' => $instructor->id])->saveQuietly();

    $this->actingAs($instructor)->get(route('students.index'))->assertOk()
        ->assertSee($world['student']->name_ar)
        ->assertDontSee(__('students.fields.open_requests'))
        ->assertDontSee('track-students__open', false);
});

test('nothing on the list belongs to a student account or contact (C-19, C-23)', function (): void {
    studentsAdminWorld();

    $html = (string) $this->actingAs(aUser('SUPER_ADMIN'))->get(route('students.index'))->assertOk()->getContent();

    // v2 draws email, phone, password state, reset and deactivate, and New-student and Import buttons.
    foreach (['@s.mu.edu.sa', 'name="email"', 'name="phone"', 'key-round', 'user-x', 'user-plus', 'file-spreadsheet'] as $absent) {
        expect(str_contains($html, $absent))->toBeFalse("the list renders {$absent}");
    }
});

test('a page of students is a fixed number of queries, however many rows', function (): void {
    $world = studentsAdminWorld();
    $admin = aUser('SUPER_ADMIN');

    // Measured warm: the first request of a test also loads the permission and settings caches, which
    // is a cost of the test's cold start, not of the rows.
    $count = function () use ($admin): int {
        $this->actingAs($admin)->get(route('students.index'))->assertOk();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($admin)->get(route('students.index'))->assertOk();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    };

    $few = $count();

    foreach (range(1, 6) as $i) {
        $student = Student::factory()->create();
        SectionEnrollment::factory()->create(['student_id' => $student->id, 'section_id' => $world['mine']->id]);
        SupportRequest::factory()->create(['student_id' => $student->id, 'term_id' => $world['term']->id]);
    }

    expect($count())->toBe($few);
});

test('an invalid filter value is refused, not silently ignored', function (): void {
    studentsAdminWorld();

    $this->actingAs(aUser('SUPER_ADMIN'))->getJson(route('api.v1.students.index', ['branch_id' => 'abc']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('branch_id');
});

test('the student page shows each section\'s programme, degree and activity', function (): void {
    $world = studentsAdminWorld();
    $degree = collect(App\Modules\Settings\Settings::options(App\Modules\Settings\Settings::SECTION_DEGREES))->firstWhere('value', 'intermediate_diploma');

    $this->actingAs(aUser('SUPER_ADMIN'))->get(route('students.show', $world['student']))->assertOk()
        ->assertSee(__('students.fields.program'))
        ->assertSee($world['mine']->program->name_ar)
        ->assertSee($degree['ar']);
});

/*
| Phase 26 review — the cases the first ten tests did not reach.
*/

test('a coordinator naming another branch\'s section or programme gets nothing, and is not offered them', function (): void {
    $world = studentsAdminWorld();
    $coordinator = coordinatorOf($world['branch']);

    foreach ([['section_id' => $world['theirs']->id], ['program_id' => $world['theirs']->program_id]] as $filter) {
        $this->actingAs($coordinator)->get(route('students.index', $filter))->assertOk()
            ->assertDontSee($world['stranger']->name_ar)
            ->assertDontSee($world['student']->name_ar);
    }

    // The programme select lists the caller's current sections' programmes, not the catalogue.
    $this->actingAs($coordinator)->get(route('students.index'))->assertOk()
        ->assertSee('value="'.$world['mine']->program_id.'"', false)
        ->assertDontSee('value="'.$world['theirs']->program_id.'"', false);
});

test('a resolved request is still open; closed and rejected are not', function (): void {
    $world = studentsAdminWorld();

    foreach (['resolved' => null, 'closed' => null, 'rejected' => 'Out of scope'] as $status => $reason) {
        $request = SupportRequest::factory()->create(['student_id' => $world['student']->id, 'term_id' => $world['term']->id]);
        $request->forceFill(['status' => $status, 'rejection_reason' => $reason])->saveQuietly();
    }

    $this->actingAs(aUser('SUPER_ADMIN'))->get(route('students.index'))->assertOk()
        ->assertSee('<span class="track-students__open">1</span>', false);
});

test('wildcards typed into the search match nobody, never everybody', function (string $typed): void {
    $world = studentsAdminWorld();

    $this->actingAs(aUser('SUPER_ADMIN'))->get(route('students.index', ['q' => $typed]))->assertOk()
        ->assertDontSee($world['student']->name_ar)
        ->assertDontSee($world['stranger']->name_ar);
})->with(['percent' => ['%'], 'underscore' => ['_'], 'both' => ['%_%'], 'a backslash' => ['\\']]);

test('wildcards around a name are ignored, and the name is still found', function (): void {
    $world = studentsAdminWorld();

    $this->actingAs(aUser('SUPER_ADMIN'))->get(route('students.index', ['q' => '%فرع آخر_']))->assertOk()
        ->assertSee($world['stranger']->name_ar)
        ->assertDontSee('طالب الفرع<', false);
});

test('the legacy university_id filter takes digits only, so it cannot carry a wildcard', function (string $typed): void {
    studentsAdminWorld();

    $this->actingAs(aUser('SUPER_ADMIN'))->getJson(route('api.v1.students.index', ['university_id' => $typed]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('university_id');
})->with(['percent' => ['%'], 'underscore' => ['_'], 'a digit then a wildcard' => ['44%']]);

test('the legacy university_id filter still finds by prefix, in either script', function (string $typed): void {
    $world = studentsAdminWorld();

    $this->actingAs(aUser('SUPER_ADMIN'))->getJson(route('api.v1.students.index', ['university_id' => $typed]))->assertOk()
        ->assertJsonPath('data.0.university_id', $world['student']->university_id)
        ->assertJsonCount(1, 'data');
})->with(['western' => ['4411'], 'arabic-indic' => ['٤٤١١']]);

test('digits, in either script, search the University ID prefix', function (string $typed): void {
    $world = studentsAdminWorld();

    $this->actingAs(aUser('SUPER_ADMIN'))->get(route('students.index', ['q' => $typed]))->assertOk()
        ->assertSee($world['student']->name_ar)
        ->assertDontSee($world['stranger']->name_ar);
})->with(['western' => ['4411'], 'arabic-indic' => ['٤٤١١']]);

test('a student in two sections of different programmes lists each programme once', function (): void {
    $world = studentsAdminWorld();
    $second = Section::factory()->create(['branch_id' => $world['branch']->id, 'term_id' => $world['term']->id]);
    $third = Section::factory()->create(['branch_id' => $world['branch']->id, 'term_id' => $world['term']->id, 'program_id' => $second->program_id]);

    foreach ([$second, $third] as $section) {
        SectionEnrollment::factory()->create(['student_id' => $world['student']->id, 'section_id' => $section->id]);
    }

    $html = (string) $this->actingAs(aUser('SUPER_ADMIN'))->get(route('students.index', ['q' => '441100001']))->assertOk()->getContent();

    // The programme cell's text, not the page: the filter lists the same names, and the cell's
    // data-sort-value repeats its text.
    preg_match('#<td\s+data-label="'.preg_quote(__('students.fields.program'), '#').'"[^>]*>(.*?)</td>#s', $html, $cell);
    $names = array_map('trim', explode(trim(__('students.list_separator')), trim($cell[1])));

    expect($names)->toEqualCanonicalizing([e($world['mine']->program->name_ar), e($second->program->name_ar)]);
});

test('a withdrawn enrollment is not one of the sections a student holds', function (): void {
    $world = studentsAdminWorld();
    SectionEnrollment::query()->withoutGlobalScopes()->where('student_id', $world['student']->id)
        ->update(['status' => 'withdrawn', 'withdrawn_at' => now(), 'withdraw_reason' => 'Dropped the course']);

    $this->actingAs(aUser('SUPER_ADMIN'))->get(route('students.index', ['branch_id' => $world['branch']->id]))->assertOk()
        ->assertDontSee($world['student']->name_ar);
});

test('a section that is not in the chosen branch is dropped, and the branch decides', function (): void {
    $world = studentsAdminWorld();

    $this->actingAs(aUser('SUPER_ADMIN'))->get(route('students.index', ['branch_id' => $world['branch']->id, 'section_id' => $world['theirs']->id]))->assertOk()
        ->assertSee($world['student']->name_ar)
        ->assertDontSee($world['stranger']->name_ar);
});

test('the API rows are the student record alone: no screen columns are loaded or counted', function (): void {
    $world = studentsAdminWorld();
    SupportRequest::factory()->create(['student_id' => $world['student']->id, 'term_id' => $world['term']->id]);
    $admin = aUser('SUPER_ADMIN');
    $this->actingAs($admin)->getJson(route('api.v1.students.index'))->assertOk();

    DB::flushQueryLog();
    DB::enableQueryLog();
    $response = $this->actingAs($admin)->getJson(route('api.v1.students.index'))->assertOk();
    DB::disableQueryLog();

    $sql = collect(DB::getQueryLog())->pluck('query')->implode("\n");

    expect($sql)->not->toContain('support_requests')
        ->and($sql)->not->toContain('from "programs"')
        ->and(array_keys($response->json('data.0')))->toBe(['id', 'university_id', 'name_ar', 'name_en']);
});
