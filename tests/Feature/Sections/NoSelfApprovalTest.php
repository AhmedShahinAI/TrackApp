<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| T475 — nobody approves their own work (FR-178, SC-040)
|--------------------------------------------------------------------------
|
| Both refusals are 403, not 404: the caller can see the section, and simply may not decide it.
|
*/

test('a creator cannot decide their own section, with any decision', function (string $decision): void {
    $world = approvalWorld();
    $section = sectionCreatedBy($world['coordinator'], $world);

    decideSection($world['coordinator'], $section, $decision, 'A reason long enough to pass.')->assertForbidden();

    expect($section->refresh()->status)->toBe('pending');
})->with(['approve', 'return', 'reject']);

test('a coordinator cannot decide a section created by another coordinator of the same branch', function (): void {
    $world = approvalWorld();
    $section = sectionCreatedBy($world['peer'], $world);

    // The coordinator can see it: their branch. They may not decide it.
    app('auth')->forgetGuards();
    $this->actingAs($world['coordinator'])->getJson('/api/v1/sections/'.$section->id)->assertOk();

    decideSection($world['coordinator'], $section, 'approve')->assertForbidden();

    expect($section->refresh()->status)->toBe('pending');
});

test('a coordinator\'s section is the Super Admin\'s to decide', function (): void {
    $world = approvalWorld();
    $section = sectionCreatedBy($world['coordinator'], $world);

    decideSection($world['admin'], $section, 'approve')->assertOk()->assertJsonPath('data.status', 'active');
});

test('a coordinator decides an instructor\'s section in their branch', function (): void {
    $world = approvalWorld();
    $section = sectionCreatedBy($world['instructor'], $world);

    decideSection($world['coordinator'], $section, 'approve')->assertOk()
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.reviewed_by', $world['coordinator']->id);
});

test('a coordinator who also teaches is still a peer, not a second pair of eyes', function (): void {
    $world = approvalWorld();

    // The peer coordinator also teaches in the branch, and creates a section as such.
    $world['peer']->assignRole('INSTRUCTOR');
    $section = sectionCreatedBy($world['peer'], $world);

    decideSection($world['coordinator'], $section, 'approve')->assertForbidden();
});
