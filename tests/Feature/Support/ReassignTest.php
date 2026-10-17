<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T405 — reassignment: recorded both ways, Super Admin only, never into the dark (FR-152)
|--------------------------------------------------------------------------
*/

test('both the previous and the new assignee are recorded', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['status' => 'in_progress', 'assigned_to' => $world['admin']->id]);

    $this->actingAs($world['admin'])
        ->patchJson('/api/v1/support-requests/'.$request->reference.'/assignee', ['assigned_to' => $world['coordinator']->id])
        ->assertOk()
        ->assertJsonPath('data.assignee.id', $world['coordinator']->id);

    $entry = DB::table('audit_logs')->where('entity_id', $request->id)->where('action', 'support.reassigned')->sole();

    expect(json_decode((string) $entry->old_values, true))->toBe(['assigned_to' => $world['admin']->id])
        ->and(json_decode((string) $entry->new_values, true))->toBe(['assigned_to' => $world['coordinator']->id]);
});

test('unassigning is allowed, and recorded', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['assigned_to' => $world['coordinator']->id]);

    $this->actingAs($world['admin'])
        ->patchJson('/api/v1/support-requests/'.$request->reference.'/assignee', ['assigned_to' => null])
        ->assertOk();

    expect($request->fresh()?->assigned_to)->toBeNull()
        ->and(supportAuditActions($request))->toBe(['support.reassigned']);
});

test('a coordinator may not reassign', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch']);

    $this->actingAs($world['coordinator'])
        ->patchJson('/api/v1/support-requests/'.$request->reference.'/assignee', ['assigned_to' => $world['admin']->id])
        ->assertForbidden();
});

test('a request is never given to someone who could not open it', function (string $who): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch']);

    $target = match ($who) {
        // No support.view at all.
        'an instructor' => $world['instructor'],
        // support.view, but the student is in another branch.
        'a coordinator of another branch' => $world['outsider'],
        // Deactivated: holds the permission and can open nothing.
        'a deactivated coordinator' => tap($world['coordinator'], fn ($user) => $user->forceFill(['is_active' => false])->saveQuietly()),
        default => throw new LogicException("No such assignee case: $who"),
    };

    $this->actingAs($world['admin'])
        ->patchJson('/api/v1/support-requests/'.$request->reference.'/assignee', ['assigned_to' => $target->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors('assigned_to');

    expect($request->fresh()?->assigned_to)->toBeNull();
})->with(['an instructor', 'a coordinator of another branch', 'a deactivated coordinator']);

test('the screen offers only people who could open the request', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['status' => 'in_progress']);

    $html = (string) $this->actingAs($world['admin'])->get(route('support-requests.show', $request))->getContent();

    expect($html)->toContain('value="'.$world['coordinator']->id.'"')
        ->and($html)->not->toContain('value="'.$world['outsider']->id.'"')
        ->and($html)->not->toContain('value="'.$world['instructor']->id.'"');
});

test('the reassignment control costs the same queries however many staff there are', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['status' => 'in_progress']);

    $this->actingAs($world['admin']);
    $this->get(route('support-requests.show', $request))->assertOk();

    $count = function () use ($request): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('support-requests.show', $request))->assertOk();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $queries;
    };

    $few = $count();

    foreach (range(1, 8) as $i) {
        aUser('BRANCH_COORDINATOR')->coordinatorBranches()->sync([$world['other']->id]);
    }

    expect($count())->toBe($few);
});

test('the screen never offers staff of another organization (SEC-055)', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['status' => 'in_progress']);
    $foreignAdmin = aUser('SUPER_ADMIN', ['organization_id' => App\Models\Organization::factory()->create(['type' => 'college'])->id]);

    $html = (string) $this->actingAs($world['admin'])->get(route('support-requests.show', $request))->getContent();

    expect($html)->toContain('value="'.$world['coordinator']->id.'"')
        ->and($html)->not->toContain('value="'.$foreignAdmin->id.'"');
});
