<?php

declare(strict_types=1);

use App\Models\SupportRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;

/*
|--------------------------------------------------------------------------
| T387 — the support channel accepts no file (FR-125)
|--------------------------------------------------------------------------
|
| No upload route, no file control in the form, no multipart encoding — and a file sent anyway is
| ignored, not stored. An attachment on a public, unauthenticated form is a malware drop and a storage
| bill, and the spec rules it out.
|
*/

beforeEach(function (): void {
    Sleep::fake();
    supportWorld();
});

test('no support route takes a file or accepts PUT, PATCH or DELETE', function (): void {
    $support = collect(Route::getRoutes()->getRoutes())
        // The public form's routes only; the staff screens (support-requests) are not this surface.
        ->filter(fn (Illuminate\Routing\Route $route): bool => $route->uri() === 'support' || str_starts_with($route->uri(), 'support/'));

    expect($support->map(fn ($r): string => implode('|', $r->methods()).' '.$r->uri())->values()->all())
        ->toEqualCanonicalizing([
            'GET|HEAD support', 'POST support', 'GET|HEAD support/challenge', 'GET|HEAD support/submitted',
            // Phase 22 — the status lookup, which takes no file either.
            'GET|HEAD support/status', 'POST support/status',
        ])
        ->and($support->filter(fn ($r): bool => (bool) preg_match('/upload|attach|file/i', $r->uri()))->all())->toBe([]);
});

test('the form has no file control and is not multipart', function (): void {
    $html = (string) $this->get('/support')->getContent();

    expect($html)->not->toContain('type="file"')
        ->and($html)->not->toContain('multipart/form-data');
});

test('a file sent with a submission is not stored', function (): void {
    Storage::fake('local');
    Storage::fake('public');
    enrolledStudent('441234567');

    $this->post('/support', supportSubmission() + ['attachment' => UploadedFile::fake()->create('evil.exe', 10)])
        ->assertRedirect(route('support.submitted'));

    expect(Storage::disk('local')->allFiles())->toBe([])
        ->and(Storage::disk('public')->allFiles())->toBe([])
        ->and(SupportRequest::query()->sole()->getAttributes())->not->toHaveKey('attachment');
});
