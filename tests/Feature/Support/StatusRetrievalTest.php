<?php

declare(strict_types=1);

use App\Models\SupportResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Sleep;

/*
|--------------------------------------------------------------------------
| T423 — the right reference and the right University ID return the request, its state and every reply
|--------------------------------------------------------------------------
*/

beforeEach(function (): void {
    Sleep::fake();
    supportWorld();
});

/**
 * A request belonging to an enrolled student, with replies.
 *
 * @param  list<string>  $replies
 * @return array{0: App\Models\SupportRequest, 1: string}
 */
function requestWithReplies(array $replies, string $status = 'in_progress', ?string $reason = null): array
{
    $student = enrolledStudent('441234567');
    $request = App\Models\SupportRequest::factory()->create(['student_id' => $student->id]);
    $request->forceFill(['status' => $status, 'rejection_reason' => $reason])->saveQuietly();

    $staff = aUser('SUPER_ADMIN');

    foreach ($replies as $i => $body) {
        $reply = new SupportResponse(['body' => $body]);
        $reply->forceFill(['support_request_id' => $request->id, 'user_id' => $staff->id, 'created_at' => now()->subMinutes(10 - $i)])->save();
    }

    return [$request, $student->university_id];
}

test('the right pair shows the request, its state and every reply, in order', function (): void {
    [$request, $universityId] = requestWithReplies(['First reply', 'Second reply']);

    $page = $this->post('/support/status', ['reference' => $request->display_reference, 'university_id' => $universityId]);

    $page->assertOk()
        ->assertSee($request->display_reference)
        ->assertSee(__('support.statuses.in_progress'))
        ->assertSeeInOrder(['First reply', 'Second reply'])
        ->assertHeader('Cache-Control', 'no-store, private');
});

test('the reference may be typed any way a person would, and the identifier in Arabic-Indic digits', function (): void {
    [$request] = requestWithReplies([]);

    foreach ([strtolower($request->display_reference), $request->reference, ' '.$request->display_reference.' '] as $reference) {
        $this->postJson('/support/status', ['reference' => $reference, 'university_id' => '٤٤١٢٣٤٥٦٧'])
            ->assertOk()
            ->assertJsonPath('data.reference', $request->display_reference);
    }
});

test('a rejection reason is shown to the student', function (): void {
    [$request, $universityId] = requestWithReplies([], 'rejected', 'Your timetable is set by the registrar.');

    $this->postJson('/support/status', ['reference' => $request->reference, 'university_id' => $universityId])
        ->assertOk()
        ->assertJsonPath('data.status.value', 'rejected')
        ->assertJsonPath('data.rejection_reason', 'Your timetable is set by the registrar.');
});

test('a lookup writes nothing — no audit entry, no state change (T430)', function (): void {
    [$request, $universityId] = requestWithReplies([], 'new');
    DB::table('audit_logs')->delete();

    $this->postJson('/support/status', ['reference' => $request->reference, 'university_id' => $universityId])->assertOk();
    $this->postJson('/support/status', ['reference' => $request->reference, 'university_id' => '441000000'])->assertStatus(422);

    expect(DB::table('audit_logs')->count())->toBe(0)
        ->and($request->fresh()?->status->value)->toBe('new');
});

test('both fields are required, and the form says so for each', function (): void {
    $this->post('/support/status', [])->assertSessionHasErrors(['reference', 'university_id']);
});

test('the confirmation page links to the status page (T432a)', function (): void {
    enrolledStudent('441234567');

    $this->post('/support', supportSubmission())->assertRedirect(route('support.submitted'));

    $this->get('/support/submitted')->assertSee(route('support.status.form'), false);
});

test('a lookup refused for an empty field does not print back what was typed in the other', function (): void {
    $this->post('/support/status', ['university_id' => '441234567'])
        ->assertRedirect(route('support.status.form'))
        ->assertSessionHasErrors('reference');

    expect(session()->getOldInput())->toBe([])
        ->and((string) $this->get('/support/status')->getContent())->not->toContain('441234567');
});

test('a failed lookup marks both fields, ties them to the one message, and puts focus on the reference', function (): void {
    $html = (string) $this->post('/support/status', ['reference' => 'K7Q2M9XVT3A', 'university_id' => '441000001'])
        ->assertStatus(422)
        ->getContent();

    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);

    $reference = $xpath->query("//input[@name='reference']")?->item(0);
    $universityId = $xpath->query("//input[@name='university_id']")?->item(0);
    $message = $xpath->query("//*[@id='f-reference-error']")?->item(0);

    expect($message?->textContent)->toContain(__('support.failures.status'))
        ->and($message?->attributes?->getNamedItem('role')?->nodeValue)->toBe('alert')
        ->and($reference?->attributes?->getNamedItem('aria-invalid')?->nodeValue)->toBe('true')
        ->and($reference?->attributes?->getNamedItem('aria-describedby')?->nodeValue)->toBe('f-reference-error')
        ->and($reference?->attributes?->getNamedItem('autofocus'))->not->toBeNull()
        ->and($universityId?->attributes?->getNamedItem('aria-invalid')?->nodeValue)->toBe('true')
        ->and($universityId?->attributes?->getNamedItem('aria-describedby')?->nodeValue)->toBe('f-reference-error');
});

test('the failure message names both fields, so a mistyped ID is not mistaken for a wrong reference', function (string $locale): void {
    app()->setLocale($locale);

    expect(__('support.failures.status'))->toContain(__('support.form.university_id'));
})->with(['ar', 'en']);

test('the public field component gives its hint and error the ids its control points at', function (): void {
    $html = (string) $this->get('/support/status')->getContent();

    // aria-describedby="f-reference-hint" must name an element that exists — platform-wide (Phase 22 review).
    expect($html)->toContain('aria-describedby="f-reference-hint"')
        ->and($html)->toContain('id="f-reference-hint"');
});
