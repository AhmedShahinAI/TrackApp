<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| T406 — the student's contact is unverified, in the payload and on the screen (FR-145)
|--------------------------------------------------------------------------
*/

test('the payload carries the contact with verified: false, never as a bare string', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['contact' => 'student@example.com']);

    $contact = $this->actingAs($world['admin'])->getJson('/api/v1/support-requests/'.$request->reference)->json('data.contact');

    expect($contact)->toBe(['value' => 'student@example.com', 'verified' => false]);
});

test('no contact is still the flagged shape, with no value', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['contact' => null]);

    expect($this->actingAs($world['admin'])->getJson('/api/v1/support-requests/'.$request->reference)->json('data.contact'))
        ->toBe(['value' => null, 'verified' => false]);
});

test('the screen shows a visible unverified marker beside it, in words', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['contact' => '0555000000']);

    $this->actingAs($world['admin'])->get(route('support-requests.show', $request))
        ->assertSee('0555000000')
        ->assertSee('track-support__unverified', false)
        ->assertSee(__('support.staff.unverified'));
});
