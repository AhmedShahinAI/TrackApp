<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| T400 — each of the seven filters narrows, and the total counts only what matches
|--------------------------------------------------------------------------
*/

/**
 * @param  array<string, mixed>  $query
 * @return list<string>
 */
function queueReferences(App\Models\User $as, array $query): array
{
    $response = test()->actingAs($as)->getJson('/api/v1/support-requests?'.http_build_query($query))->assertOk();

    /** @var list<array<string, mixed>> $data */
    $data = (array) $response->json('data');

    expect($response->json('meta.total'))->toBe(count($data));

    return collect($data)->pluck('reference')->sort()->values()->all();
}

test('status, category, assignee and date each narrow the queue', function (): void {
    $world = supportStaffWorld();
    $a = supportRequestFor($world['branch'], ['status' => 'in_progress', 'category' => 'other', 'assigned_to' => $world['admin']->id], 60 * 24 * 10);
    $b = supportRequestFor($world['branch'], [], 30);

    expect(queueReferences($world['admin'], ['status' => 'in_progress']))->toBe([$a->display_reference])
        ->and(queueReferences($world['admin'], ['category' => 'other']))->toBe([$a->display_reference])
        ->and(queueReferences($world['admin'], ['assigned_to' => $world['admin']->id]))->toBe([$a->display_reference])
        ->and(queueReferences($world['admin'], ['date_from' => now()->subDay()->toDateString()]))->toBe([$b->display_reference])
        ->and(queueReferences($world['admin'], ['date_to' => now()->subDays(5)->toDateString()]))->toBe([$a->display_reference]);
});

test('q finds a request by reference however it is typed, by University ID, or by name', function (): void {
    $world = supportStaffWorld();
    $a = supportRequestFor($world['branch']);
    $a->student->forceFill(['name_ar' => 'سلمى العتيبي'])->saveQuietly();
    supportRequestFor($world['branch']);

    foreach ([strtolower($a->display_reference), $a->reference, $a->student->university_id, 'سلمى'] as $q) {
        expect(queueReferences($world['admin'], ['q' => $q]))->toBe([$a->display_reference], "q=$q");
    }

    // A LIKE wildcard is a character, not a pattern.
    expect(queueReferences($world['admin'], ['q' => '%']))->toBe([]);
});

test('branch narrows for the Super Admin', function (): void {
    $world = supportStaffWorld();
    $mine = supportRequestFor($world['branch']);
    supportRequestFor($world['other']);

    expect(queueReferences($world['admin'], ['branch_id' => $world['branch']->id]))->toBe([$mine->display_reference]);
});

test('a coordinator naming another branch gets their own scope back, not a probe answer', function (): void {
    $world = supportStaffWorld();
    $mine = supportRequestFor($world['branch']);
    supportRequestFor($world['other']);

    expect(queueReferences($world['coordinator'], ['branch_id' => $world['other']->id]))->toBe([$mine->display_reference])
        ->and(queueReferences($world['coordinator'], ['branch_id' => $world['branch']->id]))->toBe([$mine->display_reference]);
});

test('an unknown status or category is refused, not silently ignored', function (): void {
    $world = supportStaffWorld();

    $this->actingAs($world['admin'])->getJson('/api/v1/support-requests?status=lost')->assertStatus(422);
    $this->actingAs($world['admin'])->getJson('/api/v1/support-requests?category=free_text')->assertStatus(422);
});

test('the date filters mean the day where the reader is, not the UTC day', function (): void {
    config(['app.display_timezone' => 'Asia/Riyadh']);
    $world = supportStaffWorld();

    // 01:30 on the 12th in Riyadh is 22:30 on the 11th in UTC.
    $request = supportRequestFor($world['branch']);
    $request->forceFill(['created_at' => Illuminate\Support\Carbon::parse('2026-10-11 22:30:00', 'UTC')])->saveQuietly();

    expect(queueReferences($world['admin'], ['date_from' => '2026-10-12']))->toBe([$request->display_reference])
        ->and(queueReferences($world['admin'], ['date_to' => '2026-10-11']))->toBe([])
        ->and(queueReferences($world['admin'], ['date_from' => '2026-10-12', 'date_to' => '2026-10-12']))->toBe([$request->display_reference]);
});

test('a date that is not a calendar day, or a range that ends before it starts, is refused', function (array $query): void {
    $world = supportStaffWorld();

    $this->actingAs($world['admin'])->getJson('/api/v1/support-requests?'.http_build_query($query))->assertStatus(422);
})->with([
    'a datetime' => [['date_to' => '2026-10-11T10:00']],
    'another format' => [['date_from' => '11/10/2026']],
    'reversed' => [['date_from' => '2026-10-12', 'date_to' => '2026-10-11']],
]);

test('a coordinator\'s assignee filter lists their own branch\'s staff, never another branch\'s', function (): void {
    $world = supportStaffWorld();
    supportRequestFor($world['branch']);

    $html = (string) $this->actingAs($world['coordinator'])->get('/support-requests')->getContent();

    expect($html)->toContain('value="'.$world['coordinator']->id.'"')
        ->and($html)->not->toContain('value="'.$world['outsider']->id.'"');
});
