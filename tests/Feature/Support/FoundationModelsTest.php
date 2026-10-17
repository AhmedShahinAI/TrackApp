<?php

declare(strict_types=1);

use App\Models\AcademicTerm;
use App\Models\Branch;
use App\Models\Section;
use App\Models\SectionEnrollment;
use App\Models\Student;
use App\Models\SupportRequest;
use App\Models\SupportResponse;
use App\Modules\Support\Enums\SupportRequestStatus;
use App\Modules\Support\Services\ReferenceGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Phase 19 — the support schema and models (T360–T365)
|--------------------------------------------------------------------------
|
| The checkpoint's first half: the two tables migrate with the columns and indexes data-model.md
| names, the request's reference and rejection rules hold at the model, a response cannot be edited
| or removed, the audit entry is keyed by contract and carries no free text, and the branch scope
| reaches a request through its student's active enrollments — and reaches nothing for an
| instructor.
|
*/

/**
 * A request whose student sits in a section of $branch, with the enrollment in $status.
 */
function supportRequestIn(Branch $branch, string $status = 'active'): SupportRequest
{
    $term = AcademicTerm::factory()->current()->create();
    $section = Section::factory()->create(['branch_id' => $branch->id, 'term_id' => $term->id]);
    $student = Student::factory()->create();

    SectionEnrollment::factory()
        ->state($status === 'withdrawn' ? SectionEnrollment::factory()->withdrawn()->raw() : [])
        ->create(['student_id' => $student->id, 'section_id' => $section->id]);

    return SupportRequest::factory()->create(['student_id' => $student->id, 'term_id' => $term->id]);
}

test('support_requests has the columns, defaults and indexes T360 and T361 name', function (): void {
    expect(Schema::getColumnListing('support_requests'))->toEqualCanonicalizing([
        'id', 'reference', 'student_id', 'term_id', 'category', 'description', 'contact', 'status',
        'rejection_reason', 'assigned_to', 'submitted_ip', 'anonymised_at', 'created_at', 'updated_at',
    ]);

    $indexes = collect(Schema::getIndexes('support_requests'))
        ->mapWithKeys(fn (array $index): array => [implode(',', $index['columns']) => $index['unique']]);

    expect($indexes->get('reference'))->toBeTrue();

    foreach (['status,created_at', 'student_id', 'category,created_at', 'assigned_to', 'created_at'] as $columns) {
        expect($indexes->has($columns))->toBeTrue("missing index ($columns)");
    }
});

test('support_responses has no updated_at column — append-only at the schema', function (): void {
    expect(Schema::getColumnListing('support_responses'))
        ->toEqualCanonicalizing(['id', 'support_request_id', 'user_id', 'body', 'created_at']);

    $indexes = collect(Schema::getIndexes('support_responses'))->map(fn (array $i): string => implode(',', $i['columns']));

    expect($indexes)->toContain('support_request_id,created_at');
});

test('a new request starts in new, and its reference is shown hyphenated', function (): void {
    $request = SupportRequest::factory()->create();

    expect($request->status)->toBe(SupportRequestStatus::New)
        ->and($request->display_reference)->toBe(ReferenceGenerator::format($request->reference))
        ->and(ReferenceGenerator::normalise($request->display_reference))->toBe($request->reference);
});

test('a reference is never changed once stored', function (): void {
    $request = SupportRequest::factory()->create();

    expect(fn () => $request->forceFill(['reference' => (new ReferenceGenerator)->candidate()])->save())
        ->toThrow(LogicException::class);
});

test('a request cannot be rejected without a reason (FR-149)', function (): void {
    $request = SupportRequest::factory()->create();

    expect(fn () => $request->forceFill(['status' => 'rejected', 'rejection_reason' => '  '])->save())
        ->toThrow(LogicException::class);

    $request->forceFill(['status' => 'rejected', 'rejection_reason' => 'Not a platform matter'])->save();

    expect($request->fresh()?->status)->toBe(SupportRequestStatus::Rejected);
});

