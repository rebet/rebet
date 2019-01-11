<?php
namespace Rebet\Tests\Auth\Guard;

use Rebet\Auth\AuthUser;
use Rebet\Auth\Guard\SessionGuard;
use Rebet\Auth\Provider\ArrayProvider;
use Rebet\Http\Cookie\Cookie;
use Rebet\Http\Response\RedirectResponse;
use Rebet\Tests\RebetTestCase;

class SessionGuardTest extends RebetTestCase
{
    private $guard;
    private $provider;
    private $request;
    private $user;

    public function setUp()
    {
        parent::setUp();

        $authenticator = 'web';
        $this->guard   = new SessionGuard();
        $this->guard->authenticator($authenticator);

        $this->provider = new ArrayProvider(
            [
                $user_1 = ['user_id' => 1, 'role' => 'admin', 'name' => 'test', 'email' => 'test@rebet.local', 'password' => '$2y$10$2LE4ETkjzUomoqFgcRaCnOX2AOjqbCcP3ADJJfzsWjp927EzxAeXu'],
            ],
            'email'
        );
        $this->provider->authenticator($authenticator);

        $this->user = new AuthUser($user_1);
        $this->user->guard($this->guard);
        $this->user->provider($this->provider);
        $this->authenticator = $authenticator;

        $this->request = $this->createRequestMock('/');
    }

    public function test___construct()
    {
        $guard = new SessionGuard();
        $this->assertInstanceOf(SessionGuard::class, $guard);
    }

    public function test_getRememberDays()
    {
        $guard = new SessionGuard(30);
        $this->assertSame(30, $guard->getRememberDays());
    }

    public function test_signin()
    {
        $this->guard->signin($this->request, $this->user);
        $session = $this->request->session();
        $this->assertSame(1, $session->get('auth:web:id'));

        $mock = $this->getMockBuilder(ArrayProvider::class)
                ->disableOriginalConstructor()
                ->getMock();
        $mock->method('supportRememberToken')->willReturn(true);
        $mock->method('issuingRememberToken')->willReturn('MOCKED_TOKEN');
        $this->user->provider($mock);
        
        $this->guard->signin($this->request, $this->user, true);
        $session = $this->request->session();
        $this->assertSame(1, $session->get('auth:web:id'));
        $cookie = Cookie::dequeue(SessionGuard::COOKIE_KEY_REMEMBER);
        $this->assertSame('MOCKED_TOKEN', $cookie->getValue());
    }

    public function test_signout()
    {
        $removed = null;
        $mock    = $this->getMockBuilder(ArrayProvider::class)
                ->disableOriginalConstructor()
                ->getMock();
        $mock->method('supportRememberToken')->willReturn(true);
        $mock->method('issuingRememberToken')->willReturn('MOCKED_TOKEN');
        $mock->method('removeRememberToken')->will($this->returnCallback(function ($token) use (&$removed) { $removed = $token; }));
        $this->user->provider($mock);

        $this->guard->signin($this->request, $this->user, true);
        $session = $this->request->session();
        $this->assertSame(1, $session->get('auth:web:id'));
        $cookie = Cookie::dequeue(SessionGuard::COOKIE_KEY_REMEMBER);
        $this->assertSame('MOCKED_TOKEN', $cookie->getValue());
        $this->assertSame(null, $removed);
        $this->request->cookies->set(SessionGuard::COOKIE_KEY_REMEMBER, 'MOCKED_TOKEN');
    
        $response = $this->guard->signout($this->request, $this->user, '/signouted');
        $session  = $this->request->session();
        $this->assertNull($session->get('auth:web:id'));
        $cookie = Cookie::dequeue(SessionGuard::COOKIE_KEY_REMEMBER);
        $this->assertNull($cookie->getValue());
        $this->assertSame('remember=deleted; expires='.gmdate('D, d-M-Y H:i:s T', time() - 31536001).'; Max-Age=0; path=/; httponly; samesite=lax', "{$cookie}");
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/signouted', $response->getTargetUrl());
        $this->assertSame('MOCKED_TOKEN', $removed);
    }

    public function test_authenticate()
    {
        $user = $this->guard->authenticate($this->request, $this->provider);
        $this->assertEquals(AuthUser::guest(), $user);

        $this->guard->signin($this->request, $this->user);
        $session = $this->request->session();
        $this->assertSame(1, $session->get('auth:web:id'));

        $user = $this->guard->authenticate($this->request, $this->provider);
        $this->assertEquals($this->user->raw(), $user->raw());

        $response = $this->guard->signout($this->request, $this->user, '/signouted');
        $user     = $this->guard->authenticate($this->request, $this->provider);
        $this->assertEquals(AuthUser::guest(), $user);

        $mock = $this->getMockBuilder(ArrayProvider::class)
                ->disableOriginalConstructor()
                ->getMock();
        $mock->method('supportRememberToken')->willReturn(true);
        $mock->method('findByRememberToken')->willReturn($this->user);
        $user = $this->guard->authenticate($this->request, $mock);
        $this->assertEquals($this->user->raw(), $user->raw());
    }
}
