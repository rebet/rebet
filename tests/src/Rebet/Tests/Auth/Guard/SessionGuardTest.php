<?php
namespace Rebet\Tests\Auth\Guard;

use Rebet\Auth\Auth;
use Rebet\Auth\AuthUser;
use Rebet\Auth\Exception\AuthenticateException;
use Rebet\Auth\Guard\SessionGuard;
use Rebet\Auth\Provider\ArrayProvider;
use Rebet\Auth\Provider\AuthProvider;
use Rebet\Http\Cookie\Cookie;
use Rebet\Http\Response\RedirectResponse;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Config\Config;

class SessionGuardTest extends RebetTestCase
{
    public function test___construct()
    {
        $guard = new SessionGuard('user');
        $this->assertInstanceOf(SessionGuard::class, $guard);
    }

    public function test_name()
    {
        $guard = new SessionGuard('user', '/user/signin', 30, $request = $this->createRequestMock('/user/mypage'));
        $this->assertNull($guard->name());
        $guard->name('user');
        $this->assertSame('user', $guard->name());
    }

    public function test_provider()
    {
        $guard = (new SessionGuard('user', '/user/signin', 30, $request = $this->createRequestMock('/user/mypage')))->name('user');
        $this->assertInstanceOf(AuthProvider::class, $provider = $guard->provider());
        $this->assertSame('user', $provider->name());
    }

    public function test_attempt()
    {
        $guard = (new SessionGuard('user', '/user/signin', 30, $request = $this->createRequestMock('/user/mypage')))->name('user');
        $user  = $guard->attempt('invalid', 'invalid');
        $this->assertTrue($user->isGuest());
        $user = $guard->attempt('admin@rebet.local', 'invalid');
        $this->assertTrue($user->isGuest());
        $user = $guard->attempt('admin@rebet.local', 'admin');
        $this->assertInstanceOf(AuthUser::class, $user);
        $this->assertSame('Admin', $user->name);
        $user = $guard->attempt('admin', 'admin');
        $this->assertTrue($user->isGuest());
    }

    public function test_signin_byDifferentProvider()
    {
        $this->expectException(AuthenticateException::class);
        $this->expectExceptionMessage("Can not sign-in by authenticated user who is got by different provider.");

        $guard    = (new SessionGuard('user', '/user/signin', 30, $request = $this->createRequestMock('/user/mypage')))->name('user');
        $response = $guard->signin(Auth::provider('admin')->findById(1));
    }

    public function test_signin_guest()
    {
        $guard    = (new SessionGuard('user', '/user/signin', 30, $request = $this->createRequestMock('/user/mypage')))->name('user');
        $response = $guard->signin(AuthUser::guest());
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/user/signin', $response->getTargetUrl());
    }

    public function test_signin_user()
    {
        $guard = (new SessionGuard('user', '/user/signin', 30, $request = $this->createRequestMock('/user/mypage')))->name('user');
        $user  = $guard->attempt('admin@rebet.local', 'admin');
        $this->assertInstanceOf(AuthUser::class, $user);
        $this->assertSame('Admin', $user->name);
        $response = $guard->signin($user);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/', $response->getTargetUrl());
        $session = $request->session();
        $this->assertSame(1, $session->get('auth:user:signin_id'));
        $this->assertEquals($user, $guard->user());
    }

    public function test_signin_userAfterFallback()
    {
        $guard    = (new SessionGuard('user', '/user/signin', 30, $request = $this->createRequestMock('/user/mypage?foo=bar', 'user')))->name('user');
        $fallback = $guard->authenticate();
        $this->assertInstanceOf(RedirectResponse::class, $fallback);
        $this->assertSame('/user/signin', $fallback->getTargetUrl());

        $user = $guard->attempt('admin@rebet.local', 'admin');
        $this->assertInstanceOf(AuthUser::class, $user);
        $this->assertSame('Admin', $user->name);
        $response = $guard->signin($user);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/user/mypage?foo=bar', $response->getTargetUrl());
        $session = $request->session();
        $this->assertSame(1, $session->get('auth:user:signin_id'));
        $this->assertEquals($user, $guard->user());
    }

    public function test_signin_userWithRemember()
    {
        $mock = $this->createMock(ArrayProvider::class);
        $mock->method('findByCredentials')->willReturn(new AuthUser(Auth::provider('user')->findById(1)->raw(), [], $mock));
        $mock->method('supportRememberToken')->willReturn(true);
        $mock->method('issuingRememberToken')->willReturn('MOCKED_TOKEN');
        $mock->method('name')->willReturn($mock);

        Config::runtime([
            Auth::class => [
                'providers' => [
                    'member' => $mock,
                ],
            ]
        ]);

        $guard = (new SessionGuard('member', '/user/signin', 30, $request = $this->createRequestMock('/user/mypage')))->name('member');

        $user = $guard->attempt('admin@rebet.local', 'admin');
        $this->assertInstanceOf(AuthUser::class, $user);
        $this->assertSame('Admin', $user->name);
        $response = $guard->signin($user, '/top', true);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/top', $response->getTargetUrl());
        $session = $request->session();
        $this->assertSame(1, $session->get('auth:member:signin_id'));
        $cookie = Cookie::dequeue('auth:member:remember_token');
        $this->assertSame('MOCKED_TOKEN', $cookie->getValue());
        $this->assertEquals($user, $guard->user());
    }

