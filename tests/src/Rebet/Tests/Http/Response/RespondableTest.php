<?php
namespace Rebet\Tests\Http\Response;

use Rebet\Tools\Reflection\Reflector;
use Rebet\Http\Cookie\Cookie;
use Rebet\Http\Request;
use Rebet\Http\Response\BasicResponse;
use Rebet\Http\Response\Respondable;
use Rebet\Tests\RebetTestCase;

class RespondableTest extends RebetTestCase
{
    public function test_getHeader()
    {
        $response = new BasicResponse('Content', 200, [
            'Content-Type' => 'text/html',
            'X-Test'       => 'This is test',
        ]);
        $this->assertTrue(Reflector::uses($response, Respondable::class));
        $this->assertSame('text/html', $response->getHeader('Content-Type'));
        $this->assertSame('This is test', $response->getHeader('X-Test'));
        $this->assertSame(null, $response->getHeader('X-Nothing'));
    }

    public function test_setHeader()
    {
        $response = new BasicResponse();
        $this->assertSame(null, $response->getHeader('X-Test'));
        $response->setHeader('X-Test', 'a');
        $this->assertSame('a', $response->getHeader('X-Test'));
        $response->setHeader('X-Test', 'b');
        $this->assertSame('b', $response->getHeader('X-Test'));
        $response->setHeader('X-Test', 'c', false);
        $this->assertSame(['b', 'c'], $response->getHeader('X-Test'));
        $this->assertSame('b', $response->getHeader('X-Test', true));
    }

    public function test_getAndSetCookie()
    {
        $response = new BasicResponse();
        $this->assertSame(null, $response->getCookie('foo'));
        $this->assertSame(null, $response->getCookie('bar'));

        $cookie_1 = new Cookie('foo', '1');
        $response->setCookie($cookie_1);
        $this->assertSame($cookie_1, $response->getCookie('foo'));
        $this->assertSame(null, $response->getCookie('bar'));

        $cookie_2 = new Cookie('foo', '2');
        $response->setCookie($cookie_2);
        $this->assertSame($cookie_2, $response->getCookie('foo'));
        $this->assertSame(null, $response->getCookie('bar'));

        $cookie_3 = new Cookie('foo', '3', null, '/path');
        $response->setCookie($cookie_3);
        $this->assertSame([$cookie_2, $cookie_3], $response->getCookie('foo'));
        $this->assertSame($cookie_2, $response->getCookie('foo', '/'));
        $this->assertSame($cookie_3, $response->getCookie('foo', '/path'));

        $cookie_4 = new Cookie('foo', '4', null, '/path', 'domain.com');
        $response->setCookie($cookie_4);
        $this->assertSame([$cookie_2, $cookie_3, $cookie_4], $response->getCookie('foo'));
        $this->assertSame($cookie_2, $response->getCookie('foo', '/'));
        $this->assertSame([$cookie_3, $cookie_4], $response->getCookie('foo', '/path'));
        $this->assertSame($cookie_3, $response->getCookie('foo', '/path', null));
        $this->assertSame($cookie_4, $response->getCookie('foo', '/path', 'domain.com'));
        $this->assertSame('/path', $response->getCookie('foo', '/path', 'domain.com')->getPath());

        $request = $this->createMock(Request::class);
        $request->method('getRoutePrefix')->willReturn('/prefix');
        $this->inject(Request::class, ['current' => $request]);
        $this->assertSame('/prefix', Request::current()->getRoutePrefix());

        $cookie_5 = new Cookie('foo', '5', null, '/path', 'prefix.com');
        $response->setCookie($cookie_5);
        $this->assertSame($cookie_5, $response->getCookie('foo', '/path', 'prefix.com'));
        $this->assertSame('/prefix/path', $response->getCookie('foo', '/path', 'prefix.com')->getPath());

        $this->assertSame(null, $response->getCookie('foo', '/path', 'domain.com'));
        $this->assertSame($cookie_4, $response->getCookie('foo', '@/path', 'domain.com'));
    }
}
