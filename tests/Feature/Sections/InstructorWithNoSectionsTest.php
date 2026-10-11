<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| T252 — an instructor with no sections gets an explanation, not a blank page
|--------------------------------------------------------------------------
|
| An instructor's whole reason for opening TRACK is their sections. With none assigned, an empty
| table says nothing; the empty state says why, and names who can change it — their branch
| coordinator or the system administrator — because that is the one action available to them.
|
*/

test('the sections list tells an instructor with no sections whom to contact', function (): void {
    $instructor = aUser('INSTRUCTOR');

    $this->actingAs($instructor)->get(route('sections.index'))
        ->assertOk()
        ->assertSee(__('sections.empty.instructor'))
        ->assertDontSee(__('sections.empty.message'));

    expect(__('sections.empty.instructor'))->toContain('منسّق فرعك')
        ->and(__('sections.empty.instructor', [], 'en'))->toContain('branch coordinator');
});

test('an instructor with no imports is told where a section list is imported from', function (): void {
    $instructor = aUser('INSTRUCTOR');

    $this->actingAs($instructor)->get(route('imports.index'))
        ->assertOk()
        ->assertSee(__('imports.empty.instructor'))
        ->assertDontSee(__('imports.empty.message'))
        // Their way in is their sections, never the central import they cannot run.
        ->assertSee(route('sections.index'))
        ->assertDontSee(route('imports.create'));
});
