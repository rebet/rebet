<?php
namespace Rebet\Tests\Auth;

use Rebet\Auth\Auth;
use Rebet\Auth\AuthUser;
use Rebet\Auth\Event\Authenticated;
use Rebet\Auth\Event\AuthenticateFailed;
use Rebet\Auth\Event\Signined;
use Rebet\Auth\Event\SigninFailed;
use Rebet\Auth\Event\Signouted;
use Rebet\Auth\Guard\SessionGuard;
use Rebet\Auth\Provider\ArrayProvider;
use Rebet\Event\Event;
use Rebet\Http\Responder;
use Rebet\Http\Response\ProblemResponse;
use Rebet\Http\Response\RedirectResponse;
use Rebet\Tests\Common\Mock\Bank;
use Rebet\Tests\Common\Mock\User;
use Rebet\Tests\RebetTestCase;

class AuthTest extends RebetTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->signout();
    }

    public function test_user()
    {
        $user = Auth::user();
        $this->assertTrue($user->isGuest());
        $this->assertSame(null, $user->id);

        $request = $this->createRequestMock('/');
        $user    = Auth::attempt($request, 'user@rebet.local', 'user');
        Auth::signin($request, $user, '/user/signin');

        $user = Auth::user();
        $this->assertFalse($user->isGuest());
        $this->assertSame(2, $user->id);
    }

    public function test_attempt()
    {
        $request  = $this->createRequestMock('/');
        $user     = Auth::attempt($request, 'user@rebet.local', 'user');
        $provider = $user->provider();
        $guard    = $user->guard();
        $this->assertSame(2, $user->id);
        $this->assertTrue($user->is('user'));
        $this->assertInstanceOf(ArrayProvider::class, $provider);
        $this->assertInstanceOf(SessionGuard::class, $guard);
        $this->assertSame('web', $provider->authenticator());
        $this->assertSame('web', $guard->authenticator());

        $user = Auth::attempt($request, 'user@rebet.local', 'invalid_password');
        $this->assertNull($user);

        $user = Auth::attempt($request, 'invalid_signin_id', 'user');
        $this->assertNull($user);

        $user = Auth::attempt($request, 'user.resigned@rebet.local', 'user.resigned');
        $this->assertNull($user);

        $precondition = function ($user) { return $user['role'] === 'admin'; };
        $user         = Auth::attempt($request, 'user@rebet.local', 'user', $precondition);
        $this->assertNull($user);

        $precondition = function ($user) { return $user['role'] === 'admin'; };
        $user         = Auth::attempt($request, 'admin@rebet.local', 'admin', $precondition);
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
        $user     = Auth::attempt($request, 'user@rebet.local', 'user');
        $response = Auth::signin($request, $user, '/user/signin');

        $user = Auth::user();
        $this->assertFalse($user->isGuest());
        $this->assertSame(2, $user->id);
        $this->assertSame(2, $signined_user_id);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/', $response->getTargetUrl());

        $user     = Auth::attempt($request, 'admin@rebet.local', 'admin');
        $response = Auth::signin($request, $user, '/admin/signin', '/admin/dashboard');
        $this->assertFalse($user->isGuest());
        $this->assertSame(1, $user->id);
        $this->assertSame(1, $signined_user_id);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/dashboard', $response->getTargetUrl());
    }
    
    public function test_signin_failed()
    {
        $charenged_signin_id = null;
        Event::listen(function (SigninFailed $event) use (&$charenged_signin_id) { $charenged_signin_id = $event->charenged_signin_id; });

        $user = Auth::user();
        $this->assertTrue($user->isGuest());

        $request  = $this->createRequestMock('/');
        $user     = Auth::attempt($request, 'user@rebet.local', 'invalid_password');
        $response = Auth::signin($request, $user ?? AuthUser::guest('user@rebet.local'), '/user/signin');

        $user = Auth::user();
        $this->assertTrue($user->isGuest());
        $this->assertSame('user@rebet.local', $charenged_signin_id);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/user/signin', $response->getTargetUrl());

        $user     = Auth::attempt($request, 'user.resigned@rebet.local', 'user.resigned');
        $response = Auth::signin($request, $user ?? AuthUser::guest('user.resigned@rebet.local'), '/user/signin');

        $user = Auth::user();
        $this->assertTrue($user->isGuest());
        $this->assertSame('user.resigned@rebet.local', $charenged_signin_id);
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

        $this->signin();
        $user     = Auth::user();
        $this->assertSame(2, $user->id);

        $response = Auth::signout($request, '/signouted');
        $user     = Auth::user();
        $this->assertTrue($user->isGuest());
        $this->assertSame(2, $signouted_user_id);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/signouted', $response->getTargetUrl());
    }

    public function test_authenticate()
    {
        $authenticate_user_id        = 'not set';
        $authenticate_failed_user_id = 'not set';
        Event::listen(function (Authenticated $event) use (&$authenticate_user_id) { $authenticate_user_id = $event->user->id; });
        Event::listen(function (AuthenticateFailed $event) use (&$authenticate_failed_user_id) { $authenticate_failed_user_id = $event->user->id; });

        $this->assertTrue(Auth::user()->isGuest());

        $request  = $this->createRequestMock('/');
        $response = Auth::authenticate($request);
        $this->assertNull($response);
        $this->assertSame('not set', $authenticate_user_id);
        $this->assertSame('not set', $authenticate_failed_user_id);

        $request  = $this->createRequestMock('/user/mypage', 'user');
        $response = Auth::authenticate($request);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/user/signin', $response->getTargetUrl());
        $this->assertSame('not set', $authenticate_user_id);
        $this->assertSame('not set', $authenticate_failed_user_id);

        $this->signin($request);
        $response = Auth::authenticate($request);
        $this->assertNull($response);
        $this->assertSame(2, $authenticate_user_id);
        $this->assertSame('not set', $authenticate_failed_user_id);

        $request  = $this->createRequestMock('/admin/dashboard', 'admin');
        $this->signin($request);
        $response = Auth::authenticate($request);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/user/signin', $response->getTargetUrl());
        $this->assertSame(2, $authenticate_failed_user_id);

        $request  = $this->createRequestMock('/api/user', 'admin', 'api');
        $this->signin($request);
        $response = Auth::authenticate($request);
        $this->assertInstanceOf(ProblemResponse::class, $response);
        $this->assertEquals(Responder::problem(403), $response);

        $request->query->set('api_token', 'token_1');
        $response = Auth::authenticate($request);
        $this->assertNull($response);
    }

    public function test_defineRole()
    {
        $user = Auth::user();
        $this->assertTrue($user->isGuest());
        $this->assertFalse(Auth::role($user, 'visitor'));

        Auth::defineRole('visitor', function (AuthUser $user) { return $user->is('guest', 'user'); });

        $this->assertTrue(Auth::role($user, 'visitor'));

        $request  = $this->createRequestMock('/contact', 'visitor');
        $response = Auth::authenticate($request);
        $this->assertNull($response);
        $this->assertTrue($user->isGuest());

        $this->signin($request);
        $user = Auth::user();
        $this->assertSame(2, $user->id);

        $response = Auth::authenticate($request);
        $this->assertNull($response);

        $this->signin($request, 'admin@rebet.local', 'admin');
        $user = Auth::user();
        $this->assertSame(1, $user->id);
        $response = Auth::authenticate($request);
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function test_definePolicy()
    {
        $this->signin();
        $this->assertSame(2, Auth::user()->id);

        $bank          = new Bank();
        $bank->user_id = 1;

        $this->assertFalse(Auth::policy(Auth::user(), 'update', $bank));

        Auth::definePolicy(Bank::class, 'update', function (AuthUser $user, Bank $target) { return $user->id === $target->user_id; });

        $this->assertFalse(Auth::policy(Auth::user(), 'update', $bank));

        $bank->user_id = 2;
        $this->assertTrue(Auth::policy(Auth::user(), 'update', $bank));
    }

    public function test_defineBeforePolicy()
    {
        $this->signin(null, 'admin@rebet.local', 'admin');
        $this->assertTrue(Auth::user()->is('admin'));

        $bank          = new Bank();
        $bank->user_id = 2;

        $this->assertFalse(Auth::policy(Auth::user(), 'update', $bank));

        Auth::defineBeforePolicy(Bank::class, function (AuthUser $user, Bank $target) { return $user->is('admin'); });

        $this->assertTrue(Auth::policy(Auth::user(), 'update', $bank));
    }

    public function test_policy()
    {
        $user          = new User();
        $user->user_id = 2;

        $this->assertTrue(Auth::user()->isGuest());
        $this->assertFalse(Auth::policy(Auth::user(), 'update', $user));

        $this->signin();
        $this->assertSame(2, Auth::user()->id);
        $this->assertTrue(Auth::policy(Auth::user(), 'update', $user));

        $user->user_id = 1;
        $this->assertFalse(Auth::policy(Auth::user(), 'update', $user));

        $this->signin(null, 'admin@rebet.local', 'admin');
        $this->assertTrue(Auth::user()->is('admin'));

        $user->user_id = 1;
        $this->assertTrue(Auth::policy(Auth::user(), 'update', $user));

        $user->user_id = 2;
        $this->assertTrue(Auth::policy(Auth::user(), 'update', $user));
    }

    public function test_role()
    {
        $user = Auth::user();
        $this->assertTrue($user->isGuest());

        $this->assertTrue(Auth::role($user, 'all'));
        $this->assertTrue(Auth::role($user, 'guest'));
        $this->assertFalse(Auth::role($user, 'user'));
        $this->assertFalse(Auth::role($user, 'admin'));
        $this->assertFalse(Auth::role($user, 'user', 'admin'));
        $this->assertTrue(Auth::role($user, 'guest', 'user'));

        $this->signin();
        $user = Auth::user();
        $this->assertSame(2, $user->id);

        $this->assertTrue(Auth::role($user, 'all'));
        $this->assertFalse(Auth::role($user, 'guest'));
        $this->assertTrue(Auth::role($user, 'user'));
        $this->assertFalse(Auth::role($user, 'admin'));
        $this->assertTrue(Auth::role($user, 'user', 'admin'));
        $this->assertTrue(Auth::role($user, 'guest', 'user'));
        $this->assertTrue(Auth::role($user, 'user', 'editable'));
        $this->assertFalse(Auth::role($user, 'user:editable'));

        $this->signin(null, 'user.editable@rebet.local', 'user.editable');
        $user = Auth::user();
        $this->assertTrue(Auth::role($user, 'user', 'editable'));
        $this->assertTrue(Auth::role($user, 'user:editable'));
    }
}
