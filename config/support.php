<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Support channel — operational limits (Amendment 2, Phase 22 review)
|--------------------------------------------------------------------------
|
| Not the product's tunables — those are settings a Super Admin edits (constitution IV). This is a
| capacity figure the deployment sets with the PHP pool it runs on (docs/runbook-deploy.md, "Support
| channel").
|
| `padding_max_concurrent`: how many padded support responses (POST /support, POST /support/status) may
| be held in their response-time floor at once. Each holds a PHP worker for the floor; past this number a
| new one is answered at once with a generic 503 instead of taking another worker, so a surge on the public
| form cannot drain the pool the staff screens run on. Set it below the pool's worker count — about a
| third of it leaves the rest of the platform room. 0 turns the cap off.
|
*/

return [
    'padding_max_concurrent' => (int) env('SUPPORT_PADDING_MAX_CONCURRENT', 40),
];
