<?php
namespace Rebet\Tests\Auth\Event;

use Rebet\Auth\AuthUser;
use Rebet\Auth\Event\Authentication;
use Rebet\Auth\Event\Signined;
use Rebet\Http\Request;
use Rebet\Tests\RebetTestCase;

class SigninedTest extends RebetTestCase
{
    public function test___construct()
    {
        $request  = Request::create('/');
        $user     = AuthUser::guest();
        $remember = true;
        $event    = new Signined($request, $user, $remember);
        $this->assertInstanceOf(Signined::class, $event);
        $this->assertInstanceOf(Authentication::class, $event);
        $this->assertSame($request, $event->request);
        $this->assertSame($user, $event->user);
        $this->assertSame($remember, $event->remember);
    }
}
