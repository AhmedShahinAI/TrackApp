<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Amendment 2, US10 — the staff queue's world (Phase 21)
|--------------------------------------------------------------------------
|
| Requests whose students are enrolled in a known branch, and the people who work them: a Super Admin,
| a coordinator of that branch, a coordinator of another, and an instructor.
|
*/

use App\Models\AcademicTerm;
use App\Models\Branch;
use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Models\Student;
use App\Models\SupportRequest;
use App\Models\User;
use Database\Seeders\SettingSeeder;

/**
 * @return array{branch: Branch, other: Branch, term: AcademicTerm, admin: User, coordinator: User, outsider: User, instructor: User}
 */
function supportStaffWorld(): array
{
    (new SettingSeeder)->run();

    $branch = Branch::factory()->create();
    $other = Branch::factory()->create();
    $term = AcademicTerm::query()->where('is_current', true)->first() ?? AcademicTerm::factory()->current()->create();

    $coordinator = aUser('BRANCH_COORDINATOR');
    $coordinator->coordinatorBranches()->sync([$branch->id]);

    $outsider = aUser('BRANCH_COORDINATOR');
    $outsider->coordinatorBranches()->sync([$other->id]);

    return [
        'branch' => $branch,
        'other' => $other,
        'term' => $term,
        'admin' => aUser('SUPER_ADMIN'),
        'coordinator' => $coordinator,
        'outsider' => $outsider,
        'instructor' => aUser('INSTRUCTOR'),
    ];
}

/**
 * A request from a student actively enrolled in $branch, in $status, raised $minutesAgo ago.
 *
 * @param  array<string, mixed>  $attributes
 */
function supportRequestFor(Branch $branch, array $attributes = [], int $minutesAgo = 60): SupportRequest
{
    $term = AcademicTerm::query()->where('is_current', true)->first() ?? AcademicTerm::factory()->current()->create();
    $student = Student::factory()->create();

    SectionEnrollment::factory()->create([
        'student_id' => $student->id,
        'section_id' => Section::factory()->create(['branch_id' => $branch->id, 'term_id' => $term->id])->id,
    ]);

    $request = SupportRequest::factory()->create(array_merge([
        'student_id' => $student->id,
        'term_id' => $term->id,
    ], array_diff_key($attributes, array_flip(['status', 'rejection_reason', 'assigned_to']))));

    // State set past the model's events: a fixture, not a transition, so no audit entry.
    $request->forceFill(array_intersect_key($attributes, array_flip(['status', 'rejection_reason', 'assigned_to'])) + [
        'created_at' => now()->subMinutes($minutesAgo),
    ])->saveQuietly();

    // The creation entry is the public form's (Phase 20); a staff test counts only what staff do.
    Illuminate\Support\Facades\DB::table('audit_logs')
        ->where('entity_type', 'SupportRequest')->where('entity_id', $request->id)->delete();

    return $request->refresh();
}

/**
 * The audit actions written for one request, in order.
 *
 * @return list<string>
 */
function supportAuditActions(SupportRequest $request): array
{
    return Illuminate\Support\Facades\DB::table('audit_logs')
        ->where('entity_type', 'SupportRequest')
        ->where('entity_id', $request->id)
        ->orderBy('id')
        ->pluck('action')
        ->all();
}

/**
 * Phase 25 — a request from a student enrolled in $branch, raised $months months ago, with one staff
 * reply and one state change written the way staff write them, so its history is real.
 */
function agedSupportRequest(Branch $branch, User $staff, int $months, string $contact = 'student@example.test'): SupportRequest
{
    $request = supportRequestFor($branch, ['status' => 'in_progress']);
    $request->forceFill(['contact' => $contact])->saveQuietly();

    test()->actingAs($staff)->postJson('/api/v1/support-requests/'.$request->reference.'/responses', ['body' => 'Your section is fixed.'])->assertSuccessful();
    test()->actingAs($staff)->patchJson('/api/v1/support-requests/'.$request->reference.'/status', ['status' => 'resolved'])->assertSuccessful();

    // Aged after the staff actions: they happened, and only the creation date decides retention.
    $request->forceFill(['created_at' => now()->subMonths($months)->subDay()])->saveQuietly();

    app('auth')->forgetGuards();

    return $request->refresh();
}

/** The retention sweep, run as the scheduler runs it: no one signed in. */
function runRetentionSweep(): int
{
    app('auth')->forgetGuards();

    return app(App\Modules\Support\Actions\AnonymiseExpiredRequests::class)->execute();
}
