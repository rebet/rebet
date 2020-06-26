<?php
namespace Rebet\Tests\Auth\Event;

use Rebet\Auth\AuthUser;
use Rebet\Auth\Event\Authentication;
use Rebet\Auth\Event\Signouted;
use Rebet\Http\Request;
use Rebet\Tests\RebetTestCase;

class SignoutedTest extends RebetTestCase
{
    public function test___construct()
    {
        $request  = Request::create('/');
        $user     = AuthUser::guest();
        $event    = new Signouted($request, $user);
        $this->assertInstanceOf(Signouted::class, $event);
        $this->assertInstanceOf(Authentication::class, $event);
        $this->assertSame($request, $event->request);
        $this->assertSame($user, $event->user);
    }
}