test('a response can be added but never edited or deleted', function (): void {
    $response = SupportResponse::factory()->create();

    expect($response->created_at)->not->toBeNull()
        ->and(fn () => $response->forceFill(['body' => 'edited'])->save())->toThrow(LogicException::class)
        ->and(fn () => $response->delete())->toThrow(LogicException::class)
        ->and(SupportResponse::query()->whereKey($response->id)->value('body'))->not->toBe('edited');
});

test('responses read oldest first', function (): void {
    $request = SupportRequest::factory()->create();
    $author = aUser('SUPER_ADMIN');

    foreach (['second', 'first'] as $i => $body) {
        $response = new SupportResponse(['body' => $body]);
        $response->forceFill([
            'support_request_id' => $request->id,
            'user_id' => $author->id,
            'created_at' => now()->subMinutes($i * 10),
        ])->save();
    }

    expect($request->responses()->pluck('body')->all())->toBe(['first', 'second']);
});

test('creation is audited as support.request_created, without the description or contact', function (): void {
    DB::table('audit_logs')->delete();

    $request = SupportRequest::factory()->create([
        'description' => 'My section is missing from the list',
        'contact' => '0500000000',
    ]);

    $entries = DB::table('audit_logs')->where('entity_type', 'SupportRequest')->get();

    expect($entries)->toHaveCount(1)
        ->and($entries[0]->action)->toBe('support.request_created')
        ->and($entries[0]->user_id)->toBeNull();

    $payload = json_decode((string) $entries[0]->new_values, true);

    expect($payload)->toHaveKeys(['reference', 'student_id', 'term_id', 'category', 'submitted_ip'])
        ->and(array_intersect(['description', 'contact'], array_keys($payload)))->toBe([])
        ->and((string) $entries[0]->new_values)->not->toContain('My section is missing')
        ->and((string) $entries[0]->new_values)->not->toContain('0500000000')
        ->and($request->id)->toBe((int) $entries[0]->entity_id);
});

test('a coordinator sees a request through the student\'s active enrollment in their branch', function (): void {
    $mine = Branch::factory()->create();
    $other = Branch::factory()->create();

    $visible = supportRequestIn($mine);
    $elsewhere = supportRequestIn($other);
    $withdrawn = supportRequestIn($mine, 'withdrawn');

    $coordinator = aUser('BRANCH_COORDINATOR');
    $coordinator->coordinatorBranches()->sync([$mine->id]);

    $this->actingAs($coordinator);

    expect(SupportRequest::query()->pluck('id')->all())->toBe([$visible->id])
        ->and(SupportRequest::query()->find($elsewhere->id))->toBeNull()
        ->and(SupportRequest::query()->find($withdrawn->id))->toBeNull();
});

test('a student enrolled in two branches is visible to both coordinators', function (): void {
    $a = Branch::factory()->create();
    $b = Branch::factory()->create();
    $request = supportRequestIn($a);

    $section = Section::factory()->create(['branch_id' => $b->id]);
    SectionEnrollment::factory()->create(['student_id' => $request->student_id, 'section_id' => $section->id]);

    foreach ([$a, $b] as $branch) {
        $coordinator = aUser('BRANCH_COORDINATOR');
        $coordinator->coordinatorBranches()->sync([$branch->id]);
        $this->actingAs($coordinator);

        expect(SupportRequest::query()->pluck('id')->all())->toBe([$request->id]);
    }
});

test('an instructor reaches no request — not even one from a student they teach', function (): void {
    $branch = Branch::factory()->create();
    $request = supportRequestIn($branch);

    $section = Section::query()->withoutGlobalScopes()
        ->whereIn('id', SectionEnrollment::query()->withoutGlobalScopes()->where('student_id', $request->student_id)->select('section_id'))
        ->firstOrFail();

    $instructor = aUser('INSTRUCTOR');
    $section->forceFill(['instructor_id' => $instructor->id])->saveQuietly();

    $this->actingAs($instructor);

    // BranchScope stands aside for a section-based caller; without the section constraint on the
    // model this would be every request in the college.
    expect($instructor->accessibleSectionIds())->toContain($section->id)
        ->and(SupportRequest::query()->count())->toBe(0);
});

