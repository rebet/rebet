<?php
namespace Rebet\Tests\Auth;

use Rebet\Auth\Auth;
use Rebet\Auth\AuthUser;
use Rebet\Auth\Event\Signined;
use Rebet\Auth\Event\SigninFailed;
use Rebet\Auth\Event\Signouted;
use Rebet\Auth\Guard\SessionGuard;
use Rebet\Auth\Provider\ArrayProvider;
use Rebet\Event\Event;
use Rebet\Http\Response\RedirectResponse;
use Rebet\Tests\RebetTestCase;

class AuthTest extends RebetTestCase
{
    public function setUp()
    {
        $request = $this->createRequestMock('/user/signout');
        Auth::signout($request);
    }

    public function test_user()
    {
        $user = Auth::user();
        $this->assertTrue($user->isGuest());
        $this->assertSame(null, $user->id);

        $request = $this->createRequestMock('/');
        $user    = Auth::attempt($request, 'user@rebet.com', 'user');
        Auth::signin($request, $user, '/user/signin');

        $user = Auth::user();
        $this->assertFalse($user->isGuest());
        $this->assertSame(2, $user->id);
    }

    public function test_attempt()
    {
        $request  = $this->createRequestMock('/');
        $user     = Auth::attempt($request, 'user@rebet.com', 'user');
        $provider = $user->provider();
        $guard    = $user->guard();
        $this->assertSame(2, $user->id);
        $this->assertTrue($user->is('user'));
        $this->assertInstanceOf(ArrayProvider::class, $provider);
        $this->assertInstanceOf(SessionGuard::class, $guard);
        $this->assertSame('web', $provider->authenticator());
        $this->assertSame('web', $guard->authenticator());

        $user = Auth::attempt($request, 'user@rebet.com', 'invalid_password');
        $this->assertNull($user);

        $user = Auth::attempt($request, 'invalid_signin_id', 'user');
        $this->assertNull($user);

        $user = Auth::attempt($request, 'user.resigned@rebet.com', 'user.resigned');
        $this->assertNull($user);

        $precondition = function ($user) { return $user['role'] === 'admin'; };
        $user         = Auth::attempt($request, 'user@rebet.com', 'user', $precondition);
        $this->assertNull($user);

        $precondition = function ($user) { return $user['role'] === 'admin'; };
        $user         = Auth::attempt($request, 'admin@rebet.com', 'admin', $precondition);
        $this->assertSame(1, $user->id);
        $this->assertTrue($user->is('admin'));
    }
    
    public function test_signin_success()
    {
        $signined_user_id = null;
        Event::listen(function (Signined $event) use (&$signined_user_id) { $signined_user_id = $event->user->id; });

        $user = Auth::user();
        $this->assertTrue($user->isGuest());
        $this->assertNull($signined_user_id);

        $request  = $this->createRequestMock('/');
        $user     = Auth::attempt($request, 'user@rebet.com', 'user');
        $response = Auth::signin($request, $user, '/user/signin');

        $user = Auth::user();
        $this->assertFalse($user->isGuest());
        $this->assertSame(2, $user->id);
        $this->assertSame(2, $signined_user_id);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/', $response->getTargetUrl());

        $user     = Auth::attempt($request, 'admin@rebet.com', 'admin');
        $response = Auth::signin($request, $user, '/admin/signin', '/admin/dashbord');
        $this->assertFalse($user->isGuest());
        $this->assertSame(1, $user->id);
        $this->assertSame(1, $signined_user_id);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/dashbord', $response->getTargetUrl());
    }
    
    public function test_signin_failed()
    {
        $charenged_signin_id = null;
        Event::listen(function (SigninFailed $event) use (&$charenged_signin_id) { $charenged_signin_id = $event->charenged_signin_id; });

        $user = Auth::user();
        $this->assertTrue($user->isGuest());

        $request  = $this->createRequestMock('/');
        $user     = Auth::attempt($request, 'user@rebet.com', 'invalid_password');
        $response = Auth::signin($request, $user ?? AuthUser::guest('user@rebet.com'), '/user/signin');

        $user = Auth::user();
        $this->assertTrue($user->isGuest());
        $this->assertSame('user@rebet.com', $charenged_signin_id);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/user/signin', $response->getTargetUrl());

        $user     = Auth::attempt($request, 'user.resigned@rebet.com', 'user.resigned');
        $response = Auth::signin($request, $user ?? AuthUser::guest('user.resigned@rebet.com'), '/user/signin');

        $user = Auth::user();
        $this->assertTrue($user->isGuest());
        $this->assertSame('user.resigned@rebet.com', $charenged_signin_id);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/user/signin', $response->getTargetUrl());
    }

    public function test_signout()
    {
        $signouted_user_id = null;
        Event::listen(function (Signouted $event) use (&$signouted_user_id) { $signouted_user_id = $event->user->id; });

        $user = Auth::user();
        $this->assertTrue($user->isGuest());

        $request  = $this->createRequestMock('/');
        $response = Auth::signout($request);
        $user     = Auth::user();
        $this->assertTrue($user->isGuest());
        $this->assertNull($signouted_user_id);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/', $response->getTargetUrl());

        $user     = Auth::attempt($request, 'user@rebet.com', 'user');
        $response = Auth::signin($request, $user, '/user/signin');
        $user     = Auth::user();
        $this->assertSame(2, $user->id);

        $response = Auth::signout($request, '/signouted');
        $user     = Auth::user();
        $this->assertTrue($user->isGuest());
        $this->assertSame(2, $signouted_user_id);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/signouted', $response->getTargetUrl());
    }
}
