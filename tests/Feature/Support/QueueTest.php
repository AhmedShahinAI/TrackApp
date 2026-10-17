<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| T399 — the queue: who, where, what, which state, whose, and how long (FR-137)
|--------------------------------------------------------------------------
*/

test('each row carries the student, branch, category, state, assignee and waiting time', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['assigned_to' => $world['coordinator']->id], 90);

    $row = $this->actingAs($world['admin'])->getJson('/api/v1/support-requests')->assertOk()->json('data.0');

    expect($row['reference'])->toBe($request->display_reference)
        ->and($row['student']['university_id'])->toBe($request->student->university_id)
        ->and($row['branches'])->toBe([$world['branch']->campus_name_ar])
        ->and($row['category']['value'])->toBe('incorrect_data')
        ->and($row['status']['value'])->toBe('new')
        ->and($row['assignee']['id'])->toBe($world['coordinator']->id)
        ->and($row['waiting_seconds'])->toBeGreaterThanOrEqual(90 * 60)
        ->and($row['waiting_label'])->not->toBe('');
});

test('the oldest waiting request comes first', function (): void {
    $world = supportStaffWorld();
    $newest = supportRequestFor($world['branch'], [], 5);
    $oldest = supportRequestFor($world['branch'], [], 600);
    $middle = supportRequestFor($world['branch'], [], 60);

    $references = $this->actingAs($world['admin'])->getJson('/api/v1/support-requests')->collect('data')->pluck('reference')->all();

    expect($references)->toBe([$oldest->display_reference, $middle->display_reference, $newest->display_reference]);
});

test('the screen renders the queue for a coordinator, scoped to their branch', function (): void {
    $world = supportStaffWorld();
    $mine = supportRequestFor($world['branch']);
    $theirs = supportRequestFor($world['other']);

    $this->actingAs($world['coordinator'])->get('/support-requests')
        ->assertOk()
        ->assertSee($mine->display_reference)
        ->assertDontSee($theirs->display_reference);
});

test('an anonymised request shows the student as removed, to the Super Admin only', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch']);
    $request->forceFill(['student_id' => null, 'anonymised_at' => now()])->saveQuietly();

    $row = $this->actingAs($world['admin'])->getJson('/api/v1/support-requests')->json('data.0');

    expect($row['student'])->toBe(['name' => null, 'university_id' => null, 'removed' => true])
        ->and($row['branches'])->toBe([]);

    $this->actingAs($world['coordinator'])->getJson('/api/v1/support-requests')->assertJsonCount(0, 'data');
});

test('a page of requests is a fixed number of queries, not one per row', function (): void {
    $world = supportStaffWorld();

    foreach (range(1, 3) as $i) {
        supportRequestFor($world['branch']);
    }

    $this->actingAs($world['admin']);
    $this->getJson('/api/v1/support-requests')->assertOk();

    Illuminate\Support\Facades\DB::enableQueryLog();
    $this->getJson('/api/v1/support-requests')->assertOk();
    $few = count(Illuminate\Support\Facades\DB::getQueryLog());

    foreach (range(1, 6) as $i) {
        supportRequestFor($world['branch']);
    }

    Illuminate\Support\Facades\DB::flushQueryLog();
    $this->getJson('/api/v1/support-requests')->assertOk();
    $many = count(Illuminate\Support\Facades\DB::getQueryLog());
    Illuminate\Support\Facades\DB::disableQueryLog();

    expect($many)->toBe($few);
});

test('a request no longer waiting on staff carries no waiting time', function (string $status): void {
    $world = supportStaffWorld();
    supportRequestFor($world['branch'], ['status' => $status, 'rejection_reason' => $status === 'rejected' ? 'Out of scope' : null], 60 * 24 * 90);

    $row = $this->actingAs($world['admin'])->getJson('/api/v1/support-requests')->json('data.0');

    expect($row['waiting_seconds'])->toBeNull()
        ->and($row['waiting_label'])->toBeNull();
})->with(['resolved', 'rejected', 'closed']);
