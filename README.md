<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Student support channel — proof-of-work challenge (T358, research.md R-08)

The public support form (Amendment 2) is protected by a **self-hosted proof-of-work challenge**.
The **hand-rolled fallback** from R-08 was chosen, not the ALTCHA widget.

- **Why not ALTCHA (checked at 3.3.0).** ALTCHA itself requests no external origin: its `fetch`
  calls go only to the challenge and verify URLs it is given, and `altcha.org` appears only as a
  footer link. But it starts its hashing workers from `blob:` URLs, falling back to `data:` URLs.
  Under this platform's Content-Security-Policy (`worker-src` falls back to `script-src 'self'`),
  both are refused. Allowing them would be a new CSP exemption, which T359 forbids.
- **What is used instead**: `resources/js/pow/`, three dependency-free modules.
  - `puzzle.js` holds the one definition of the puzzle and the one `search` loop. It includes a
    pure-JavaScript SHA-256, so it also works where `crypto.subtle` is missing (plain-HTTP origins).
  - `worker.js` runs `search` off the main thread.
  - `solve.js` starts the worker with `new Worker(new URL('./worker.js', import.meta.url), { type: 'module' })`.
    Vite (`worker.format: 'es'`) emits the worker as its own hashed file under `/build/assets/`,
    on our own origin, so `script-src 'self'` covers it. Never `?worker&inline`, which would become
    a `blob:` URL. Where the worker cannot start or load, `solve.js` runs the same `search` in the
    page, so either path returns the same nonce.
  - The search yields to the event loop, reports progress, has an iteration cap, and stops when its
    `AbortSignal` fires. It rejects with `invalid`, `exhausted` or `aborted`, and the form then
    refuses to submit (fail closed, FR-129).
  - None of the files makes a network request. `tests/Architecture/ChallengeSolverIsSelfHostedTest.php`
    keeps every file in the directory that way. `tests/Feature/Security/NoNewCspExemptionTest.php`
    pins the policy unchanged, including on the `public` route group.
- **The puzzle**. The server's verifier (T370) must check exactly this rule:
  - `challenge` is base64url, matching `[A-Za-z0-9_-]{16,256}`.
  - `difficulty` is an integer from 1 to 32. The server chooses it and remembers it with the
    challenge; it never takes it from the request.
  - `nonce` is an integer from 0 to 2^53 − 1. It is submitted as `challenge_solution`.
  - The answer is accepted when `SHA-256` of the UTF-8 bytes of `challenge + ":" + nonce`, with the
    nonce in canonical decimal (no sign, no leading zeros), starts with at least `difficulty` zero
    bits, counted from the most significant bit across byte boundaries.
  - **Any** nonce that meets the rule is valid. The verifier must not require the smallest one;
    checking an answer takes one hash.
- **Tests**: `npm run test:js` (Node's built-in `node:test`, also run in CI). It checks the puzzle
  against Node's own SHA-256.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
