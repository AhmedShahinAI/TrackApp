<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
 * T100 — the component gallery, the surface tests/Visual/components.spec.ts screenshots.
 *
 * Registered only when APP_ENV=local. Not merely hidden behind a permission: it renders every
 * component in every state with no authorization of its own, and the architecture test that
 * requires an authorization check on every route (T053) would be right to fail it. Keeping it out
 * of the route table entirely is the honest answer.
 *
 * `?locale=` is accepted here and nowhere else in the application, because the visual suite has to
 * shoot both directions without signing a user in and changing their stored preference.
 */
if (app()->environment('local')) {
    Route::get('/dev/components', function (Illuminate\Http\Request $request) {
        $locale = in_array($request->query('locale'), ['ar', 'en'], true)
            ? $request->string('locale')->toString()
            : app()->getLocale();

        app()->setLocale($locale);

        return view('dev.component-gallery');
    })->name('dev.components');

    /*
     * T103 — serve the vendored reference kit over http, read-only.
     *
     * The kit compiles its .jsx in the browser with Babel standalone, which fetches each file by
     * XHR. Over file:// that is blocked as a cross-origin request, so the kit renders an empty
     * document — which is how tests/Visual/reference.spec.ts found this.
     *
     * Strictly a read. The path is resolved and then checked to be inside design-system/, so a
     * traversal cannot reach anything else, and nothing here writes: design-system/ is vendored
     * read-only (design-system/VENDORED.md).
     */
    Route::get('/dev/reference/{path?}', function (string $path = 'ui_kits/web-app/index.html') {
        $root = realpath(base_path('design-system'));
        $file = realpath(base_path('design-system/'.$path));

        abort_if($root === false || $file === false || ! str_starts_with($file, $root), 404);
        abort_unless(is_file($file), 404);

        $types = [
            'html' => 'text/html', 'js' => 'text/javascript', 'jsx' => 'text/javascript',
            'css' => 'text/css', 'json' => 'application/json', 'svg' => 'image/svg+xml',
            'png' => 'image/png', 'jpg' => 'image/jpeg', 'woff2' => 'font/woff2',
            'md' => 'text/markdown',
        ];

        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        return response()->file($file, [
            'Content-Type' => ($types[$extension] ?? 'application/octet-stream').'; charset=utf-8',
        ]);
    })->where('path', '.*')->name('dev.reference');
}
