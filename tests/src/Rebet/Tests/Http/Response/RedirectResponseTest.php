<?php
namespace Rebet\Tests\Http\Response;

use Rebet\Http\Response;
use Rebet\Http\Response\RedirectResponse;
use Rebet\Tests\RebetTestCase;

class RedirectResponseTest extends RebetTestCase
{
    public function test___construct()
    {
        $response = new RedirectResponse('/');
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertInstanceOf(Response::class, $response);
    }

    public function test_with()
    {
        $request  = $this->createRequestMock('/');
        $response = (new RedirectResponse('/user/edit'))->with(['name' => 'Name']);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(['name' => 'Name'], $request->session()->loadInheritData('input', '/user/edit'));
    }

    public function test_errors()
    {
        $request  = $this->createRequestMock('/');
        $response = (new RedirectResponse('/user/edit'))->errors(['name' => 'Invalid']);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(['name' => 'Invalid'], $request->session()->loadInheritData('errors', '/user/edit'));
    }
}
