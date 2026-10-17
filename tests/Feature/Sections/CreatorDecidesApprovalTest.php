<?php

declare(strict_types=1);

use App\Models\SectionApprovalHistory;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T474 — who creates a section decides whether it needs approving (FR-171, FR-172, SC-039)
|--------------------------------------------------------------------------
*/

test('a Super Admin\'s section is active at once; an instructor\'s and a coordinator\'s wait', function (string $creator, string $status, bool $requiresApproval): void {
    $world = approvalWorld();

    $response = createSectionAs($world[$creator], $world)->assertCreated();

    expect($response->json('data.status'))->toBe($status)
        ->and($response->json('data.requires_approval'))->toBe($requiresApproval)
        ->and($response->json('data.created_by'))->toBe($world[$creator]->id);
})->with([
    'Super Admin' => ['admin', 'active', false],
    'instructor' => ['instructor', 'pending', true],
    // The first version read `section.approve`, which a coordinator holds, and created theirs active.
    'coordinator' => ['coordinator', 'pending', true],
]);

test('the status cannot be set from the request', function (): void {
    $world = approvalWorld();

    $response = createSectionAs($world['instructor'], $world, ['status' => 'active', 'requires_approval' => false, 'created_by' => $world['admin']->id])->assertCreated();

    expect($response->json('data.status'))->toBe('pending')
        ->and($response->json('data.requires_approval'))->toBeTrue()
        ->and($response->json('data.created_by'))->toBe($world['instructor']->id);
});

test('creation opens the approval history and is audited with the creator\'s role', function (): void {
    $world = approvalWorld();
    DB::table('audit_logs')->delete();

    $section = sectionCreatedBy($world['instructor'], $world);

    $history = SectionApprovalHistory::query()->where('section_id', $section->id)->sole();
    $created = DB::table('audit_logs')->where('action', 'section.created')->where('entity_id', $section->id)->sole();

    expect($history->from_status)->toBeNull()
        ->and($history->to_status)->toBe('pending')
        ->and((int) $history->actor_id)->toBe($world['instructor']->id)
        ->and(json_decode((string) $created->new_values, true))->toMatchArray(['status' => 'pending', 'creator_role' => 'INSTRUCTOR'])
        ->and(sectionApprovalActions($section))->toBe(['section.created', 'section.submitted_for_approval']);
});

test('a Super Admin\'s section is not submitted for approval at all', function (): void {
    $world = approvalWorld();

    $section = sectionCreatedBy($world['admin'], $world);

    expect(sectionApprovalActions($section))->toBe(['section.created']);
});