test('an anonymised request is visible to the Super Admin only', function (): void {
    $branch = Branch::factory()->create();
    $request = supportRequestIn($branch);
    $request->forceFill(['student_id' => null, 'anonymised_at' => now()])->saveQuietly();

    $coordinator = aUser('BRANCH_COORDINATOR');
    $coordinator->coordinatorBranches()->sync([$branch->id]);

    $this->actingAs($coordinator);
    expect(SupportRequest::query()->count())->toBe(0);

    $this->actingAs(aUser('SUPER_ADMIN'));
    expect(SupportRequest::query()->pluck('id')->all())->toBe([$request->id]);
});

test('with no one signed in the scope is inert, which the public controllers rely on', function (): void {
    $request = supportRequestIn(Branch::factory()->create());

    expect(auth()->check())->toBeFalse()
        ->and(SupportRequest::query()->pluck('id')->all())->toBe([$request->id]);
});

test('every request status has its own label in both languages, for the badge to be given', function (): void {
    // StatusBadgeRequiresLabelTest exempts support_requests because this vocabulary is the support
    // feature's own; this is the half of that bargain that keeps the exemption honest.
    foreach (['ar', 'en'] as $locale) {
        app()->setLocale($locale);

        foreach (SupportRequestStatus::cases() as $status) {
            expect(__($status->labelKey()))->not->toBe($status->labelKey(), "[$locale] {$status->value}");
        }
    }

    expect(array_map(fn (SupportRequestStatus $s): string => $s->value, SupportRequestStatus::cases()))
        ->toBe(['new', 'in_progress', 'resolved', 'closed', 'rejected']);
});

// ── Phase 19 review ───────────────────────────────────────────────────────────────────────────────

test('an Eloquent update or delete writes no audit entry of its own — the named actions do', function (): void {
    $request = SupportRequest::factory()->create();
    DB::table('audit_logs')->delete();

    // What the retention sweep will clear: the old values must not be copied into a 5-year entry.
    $request->forceFill(['status' => 'in_progress', 'student_id' => null, 'submitted_ip' => '0.0.0.0'])->save();
    $request->delete();

    expect(DB::table('audit_logs')->count())->toBe(0);
});

test('a request is never created without its student, its reference or its address', function (array $missing): void {
    $attributes = SupportRequest::factory()->raw($missing);

    expect(fn () => (new SupportRequest)->forceFill($attributes)->save())->toThrow(LogicException::class)
        ->and(SupportRequest::query()->count())->toBe(0);
})->with([
    'no student' => [['student_id' => null]],
    'no reference' => [['reference' => '']],
    'no address' => [['submitted_ip' => null]],
    'an empty address' => [['submitted_ip' => '']],
]);

test('a coordinator who also teaches still sees their branch\'s queue', function (): void {
    $branch = Branch::factory()->create();
    $request = supportRequestIn($branch);

    $coordinator = aUser('BRANCH_COORDINATOR');
    $coordinator->coordinatorBranches()->sync([$branch->id]);
    Section::factory()->create(['branch_id' => $branch->id, 'instructor_id' => $coordinator->id]);

    $this->actingAs($coordinator);

    expect($coordinator->accessibleSectionIds())->not->toBe([])
        ->and(SupportRequest::query()->pluck('id')->all())->toBe([$request->id]);
});

test('an enrollment in a section not yet approved grants no visibility', function (): void {
    $branch = Branch::factory()->create();
    $term = AcademicTerm::factory()->current()->create();
    $student = Student::factory()->create();
    SectionEnrollment::factory()->create([
        'student_id' => $student->id,
        'section_id' => Section::factory()->pending()->create(['branch_id' => $branch->id, 'term_id' => $term->id])->id,
    ]);
    SupportRequest::factory()->create(['student_id' => $student->id, 'term_id' => $term->id]);

    $coordinator = aUser('BRANCH_COORDINATOR');
    $coordinator->coordinatorBranches()->sync([$branch->id]);
    $this->actingAs($coordinator);

    expect(SupportRequest::query()->count())->toBe(0);
});
