<?php

declare(strict_types=1);

use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T177 — withdrawing a student (FR-047)
|--------------------------------------------------------------------------
|
| The reason is the point. A withdrawal without one is a number that dropped, and nobody a year
| later can say why — which is exactly the question a withdrawal provokes.
|
*/

function anEnrollment(string $status = 'active'): SectionEnrollment
{
    return SectionEnrollment::factory()->create([
        'section_id' => Section::factory()->create()->id,
        'student_id' => Student::factory()->create()->id,
        'status' => $status,
    ]);
}

test('the reason is mandatory', function (mixed $reason): void {
    $admin = aUser('SUPER_ADMIN');
    $enrollment = anEnrollment();

    test()->actingAs($admin)
        ->patchJson("/api/v1/enrollments/{$enrollment->id}/withdraw", array_filter(['reason' => $reason], fn ($v) => $v !== null))
        ->assertStatus(422);

    expect($enrollment->fresh()->status)->toBe('active');
})->with([
    'absent' => null,
    'empty' => '',
    'whitespace' => '   ',
]);

test('a withdrawal records the date and the reason, and the student survives', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $enrollment = anEnrollment();
    $studentId = $enrollment->student_id;

    test()->actingAs($admin)
        ->patchJson("/api/v1/enrollments/{$enrollment->id}/withdraw", ['reason' => 'انسحاب عبر عمادة القبول'])
        ->assertOk()
        ->assertJsonPath('data.status', 'withdrawn');

    $fresh = $enrollment->fresh();

    expect($fresh->status)->toBe('withdrawn')
        ->and($fresh->withdrawn_at)->not->toBeNull()
        ->and($fresh->withdraw_reason)->toBe('انسحاب عبر عمادة القبول');

    /*
     * Constitution V. The enrollment's state changed; nothing was removed. The student is still a
     * student, still enrolled elsewhere, and still owns every record naming them.
     */
    expect(Student::query()->whereKey($studentId)->exists())->toBeTrue();
});

test('an already-withdrawn enrollment is 409, and the first reason survives', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $enrollment = anEnrollment();

    test()->actingAs($admin)
        ->patchJson("/api/v1/enrollments/{$enrollment->id}/withdraw", ['reason' => 'السبب الأول'])
        ->assertOk();

    test()->actingAs($admin)
        ->patchJson("/api/v1/enrollments/{$enrollment->id}/withdraw", ['reason' => 'سبب مختلف'])
        ->assertStatus(409);

    /*
     * 409 rather than letting it through: a second withdrawal would overwrite the explanation that
     * was actually given at the time, which is the one thing this action exists to write down.
     */
    expect($enrollment->fresh()->withdraw_reason)->toBe('السبب الأول');
});

test('the withdrawal is recorded once, with both sides', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $enrollment = anEnrollment();

    DB::table('audit_logs')->delete();

    test()->actingAs($admin)
        ->patchJson("/api/v1/enrollments/{$enrollment->id}/withdraw", ['reason' => 'انقطاع'])
        ->assertOk();

    $actions = DB::table('audit_logs')->where('entity_type', 'SectionEnrollment')->pluck('action')->all();

    expect($actions)->toBe(['enrollment.withdrawn']);

    $entry = DB::table('audit_logs')->where('action', 'enrollment.withdrawn')->first();

    expect(json_decode((string) $entry->old_values, true)['status'])->toBe('active')
        ->and(json_decode((string) $entry->new_values, true)['withdraw_reason'])->toBe('انقطاع');
});

test('re-enrolling after a withdrawal reopens the same row', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $section = Section::factory()->create();
    $student = Student::factory()->create(['university_id' => '441234567']);

    $enrollment = SectionEnrollment::factory()->create([
        'section_id' => $section->id, 'student_id' => $student->id, 'status' => 'active',
    ]);

    test()->actingAs($admin)
        ->patchJson("/api/v1/enrollments/{$enrollment->id}/withdraw", ['reason' => 'انسحاب'])
        ->assertOk();

    test()->actingAs($admin)
        ->postJson("/api/v1/sections/{$section->id}/enrollments", [
            'university_id' => '441234567',
            'name_ar' => $student->name_ar,
        ])
        ->assertCreated();

    /*
     * One row, reopened. Two rows for one person in one section would double every roster count and
     * make "withdrew then returned" indistinguishable from "enrolled twice".
     */
    expect(SectionEnrollment::query()->where('section_id', $section->id)->count())->toBe(1)
        ->and($enrollment->fresh()->status)->toBe('active')
        ->and($enrollment->fresh()->withdraw_reason)->toBeNull();
});
