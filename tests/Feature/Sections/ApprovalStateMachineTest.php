<?php

declare(strict_types=1);

use App\Models\Section;
use App\Modules\Sections\Enums\SectionStatus;

/*
|--------------------------------------------------------------------------
| T478 — every permitted transition succeeds and every other is refused (FR-180)
|--------------------------------------------------------------------------
|
| data-model.md Group 8: pending → active | returned | rejected; returned → rejected by a reviewer,
| or back to pending by its creator. `rejected` is terminal, and nothing is decided on `active`.
|
*/

/**
 * An instructor's section put into $status the way the workflow gets it there.
 *
 * @param  array<string, mixed>  $world
 */
function sectionInApprovalState(array $world, string $status): Section
{
    $section = sectionCreatedBy($world['instructor'], $world);

    match ($status) {
        'pending' => null,
        'active' => decideSection($world['coordinator'], $section, 'approve')->assertOk(),
        'returned' => decideSection($world['coordinator'], $section, 'return', 'Wrong programme for this course.')->assertOk(),
        'rejected' => decideSection($world['coordinator'], $section, 'reject', 'This section duplicates another one.')->assertOk(),
        default => throw new InvalidArgumentException("No fixture for [{$status}]."),
    };

    return $section->refresh();
}

test('a decision succeeds exactly when the state machine permits it', function (string $from, string $decision): void {
    $world = approvalWorld();
    $section = sectionInApprovalState($world, $from);
    $to = SectionStatus::forDecision($decision);
    $permitted = SectionStatus::from($from)->canBecome($to);

    $response = decideSection($world['coordinator'], $section, $decision, 'A reason long enough to pass.');

    if ($permitted) {
        $response->assertOk()->assertJsonPath('data.status', $to->value);
    } else {
        $response->assertConflict();
        expect($section->refresh()->status)->toBe($from);
    }
})->with(['pending', 'returned', 'rejected', 'active'])->with(['approve', 'return', 'reject']);

test('the constant agrees with data-model.md, cell for cell', function (): void {
    expect(SectionStatus::Pending->decisions())->toBe([SectionStatus::Active, SectionStatus::Returned, SectionStatus::Rejected])
        ->and(SectionStatus::Returned->decisions())->toBe([SectionStatus::Rejected])
        ->and(SectionStatus::Rejected->decisions())->toBe([])
        ->and(SectionStatus::Active->decisions())->toBe([])
        ->and(SectionStatus::Returned->canResubmit())->toBeTrue()
        ->and(SectionStatus::Rejected->canResubmit())->toBeFalse();
});

test('rejected is terminal: neither a decision nor a resubmission leaves it', function (): void {
    $world = approvalWorld();
    $section = sectionInApprovalState($world, 'rejected');

    foreach (['approve', 'return', 'reject'] as $decision) {
        decideSection($world['admin'], $section, $decision, 'A reason long enough to pass.')->assertConflict();
    }

    app('auth')->forgetGuards();
    $this->actingAs($world['instructor'])->patchJson('/api/v1/sections/'.$section->id.'/resubmit', [])->assertConflict();

    expect($section->refresh()->status)->toBe('rejected');
});

test('the model refuses a return or a rejection without a reason, past the form request', function (string $status): void {
    $world = approvalWorld();
    $section = sectionCreatedBy($world['instructor'], $world);

    expect(fn () => $section->forceFill(['status' => $status, 'review_reason' => '  '])->save())->toThrow(LogicException::class);
})->with(['returned', 'rejected']);

test('every decision appends one history row with its actor and reason', function (): void {
    $world = approvalWorld();
    $section = sectionInApprovalState($world, 'returned');

    $history = $section->approvalHistory()->get(['from_status', 'to_status', 'actor_id', 'reason'])->toArray();

    expect($history)->toBe([
        ['from_status' => null, 'to_status' => 'pending', 'actor_id' => $world['instructor']->id, 'reason' => null],
        ['from_status' => 'pending', 'to_status' => 'returned', 'actor_id' => $world['coordinator']->id, 'reason' => 'Wrong programme for this course.'],
    ]);

    expect(fn () => $section->approvalHistory()->first()->update(['reason' => 'rewritten']))->toThrow(LogicException::class);
});
