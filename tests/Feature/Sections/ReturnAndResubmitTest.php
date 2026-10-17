<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| T479 — a return needs a reason, the creator sees it, and resubmitting sends it back (FR-179, FR-183)
|--------------------------------------------------------------------------
*/

test('a return or a rejection without a reason of at least 10 characters is refused', function (string $decision, ?string $reason): void {
    $world = approvalWorld();
    $section = sectionCreatedBy($world['instructor'], $world);

    decideSection($world['coordinator'], $section, $decision, $reason)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('reason');

    expect($section->refresh()->status)->toBe('pending');
})->with([
    'return, none' => ['return', null],
    'return, too short' => ['return', 'Too short'],
    'return, only spaces' => ['return', '            '],
    'reject, none' => ['reject', null],
]);

test('an approval needs no reason, and keeps none', function (): void {
    $world = approvalWorld();
    $section = sectionCreatedBy($world['instructor'], $world);

    decideSection($world['coordinator'], $section, 'approve', 'Looks right to me.')->assertOk()->assertJsonPath('data.review_reason', null);
});

test('the creator sees the reason on the section and can resubmit it', function (): void {
    $world = approvalWorld();
    $section = sectionCreatedBy($world['instructor'], $world);
    decideSection($world['coordinator'], $section, 'return', 'Wrong programme for this course.')->assertOk();

    app('auth')->forgetGuards();
    $world['instructor']->forgetScopeCache();
    $this->actingAs($world['instructor'], 'web')->get(route('sections.show', $section->id))->assertOk()
        ->assertSee('Wrong programme for this course.')
        ->assertSee(__('sections.approval.status.returned'))
        ->assertSee(__('sections.approval.actions.resubmit'));

    app('auth')->forgetGuards();
    $this->actingAs($world['instructor'])->patchJson('/api/v1/sections/'.$section->id.'/resubmit', [])
        ->assertOk()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.review_reason', null);

    // Back in the queue, and the return's reason is kept where it was written: the history.
    expect($section->approvalHistory()->pluck('to_status')->all())->toBe(['pending', 'returned', 'pending'])
        ->and($section->approvalHistory()->where('to_status', 'returned')->value('reason'))->toBe('Wrong programme for this course.');
});

test('the creator may fix what the return asked for in the same request', function (): void {
    $world = approvalWorld();
    $section = sectionCreatedBy($world['instructor'], $world);
    decideSection($world['coordinator'], $section, 'return', 'Use number 8899, the timetable one.')->assertOk();

    app('auth')->forgetGuards();
    $this->actingAs($world['instructor'])->patchJson('/api/v1/sections/'.$section->id.'/resubmit', ['section_number' => '8899'])
        ->assertOk();

    expect($section->refresh()->section_number)->toBe('8899')
        ->and($section->status)->toBe('pending');
});

test('only the creator resubmits, and only a returned section', function (): void {
    $world = approvalWorld();
    $section = sectionCreatedBy($world['instructor'], $world);

    // Pending, not returned: 409.
    app('auth')->forgetGuards();
    $this->actingAs($world['instructor'])->patchJson('/api/v1/sections/'.$section->id.'/resubmit', [])->assertConflict();

    decideSection($world['coordinator'], $section, 'return', 'Wrong programme for this course.')->assertOk();

    // Someone else, though they can see it: 403.
    app('auth')->forgetGuards();
    $this->actingAs($world['coordinator'])->patchJson('/api/v1/sections/'.$section->id.'/resubmit', [])->assertForbidden();

    expect($section->refresh()->status)->toBe('returned');
});
