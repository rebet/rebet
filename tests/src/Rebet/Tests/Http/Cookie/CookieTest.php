<?php
namespace Rebet\Tests\Http\Cookie;

use Rebet\Config\Config;
use Rebet\Http\Cookie\Cookie;
use Rebet\Http\Request;
use Rebet\Tests\RebetTestCase;

class CookieTest extends RebetTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function test___construct()
    {
        $cookie = new Cookie('key');
        $this->assertInstanceOf(Cookie::class, $cookie);
        $this->assertSame('key', $cookie->getName());
        $this->assertSame(null, $cookie->getValue());
        $this->assertSame(0, $cookie->getExpiresTime());
        $this->assertSame('/', $cookie->getPath());
        $this->assertSame(null, $cookie->getDomain());
        $this->assertSame(false, $cookie->isSecure());
        $this->assertSame(true, $cookie->isHttpOnly());
        $this->assertSame(false, $cookie->isRaw());
        $this->assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());

        $cookie = new Cookie('key', 'value', 100, '/path', 'domain', true, false, true, Cookie::SAMESITE_STRICT);
        $this->assertInstanceOf(Cookie::class, $cookie);
        $this->assertSame('key', $cookie->getName());
        $this->assertSame('value', $cookie->getValue());
        $this->assertSame(100, $cookie->getExpiresTime());
        $this->assertSame('/path', $cookie->getPath());
        $this->assertSame('domain', $cookie->getDomain());
        $this->assertSame(true, $cookie->isSecure());
        $this->assertSame(false, $cookie->isHttpOnly());
        $this->assertSame(true, $cookie->isRaw());
        $this->assertSame(Cookie::SAMESITE_STRICT, $cookie->getSameSite());

        Config::application([
            Cookie::class => [
                'domain' => 'rebet.local'
            ]
        ]);
        $request                = $this->createRequestMock('/');
        $request->route->prefix = '/test';

        $cookie = new Cookie('key');
        $this->assertInstanceOf(Cookie::class, $cookie);
        $this->assertSame('key', $cookie->getName());
        $this->assertSame(null, $cookie->getValue());
        $this->assertSame(0, $cookie->getExpiresTime());
        $this->assertSame('/test', $cookie->getPath());
        $this->assertSame('rebet.local', $cookie->getDomain());
        $this->assertSame(false, $cookie->isSecure());
        $this->assertSame(true, $cookie->isHttpOnly());
        $this->assertSame(false, $cookie->isRaw());
        $this->assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());

        $cookie = new Cookie('key', 'value', null, '/path');
        $this->assertSame('/test/path', $cookie->getPath());
    }

    public function test_create()
    {
        $this->assertInstanceOf(Cookie::class, Cookie::create('key', 'value'));
    }

    /**
     * @expectedException Rebet\Common\Exception\LogicException
     * @expectedExceptionMessage Request has not been initialized.
     */
    public function test_has_requestNotInit()
    {
        $this->setProperty(Request::class, 'current', null);
        Cookie::has('key');
    }

    public function test_has()
    {
        $request = $this->createRequestMock('/');
        $this->assertFalse(Cookie::has('key'));
        $request->cookies->set('key', 'value');
        $this->assertTrue(Cookie::has('key'));
    }

    /**
     * @expectedException Rebet\Common\Exception\LogicException
     * @expectedExceptionMessage Request has not been initialized.
     */
    public function test_get_requestNotInit()
    {
        $this->setProperty(Request::class, 'current', null);
        Cookie::get('key');
    }

    public function test_get()
    {
        $request = $this->createRequestMock('/');
        $this->assertSame(null, Cookie::get('key'));
        Cookie::set('key', 'value');
        $this->assertSame(null, Cookie::get('key'));
        $request->cookies->set('key', 'value');
        $this->assertSame('value', Cookie::get('key'));
    }

    public function test_set()
    {
        Cookie::set('key', 'value', '1 day');
        $this->assertEquals(Cookie::create('key', 'value', '1 day'), Cookie::dequeue('key'));
    }

    public function test_remove()
    {
        Cookie::set('key', 'value', '1 day');
        $this->assertEquals(['key' => Cookie::create('key', 'value', '1 day')], Cookie::queued());
        Cookie::remove('key');
        $this->assertEquals(['key' => Cookie::create('key', null, 0)], Cookie::queued());
    }

    public function test_enqueue()
    {
        Cookie::enqueue(new Cookie('key', 'value', '1 day'));
        $this->assertEquals(['key' => Cookie::create('key', 'value', '1 day')], Cookie::queued());
    }

    public function test_dequeue()
    {
        Cookie::enqueue(new Cookie('key', 'value', '1 day'));
        $this->assertEquals(['key' => Cookie::create('key', 'value', '1 day')], Cookie::queued());
        Cookie::dequeue('key');
        $this->assertEquals([], Cookie::queued());
    }

    public function test_queued()
    {
        Cookie::enqueue(new Cookie('key', 'value', '1 day'));
        $this->assertEquals(['key' => Cookie::create('key', 'value', '1 day')], Cookie::queued());
    }
}