    public function test_signout()
    {
        $removed = null;
        $mock    = $this->createMock(ArrayProvider::class);
        $mock->method('findByCredentials')->willReturn(new AuthUser(Auth::provider('user')->findById(1)->raw(), [], $mock));
        $mock->method('supportRememberToken')->willReturn(true);
        $mock->method('issuingRememberToken')->willReturn('MOCKED_TOKEN');
        $mock->method('removeRememberToken')->will($this->returnCallback(function ($token) use (&$removed) { $removed = $token; }));
        $mock->method('name')->willReturn($mock);

        Config::runtime([
            Auth::class => [
                'providers' => [
                    'member' => $mock,
                ],
            ]
        ]);

        $guard = (new SessionGuard('member', '/user/signin', 30, $request = $this->createRequestMock('/user/mypage')))->name('member');

        $user = $guard->attempt('admin@rebet.local', 'admin');
        $this->assertInstanceOf(AuthUser::class, $user);
        $this->assertSame('Admin', $user->name);
        $response = $guard->signin($user, '/top', true);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/top', $response->getTargetUrl());
        $session = $request->session();
        $this->assertSame(1, $session->get('auth:member:signin_id'));
        $cookie = Cookie::dequeue('auth:member:remember_token');
        $this->assertSame('MOCKED_TOKEN', $cookie->getValue());
        $this->assertEquals($user, $guard->user());
        $this->assertSame(null, $removed);
        $request->cookies->set('auth:member:remember_token', 'MOCKED_TOKEN');

        $response = $guard->signout('/signouted');
        $this->assertNull($session->get('auth:member:signin_id'));
        $cookie = Cookie::peek('auth:member:remember_token');
        $this->assertNull($cookie->getValue());
        $this->assertSame('auth:member:remember_token=deleted; expires='.gmdate('D, d-M-Y H:i:s T', time() - 31536001).'; Max-Age=0; path=/; httponly; samesite=lax', "{$cookie}");
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/signouted', $response->getTargetUrl());
        $this->assertSame('MOCKED_TOKEN', $removed);
    }

    public function test_authenticate()
    {
        $mock    = $this->createMock(ArrayProvider::class);
        $mock->method('findByCredentials')->willReturn(new AuthUser(Auth::provider('user')->findById(1)->raw(), [], $mock));
        $mock->method('supportRememberToken')->willReturn(true);
        $mock->method('issuingRememberToken')->willReturn('MOCKED_TOKEN');
        $mock->method('name')->willReturn($mock);

        Config::runtime([
            Auth::class => [
                'providers' => [
                    'admin' => $mock,
                ],
            ]
        ]);

        $guard = (new SessionGuard('admin', '/admin/signin', 30, $request = $this->createRequestMock('/admin/mypage?foo=bar', 'admin')))->name('admin');

        $fallback = $guard->authenticate();
        $this->assertNotNull($fallback);
        $this->assertInstanceOf(RedirectResponse::class, $fallback);
        $this->assertSame('/admin/signin', $fallback->getTargetUrl());
        $this->assertTrue($request->isSaved('auth:admin:guarded_request'));
        $this->assertTrue($guard->user()->isGuest());

        $user = $guard->attempt('admin@rebet.local', 'admin');
        $this->assertSame(1, $user->id);
        $response = $guard->signin($user, '/top', true);
        $this->assertEquals($user, $guard->user());
        $mock->method('findByRememberToken')->willReturn($user);

        $fallback = $guard->authenticate();
        $this->assertNull($fallback);
        $this->assertEquals($user, $guard->user());

        $guard = (new SessionGuard('user', '/user/signin', 30, $request = $this->createRequestMock('/user/mypage?foo=bar', 'user')))->name('user');
        $request->session()->set('auth:user:signin_id', 2);
        $fallback = $guard->authenticate();
        $this->assertNull($fallback);
        $this->assertEquals(Auth::provider('user')->findById(2)->raw(), $guard->user()->raw());
    }

    public function test_getRememberDays()
    {
        $guard = (new SessionGuard('user', '/user/signin', 30, $request = $this->createRequestMock('/user/mypage')))->name('remember');
        $this->assertSame(30, $guard->getRememberDays());
    }
}
