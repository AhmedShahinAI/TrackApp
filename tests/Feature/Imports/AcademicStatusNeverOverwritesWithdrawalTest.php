<?php

declare(strict_types=1);

use App\Models\SectionEnrollment;
use App\Modules\Imports\Pipeline\ImportReasons;
use App\Modules\Students\Actions\WithdrawEnrollment;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T326 — the registrar's status and TRACK's withdrawal never overwrite each other (FR-111, SC-022)
|--------------------------------------------------------------------------
*/

/**
 * One student, imported once from a registrar file with the given status, then returned.
 *
 * @param  array<string, mixed>  $world
 */
function importedEnrollment(array $world, string $status): SectionEnrollment
{
    importRegistrar($world, registrarFile(rows: [['1', '441234567', 'طالب الاختبار', $status]]));

    return SectionEnrollment::query()->withoutGlobalScopes()->sole();
}

test('a registrar «منتظم» over a TRACK withdrawal leaves status, withdrawn_at and withdraw_reason untouched', function (): void {
    $world = registrarWorld();
    $enrollment = importedEnrollment($world, 'معتذر');

    $this->actingAs($world['admin']);
    app(WithdrawEnrollment::class)->execute($enrollment, 'انسحب بطلب من الطالب');
    $withdrawn = $enrollment->fresh()->only(['status', 'withdrawn_at', 'withdraw_reason']);

    // The registrar's next export still says regular.
    importRegistrar($world, registrarFile(rows: [['7', '441234567', 'طالب الاختبار', 'منتظم']]));

    $after = $enrollment->fresh();

    expect($after->only(['status', 'withdrawn_at', 'withdraw_reason']))->toEqual($withdrawn)
        ->and($after->status)->toBe('withdrawn')
        ->and($after->withdraw_reason)->toBe('انسحب بطلب من الطالب')
        // Both kept, side by side: the registrar's value and order did arrive.
        ->and($after->academic_status)->toBe('regular')
        ->and($after->roster_sequence)->toBe(7);
});

test('a TRACK withdrawal leaves academic_status untouched', function (): void {
    $world = registrarWorld();
    $enrollment = importedEnrollment($world, 'منتظم');

    $this->actingAs($world['admin']);
    app(WithdrawEnrollment::class)->execute($enrollment, 'تعارض في الجدول');

    expect($enrollment->fresh()->status)->toBe('withdrawn')
        ->and($enrollment->fresh()->academic_status)->toBe('regular');
});

test('the section roster shows both values for that student (SC-022)', function (): void {
    $world = registrarWorld();
    $enrollment = importedEnrollment($world, 'منتظم');

    $this->actingAs($world['admin']);
    app(WithdrawEnrollment::class)->execute($enrollment, 'تعارض في الجدول');

    $this->actingAs($world['admin'])->get(route('sections.show', $enrollment->section_id))
        ->assertOk()
        ->assertSee(__('status.withdrawn'))
        ->assertSee(__('status.regular'))
        ->assertSee('تعارض في الجدول');
});

test('a re-import writes no enrollment audit entry the contract does not list', function (): void {
    $world = registrarWorld();
    importedEnrollment($world, 'منتظم');
    DB::table('audit_logs')->delete();

    importRegistrar($world, registrarFile(rows: [['2', '441234567', 'طالب الاختبار', 'معتذر']]));

    expect(DB::table('audit_logs')->where('action', 'like', 'enrollment.%')->count())->toBe(0)
        ->and(SectionEnrollment::query()->withoutGlobalScopes()->sole()->academic_status)->toBe('excused');
});

test('an unreadable status or sequence is a warning, stored empty, and never erases an earlier value', function (): void {
    $world = registrarWorld();
    $enrollment = importedEnrollment($world, 'معتذر');

    importRegistrar($world, registrarFile(rows: [['', '441234567', 'طالب الاختبار', 'غير معروف']]));

    $job = App\Models\ImportJob::query()->withoutGlobalScopes()->latest('id')->firstOrFail();
    $reasons = collect(App\Models\ImportRow::query()->where('import_job_id', $job->id)->sole()->reasons)->pluck('reason_key')->all();

    expect($reasons)->toContain(ImportReasons::UNKNOWN_ACADEMIC_STATUS, ImportReasons::INVALID_SEQUENCE)
        ->and($enrollment->fresh()->only(['academic_status', 'roster_sequence']))->toBe(['academic_status' => 'excused', 'roster_sequence' => 1]);
});

test('a new enrollment with an unreadable status or sequence still imports, with both stored null', function (): void {
    $world = registrarWorld();

    importRegistrar($world, registrarFile(rows: [['أ', '441234567', 'طالب الاختبار', 'مؤجل']]));

    expect(SectionEnrollment::query()->withoutGlobalScopes()->sole()->only(['status', 'academic_status', 'roster_sequence']))
        ->toBe(['status' => 'active', 'academic_status' => null, 'roster_sequence' => null]);
});
