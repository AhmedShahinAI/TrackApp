<?php

declare(strict_types=1);

use App\Models\SupportResponse;

/*
|--------------------------------------------------------------------------
| T402 — a response is recorded against its author and refused on a closed request (FR-150, FR-151)
|--------------------------------------------------------------------------
*/

test('a response is recorded against its author and is part of the request\'s thread', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['status' => 'in_progress']);

    $this->actingAs($world['coordinator'])
        ->postJson('/api/v1/support-requests/'.$request->reference.'/responses', ['body' => 'We have corrected your section.'])
        ->assertCreated()
        ->assertJsonPath('data.body', 'We have corrected your section.');

    $response = SupportResponse::query()->sole();

    expect($response->user_id)->toBe($world['coordinator']->id)
        ->and($response->support_request_id)->toBe($request->id);

    // What the student's status page will read (Phase 22): the request's own thread.
    $thread = $this->actingAs($world['admin'])->getJson('/api/v1/support-requests/'.$request->reference)->json('data.responses');

    expect($thread)->toHaveCount(1)
        ->and($thread[0]['body'])->toBe('We have corrected your section.')
        ->and($thread[0]['author']['id'])->toBe($world['coordinator']->id);
});

test('a response on a closed request is refused with 409 and nothing is recorded', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['status' => 'closed']);

    $this->actingAs($world['admin'])
        ->postJson('/api/v1/support-requests/'.$request->reference.'/responses', ['body' => 'Late reply'])
        ->assertStatus(409);

    expect(SupportResponse::query()->count())->toBe(0)
        ->and(supportAuditActions($request))->toBe([]);
});

test('answering a new request opens it first', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch']);

    $this->actingAs($world['admin'])
        ->postJson('/api/v1/support-requests/'.$request->reference.'/responses', ['body' => 'On it.'])
        ->assertCreated();

    expect($request->fresh()?->status->value)->toBe('in_progress')
        ->and(supportAuditActions($request))->toBe(['support.status_changed', 'support.responded']);
});

test('an empty or over-long response is refused by its own field rule', function (string $body): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['status' => 'in_progress']);

    $this->actingAs($world['admin'])
        ->postJson('/api/v1/support-requests/'.$request->reference.'/responses', ['body' => $body])
        ->assertStatus(422)
        ->assertJsonValidationErrors('body');
})->with(['empty' => ['   '], 'too long' => [str_repeat('a', 2001)]]);

test('from the screen, the composer posts and comes back to the request', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['status' => 'in_progress']);
    $page = route('support-requests.show', $request);

    $this->actingAs($world['admin'])->from($page)
        ->post('/api/v1/support-requests/'.$request->reference.'/responses', ['body' => "Line one\r\nLine two"])
        ->assertRedirect($page);

    expect(SupportResponse::query()->sole()->body)->toBe("Line one\nLine two");
});

test('a long thread is capped at the latest responses, with the total', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['status' => 'in_progress']);
    $limit = App\Modules\Support\Http\SupportRequestPresenter::THREAD_LIMIT;

    foreach (range(1, $limit + 5) as $i) {
        $reply = new SupportResponse(['body' => "Reply $i"]);
        $reply->forceFill(['support_request_id' => $request->id, 'user_id' => $world['admin']->id, 'created_at' => now()->subMinutes(500 - $i)])->save();
    }

    $detail = $this->actingAs($world['admin'])->getJson('/api/v1/support-requests/'.$request->reference)->json('data');

    expect($detail['responses'])->toHaveCount($limit)
        ->and($detail['responses_total'])->toBe($limit + 5)
        ->and(end($detail['responses'])['body'])->toBe('Reply '.($limit + 5))
        ->and($detail['responses'][0]['body'])->toBe('Reply 6');
});
