<?php

declare(strict_types=1);

use App\Console\Commands\CoreCounts;
use App\Models\ImportJob;
use App\Modules\Imports\Pipeline\MetadataBlockReader;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T322 — the metadata block above the header row (FR-097, spec Edge Cases)
|--------------------------------------------------------------------------
*/

test('the block is read into campus, degree, course, activity and section, and the header is found after it', function (): void {
    $file = registrarFile(rows: registrarRows(3));

    $block = (new MetadataBlockReader)->read($file->getRealPath(), 'xlsx');

    expect($block)->not->toBeNull()
        ->and($block['metadata'])->toBe([
            'campus' => 'المجمعة (طلاب)',
            'degree' => 'الدبلوم المتوسط',
            'course' => 'برمجة 2',
            'activity' => 'محاضرة',
            'section' => '2298',
        ])
        ->and($block['header_row'])->toBe(7)
        ->and($block['headers'])->toBe(REGISTRAR_HEADERS)
        ->and($block['rows_above_header'])->toBe(5);
});

test('labels are matched, not positions: a reordered block with a colon and another spelling still reads', function (): void {
    $file = registrarFile([
        'الشعبة:' => '2298',
        'اسم المقرر' => 'برمجة 2',
        'المقر' => 'المجمعة (طلاب)',
        'ملاحظة' => 'نص لا تعرفه المنصة',
    ], registrarRows(1));

    $block = (new MetadataBlockReader)->read($file->getRealPath(), 'xlsx');

    // An unknown label ends the block: the row after the known three is read as the header.
    expect($block['metadata'])->toBe(['section' => '2298', 'course' => 'برمجة 2', 'campus' => 'المجمعة (طلاب)'])
        ->and(MetadataBlockReader::missing($block['metadata']))->toBe([]);
});

test('the upload returns the parsed block and what it resolved to (contracts/imports.md 201)', function (): void {
    $world = registrarWorld();

    $response = postRegistrar($world, registrarFile(rows: registrarRows(4)))->assertCreated();

    expect($response->json('data'))->toMatchArray([
        'mode' => 'registrar',
        'status' => 'uploaded',
        'total_rows' => 4,
        'instructor_id' => $world['instructor']->id,
        'file_metadata' => [
            'campus' => 'المجمعة (طلاب)', 'degree' => 'الدبلوم المتوسط', 'course' => 'برمجة 2',
            'activity' => 'محاضرة', 'section' => '2298',
        ],
    ])
        ->and($response->json('data.resolved'))->toMatchArray([
            'branch_id' => $world['branch']->id,
            'course_id' => $world['course']->id,
            'section_id' => null,
            'degree' => 'intermediate_diploma',
            'activity' => 'lecture',
        ])
        ->and($response->json('data.suggested_mapping'))->toBe([
            'sequence' => 'تسلسل', 'university_id' => 'رقم الطالب', 'student_name' => 'اسم الطالب', 'academic_status' => 'الحالة',
        ]);
});

test('import.uploaded carries the filename and the parsed block', function (): void {
    $world = registrarWorld();
    DB::table('audit_logs')->delete();

    postRegistrar($world, registrarFile(rows: registrarRows(1), name: 'export.xlsx'))->assertCreated();

    $entry = DB::table('audit_logs')->where('action', 'import.uploaded')->sole();
    $new = json_decode((string) $entry->new_values, true);

    expect($new['original_filename'])->toBe('export.xlsx')
        ->and($new['mode'])->toBe('registrar')
        ->and($new['metadata']['campus'])->toBe('المجمعة (طلاب)')
        ->and($new['instructor_id'])->toBe($world['instructor']->id);
});

test('a file without a block is refused at upload, naming the other modes, and nothing is kept', function (): void {
    $world = registrarWorld();

    // A central roster: its first row is a header, not a block row.
    $roster = rosterFile([rosterRow(importWorld())]);
    $before = CoreCounts::snapshot();

    $response = postRegistrar($world, $roster)->assertUnprocessable();

    // In the operator's language (the fixture admin reads Arabic), naming both other modes.
    expect($response->json('errors.file.0'))->toBe(__('imports.registrar.not_registrar_export', [], 'ar'))
        ->and($response->json('errors.file.0'))->toContain('الاستيراد المركزي')->toContain('صفحة الشعبة')
        ->and(__('imports.registrar.not_registrar_export', [], 'en'))->toContain('central import')->toContain('section’s page')
        ->and(ImportJob::query()->withoutGlobalScopes()->count())->toBe(0)
        ->and(CoreCounts::snapshot())->toBe($before);
});

test('a block missing campus, course or section is refused, naming what is missing', function (): void {
    $world = registrarWorld();

    $response = postRegistrar($world, registrarFile(['الدرجة' => 'الدبلوم المتوسط', 'اسم المقرر' => 'برمجة 2'], registrarRows(1)))
        ->assertUnprocessable();

    expect($response->json('errors.file.0'))->toBe(__('imports.registrar.metadata_missing', [
        'fields' => __('imports.registrar.fields.campus').'، '.__('imports.registrar.fields.section'),
    ]));
});
