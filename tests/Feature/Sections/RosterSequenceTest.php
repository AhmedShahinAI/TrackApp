<?php

declare(strict_types=1);

use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Models\Student;

/*
|--------------------------------------------------------------------------
| FR-111 — the registrar's sequence and status on the roster
|--------------------------------------------------------------------------
*/

/**
 * The university ids on one roster page, in the order shown.
 *
 * @return list<string>
 */
function rosterOrder(string $html): array
{
    preg_match_all('/<span dir="ltr" class="track-code">(\d{9})<\/span>/', $html, $matches);

    return $matches[1];
}

test('a sequenced roster is in the registrar\'s order, a withdrawn student keeps their place, and unsequenced rows follow', function (): void {
    $section = Section::factory()->create();
    $enrol = fn (string $uid, ?int $sequence, string $status = 'active') => SectionEnrollment::factory()->create([
        'section_id' => $section->id,
        'student_id' => Student::factory()->create(['university_id' => $uid])->id,
        'roster_sequence' => $sequence,
        'status' => $status,
        'withdrawn_at' => $status === 'withdrawn' ? now() : null,
    ]);

    $enrol('441000003', 3);
    $enrol('441000099', null);
    $enrol('441000001', 1);
    $enrol('441000002', 2, 'withdrawn');

    $html = (string) $this->actingAs(aUser('SUPER_ADMIN'))->get(route('sections.show', $section))->assertOk()->getContent();

    expect(rosterOrder($html))->toBe(['441000001', '441000002', '441000003', '441000099'])
        ->and($html)->toContain(__('students.fields.roster_sequence'));
});

test('the sequence column is on every page of a sequenced roster, not only on pages that happen to carry one', function (): void {
    $section = Section::factory()->create();

    // 25 sequenced rows fill page 1; the hand-added rows on page 2 have none.
    foreach (range(1, 25) as $i) {
        SectionEnrollment::factory()->create(['section_id' => $section->id, 'roster_sequence' => $i]);
    }
    SectionEnrollment::factory()->count(3)->create(['section_id' => $section->id, 'roster_sequence' => null]);

    $this->actingAs(aUser('SUPER_ADMIN'))->get(route('sections.show', ['section' => $section, 'roster' => 2]))
        ->assertOk()
        ->assertSee(__('students.fields.roster_sequence'));
});

test('an unsequenced roster has no sequence column and lists active students first', function (): void {
    $section = Section::factory()->create();
    SectionEnrollment::factory()->create([
        'section_id' => $section->id, 'status' => 'withdrawn', 'withdrawn_at' => now(),
        'student_id' => Student::factory()->create(['university_id' => '441000001'])->id,
    ]);
    SectionEnrollment::factory()->create([
        'section_id' => $section->id,
        'student_id' => Student::factory()->create(['university_id' => '441000002'])->id,
    ]);

    $html = (string) $this->actingAs(aUser('SUPER_ADMIN'))->get(route('sections.show', $section))->assertOk()->getContent();

    expect(rosterOrder($html))->toBe(['441000002', '441000001'])
        ->and($html)->not->toContain(__('students.fields.roster_sequence'));
});

test('each status has its own badge, and an enrollment no registrar file described has none', function (string $locale): void {
    $section = Section::factory()->create();
    SectionEnrollment::factory()->create(['section_id' => $section->id, 'academic_status' => 'excused']);
    SectionEnrollment::factory()->create(['section_id' => $section->id, 'academic_status' => null]);

    $this->actingAs(aUser('SUPER_ADMIN', ['locale' => $locale]))
        ->withSession(['locale' => $locale])
        ->get(route('sections.show', $section))
        ->assertOk()
        ->assertSee(__('students.fields.academic_status', [], $locale))
        ->assertSee('track-badge--warning', false)
        ->assertSee(__('status.excused', [], $locale))
        ->assertDontSee('status.unknown');
})->with(['ar', 'en']);
