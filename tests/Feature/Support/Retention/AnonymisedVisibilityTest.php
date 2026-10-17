<?php

declare(strict_types=1);

use App\Modules\Support\Services\ReferenceGenerator;
use Illuminate\Support\Sleep;

/*
|--------------------------------------------------------------------------
| T455 — an anonymised request is the Super Admin's alone, and a stranger to its old student
|--------------------------------------------------------------------------
|
| No student, so no enrollments, so no branch (R-12): the coordinator who answered it no longer sees
| it, and the Super Admin still does, with the student shown as removed. The student's old pair now
| fails exactly as a reference that never existed does (contracts/support-public.md "Anonymised
| requests").
|
*/

beforeEach(function (): void {
    Sleep::fake();
    supportWorld();
});

test('the coordinator loses it and the Super Admin keeps it', function (): void {
    $world = supportStaffWorld();
    $request = agedSupportRequest($world['branch'], $world['coordinator'], 25);
    $path = '/api/v1/support-requests/'.$request->reference;

    $this->actingAs($world['coordinator'])->getJson($path)->assertOk();

    runRetentionSweep();

    $this->actingAs($world['coordinator'])->getJson($path)->assertNotFound();
    $this->actingAs($world['coordinator'])->getJson('/api/v1/support-requests')->assertJsonCount(0, 'data');
    app('auth')->forgetGuards();

    $this->actingAs($world['admin'])->getJson($path)->assertOk()
        ->assertJsonPath('data.anonymised', true)
        ->assertJsonPath('data.student.removed', true)
        ->assertJsonPath('data.submitted_ip', null);

    // The web guard named: after API calls as another user, the test client's default guard is stale.
    $this->actingAs($world['admin'], 'web')->get('/support-requests/'.$request->reference)
        ->assertOk()
        ->assertSee(__('support.staff.student_removed'));
});

test('the old reference and University ID fail exactly as a reference that never existed', function (): void {
    $world = supportStaffWorld();
    $request = agedSupportRequest($world['branch'], $world['admin'], 25);
    $universityId = $request->student->university_id;

    // Before the sweep the pair works: the failure below is the sweep's doing.
    $this->postJson('/support/status', ['reference' => $request->reference, 'university_id' => $universityId])->assertOk();

    runRetentionSweep();

    $missing = 'K7Q2M9XVT3'.ReferenceGenerator::checkCharacter('K7Q2M9XVT3');
    $anonymised = $this->postJson('/support/status', ['reference' => $request->reference, 'university_id' => $universityId]);
    $never = $this->postJson('/support/status', ['reference' => $missing, 'university_id' => $universityId]);

    expect($anonymised->status())->toBe(422)
        ->and($anonymised->status())->toBe($never->status())
        ->and($anonymised->getContent())->toBe($never->getContent());
});
