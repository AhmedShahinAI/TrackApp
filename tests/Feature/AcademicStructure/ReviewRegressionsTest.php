<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Models\SectionInstructorHistory;
use App\Models\Student;
use App\Modules\Settings\Settings;
use App\Modules\Shared\Rules\UniversityId;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/*
|--------------------------------------------------------------------------
| Regression cover for the Phase 7 review findings
|--------------------------------------------------------------------------
|
| Every test here failed before its fix and passes after it. They are collected in one file because
| they share nothing except their origin: each is a case the Phase 7 suite was green over, which is
| the only property that matters about them.
|
| The lesson they have in common is worth stating once. Four of the six passed originally because
| the test acted as SUPER_ADMIN, or built its fixture by hand, or asserted the JSON an API client
| sees. None of those is how the feature is used.
|
*/

/**
 * A coordinator of exactly one branch — the role the enrollment screen is built for, and the one
 * every enrollment test avoided by acting as an administrator.
 *
 * @param  list<string>  $permissions
 */
function aCoordinatorOf(Branch $branch, array $permissions): App\Models\User
{
    $user = aUser('BRANCH_COORDINATOR');

    $role = Role::findOrCreate('REVIEW_PROBE', 'web');
    $role->syncPermissions([...$permissions, 'dashboard.view']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user->syncRoles(['REVIEW_PROBE']);
    $user->coordinatorBranches()->sync([$branch->getKey()]);

    return $user->fresh();
}

/**
 * Overwrite the stored university-id pattern.
 *
 * Settings has no setter — it is a read API over a `settings` table whose rows are seeded and
 * edited through the settings screen. The row is written directly here, and the memoised cache
 * dropped, because what is under test is what the rule does with a pattern an administrator saved.
 */
function storePattern(string $pattern): void
{
    App\Models\Setting::query()->updateOrCreate(
        ['key' => Settings::UNIVERSITY_ID_PATTERN],
        ['value' => $pattern],
    );

    Settings::forget();
}

test('a coordinator can enrol a student who exists outside their branch', function (): void {
    /*
     * The finding: Student carries the branch and section scopes, so EnrollStudent's lookup asked
     * "students I can already see". For a coordinator that is never a student from another campus —
     * so the action created a second row and the unique index on `university_id` answered with a
     * 500 on an ordinary enrollment.
     *
     * Invisible to the original suite because every enrollment test acted as SUPER_ADMIN, who holds
     * `scope.bypass_all` and therefore sees every student already.
     */
    $elsewhere = Section::factory()->create();
    $student = Student::factory()->create(['university_id' => '441007777', 'name_ar' => 'سارة القحطاني']);

    SectionEnrollment::factory()->create([
        'section_id' => $elsewhere->id,
        'student_id' => $student->id,
        'status' => 'active',
    ]);

    $mine = Section::factory()->create();
    $coordinator = aCoordinatorOf($mine->branch, ['section.view', 'student.view', 'enrollment.create']);

    $response = test()->actingAs($coordinator)
        ->postJson("/api/v1/sections/{$mine->id}/enrollments", [
            'university_id' => '441007777',
            'name_ar' => 'سارة القحطاني',
        ])
        ->assertCreated();

    // One person, one row — linked, not duplicated (FR-043).
    expect(Student::query()->withoutGlobalScopes()->where('university_id', '441007777')->count())->toBe(1)
        ->and($response->json('data.id'))->toBe($student->id);
});

test('creating a section through the endpoint opens its instructor history', function (): void {
    /*
     * The finding: ChangeSectionInstructor closes "the open period" and opens a new one, assuming
     * creation left one behind. Nothing did, so the founding instructor appeared in no row and the
     * first handover produced a history beginning with the second person (FR-048, SC-018).
     *
     * ChangeInstructorTest passed because it seeded the row itself.
     */
    $admin = aUser('SUPER_ADMIN');
    $like = Section::factory()->create();
    $instructor = aUser('INSTRUCTOR');

    $response = test()->actingAs($admin)
        ->postJson('/api/v1/sections', [
            'term_id' => $like->term_id,
            'branch_id' => $like->branch_id,
            'course_id' => $like->course_id,
            'program_id' => $like->program_id,
            'section_number' => '771',
            'instructor_id' => $instructor->id,
        ])
        ->assertCreated();

    $rows = SectionInstructorHistory::query()
        ->where('section_id', $response->json('data.id'))
        ->get();

    expect($rows)->toHaveCount(1)
        ->and($rows[0]->instructor_id)->toBe($instructor->id)
        ->and($rows[0]->unassigned_at)->toBeNull()
        ->and($rows[0]->changed_by)->toBe($admin->id);
});

test('PUT cannot move a section to another instructor', function (): void {
    /*
     * The finding: update() mass-assigned `instructor_id` from the validated payload, so a PUT
     * transferred access with no history row and no `section.instructor_changed` — and let
     * `section.edit` do the work of `section.change_instructor`, which exists as a separate
     * permission precisely because it is a separate act.
     */
    $admin = aUser('SUPER_ADMIN');
    $section = Section::factory()->create();
    $other = aUser('INSTRUCTOR');

    $before = $section->instructor_id;

    test()->actingAs($admin)
        ->putJson("/api/v1/sections/{$section->id}", [
            'term_id' => $section->term_id,
            'branch_id' => $section->branch_id,
            'course_id' => $section->course_id,
            'program_id' => $section->program_id,
            'section_number' => $section->section_number,
            'instructor_id' => $other->id,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('instructor_id');

    expect($section->fresh()->instructor_id)->toBe($before);
});

test('a section cannot be created in a branch the caller cannot see', function (): void {
    /*
     * The finding: `exists:branches,id` asks whether the branch exists anywhere, and the answer is
     * yes for every campus in the university. The read side was scoped; the write side was not, so
     * a coordinator could post any `branch_id` and create a section in a campus they cannot open.
     */
    $mine = Section::factory()->create();
    $foreign = Section::factory()->create();

    $coordinator = aCoordinatorOf($mine->branch, ['section.view', 'section.create']);

    test()->actingAs($coordinator)
        ->postJson('/api/v1/sections', [
            'term_id' => $foreign->term_id,
            'branch_id' => $foreign->branch_id,
            'course_id' => $foreign->course_id,
            'program_id' => $foreign->program_id,
            'section_number' => '781',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('branch_id');

    expect(Section::query()->withoutGlobalScopes()->where('section_number', '781')->exists())->toBeFalse();
});

test('an unusable university id pattern refuses the value instead of throwing', function (): void {
    /*
     * The finding: the settings-held pattern was interpolated raw between two slashes, so a
     * perfectly reasonable institutional rule containing a `/` closed the regex early and PCRE
     * raised a warning that Laravel promotes to an ErrorException — a 500 on every enrollment and
     * on every row of every import, from a value typed into a settings screen.
     */
    storePattern('^4/\d{8}$');

    $rule = new UniversityId;

    // No exception, and the broken pattern is not silently treated as "anything goes".
    expect($rule->matches('441002315'))->toBeTrue()
        ->and($rule->matches('not-an-id'))->toBeFalse();
});

test('a pattern without anchors still means the whole value', function (): void {
    // `\d{9}` means nine digits, not nine digits buried inside anything. Unanchored, the rule
    // accepted `xx441234567yy` as a university id.
    storePattern('\d{9}');

    $rule = new UniversityId;

    expect($rule->matches('441234567'))->toBeTrue()
        ->and($rule->matches('xx441234567yy'))->toBeFalse();
});

test('deactivating a branch from a form records branch.deactivated', function (): void {
    /*
     * The finding: the `boolean` rule ACCEPTS "0" but validate() returns it unchanged, so
     * `$validated['is_active'] === false` was false for a form-encoded submission. The branch was
     * deactivated and the audit said `branch.updated` — leaving no answer to the one question the
     * log is kept for.
     */
    $admin = aUser('SUPER_ADMIN');
    $branch = Branch::factory()->create(['organization_id' => anOrganisation()->id, 'is_active' => true]);

    DB::table('audit_logs')->delete();

    // Form-encoded, as the screen sends it — not putJson, which casts to a real boolean and is why
    // the original test passed.
    test()->actingAs($admin)
        ->put("/api/v1/branches/{$branch->id}", ['is_active' => '0'], ['Accept' => 'application/json'])
        ->assertOk();

    expect($branch->fresh()->is_active)->toBeFalse()
        ->and(DB::table('audit_logs')->where('entity_type', 'Branch')->pluck('action')->all())
        ->toBe(['branch.deactivated']);
});

test('re-enrolling after a withdrawal records enrollment.created', function (): void {
    // The finding: the reopen path called save(), so the trait wrote `enrollment.updated` — not in
    // the contract's closed list for this act, and the wrong word for what happened.
    $admin = aUser('SUPER_ADMIN');
    $section = Section::factory()->create();
    $student = Student::factory()->create(['university_id' => '441008888']);

    $enrollment = SectionEnrollment::factory()->create([
        'section_id' => $section->id,
        'student_id' => $student->id,
        'status' => 'withdrawn',
        'withdrawn_at' => now(),
        'withdraw_reason' => 'انسحاب نظامي',
    ]);

    DB::table('audit_logs')->delete();

    test()->actingAs($admin)
        ->postJson("/api/v1/sections/{$section->id}/enrollments", [
            'university_id' => '441008888',
            'name_ar' => $student->name_ar,
        ])
        ->assertCreated();

    expect($enrollment->fresh()->status)->toBe('active')
        ->and(DB::table('audit_logs')->where('entity_type', 'SectionEnrollment')->pluck('action')->all())
        ->toBe(['enrollment.created']);
});

test('a dialog submitted as an ordinary form comes back to its page', function (): void {
    /*
     * The finding with the widest blast radius: all eight Phase 7 dialogs are plain HTML forms
     * posting to their `api.v1.*` routes, and those controllers returned response()->json()
     * unconditionally. Submitting any of them navigated the browser to a raw JSON blob — the screen
     * never returned, the flash never appeared, and every `$errors->first(...)` binding in the
     * dialogs was unreachable code.
     *
     * Nothing caught it because every write test in the phase used postJson/putJson, which sends
     * `Accept: application/json` and therefore exercises the one caller that was already working.
     */
    $admin = aUser('SUPER_ADMIN');
    $branch = Branch::factory()->create(['organization_id' => anOrganisation()->id]);

    test()->actingAs($admin)
        ->from('/branches')
        ->post('/api/v1/branches', [
            'campus_name_ar' => 'فرع الزلفي',
            'campus_name_en' => 'Zulfi campus',
            'gender' => 'male',
        ])
        ->assertRedirect('/branches')
        ->assertSessionHas('status');

    expect(Branch::query()->where('campus_name_en', 'Zulfi campus')->exists())->toBeTrue();
});

test('a form submission with a bad payload returns to the dialog with its errors', function (): void {
    // The other half. ValidationException already redirected for a non-JSON request, but that was
    // only ever reachable if the success path redirected too — otherwise the form never posted
    // from a browser in the first place.
    $admin = aUser('SUPER_ADMIN');

    test()->actingAs($admin)
        ->from('/branches')
        ->post('/api/v1/branches', ['campus_name_ar' => '', 'gender' => 'neither'])
        ->assertRedirect('/branches')
        ->assertSessionHasErrors(['campus_name_ar', 'gender']);
});

test('a 409 returns to the page instead of rendering an error screen', function (): void {
    /*
     * The academic actions answer 409 for states a person can actually reach. For an API client the
     * status is the answer; for a dialog posted as a form, an unhandled 409 rendered the generic
     * error page — losing the screen they were working in and saying nothing about what went wrong.
     */
    $admin = aUser('SUPER_ADMIN');
    $section = Section::factory()->create();

    $enrollment = SectionEnrollment::factory()->create([
        'section_id' => $section->id,
        'student_id' => Student::factory()->create()->id,
        'status' => 'withdrawn',
        'withdrawn_at' => now(),
        'withdraw_reason' => 'انسحاب سابق',
    ]);

    test()->actingAs($admin)
        ->from("/sections/{$section->id}")
        ->patch("/api/v1/enrollments/{$enrollment->id}/withdraw", ['reason' => 'محاولة ثانية'])
        ->assertRedirect("/sections/{$section->id}")
        ->assertSessionHasErrors('conflict');

    // The first reason stands. A second withdrawal would overwrite the explanation actually given.
    expect($enrollment->fresh()->withdraw_reason)->toBe('انسحاب سابق');
});

test('a whitespace-only withdrawal reason is a 422 naming the field', function (): void {
    // Both layers refuse it, but only one tells the registrar what to do about it: a reason of three
    // spaces is a missing reason, not a conflicting state.
    $admin = aUser('SUPER_ADMIN');
    $section = Section::factory()->create();

    $enrollment = SectionEnrollment::factory()->create([
        'section_id' => $section->id,
        'student_id' => Student::factory()->create()->id,
        'status' => 'active',
    ]);

    test()->actingAs($admin)
        ->patchJson("/api/v1/enrollments/{$enrollment->id}/withdraw", ['reason' => '   '])
        ->assertStatus(422)
        ->assertJsonValidationErrors('reason');

    expect($enrollment->fresh()->status)->toBe('active');
});
