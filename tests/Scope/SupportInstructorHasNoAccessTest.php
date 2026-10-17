<?php

declare(strict_types=1);

use App\Models\Section;

/*
|--------------------------------------------------------------------------
| T437 — an instructor has no route to any request (FR-142, SC-038)
|--------------------------------------------------------------------------
|
| Through the interface (no sidebar entry), the queue, a typed address, and every action — each a 404,
| as though the feature were not there. Including the instructor who teaches the student who asked.
|
*/

test('every support address is a 404 for an instructor, even for a student they teach', function (string $method, string $uri, array $payload): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['status' => 'in_progress']);

    // The instructor teaches the very section the student is in.
    $section = Section::query()->withoutGlobalScopes()
        ->whereIn('id', App\Models\SectionEnrollment::query()->withoutGlobalScopes()->where('student_id', $request->student_id)->select('section_id'))
        ->firstOrFail();
    $section->forceFill(['instructor_id' => $world['instructor']->id])->saveQuietly();

    $this->actingAs($world['instructor'])
        ->json($method, str_replace('{ref}', $request->reference, $uri), $payload)
        ->assertNotFound();

    expect($request->fresh()?->status->value)->toBe('in_progress');
})->with([
    'the queue (API)' => ['GET', '/api/v1/support-requests', []],
    'the queue (screen)' => ['GET', '/support-requests', []],
    'a request (API)' => ['GET', '/api/v1/support-requests/{ref}', []],
    'a request (screen)' => ['GET', '/support-requests/{ref}', []],
    'respond' => ['POST', '/api/v1/support-requests/{ref}/responses', ['body' => 'Reply']],
    'transition' => ['PATCH', '/api/v1/support-requests/{ref}/status', ['status' => 'resolved']],
    'reassign' => ['PATCH', '/api/v1/support-requests/{ref}/assignee', ['assigned_to' => null]],
]);

test('an instructor\'s sidebar has no support entry', function (): void {
    $world = supportStaffWorld();

    $html = (string) $this->actingAs($world['instructor'])->get('/')->getContent();

    expect($html)->not->toContain(route('support-requests.index'))
        ->and($html)->not->toContain(__('nav.support'));
});

test('the queue\'s 404 is byte-for-byte a missing request\'s 404, in every format (SC-038, Phase 23 review)', function (): void {
    $world = supportStaffWorld();
    $missing = 'K7Q2M9XVT3'.App\Modules\Support\Services\ReferenceGenerator::checkCharacter('K7Q2M9XVT3');

    $this->actingAs($world['instructor']);

    // The queue answers as a request that does not exist answers: the same body, the same type.
    $pairs = [
        'API, JSON' => [fn () => $this->getJson('/api/v1/support-requests'), fn () => $this->getJson('/api/v1/support-requests/'.$missing)],
        'API, no Accept' => [fn () => $this->get('/api/v1/support-requests'), fn () => $this->get('/api/v1/support-requests/'.$missing)],
        'the screen' => [fn () => $this->get('/support-requests'), fn () => $this->get('/support-requests/'.$missing)],
    ];

    foreach ($pairs as $format => [$queue, $absent]) {
        $q = $queue();
        $a = $absent();

        $q->assertNotFound();
        $a->assertNotFound();

        expect($q->getContent())->toBe($a->getContent(), $format)
            ->and($q->headers->get('Content-Type'))->toBe($a->headers->get('Content-Type'), $format);
    }
});
