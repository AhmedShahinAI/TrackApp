<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Program;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| T306 — create, refuse a duplicate code by name, edit, deactivate (FR-089, FR-092, FR-093)
|--------------------------------------------------------------------------
*/

/** @return list<string> */
function programAuditActions(): array
{
    return DB::table('audit_logs')->where('entity_type', 'Program')->orderBy('id')->pluck('action')->all();
}

test('a Super Admin creates a programme; its code is stored uppercase and the creation is audited once', function (): void {
    $admin = aUser('SUPER_ADMIN');

    $response = $this->actingAs($admin)->postJson(route('api.v1.programs.store'), [
        'code' => ' hr ',
        'name_ar' => 'الموارد البشرية',
        'name_en' => 'Human Resources',
        'unit' => 'admin',
    ])->assertCreated();

    expect($response->json('data'))->toMatchArray([
        'code' => 'HR', 'name_ar' => 'الموارد البشرية', 'name_en' => 'Human Resources',
        'unit' => 'admin', 'is_active' => true, 'courses_count' => 0, 'sections_count' => 0,
    ])
        ->and(Program::query()->sole()->organization_id)->toBe($admin->organization_id)
        ->and(programAuditActions())->toBe(['program.created']);
});

test('every required field is required, and the unit is one of the two', function (): void {
    $this->actingAs(aUser('SUPER_ADMIN'))
        ->postJson(route('api.v1.programs.store'), ['unit' => 'science'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['code', 'name_ar', 'name_en', 'unit']);
});

test('a duplicate code is refused with 422, naming the code — whatever case it was typed in', function (string $locale): void {
    app()->setLocale($locale);
    aProgram(['code' => 'HR']);

    $response = $this->actingAs(aUser('SUPER_ADMIN'))->postJson(route('api.v1.programs.store'), [
        'code' => 'hr', 'name_ar' => 'مكرر', 'name_en' => 'Duplicate', 'unit' => 'admin',
    ])->assertUnprocessable();

    expect($response->json('errors.code.0'))->toBe(__('programs.errors.code_taken', ['code' => 'HR']))
        ->and($response->json('errors.code.0'))->toContain('HR')
        ->and(Program::query()->count())->toBe(1);
})->with(['ar', 'en']);

test('a soft-deleted programme still holds its code (FR-089), refused as a duplicate rather than a 500', function (): void {
    aProgram(['code' => 'OLD'])->delete();

    $this->actingAs(aUser('SUPER_ADMIN'))->postJson(route('api.v1.programs.store'), [
        'code' => 'old', 'name_ar' => 'جديد', 'name_en' => 'New', 'unit' => 'tech',
    ])->assertUnprocessable()->assertJsonValidationErrors(['code']);
});

test('the same code in another organization is not a duplicate', function (): void {
    $admin = aUser('SUPER_ADMIN'); // first, so the default organization is the admin's own
    Program::factory()->create(['organization_id' => Organization::factory()->create()->id, 'code' => 'HR']);

    $this->actingAs($admin)->postJson(route('api.v1.programs.store'), [
        'code' => 'HR', 'name_ar' => 'الموارد البشرية', 'name_en' => 'Human Resources', 'unit' => 'admin',
    ])->assertCreated();
});

test('editing a programme updates it and records program.updated', function (): void {
    $program = aProgram(['code' => 'MKT', 'unit' => 'admin']);
    DB::table('audit_logs')->delete();

    $this->actingAs(aUser('SUPER_ADMIN'))
        ->putJson(route('api.v1.programs.update', $program), ['name_ar' => 'التسويق الرقمي', 'unit' => 'tech', 'code' => 'dm'])
        ->assertOk()
        ->assertJsonPath('data.code', 'DM')
        ->assertJsonPath('data.unit', 'tech');

    expect($program->fresh()->name_ar)->toBe('التسويق الرقمي')
        ->and(programAuditActions())->toBe(['program.updated']);
});

test('an edit keeping its own code is not a duplicate of itself; taking another\'s is', function (): void {
    $program = aProgram(['code' => 'LOG']);
    aProgram(['code' => 'ACC']);
    $admin = aUser('SUPER_ADMIN');

    $this->actingAs($admin)->putJson(route('api.v1.programs.update', $program), ['code' => 'log'])->assertOk();

    $this->actingAs($admin)->putJson(route('api.v1.programs.update', $program), ['code' => 'acc'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.code.0', __('programs.errors.code_taken', ['code' => 'ACC']));
});

test('deactivating records program.deactivated — once, and not program.updated', function (): void {
    $program = aProgram();
    DB::table('audit_logs')->delete();

    $this->actingAs(aUser('SUPER_ADMIN'))
        ->putJson(route('api.v1.programs.update', $program), ['is_active' => false])
        ->assertOk()
        ->assertJsonPath('data.is_active', false);

    expect($program->fresh()->is_active)->toBeFalse()
        ->and($program->fresh()->trashed())->toBeFalse()
        ->and(programAuditActions())->toBe(['program.deactivated']);
});

test('the dialog\'s form-encoded "0" deactivates too, and the browser is sent back', function (): void {
    $program = aProgram();
    DB::table('audit_logs')->delete();

    $this->actingAs(aUser('SUPER_ADMIN'))
        ->from(route('programs.index'))
        ->put(route('api.v1.programs.update', $program), [
            'code' => $program->code, 'name_ar' => $program->name_ar, 'name_en' => $program->name_en,
            'unit' => $program->unit, 'is_active' => '0',
        ])
        ->assertRedirect(route('programs.index'));

    expect($program->fresh()->is_active)->toBeFalse()
        ->and(programAuditActions())->toBe(['program.deactivated']);
});

test('the list filters by unit, active flag and text, and counts courses and sections', function (): void {
    $hr = aProgram(['code' => 'HR', 'unit' => 'admin', 'name_en' => 'Human Resources']);
    aProgram(['code' => 'CS', 'unit' => 'tech', 'name_en' => 'Computer Science']);
    aProgram(['code' => 'GD', 'unit' => 'tech', 'is_active' => false, 'name_en' => 'Graphic Design']);
    $hr->courses()->attach(App\Models\Course::factory()->count(2)->create(['organization_id' => $hr->organization_id]));
    App\Models\Section::factory()->create(['program_id' => $hr->id]);

    $admin = aUser('SUPER_ADMIN');
    $codes = fn (array $query) => $this->actingAs($admin)->getJson(route('api.v1.programs.index', $query))->assertOk()->json('data.*.code');

    expect($codes([]))->toBe(['CS', 'GD', 'HR'])
        ->and($codes(['unit' => 'tech']))->toBe(['CS', 'GD'])
        ->and($codes(['is_active' => 0]))->toBe(['GD'])
        ->and($codes(['q' => 'human']))->toBe(['HR'])
        ->and($this->actingAs($admin)->getJson(route('api.v1.programs.index', ['q' => 'HR']))->json('data.0'))
        ->toMatchArray(['courses_count' => 2, 'sections_count' => 1]);
});

test('another organization\'s programme is not listed and is a 404, never a 403', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $foreign = Program::factory()->create(['organization_id' => Organization::factory()->create()->id]);

    expect($this->actingAs($admin)->getJson(route('api.v1.programs.index'))->json('data'))->toBe([]);

    $this->actingAs($admin)->getJson(route('api.v1.programs.show', $foreign))->assertNotFound();
    $this->actingAs($admin)->putJson(route('api.v1.programs.update', $foreign), ['name_ar' => 'x'])->assertNotFound();
    $this->actingAs($admin)->putJson(route('api.v1.programs.courses.update', $foreign), ['course_ids' => []])->assertNotFound();
});

test('the programmes screen renders the list, the code LTR, and the create dialog', function (string $locale): void {
    aProgram(['code' => 'HR', 'name_ar' => 'الموارد البشرية', 'name_en' => 'Human Resources', 'unit' => 'admin']);
    $admin = aUser('SUPER_ADMIN', ['locale' => $locale]);

    $this->actingAs($admin)->withSession(['locale' => $locale])->get(route('programs.index'))
        ->assertOk()
        ->assertSee('<b dir="ltr" class="track-code">HR</b>', false)
        ->assertSee(__('programs.subtitle', [], $locale))
        ->assertSee(__('programs.units.admin', [], $locale))
        ->assertSee('id="program-dialog"', false)
        ->assertDontSee('programs.fields.');
})->with(['ar', 'en']);

/*
|--------------------------------------------------------------------------
| Review corrections (2026-10-10)
|--------------------------------------------------------------------------
*/

test('a duplicate that slips past validation meets the unique key and is still a 422 naming the code', function (string $method): void {
    $admin = aUser('SUPER_ADMIN');
    $existing = $method === 'put' ? aProgram(['code' => 'OLD']) : null;

    /*
     * The race, made deterministic: another writer inserts HR after Rule::unique has passed and
     * before this request's own write reaches the database.
     */
    $raced = false;
    $race = function (Program $program) use (&$raced): void {
        if ($raced || $program->code !== 'HR') {
            return;
        }

        $raced = true;
        DB::table('programs')->insert([
            'organization_id' => $program->organization_id, 'code' => 'HR', 'name_ar' => 'سابق',
            'name_en' => 'First', 'unit' => 'admin', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    };
    Program::creating($race);
    Program::updating($race);

    $response = $method === 'post'
        ? $this->actingAs($admin)->postJson(route('api.v1.programs.store'), ['code' => 'HR', 'name_ar' => 'ب', 'name_en' => 'B', 'unit' => 'admin'])
        : $this->actingAs($admin)->putJson(route('api.v1.programs.update', $existing), ['code' => 'HR']);

    $response->assertUnprocessable()
        ->assertJsonPath('errors.code.0', __('programs.errors.code_taken', ['code' => 'HR']));

    // Never two. (On POST the simulated writer's row shares the request's transaction and rolls back
    // with it; a real concurrent writer's row is already committed. Either way, no second HR.)
    expect(Program::query()->where('code', 'HR')->count())->toBeLessThanOrEqual(1);
})->with(['post', 'put']);

test('a programme holding sections keeps its code; one with none may still correct it', function (): void {
    $admin = aUser('SUPER_ADMIN');
    $held = aProgram(['code' => 'HR']);
    App\Models\Section::factory()->create(['program_id' => $held->id]);
    $empty = aProgram(['code' => 'TYPO']);

    $this->actingAs($admin)->putJson(route('api.v1.programs.update', $held), ['code' => 'HRM'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.code.0', __('programs.errors.code_locked'));

    // Resubmitting its own code, as the edit form does, is not a change.
    $this->actingAs($admin)->putJson(route('api.v1.programs.update', $held), ['code' => 'hr', 'name_ar' => 'معدّل'])->assertOk();
    $this->actingAs($admin)->putJson(route('api.v1.programs.update', $empty), ['code' => 'TYPE'])->assertOk();

    expect($held->fresh()->code)->toBe('HR')
        ->and($empty->fresh()->code)->toBe('TYPE');
});

test('the edit dialog makes the code read-only for a programme holding sections', function (): void {
    $held = aProgram(['code' => 'HR']);
    App\Models\Section::factory()->create(['program_id' => $held->id]);
    aProgram(['code' => 'NEW']);

    $html = (string) $this->actingAs(aUser('SUPER_ADMIN'))->get(route('programs.index'))->assertOk()->getContent();

    expect($html)->toMatch('/data-program-code="HR"[^>]*data-program-locked="1"/')
        ->and($html)->toMatch('/data-program-code="NEW"[^>]*data-program-locked="0"/')
        ->and($html)->toContain('x-bind:readonly="codeLocked()"');
});

test('the screen searches on the server, so a programme on page 2 is found', function (): void {
    foreach (range(1, 30) as $i) {
        aProgram(['code' => 'A'.str_pad((string) $i, 2, '0', STR_PAD_LEFT)]);
    }
    aProgram(['code' => 'ZZZ', 'name_en' => 'Last of all']);
    $admin = aUser('SUPER_ADMIN');

    $this->actingAs($admin)->get(route('programs.index'))->assertOk()->assertDontSee('>ZZZ<', false);

    $this->actingAs($admin)->get(route('programs.index', ['q' => 'zzz']))
        ->assertOk()
        ->assertSee('>ZZZ<', false)
        ->assertDontSee('>A01<', false);

    $this->actingAs($admin)->get(route('programs.index', ['q' => 'nothing-matches']))
        ->assertOk()
        ->assertSee(__('programs.empty.filtered'));
});

test('a coordinator\'s section counts are their own branches\' only (Amendment 1)', function (): void {
    $program = aProgram(['code' => 'HR']);
    $theirs = App\Models\Branch::factory()->create(['organization_id' => $program->organization_id]);
    $other = App\Models\Branch::factory()->create(['organization_id' => $program->organization_id]);
    App\Models\Section::factory()->create(['program_id' => $program->id, 'branch_id' => $theirs->id]);
    App\Models\Section::factory()->count(2)->create(['program_id' => $program->id, 'branch_id' => $other->id]);

    $coordinator = aUser('BRANCH_COORDINATOR');
    $coordinator->coordinatorBranches()->sync([$theirs->id]);

    $this->actingAs($coordinator->fresh())->getJson(route('api.v1.programs.index'))
        ->assertOk()
        ->assertJsonPath('data.0.sections_count', 1);

    $this->actingAs(aUser('SUPER_ADMIN'))->getJson(route('api.v1.programs.index'))
        ->assertJsonPath('data.0.sections_count', 3);
});
