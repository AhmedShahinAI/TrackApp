<?php

declare(strict_types=1);

use App\Models\SupportRequest;
use Illuminate\Support\Sleep;

/*
|--------------------------------------------------------------------------
| T427 — no reply yet reads as "still being handled", not as an empty list (FR-136)
|--------------------------------------------------------------------------
*/

beforeEach(function (): void {
    Sleep::fake();
    supportWorld();
});

test('a request with no reply shows its state and says it is still being handled', function (string $locale): void {
    $student = enrolledStudent('441234567');
    $request = SupportRequest::factory()->create(['student_id' => $student->id]);

    $this->get('/support/status?lang='.$locale)->assertOk();

    $this->post('/support/status', ['reference' => $request->display_reference, 'university_id' => '441234567'])
        ->assertOk()
        ->assertSee(__('support.statuses.new'))
        ->assertSee(__('support.status.still_handled'));
})->with(['ar', 'en']);

test('once there is a reply, the still-being-handled sentence is gone', function (): void {
    $student = enrolledStudent('441234567');
    $request = SupportRequest::factory()->create(['student_id' => $student->id]);
    $reply = new App\Models\SupportResponse(['body' => 'Fixed.']);
    $reply->forceFill(['support_request_id' => $request->id, 'user_id' => aUser('SUPER_ADMIN')->id])->save();

    $this->post('/support/status', ['reference' => $request->reference, 'university_id' => '441234567'])
        ->assertOk()
        ->assertSee('Fixed.')
        ->assertDontSee(__('support.status.still_handled'));
});
