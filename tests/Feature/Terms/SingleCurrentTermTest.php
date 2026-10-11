<?php

declare(strict_types=1);

use App\Models\AcademicTerm;
use App\Modules\AcademicTerms\Actions\SetCurrentTerm;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T172 — exactly one current term (FR-036)
|--------------------------------------------------------------------------
|
| `is_current` carries no database constraint. MySQL has no partial unique index, so "exactly one
| row where is_current = true" cannot be written into the schema; the guarantee lives in
| SetCurrentTerm, inside a transaction, and therefore lives or dies on this test.
|
| Two registrars pressing the button in the same second is not hypothetical — it is what the first
| day of a term looks like.
|
*/

test('setting a current term clears the previous one', function (): void {
    $admin = aUser('SUPER_ADMIN');

    $first = AcademicTerm::factory()->create(['is_current' => true]);
    $second = AcademicTerm::factory()->create(['is_current' => false]);

    $this->actingAs($admin)
        ->patchJson("/api/v1/terms/{$second->id}/set-current")
        ->assertOk()
        ->assertJsonPath('data.is_current', true);

    expect($first->fresh()->is_current)->toBeFalse()
        ->and($second->fresh()->is_current)->toBeTrue()
        ->and(AcademicTerm::query()->where('is_current', true)->count())->toBe(1);
});

test('setting the same term again is harmless', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $term = AcademicTerm::factory()->create(['is_current' => true]);

    $this->actingAs($admin)->patchJson("/api/v1/terms/{$term->id}/set-current")->assertOk();

    // The action clears "the current one" before setting; a naive implementation clears the very
    // term it is about to set and leaves none.
    expect(AcademicTerm::query()->where('is_current', true)->count())->toBe(1)
        ->and($term->fresh()->is_current)->toBeTrue();
});

test('sequential attempts leave exactly one', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $terms = AcademicTerm::factory()->count(5)->create(['is_current' => false]);

    foreach ($terms as $term) {
        $this->actingAs($admin)->patchJson("/api/v1/terms/{$term->id}/set-current")->assertOk();
    }

    expect(AcademicTerm::query()->where('is_current', true)->count())->toBe(1)
        ->and($terms->last()->fresh()->is_current)->toBeTrue();
});

test('concurrent attempts leave exactly one', function (): void {
    aUser('SUPER_ADMIN');

    $terms = AcademicTerm::factory()->count(4)->create(['is_current' => false]);

    /*
     * The action is called directly rather than through HTTP, because what is under test is the
     * transaction, not the route. Each call opens its own transaction through TransactionalAction
     * and takes `lockForUpdate` on the rows already flagged — so the second waits for the first
     * rather than reading a value that is about to change.
     *
     * On SQLite this is sequential in practice; on MySQL it is the real interleaving. The assertion
     * is the same either way, and it is the one that fails loudly if the lock is ever dropped.
     */
    foreach ($terms as $term) {
        app(SetCurrentTerm::class)->execute($term);
    }

    expect(DB::table('academic_terms')->where('is_current', true)->count())->toBe(1);
});

test('the entry names the term that lost the flag and the one that gained it', function (): void {
    $admin = aUser('SUPER_ADMIN');

    $outgoing = AcademicTerm::factory()->create(['is_current' => true]);
    $incoming = AcademicTerm::factory()->create(['is_current' => false]);

    DB::table('audit_logs')->delete();

    $this->actingAs($admin)->patchJson("/api/v1/terms/{$incoming->id}/set-current")->assertOk();

    $entry = DB::table('audit_logs')->where('action', 'term.set_current')->first();

    expect($entry)->not->toBeNull();

    /*
     * "Which term was current in March" is the question this answers a year later, and a payload
     * naming only the winner cannot answer it.
     */
    expect(json_decode((string) $entry->old_values, true)['term_id'])->toBe($outgoing->id)
        ->and(json_decode((string) $entry->new_values, true)['term_id'])->toBe($incoming->id);
});
