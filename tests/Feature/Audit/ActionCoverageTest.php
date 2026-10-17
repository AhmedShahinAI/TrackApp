<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Course;
use App\Models\Program;
use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T156 — the action list is a contract, and it is closed (FR-028)
|--------------------------------------------------------------------------
|
| contracts/audit.md defines 38 keys and says the list is a contract. This file holds it to that in
| two directions, which are different claims:
|
|   • Every key a flow can produce today is produced exactly once by that flow. "Exactly one entry
|     per occurrence" is the requirement; two entries for one action is as wrong as none, and it is
|     the easier mistake to make — an action class that records explicitly while the model's trait
|     also fires writes both.
|
|   • Nothing outside the 38 is ever written. This is the direction that catches drift: the
|     Auditable trait generates `{entity}.{event}` from a class name, so adding a model or renaming
|     one silently invents an action key that no filter offers and no label covers.
|
| ## Six models can still emit a `.deleted` the contract does not name
|
| AcademicTerm, Course, Section, SectionEnrollment, Student and ImportJob are audited and
| soft-deletable, and contracts/audit.md names no deletion key for any of them — unlike User, Branch
| and Program, whose `deleted` event is mapped to `.deactivated`. No implemented flow deletes the
| six, so nothing is emitted today and the allowlist check below stays green. The moment a delete
| flow is added for one, it will emit an off-contract key, and that test will say so.
|
| The contract names the key first. Inventing one here would be deciding a product question in a
| test file.
|
| ## On the nine that are not performed
|
| The task asks for all 38. Nine of them belong to features later phases build — terms, programs,
| instructor reassignment, withdrawal — and a test cannot perform an action that has no code. (The
| import pipeline's four were in this list until US5 landed them.) Rather than assert 38 and mean
| 29, each one is named in
| actionsAwaitingTheirPhase() with the phase that will emit it. The arithmetic below is asserted, so
| the day a phase lands and its entry is not removed, this test says so.
|
*/

/**
 * Run a closure and return the action keys it wrote, in order.
 *
 * @return list<string>
 */
function actionsWrittenBy(Closure $flow): array
{
    DB::table('audit_logs')->delete();

    $flow();

    return DB::table('audit_logs')->orderBy('id')->pluck('action')->all();
}

/**
 * One performer per implemented contract key.
 *
 * @return array<string, Closure>
 */
function actionPerformers(): array
{
    return [
        'auth.login' => function (): void {
            $user = aUser('INSTRUCTOR');
            test()->post('/login', ['email' => $user->email, 'password' => 'Track2026']);
        },
        'auth.logout' => function (): void {
            test()->actingAs(aUser('INSTRUCTOR'))->post('/logout');
        },
        'auth.login_failed' => function (): void {
            $user = aUser('INSTRUCTOR');
            test()->post('/login', ['email' => $user->email, 'password' => 'wrong-entirely']);
        },
        'auth.login_blocked' => function (): void {
            $user = aUser('INSTRUCTOR');

            // Six attempts: the sixth is the one the limiter refuses before checking anything.
            for ($i = 0; $i < 6; $i++) {
                test()->post('/login', ['email' => $user->email, 'password' => 'wrong-entirely']);
            }
        },

        // SEC-061: the sections CSV export is audited like the other exports.
        'section.exported' => function (): void {
            test()->actingAs(aUser('SUPER_ADMIN'))->get('/api/v1/sections/export')->streamedContent();
        },

        // SEC-044: an operator ends an account lock from the console.
        'auth.login_unblocked' => function (): void {
            Illuminate\Support\Facades\Artisan::call('track:login-unblock', ['email' => aUser('INSTRUCTOR')->email]);
        },

        'user.created' => function (): void {
            test()->actingAs(aUser('SUPER_ADMIN'))->postJson('/api/v1/users', [
                'name_ar' => 'جديد', 'name_en' => 'New',
                'email' => 'new.account@mu.edu.sa', 'password' => 'Track2026-Own',
                'role' => 'INSTRUCTOR',
            ]);
        },
        'user.updated' => function (): void {
            $target = aUser('INSTRUCTOR');
            test()->actingAs(aUser('SUPER_ADMIN'))->putJson("/api/v1/users/{$target->id}", [
                'name_ar' => 'اسم معدّل', 'name_en' => 'Edited Name', 'email' => $target->email,
            ]);
        },
        'user.deactivated' => function (): void {
            $target = aUser('INSTRUCTOR');
            test()->actingAs(aUser('SUPER_ADMIN'))->patchJson("/api/v1/users/{$target->id}/deactivate");
        },
        'user.password_changed' => function (): void {
            $user = aUser('INSTRUCTOR');
            test()->actingAs($user)->putJson('/api/v1/auth/password', [
                'current_password' => 'Track2026',
                'password' => 'Track2027-new-own',
                'password_confirmation' => 'Track2027-new-own',
            ]);
        },
        'user.password_reset' => function (): void {
            $target = aUser('INSTRUCTOR');
            test()->actingAs(aUser('SUPER_ADMIN'))
                ->postJson("/api/v1/users/{$target->id}/reset-password", [
                    'password' => 'Track2027-new-own',
                    'password_confirmation' => 'Track2027-new-own',
                ]);
        },
        'user.roles_changed' => function (): void {
            $target = aUser('INSTRUCTOR');
            test()->actingAs(aUser('SUPER_ADMIN'))
                ->putJson("/api/v1/users/{$target->id}/roles", ['roles' => ['BRANCH_COORDINATOR']]);
        },
        'user.branches_changed' => function (): void {
            $target = aUser('BRANCH_COORDINATOR');
            $branch = Branch::factory()->create(['organization_id' => anOrganisation()->id]);

            test()->actingAs(aUser('SUPER_ADMIN'))
                ->putJson("/api/v1/users/{$target->id}/branches", ['branch_ids' => [$branch->id]]);
        },

        'access.denied' => function (): void {
            test()->actingAs(aUser('INSTRUCTOR'))->getJson('/api/v1/users');
        },
        'section.access_granted' => function (): void {
            $instructor = aUser('INSTRUCTOR');
            $section = Section::factory()->create();

            test()->actingAs(aUser('SUPER_ADMIN'))
                ->postJson("/api/v1/sections/{$section->id}/access-grants", ['user_id' => $instructor->id]);
        },

        'branch.created' => fn () => Branch::factory()->create(['organization_id' => anOrganisation()->id]),
        'branch.updated' => function (): void {
            $branch = Branch::factory()->create(['organization_id' => anOrganisation()->id]);
            DB::table('audit_logs')->delete();
            $branch->update(['campus_name_ar' => 'فرع معدّل']);
        },
        'course.created' => fn () => Course::factory()->create(['organization_id' => anOrganisation()->id]),
        'course.updated' => function (): void {
            $course = Course::factory()->create(['organization_id' => anOrganisation()->id]);
            DB::table('audit_logs')->delete();
            $course->update(['name_ar' => 'مقرر معدّل']);
        },

        'program.created' => fn () => Program::factory()->create(['organization_id' => anOrganisation()->id]),
        'program.updated' => function (): void {
            $program = Program::factory()->create(['organization_id' => anOrganisation()->id]);
            DB::table('audit_logs')->delete();
            $program->update(['name_ar' => 'برنامج معدّل']);
        },
        // Phase 14 — through the real endpoints, which own both keys.
        'program.deactivated' => function (): void {
            $program = Program::factory()->create(['organization_id' => anOrganisation()->id]);
            $admin = aUser('SUPER_ADMIN');
            DB::table('audit_logs')->delete();

            test()->actingAs($admin)->putJson("/api/v1/programs/{$program->id}", ['is_active' => false]);
        },
        'program.courses_changed' => function (): void {
            $program = Program::factory()->create(['organization_id' => anOrganisation()->id]);
            $course = Course::factory()->create(['organization_id' => anOrganisation()->id]);
            $admin = aUser('SUPER_ADMIN');
            DB::table('audit_logs')->delete();

            test()->actingAs($admin)->putJson("/api/v1/programs/{$program->id}/courses", ['course_ids' => [$course->id]]);
        },

        'section.created' => fn () => Section::factory()->create(),
        'section.updated' => function (): void {
            $section = Section::factory()->create();
            DB::table('audit_logs')->delete();
            $section->update(['activity' => 'lab']);
        },

        // Phase 27 — section approval, each through its endpoint.
        'section.submitted_for_approval' => function (): void {
            $world = approvalWorld();
            DB::table('audit_logs')->delete();
            createSectionAs($world['instructor'], $world);
        },
        'section.approved' => function (): void {
            $world = approvalWorld();
            $section = sectionCreatedBy($world['instructor'], $world);
            DB::table('audit_logs')->delete();
            decideSection($world['coordinator'], $section, 'approve');
        },
        'section.returned' => function (): void {
            $world = approvalWorld();
            $section = sectionCreatedBy($world['instructor'], $world);
            DB::table('audit_logs')->delete();
            decideSection($world['coordinator'], $section, 'return', 'Wrong programme for this course.');
        },
        'section.rejected' => function (): void {
            $world = approvalWorld();
            $section = sectionCreatedBy($world['instructor'], $world);
            DB::table('audit_logs')->delete();
            decideSection($world['coordinator'], $section, 'reject', 'This section duplicates 1201.');
        },
        'section.resubmitted' => function (): void {
            $world = approvalWorld();
            $section = sectionCreatedBy($world['instructor'], $world);
            decideSection($world['coordinator'], $section, 'return', 'Wrong programme for this course.');
            DB::table('audit_logs')->delete();
            app('auth')->forgetGuards();
            test()->actingAs($world['instructor'])->patchJson('/api/v1/sections/'.$section->id.'/resubmit', []);
        },

        'student.created' => fn () => Student::factory()->create(),
        'student.updated' => function (): void {
            $student = Student::factory()->create();
            DB::table('audit_logs')->delete();
            $student->update(['name_ar' => 'طالب معدّل']);
        },
        'enrollment.created' => function (): void {
            $section = Section::factory()->create();
            $student = Student::factory()->create();
            DB::table('audit_logs')->delete();

            SectionEnrollment::factory()->create([
                'section_id' => $section->id, 'student_id' => $student->id, 'status' => 'active',
            ]);
        },

        /*
         * The import pipeline (US5). Each runs through the real endpoints, so the flow also writes
         * the student, section and enrollment entries the commit creates — which is the point of
         * running it here: the allowlist test below then proves none of those is off-contract.
         */
        'import.uploaded' => function (): void {
            $world = importWorld();
            DB::table('audit_logs')->delete();

            uploadRoster($world['admin'], rosterFile([rosterRow($world)]), $world['term']->id);
        },
        'import.committed' => function (): void {
            $world = importWorld();
            DB::table('audit_logs')->delete();

            importRoster($world, [rosterRow($world, ['section_number' => '611'])], ['create_missing_instructors' => true]);
        },
        // SEC-062: completing an import deletes its uploaded file, and says so.
        'import.file_discarded' => function (): void {
            $world = importWorld();
            importRoster($world, [rosterRow($world, ['section_number' => '612'])], ['create_missing_instructors' => true]);
        },
        'import.resumed' => function (): void {
            $world = importWorld();
            $job = importRoster($world, [rosterRow($world)]);

            // A commit that died: the state the resume endpoint exists for.
            $job->forceFill(['status' => 'failed'])->save();
            DB::table('audit_logs')->delete();

            test()->actingAs($world['admin'])->postJson(route('api.v1.imports.resume', $job));
        },
        'import.error_report_downloaded' => function (): void {
            $world = importWorld();
            $job = importRoster($world, [rosterRow($world, ['university_id' => 'bad'])]);
            DB::table('audit_logs')->delete();

            test()->actingAs($world['admin'])->get(route('api.v1.imports.errors.download', $job))->streamedContent();
        },

        // Phase 20 — the public form: no one signed in, so the entry's actor is null.
        'support.request_created' => function (): void {
            Illuminate\Support\Sleep::fake();
            supportWorld();
            enrolledStudent('441234567');
            DB::table('audit_logs')->delete();

            test()->post('/support', supportSubmission());
        },

        // Phase 21 — the staff side of the support channel.
        'support.responded' => function (): void {
            $world = supportStaffWorld();
            $request = supportRequestFor($world['branch'], ['status' => 'in_progress']);
            DB::table('audit_logs')->delete();

            test()->actingAs($world['admin'])->postJson('/api/v1/support-requests/'.$request->reference.'/responses', ['body' => 'Reply']);
        },
        'support.status_changed' => function (): void {
            $world = supportStaffWorld();
            $request = supportRequestFor($world['branch'], ['status' => 'in_progress']);
            DB::table('audit_logs')->delete();

            test()->actingAs($world['admin'])->patchJson('/api/v1/support-requests/'.$request->reference.'/status', ['status' => 'rejected', 'reason' => 'Out of scope']);
        },
        'support.reassigned' => function (): void {
            $world = supportStaffWorld();
            $request = supportRequestFor($world['branch']);
            $request->forceFill(['status' => 'in_progress'])->saveQuietly();
            DB::table('audit_logs')->delete();

            test()->actingAs($world['admin'])->patchJson('/api/v1/support-requests/'.$request->reference.'/assignee', ['assigned_to' => $world['coordinator']->id]);
        },

        // Phase 25 — the retention sweep: no one signed in, so the entry's actor is null.
        'support.anonymised' => function (): void {
            $world = supportStaffWorld();
            $request = supportRequestFor($world['branch']);
            $request->forceFill(['created_at' => now()->subMonths(25)])->saveQuietly();
            DB::table('audit_logs')->delete();

            runRetentionSweep();
        },

        'audit.exported' => function (): void {
            $admin = aUser('SUPER_ADMIN');
            DB::table('audit_logs')->delete();
            test()->actingAs($admin)->get('/api/v1/audit-logs/export')->streamedContent();
        },
    ];
}

test('the performers and the deferred list together account for all 48 contract keys', function (): void {
    $performed = array_keys(actionPerformers());
    $deferred = array_keys(actionsAwaitingTheirPhase());

    $covered = array_values(array_unique([...$performed, ...$deferred]));
    sort($covered);

    $contract = contractActionKeys();
    sort($contract);

    expect($covered)->toBe($contract);

    // No key is both performed and deferred — a deferred entry left behind after its phase lands.
    expect(array_intersect($performed, $deferred))->toBe([]);
});

test('each implemented action writes exactly one entry with its own key', function (string $action): void {
    $performer = actionPerformers()[$action];

    $written = actionsWrittenBy($performer);

    /*
     * Filtered to this action before counting. A flow usually writes more than its headline entry —
     * creating a user through the API writes user.created, and the fixtures around it write their
     * own — so what is asserted is that this key appears once, not that the flow is silent
     * otherwise.
     */
    $mine = array_values(array_filter($written, fn (string $a): bool => $a === $action));

    expect($mine)->toHaveCount(1, sprintf(
        "'%s' was written %d times; the flow wrote: %s",
        $action, count($mine), implode(', ', $written) ?: '(nothing)',
    ));
})->with(array_keys(actionPerformers()));

test('no flow writes an action the contract does not define', function (): void {
    $contract = contractActionKeys();
    $offenders = [];

    foreach (actionPerformers() as $action => $performer) {
        foreach (actionsWrittenBy($performer) as $written) {
            if (! in_array($written, $contract, true)) {
                $offenders[] = "$action -> $written";
            }
        }
    }

    /*
     * The drift guard. The Auditable trait builds a key from a class name, so a model added or
     * renamed next month invents an action that no screen can filter and no label covers — and it
     * does it silently, because the entry is written successfully.
     */
    expect(array_values(array_unique($offenders)))->toBe([]);
});
