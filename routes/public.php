<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| The public surface — every route a visitor reaches without signing in (T355)
|--------------------------------------------------------------------------
|
| Amendment 2 (student support channel) is the platform's first unauthenticated feature, and the
| constitution's "Public endpoints" subsection requires that surface to be small, reviewable and
| deliberate. So it lives here, in one file, rather than scattered across module route files where an
| unauthenticated route would look like any other:
|
|   - loaded on the `public` middleware group (bootstrap/app.php): `web` — CSRF through a guest
|     session cookie, the CSP, the locale — plus a per-IP throttle floor; never `auth:sanctum`;
|   - each route's controller lives in a module's Http/Controllers/Public, and each route is
|     documented in contracts/support-public.md with its own two-axis limits;
|   - anything that needs a signed-in user belongs in a module's Routes/web.php or Routes/api.php,
|     never here.
|
| tests/Architecture/PublicSurfaceTest.php holds the platform to this file: an unauthenticated route
| declared anywhere else fails it, as does a route here that resolves a user.
|
| A route added to this file is a route anyone on the internet can call. It is reviewed as one.
|
| Empty until Phase 21 (User Story 9) adds GET/POST /support and GET /support/challenge.
|
*/
