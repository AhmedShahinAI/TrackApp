<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\User;
use App\Modules\Imports\Pipeline\ImportReasons;

/*
|--------------------------------------------------------------------------
| T340 — each staff-row defect refused with its own reason (FR-104, FR-005, FR-004)
|--------------------------------------------------------------------------
*/

test('each defect is refused with its own reason, and none of those rows creates an account', function (): void {
    $admin = aUser('SUPER_ADMIN');
    aUser('INSTRUCTOR', ['email' => 'taken@mu.edu.sa']);
    $deleted = aUser('INSTRUCTOR', ['email' => 'gone@mu.edu.sa']);
    $deleted->delete();
    Branch::factory()->create(['organization_id' => $admin->organization_id, 'campus_name_ar' => 'الزلفي', 'gender' => 'male']);
    Branch::factory()->create(['organization_id' => $admin->organization_id, 'campus_name_ar' => 'الزلفي', 'gender' => 'female']);
    $users = User::query()->withTrashed()->count();

    $job = importBulk($admin, 'users', staffFile([
        ['مكرر', 'Taken', 'TAKEN@mu.edu.sa', 'INSTRUCTOR', ''],                       // 2: email held
        ['محذوف', 'Gone', 'gone@mu.edu.sa', 'INSTRUCTOR', ''],                        // 3: held by a deactivated account
        ['منسق', 'Coord', 'coord@mu.edu.sa', 'BRANCH_COORDINATOR', ''],                // 4: coordinator, no branch
        ['مجهول', 'Unknown', 'role@mu.edu.sa', 'DEAN', ''],                            // 5: unknown role
        ['فرع', 'Branch', 'branch@mu.edu.sa', 'BRANCH_COORDINATOR', 'شقراء (طلاب)'],   // 6: unknown branch
        ['غامض', 'Ambiguous', 'amb@mu.edu.sa', 'BRANCH_COORDINATOR', 'الزلفي'],        // 7: two branches answer
        ['بريد', 'Email', 'not-an-email', 'INSTRUCTOR', ''],                           // 8: invalid email
        ['أول', 'First', 'twice@mu.edu.sa', 'INSTRUCTOR', ''],                         // 9
        ['ثان', 'Second', 'Twice@mu.edu.sa', 'INSTRUCTOR', ''],                        // 10: duplicate in file
    ]));

    $reasons = rowReasons($job);

    expect($reasons[2])->toContain(ImportReasons::EMAIL_TAKEN)
        ->and($reasons[3])->toContain(ImportReasons::EMAIL_TAKEN)
        ->and($reasons[4])->toContain(ImportReasons::COORDINATOR_NEEDS_BRANCH)
        ->and($reasons[5])->toContain(ImportReasons::UNKNOWN_ROLE)
        ->and($reasons[6])->toContain(ImportReasons::UNKNOWN_BRANCH)
        ->and($reasons[6])->not->toContain(ImportReasons::COORDINATOR_NEEDS_BRANCH)
        ->and($reasons[7])->toContain(ImportReasons::UNKNOWN_BRANCH)
        ->and($reasons[8])->toContain(ImportReasons::INVALID_EMAIL)
        ->and($reasons[10])->toContain(ImportReasons::DUPLICATE_IN_FILE)
        ->and($job->errors)->toBe(8)
        ->and($job->imported)->toBe(1)
        ->and(User::query()->withTrashed()->count())->toBe($users + 1);
});

test('the email-taken reason names the email, in the operator\'s language', function (): void {
    $admin = aUser('SUPER_ADMIN');
    aUser('INSTRUCTOR', ['email' => 'taken@mu.edu.sa']);

    $job = checkBulk($admin, 'users', staffFile([['مكرر', 'Taken', 'taken@mu.edu.sa', 'INSTRUCTOR', '']]));
    $preview = $this->actingAs($admin)->getJson(route('api.v1.imports.preview', $job))->assertOk();

    expect($preview->json('data.rows.0.reasons'))->toContain(__('imports.reasons.email_taken', ['value' => 'taken@mu.edu.sa']));
});

test('a staff job takes no create-or-skip options', function (): void {
    $admin = aUser('SUPER_ADMIN');
    postBulk($admin, 'users', staffFile([['مدرس', 'Teacher', 'x@mu.edu.sa', 'INSTRUCTOR', '']]))->assertCreated();
    $job = App\Models\ImportJob::query()->withoutGlobalScopes()->latest('id')->firstOrFail();

    $this->actingAs($admin)->putJson(route('api.v1.imports.mapping.update', $job), [
        'mapping' => $job->file_metadata['suggested_mapping'],
        'options' => ['create_missing_programs' => true],
    ])->assertUnprocessable()->assertJsonValidationErrors(['options']);
});
