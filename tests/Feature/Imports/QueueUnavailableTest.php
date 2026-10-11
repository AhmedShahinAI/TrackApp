<?php

declare(strict_types=1);

use App\Modules\Imports\Jobs\CommitImportChunkJob;
use App\Modules\Imports\Jobs\ValidateImportJob;
use Illuminate\Support\Facades\Queue;

/*
|--------------------------------------------------------------------------
| T217 — no worker means "waiting", never "failed" (spec Edge Cases, ui-conflicts.md C-13)
|--------------------------------------------------------------------------
|
| The queue is faked, which is exactly the no-worker case: the job is pushed and nothing runs it.
| The import must stay in its queued state, say it is waiting, and stop saying so the moment a
| worker picks it up — the last assertion is what separates a heartbeat-driven state from a message
| that is simply always shown.
|
*/

/**
 * The world and an uploaded three-row roster.
 *
 * @return array{0: array<string, mixed>, 1: App\Models\ImportJob}
 */
function queuedWorld(): array
{
    $world = importWorld();
    $job = uploadRoster($world['admin'], rosterFile(rosterRows($world, 3)), $world['term']->id);

    return [$world, $job];
}

test('validation with no worker stays validating and reports that it is waiting', function (): void {
    [$world, $job] = queuedWorld();
    Queue::fake();

    $this->actingAs($world['admin'])->putJson(route('api.v1.imports.mapping.update', $job), [
        'mapping' => $job->file_metadata['suggested_mapping'],
    ])->assertOk();

    $this->actingAs($world['admin'])->postJson(route('api.v1.imports.validate', $job))
        ->assertAccepted()
        ->assertJsonPath('data.status', 'validating');

    Queue::assertPushed(ValidateImportJob::class);

    $this->actingAs($world['admin'])->getJson(route('api.v1.imports.show', $job))
        ->assertOk()
        ->assertJsonPath('data.status', 'validating')
        ->assertJsonPath('data.waiting', true)
        ->assertJsonPath('data.waiting_message', __('imports.queue.waiting'))
        ->assertJsonPath('data.failure_reason', null);

    $this->actingAs($world['admin'])->get(route('imports.show', $job))
        ->assertOk()
        ->assertSee(__('imports.queue.waiting'))
        ->assertDontSee(__('imports.report.failed'));
});

test('the waiting state clears as soon as a worker picks the job up', function (): void {
    [$world, $job] = queuedWorld();
    $queue = Queue::fake();

    mapAndValidate($world['admin'], $job);

    $this->travel(2)->seconds();

    // The worker arrives.
    $queue->pushed(ValidateImportJob::class)->first()->handle(
        app(App\Modules\Imports\Pipeline\ColumnMapper::class),
        app(App\Modules\Imports\Pipeline\HeaderReader::class),
    );

    $this->actingAs($world['admin'])->getJson(route('api.v1.imports.show', $job))
        ->assertJsonPath('data.status', 'validated')
        ->assertJsonPath('data.waiting', false);
});

test('a commit with no worker stays committing and reports that it is waiting', function (): void {
    [$world, $job] = queuedWorld();
    mapAndValidate($world['admin'], $job);
    $this->actingAs($world['admin'])->getJson(route('api.v1.imports.preview', $job))->assertOk();

    Queue::fake();

    $this->actingAs($world['admin'])->postJson(route('api.v1.imports.commit', $job), ['confirm' => true])
        ->assertAccepted()
        ->assertJsonPath('data.status', 'committing');

    Queue::assertPushed(CommitImportChunkJob::class);

    $this->actingAs($world['admin'])->getJson(route('api.v1.imports.show', $job))
        ->assertJsonPath('data.status', 'committing')
        ->assertJsonPath('data.waiting', true);
});
