<?php
namespace Rebet\Tests\Auth\Event;

use Rebet\Auth\AuthUser;
use Rebet\Auth\Event\Authenticated;
use Rebet\Auth\Event\Authentication;
use Rebet\Http\Request;
use Rebet\Tests\RebetTestCase;

class AuthenticatedTest extends RebetTestCase
{
    public function test___construct()
    {
        $request = Request::create('/');
        $user    = AuthUser::guest();
        $event   = new Authenticated($request, $user);
        $this->assertInstanceOf(Authenticated::class, $event);
        $this->assertInstanceOf(Authentication::class, $event);
        $this->assertSame($request, $event->request);
        $this->assertSame($user, $event->user);
    }
}
