<?php
namespace Rebet\Tests\Auth\Guard;

use Rebet\Auth\Guard\SessionGuard;
use Rebet\Tests\RebetTestCase;

class GuardableTest extends RebetTestCase
{
    public function test_authenticator()
    {
        $guard = new SessionGuard();
        $this->assertNull($guard->authenticator());

        $guard->authenticator('web');
        $this->assertSame('web', $guard->authenticator());

        $guard->authenticator('api');
        $this->assertSame('api', $guard->authenticator());
    }
}
