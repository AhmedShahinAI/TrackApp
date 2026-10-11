<?php

declare(strict_types=1);

use App\Models\Program;
use App\Models\Section;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| T307 — a programme holding sections is never permanently removed (FR-092, SC-021)
|--------------------------------------------------------------------------
|
| Three layers, each asserted on its own: no DELETE route, a restricting foreign key under it, and
| deactivation offered in the place a person would look for a delete button.
|
*/

test('no DELETE route exists for programmes', function (): void {
    $deletes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => in_array('DELETE', $route->methods(), true) && str_contains($route->uri(), 'programs'))
        ->map(fn ($route) => $route->uri())
        ->values()
        ->all();

    expect($deletes)->toBe([]);
});

test('a DELETE request is refused and the programme survives', function (): void {
    $section = Section::factory()->create();

    $this->actingAs(aUser('SUPER_ADMIN'))
        ->deleteJson('/api/v1/programs/'.$section->program_id)
        ->assertMethodNotAllowed();

    expect(Program::query()->whereKey($section->program_id)->exists())->toBeTrue();
});

test('the database itself refuses to delete a programme that holds sections', function (): void {
    $section = Section::factory()->create();

    expect(fn () => DB::table('programs')->where('id', $section->program_id)->delete())
        ->toThrow(QueryException::class)
        ->and(Program::query()->whereKey($section->program_id)->exists())->toBeTrue();
});

test('a force delete in code is refused in the reader\'s language, before the database is asked', function (): void {
    $section = Section::factory()->create();
    app()->setLocale('ar');

    expect(fn () => $section->program->forceDelete())
        ->toThrow(Symfony\Component\HttpKernel\Exception\ConflictHttpException::class, __('programs.errors.has_sections'));
});

test('deactivation is offered instead — said on the edit dialog, and it works on a programme with sections', function (): void {
    $section = Section::factory()->create(['program_id' => aProgram()->id]);
    $admin = aUser('SUPER_ADMIN');

    $this->actingAs($admin)->get(route('programs.index'))
        ->assertOk()
        ->assertSee(__('programs.notice'));

    $this->actingAs($admin)
        ->putJson(route('api.v1.programs.update', $section->program_id), ['is_active' => false])
        ->assertOk();

    expect($section->fresh()->program_id)->toBe($section->program_id)
        ->and($section->fresh()->program->is_active)->toBeFalse();
});
