<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T158 — an edit records both sides of every field it changed
|--------------------------------------------------------------------------
|
| An entry naming only the new value answers "what is it now", which the row itself already answers.
| The question an audit trail exists for is "what was it before", and that one has no other source
| once the row has moved on. Both halves, for every changed field, or the entry is decorative.
|
| The matching matters as much as the presence: old_values and new_values must name the *same*
| fields. A payload carrying three new values and one old one looks complete in a screenshot and is
| unusable in an investigation.
|
*/

/**
 * The decoded payload of the single entry an edit produced.
 *
 * @return array{old: array<string, mixed>, new: array<string, mixed>}
 */
function payloadOfTheEditTo(Closure $edit, string $action): array
{
    DB::table('audit_logs')->delete();

    $edit();

    $entry = DB::table('audit_logs')->where('action', $action)->first();

    expect($entry)->not->toBeNull("no '$action' entry was written");

    return [
        'old' => (array) json_decode((string) $entry->old_values, true),
        'new' => (array) json_decode((string) $entry->new_values, true),
    ];
}

test('both sides are present and name the same fields', function (): void {
    $branch = Branch::factory()->create([
        'organization_id' => anOrganisation()->id,
        'campus_name_ar' => 'المجمعة',
        'is_active' => true,
    ]);

    ['old' => $old, 'new' => $new] = payloadOfTheEditTo(
        fn () => $branch->update(['campus_name_ar' => 'الزلفي', 'is_active' => false]),
        'branch.updated',
    );

    expect(array_keys($old))->toBe(array_keys($new));

    expect($old['campus_name_ar'])->toBe('المجمعة')
        ->and($new['campus_name_ar'])->toBe('الزلفي')
        ->and($old['is_active'])->toBeTrue()
        ->and($new['is_active'])->toBeFalse();
});

test('a field that did not change is in neither side', function (): void {
    $branch = Branch::factory()->create([
        'organization_id' => anOrganisation()->id,
        'campus_name_ar' => 'المجمعة',
        'campus_name_en' => 'Al-Majmaah',
    ]);

    ['old' => $old, 'new' => $new] = payloadOfTheEditTo(
        fn () => $branch->update(['campus_name_ar' => 'الزلفي']),
        'branch.updated',
    );

    /*
     * Narrow by design. An entry that repeated every column on every edit would make the one field
     * that moved impossible to find, which is the failure mode of logging everything.
     */
    expect($old)->toHaveKey('campus_name_ar')
        ->and($old)->not->toHaveKey('campus_name_en')
        ->and($new)->not->toHaveKey('campus_name_en');
});

test('an update that changes nothing audited writes no entry at all', function (): void {
    $section = Section::factory()->create();

    DB::table('audit_logs')->delete();

    // `updated_at` moves, but it is not in the allowlist. An entry here would be noise in the one
    // log that has to stay readable.
    $section->touch();

    expect(DB::table('audit_logs')->where('action', 'section.updated')->count())->toBe(0);
});

test('a create records the new side only, and a deactivation the old side only', function (): void {
    DB::table('audit_logs')->delete();
    $student = Student::factory()->create(['name_ar' => 'طالب']);

    $created = DB::table('audit_logs')->where('action', 'student.created')->first();

    expect((array) json_decode((string) $created->old_values, true))->toBe([])
        ->and((array) json_decode((string) $created->new_values, true))->toHaveKey('name_ar');

    $branch = Branch::factory()->create(['organization_id' => anOrganisation()->id]);
    DB::table('audit_logs')->delete();
    $branch->delete();

    $gone = DB::table('audit_logs')->where('action', 'branch.deactivated')->first();

    /*
     * The old side carries what the row held when it was deactivated. That is the only copy: the
     * row survives (constitution V) but will go on changing, and this entry is what says what it
     * looked like at the moment it left service.
     */
    expect((array) json_decode((string) $gone->old_values, true))->toHaveKey('campus_name_ar')
        ->and((array) json_decode((string) $gone->new_values, true))->toBe([]);

    expect($student->exists)->toBeTrue();
});

test('the entry names the entity it is about', function (): void {
    $branch = Branch::factory()->create(['organization_id' => anOrganisation()->id]);

    DB::table('audit_logs')->delete();
    $branch->update(['campus_name_ar' => 'الزلفي']);

    $entry = DB::table('audit_logs')->where('action', 'branch.updated')->first();

    expect($entry->entity_type)->toBe('Branch')
        ->and((int) $entry->entity_id)->toBe($branch->id);
});
