<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\ImportJob;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * T147 — route-model binding resolves **through** the scoped query (research.md R-01, FR-024).
 *
 * ## Why this file exists when Laravel already does the right thing
 *
 * Implicit binding calls `resolveRouteBinding()`, which builds on `newQuery()`, which applies
 * global scopes. So an out-of-scope id already raises `ModelNotFoundException` → 404 before any
 * policy executes, which is exactly the ordering R-01 requires.
 *
 * Restating a default is usually noise. It is not noise here, for one reason: the default is one
 * `withoutGlobalScopes()` away from silently inverting. A developer debugging "why can't I find
 * this section" has an obvious-looking fix available, and taking it turns every scoped route in the
 * platform into a 403-or-200 oracle — the enumeration hole constitution I is written against. These
 * explicit bindings make that edit land *here*, in a file whose entire subject is why it must not
 * happen, rather than in a controller where it reads as a bug fix.
 *
 * The ordering this preserves:
 *
 *   request → substituteBindings → **scoped query** → 404 if invisible
 *                                → policy → 403 if visible but not permitted
 *
 * A policy that ran first would answer 403 for an invisible record, and 403 confirms existence.
 */
class RouteServiceProvider extends ServiceProvider
{
    /**
     * Route parameter name → model.
     *
     * Only the scoped models. `{user}` stays on implicit binding: accounts are not branch- or
     * section-scoped, they are permission-gated, and binding them here would imply otherwise.
     *
     * @var array<string, class-string<Model>>
     */
    private const SCOPED_BINDINGS = [
        'section' => Section::class,
        'student' => Student::class,
        'import' => ImportJob::class,
    ];

    public function boot(): void
    {
        foreach (self::SCOPED_BINDINGS as $parameter => $model) {
            Route::bind($parameter, static function (string $value) use ($model): Model {
                /*
                 * newQuery(), never newQueryWithoutScopes(). The exception is the point: Laravel
                 * renders ModelNotFoundException as a 404 carrying no detail about what was missed,
                 * so "outside your scope" and "never existed" are the same answer.
                 */
                $record = $model::query()->whereKey($value)->first();

                if ($record === null) {
                    throw (new ModelNotFoundException)->setModel($model, [$value]);
                }

                return $record;
            });
        }
    }
}
