<?php

declare(strict_types=1);

use App\Models\SupportRequest;
use App\Models\User;
use App\Modules\Support\Services\ReferenceGenerator;
use Illuminate\Support\Sleep;

/*
|--------------------------------------------------------------------------
| T377 — a student raises a request, and nothing about them becomes an account (FR-115–FR-124)
|--------------------------------------------------------------------------
*/

beforeEach(function (): void {
    Sleep::fake();
    supportWorld();
});

test('the form is served without a sign-in, with its fields and no navigation into the staff side', function (): void {
    $page = $this->get('/support')->assertOk();

    $page->assertSee('name="university_id"', false)
        ->assertSee('name="category"', false)
        ->assertSee('name="description"', false)
        ->assertSee('name="contact"', false)
        ->assertSee('name="_honeypot"', false)
        ->assertSee('name="_issued_at"', false)
        ->assertSee('maxlength="2000"', false)
        // The category labels, from the setting, in the default locale.
        ->assertSee('بيانات غير صحيحة');

    expect(auth()->check())->toBeFalse();
});

test('?lang=en serves the form in English, left to right, and remembers it for the visit', function (): void {
    $this->get('/support?lang=en')->assertOk()->assertSee('dir="ltr"', false)->assertSee('Incorrect data');

    $this->get('/support')->assertSee('lang="en"', false);
});

test('an enrolled student submits, and the request is recorded with a reference and the address', function (): void {
    $student = enrolledStudent('441234567');

    $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.20'])
        ->post('/support', supportSubmission(['contact' => 'student@example.com']));

    $response->assertRedirect(route('support.submitted'));

    $request = SupportRequest::query()->sole();

    expect($request->student_id)->toBe($student->id)
        ->and($request->status->value)->toBe('new')
        ->and($request->category)->toBe('incorrect_data')
        ->and($request->contact)->toBe('student@example.com')
        ->and($request->submitted_ip)->toBe('203.0.113.20')
        ->and(ReferenceGenerator::normalise($request->reference))->toBe($request->reference);
});

test('the reference is shown once, from the flash, and never in the URL', function (): void {
    enrolledStudent('441234567');

    $response = $this->post('/support', supportSubmission());
    $reference = SupportRequest::query()->sole()->reference;

    expect((string) $response->headers->get('Location'))->not->toContain($reference);

    // Never stored by the browser: the next person at a shared machine could read it back (T460, F-03).
    $this->get('/support/submitted')->assertOk()
        ->assertSee(ReferenceGenerator::format($reference))
        ->assertHeader('Cache-Control', 'no-store, private');

    // A refresh: the flash is spent, and the page goes back to the form rather than show nothing.
    $this->get('/support/submitted')->assertRedirect(route('support.create'));
});

test('arriving at the confirmation without having submitted goes to the form', function (): void {
    $this->get('/support/submitted')->assertRedirect(route('support.create'));
});

test('a description of nothing but spaces is refused with the field\'s own message', function (): void {
    enrolledStudent('441234567');

    $this->post('/support', supportSubmission(['description' => '     ']))
        ->assertSessionHasErrors('description');

    expect(SupportRequest::query()->count())->toBe(0);
});

test('a description over the configured maximum is refused, and the limit is named', function (): void {
    enrolledStudent('441234567');

    $this->post('/support', supportSubmission(['description' => str_repeat('a', 2001)]))
        ->assertSessionHasErrors('description');
});

test('a category outside the configured list is refused', function (): void {
    enrolledStudent('441234567');

    $this->post('/support', supportSubmission(['category' => 'free_text']))->assertSessionHasErrors('category');
});

test('no account, credential or signed-in session is created (FR-124)', function (): void {
    enrolledStudent('441234567');
    $users = User::query()->count();

    $this->post('/support', supportSubmission())->assertRedirect();

    expect(User::query()->count())->toBe($users)
        ->and(auth()->check())->toBeFalse()
        // Nothing in the guest session names the student: the only thing put there is the reference,
        // flashed for one render.
        ->and(collect(session()->all())->keys()->filter(fn (string $k): bool => str_starts_with($k, 'login_'))->all())->toBe([])
        ->and(json_encode(session()->all()))->not->toContain('441234567');
});

test('a JSON client gets 201 and where to go, never the reference in the body', function (): void {
    enrolledStudent('441234567');

    $response = $this->postJson('/support', supportSubmission())->assertCreated();

    expect($response->json('redirect'))->toBe(route('support.submitted'))
        ->and((string) $response->getContent())->not->toContain(SupportRequest::query()->sole()->reference);
});

test('a description at the limit is accepted however many line breaks the browser sent as CRLF', function (): void {
    enrolledStudent('441234567');

    // 2,000 characters as the counter and maxlength count them: ten line breaks of one character each.
    // The browser submits each as CRLF, which a byte-for-byte count would make 2,010.
    $lines = array_fill(0, 11, str_repeat('ب', 180));
    $asTyped = implode("\n", $lines).str_repeat('ب', 2000 - (11 * 180 + 10));

    expect(mb_strlen($asTyped))->toBe(2000);

    $this->post('/support', supportSubmission(['description' => str_replace("\n", "\r\n", $asTyped)]))
        ->assertRedirect(route('support.submitted'));

    expect(SupportRequest::query()->sole()->description)->toBe($asTyped);
});
