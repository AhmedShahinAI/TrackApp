<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
|
| T012 — the versioned REST surface that backs every interactive screen.
|
| Registered in bootstrap/app.php on the `web` stack plus `auth:sanctum`, with the `api` rate
| limiter at 120 requests per minute per user (constitution security floor). The `web` stack is
| deliberate, not an oversight: the platform authenticates with the session guard via Sanctum on the
| same origin, so these routes need the session, the CSRF token and the cookie — Sanctum's
| stateful-domain behaviour, not a bearer token.
|
| Versioned from day one so a future mobile client needs no backend rewrite (PRD section 9.2),
| even though there is no external consumer in this release.
|
| Routes are added here only when they belong to no single module. Module-owned routes live in
| app/Modules/<Module>/Routes/api.php and are loaded by ModuleServiceProvider, which applies this
| same prefix, middleware and name prefix.
|
| Every route must carry an authorization policy or middleware — an architecture test fails the
| build otherwise (constitution principle VIII).
|
*/
