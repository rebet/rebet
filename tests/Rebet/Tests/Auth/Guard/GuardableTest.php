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

        $this->assertInstanceOf(SessionGuard::class, $guard->authenticator('web'));
        $this->assertSame('web', $guard->authenticator());

        $this->assertInstanceOf(SessionGuard::class, $guard->authenticator('api'));
        $this->assertSame('api', $guard->authenticator());
    }
}
