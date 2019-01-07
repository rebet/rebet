<?php
namespace Rebet\Tests\Auth\Event;

use Rebet\Auth\Event\Authentication;
use Rebet\Auth\Event\SigninFailed;
use Rebet\Http\Request;
use Rebet\Tests\RebetTestCase;

class SigninFailedTest extends RebetTestCase
{
    public function test___construct()
    {
        $request  = Request::create('/');
        $event    = new SigninFailed($request);
        $this->assertInstanceOf(SigninFailed::class, $event);
        $this->assertInstanceOf(Authentication::class, $event);
        $this->assertSame($request, $event->request);
    }
}
