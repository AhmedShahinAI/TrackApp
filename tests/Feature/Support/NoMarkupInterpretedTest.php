<?php

declare(strict_types=1);

use App\Models\SupportResponse;
use Symfony\Component\Finder\Finder;

/*
|--------------------------------------------------------------------------
| T409 — what a student or a staff member writes is shown as text, never as markup (FR-153)
|--------------------------------------------------------------------------
*/

const HOSTILE = '<script>alert(1)</script><img src=x onerror=alert(2)><b>bold</b>';

test('a description, a contact and a response full of markup reach the staff screen escaped', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch'], ['status' => 'in_progress', 'description' => HOSTILE, 'contact' => HOSTILE]);

    $reply = new SupportResponse(['body' => HOSTILE]);
    $reply->forceFill(['support_request_id' => $request->id, 'user_id' => $world['admin']->id])->save();

    $html = (string) $this->actingAs($world['admin'])->get(route('support-requests.show', $request))->getContent();

    expect($html)->not->toContain('<script>alert(1)</script>')
        ->and($html)->not->toContain('<img src=x')
        ->and($html)->not->toContain('<b>bold</b>')
        ->and($html)->toContain(e(HOSTILE));
});

test('the queue escapes a student name full of markup', function (): void {
    $world = supportStaffWorld();
    $request = supportRequestFor($world['branch']);
    $request->student->forceFill(['name_ar' => HOSTILE])->saveQuietly();

    $html = (string) $this->actingAs($world['admin'])->get(route('support-requests.index'))->getContent();

    expect($html)->not->toContain('<script>alert(1)</script>');
});

test('no support view prints anything unescaped', function (): void {
    $offenders = [];

    foreach (Finder::create()->files()->in(resource_path('views/support'))->name('*.blade.php') as $file) {
        if (str_contains($file->getContents(), '{!!')) {
            $offenders[] = $file->getRelativePathname();
        }
    }

    // data-model.md "Validation rules": an architecture-level ban on {!! !!} under views/support.
    expect($offenders)->toBe([]);
});
