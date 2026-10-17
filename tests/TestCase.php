<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Acting as someone starts them on their own session fingerprint (SEC-031).
     *
     * A test has one session store, but the people it plays are separate browsers. Since `session.auth`
     * (AuthenticateSession) guards the API as well as the web stack, the password hash it stores in
     * the session for the first user would be compared against the second user's on the next request,
     * and the second user signed out as if their session had been stolen: correct behaviour for one
     * browser, wrong for two people. So switching who a test acts as clears that stored hash, exactly
     * as a fresh browser would arrive without one.
     *
     * The control itself is untouched, and is tested without this shortcut: ApiSessionRevocationTest
     * signs in through POST /login and never calls actingAs.
     */
    public function actingAs(Authenticatable $user, $guard = null)
    {
        if ($this->app->bound('session.store')) {
            foreach (array_keys(config('auth.guards', [])) as $name) {
                $this->app['session.store']->forget('password_hash_'.$name);
            }
        }

        return parent::actingAs($user, $guard);
    }
}
