<?php

declare(strict_types=1);

use App\Models\SupportRequest;
use App\Models\SupportResponse;
use Illuminate\Support\Sleep;

/*
|--------------------------------------------------------------------------
| T425 — the student sees their own request and nothing else (FR-134)
|--------------------------------------------------------------------------
|
| No assignee, no internal detail, no originating IP, no echo of the identifier they typed, no staff
| names, and nothing of any other request — in the JSON and on the page.
|
*/

beforeEach(function (): void {
    Sleep::fake();
    supportWorld();
});

test('the payload carries exactly the fields a student may see', function (): void {
    $student = enrolledStudent('441234567');
    $staff = aUser('SUPER_ADMIN', ['name_ar' => 'منسق الدعم', 'name_en' => 'Support Coordinator']);

    $mine = SupportRequest::factory()->create(['student_id' => $student->id, 'submitted_ip' => '203.0.113.99', 'contact' => 'me@example.com']);
    $mine->forceFill(['assigned_to' => $staff->id, 'status' => 'in_progress'])->saveQuietly();

    $reply = new SupportResponse(['body' => 'We are on it.']);
    $reply->forceFill(['support_request_id' => $mine->id, 'user_id' => $staff->id])->save();

    // Someone else's request, by the same student's branch-mate.
    $other = SupportRequest::factory()->create(['student_id' => enrolledStudent('441234568')->id, 'description' => 'Another student’s words']);

    $response = $this->postJson('/support/status', ['reference' => $mine->reference, 'university_id' => '441234567'])->assertOk();

    expect(array_keys($response->json('data')))->toEqualCanonicalizing(['reference', 'category', 'submitted_at', 'status', 'rejection_reason', 'responses'])
        ->and(array_keys($response->json('data.responses.0')))->toEqualCanonicalizing(['body', 'at']);

    $body = (string) $response->getContent();

    foreach (['203.0.113.99', '441234567', 'me@example.com', 'Support Coordinator', 'منسق الدعم', $other->reference, 'Another student'] as $secret) {
        expect($body)->not->toContain($secret);
    }
});

test('the page shows none of it either', function (): void {
    $student = enrolledStudent('441234567');
    $staff = aUser('SUPER_ADMIN', ['name_ar' => 'منسق الدعم', 'name_en' => 'Support Coordinator']);
    $mine = SupportRequest::factory()->create(['student_id' => $student->id, 'submitted_ip' => '203.0.113.99']);
    $mine->forceFill(['assigned_to' => $staff->id])->saveQuietly();

    $html = (string) $this->post('/support/status', ['reference' => $mine->reference, 'university_id' => '441234567'])->assertOk()->getContent();

    foreach (['203.0.113.99', 'Support Coordinator', 'منسق الدعم'] as $secret) {
        expect($html)->not->toContain($secret);
    }

    // The identifier the student typed is not echoed back into the page.
    expect(substr_count($html, '441234567'))->toBe(0);
});

test('a reply full of markup reaches the student as text', function (): void {
    $student = enrolledStudent('441234567');
    $mine = SupportRequest::factory()->create(['student_id' => $student->id]);
    $reply = new SupportResponse(['body' => '<script>alert(1)</script>']);
    $reply->forceFill(['support_request_id' => $mine->id, 'user_id' => aUser('SUPER_ADMIN')->id])->save();

    $html = (string) $this->post('/support/status', ['reference' => $mine->reference, 'university_id' => '441234567'])->getContent();

    expect($html)->not->toContain('<script>alert(1)</script>')
        ->and($html)->toContain(e('<script>alert(1)</script>'));
});
