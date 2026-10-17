<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Trusted proxies (Phase 19 review)
|--------------------------------------------------------------------------
|
| Every per-IP limit on the public surface — the `public` floor, the support submission limit, the
| status-retrieval limit — reads $request->ip(). Behind a load balancer that is the balancer's
| address for every visitor, so the limits become institution-wide: the sixth student in an hour,
| anywhere, is refused. Laravel's TrustProxies middleware (global) reads this file at request time,
| which keeps it working under `config:cache`.
|
| TRUSTED_PROXIES is a comma-separated list of the proxies' addresses or CIDRs, e.g.
| `10.0.0.0/8,172.16.0.5`. Empty means no proxy is trusted: the connecting address is the client,
| which is right when PHP faces the internet directly.
|
| **`*` is refused.** Trusting every caller as a proxy lets any client send its own X-Forwarded-For
| and pick a fresh address per request — resetting every per-IP limit at will. A wildcard here is
| read as "no proxy trusted", the failure that over-limits rather than under-limits.
|
*/

$proxies = trim((string) env('TRUSTED_PROXIES', ''));

return [
    'proxies' => $proxies === '' || str_contains($proxies, '*')
        ? null
        : array_values(array_filter(array_map(trim(...), explode(',', $proxies)))),
];
