<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T484 — exactly one audit entry per decision, with the previous and new status and the reason (FR-182)
|--------------------------------------------------------------------------
*/

test('each decision writes exactly one entry under its own key, and no generic section.updated', function (string $decision, string $key, ?string $reason): void {
    $world = approvalWorld();
    $section = sectionCreatedBy($world['instructor'], $world);
    DB::table('audit_logs')->delete();

    decideSection($world['coordinator'], $section, $decision, $reason)->assertOk();

    $entries = DB::table('audit_logs')->where('entity_type', 'Section')->where('entity_id', $section->id)->get();
    $entry = $entries->sole();

    expect($entry->action)->toBe($key)
        ->and((int) $entry->user_id)->toBe($world['coordinator']->id)
        ->and(json_decode((string) $entry->old_values, true))->toBe(['status' => 'pending'])
        ->and(json_decode((string) $entry->new_values, true))->toBe(array_filter([
            'status' => App\Modules\Sections\Enums\SectionStatus::forDecision($decision)->value,
            'reason' => $reason,
        ]));
})->with([
    'approve' => ['approve', 'section.approved', null],
    'return' => ['return', 'section.returned', 'Wrong programme for this course.'],
    'reject' => ['reject', 'section.rejected', 'This section duplicates another one.'],
]);

test('a resubmission writes one section.resubmitted, with what the creator changed', function (): void {
    $world = approvalWorld();
    $section = sectionCreatedBy($world['instructor'], $world, ['section_number' => '6601']);
    decideSection($world['coordinator'], $section, 'return', 'Use number 6602, the timetable one.')->assertOk();
    DB::table('audit_logs')->delete();

    app('auth')->forgetGuards();
    $this->actingAs($world['instructor'])->patchJson('/api/v1/sections/'.$section->id.'/resubmit', ['section_number' => '6602'])->assertOk();

    $entry = DB::table('audit_logs')->where('entity_type', 'Section')->where('entity_id', $section->id)->sole();

    expect($entry->action)->toBe('section.resubmitted')
        ->and(json_decode((string) $entry->old_values, true))->toBe(['status' => 'returned', 'section_number' => '6601'])
        ->and(json_decode((string) $entry->new_values, true))->toBe(['status' => 'pending', 'section_number' => '6602']);
});

test('a refused decision writes no decision entry', function (): void {
    $world = approvalWorld();
    $section = sectionCreatedBy($world['coordinator'], $world);
    DB::table('audit_logs')->delete();

    decideSection($world['coordinator'], $section, 'approve')->assertForbidden();

    expect(DB::table('audit_logs')->where('action', 'like', 'section.%')->count())->toBe(0);
});
