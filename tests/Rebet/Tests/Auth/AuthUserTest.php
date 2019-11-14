<?php
namespace Rebet\Tests\Auth;

use Rebet\Auth\Auth;
use Rebet\Auth\AuthUser;
use Rebet\Auth\Guard\SessionGuard;
use Rebet\Auth\Provider\ArrayProvider;
use Rebet\Common\Reflector;
use Rebet\DateTime\Date;
use Rebet\DateTime\DateTime;
use Rebet\Tests\Mock\Entity\Bank;
use Rebet\Tests\Mock\Entity\User;
use Rebet\Tests\RebetTestCase;

class AuthUserTest extends RebetTestCase
{
    private $array_user_source;
    private $object_user_source;

    public function setUp()
    {
        parent::setUp();
        $this->signout();
        DateTime::setTestNow('2010-01-01 12:34:56');

        $this->array_user_source = [
            'user_id'     => 2,
            'role'        => 'user',
            'name'        => 'User',
            'signin_id'   => 'user',
            'email'       => 'user@rebet.local',
            'password'    => '$2y$04$o9wMO8hXHHFpoNdLYRBtruWIUjPMU3Jqw9JAS0Oc7LOXiHFfn.7F2',  // password: user
            'api_token'   => 'token_2',
            'resigned_at' => null
        ];

        $user           = new User();
        $user->user_id  = 2;
        $user->email    = 'user@rebet.local';
        $user->role     = 'user';
        $user->name     = 'User';
        $user->birthday = Date::createDateTime('1991-02-03');

        $this->object_user_source = $user;

        $bank          = new Bank();
        $bank->user_id = 2;
        $bank->name    = 'Sample Bank';
        $bank->branch  = 'Sample Branch';
        $bank->number  = '1234567';
        $bank->holder  = 'User';

        $user->bank                      = $bank;
        $this->array_user_source['bank'] = $bank;
    }

    public function test___construct()
    {
        $user = new AuthUser($this->array_user_source);
        $this->assertInstanceOf(AuthUser::class, $user);
        $this->assertSame(2, $user->id);
        $this->assertSame('user', $user->role);

        $user = new AuthUser($this->object_user_source);
        $this->assertInstanceOf(AuthUser::class, $user);
        $this->assertSame(2, $user->id);
        $this->assertSame('user', $user->role);

        $user = new AuthUser($this->object_user_source, ['role' => '@admin']);
        $this->assertSame('admin', $user->role);
    }

    public function test_guest()
    {
        $user = AuthUser::guest();
        $this->assertTrue($user->isGuest());
        $this->assertSame(null, $user->id);
        $this->assertSame(null, $user->user_id);
        $this->assertSame('guest', $user->role);
        $this->assertSame(null, $user->charengedSigninId());

        $user = AuthUser::guest('user@rebet.local');
        $this->assertTrue($user->isGuest());
        $this->assertSame(null, $user->id);
        $this->assertSame(null, $user->user_id);
        $this->assertSame('guest', $user->role);
        $this->assertSame('user@rebet.local', $user->charengedSigninId());

        $user = AuthUser::guest('user@rebet.local', ['role' => '@user', 'user_id' => 0]);
        $this->assertTrue($user->isGuest());
        $this->assertSame(0, $user->id);
        $this->assertSame(0, $user->user_id);
        $this->assertSame('user', $user->role);
        $this->assertSame('user@rebet.local', $user->charengedSigninId());
    }

    public function test_guard()
    {
        $user = new AuthUser($this->object_user_source);
        $this->assertNull($user->guard());
        $this->assertInstanceOf(AuthUser::class, $user->guard(new SessionGuard()));
        $this->assertInstanceOf(SessionGuard::class, $user->guard());
    }

    public function test_provider()
    {
        $user = new AuthUser($this->object_user_source);
        $this->assertNull($user->provider());
        $this->assertInstanceOf(AuthUser::class, $user->provider(new ArrayProvider([])));
        $this->assertInstanceOf(ArrayProvider::class, $user->provider());
    }

    public function test_refresh()
    {
        $this->signin();
        $user = Auth::user();
        $this->assertInstanceOf(AuthUser::class, $user);
        $this->assertSame('User', $user->name);

        $user->name = 'Updated';
        $this->assertSame('Updated', $user->name);

        $this->assertInstanceOf(AuthUser::class, $user->refresh());
        $this->assertSame('User', $user->name);
    }

    public function test_isGuest()
    {
        $this->assertTrue(AuthUser::guest()->isGuest());
        $this->signin();
        $this->assertFalse(Auth::user()->isGuest());
    }

    public function test_charengedSigninId()
    {
        $this->assertNull(AuthUser::guest()->charengedSigninId());
        $this->assertSame(1, AuthUser::guest(1)->charengedSigninId());
    }

    public function test_is()
    {
        $user = Auth::user();
        $this->assertTrue($user->isGuest());

        $this->assertTrue($user->is('all'));
        $this->assertTrue($user->is('guest'));
        $this->assertFalse($user->is('user'));
        $this->assertFalse($user->is('admin'));
        $this->assertFalse($user->is('user', 'admin'));
        $this->assertTrue($user->is('guest', 'user'));

        $this->signin();
        $user = Auth::user();
        $this->assertSame(2, $user->id);

        $this->assertTrue($user->is('all'));
        $this->assertFalse($user->is('guest'));
        $this->assertTrue($user->is('user'));
        $this->assertFalse($user->is('admin'));
        $this->assertTrue($user->is('user', 'admin'));
        $this->assertTrue($user->is('guest', 'user'));
    }

    public function test_isnot()
    {
        $user = Auth::user();
        $this->assertTrue($user->isGuest());
        $this->assertFalse($user->isnot('guest'));
        $this->assertTrue($user->isnot('user'));
    }

    public function test_can()
    {
        $user          = new User();
        $user->user_id = 2;

        $this->assertTrue(Auth::user()->isGuest());
        $this->assertFalse(Auth::user()->can('update', $user));

        $this->signin();
        $this->assertSame(2, Auth::user()->id);
        $this->assertTrue(Auth::user()->can('update', $user));

        $user->user_id = 1;
        $this->assertFalse(Auth::user()->can('update', $user));

        $this->signin(null, 'admin@rebet.local', 'admin');
        $this->assertTrue(Auth::user()->is('admin'));

        $user->user_id = 1;
        $this->assertTrue(Auth::user()->can('update', $user));

        $user->user_id = 2;
        $this->assertTrue(Auth::user()->can('update', $user));
    }

    public function test_cannot()
    {
        $user          = new User();
        $user->user_id = 2;

        $this->assertTrue(Auth::user()->isGuest());
        $this->assertTrue(Auth::user()->cannot('update', $user));

        $this->signin();
        $this->assertSame(2, Auth::user()->id);
        $this->assertFalse(Auth::user()->cannot('update', $user));

        $user->user_id = 1;
        $this->assertTrue(Auth::user()->cannot('update', $user));

        $this->signin(null, 'admin@rebet.local', 'admin');
        $this->assertTrue(Auth::user()->is('admin'));

        $user->user_id = 1;
        $this->assertFalse(Auth::user()->cannot('update', $user));

        $user->user_id = 2;
        $this->assertFalse(Auth::user()->cannot('update', $user));
    }

    public function test_raw()
    {
        $this->assertNull(AuthUser::guest()->raw());
        $this->signin();
        $this->assertSame(2, Auth::user()->id);
        $raw = null;
        $raw = &Auth::user()->raw();
        $this->assertTrue(is_array($raw));
        $this->assertSame(2, $raw['user_id']);
        $raw['user_id'] = 999;
        $this->assertSame(999, Auth::user()->id);
    }

    public function test___get()
    {
        foreach ([$this->array_user_source, $this->object_user_source] as $user_source) {
            $user = new AuthUser($user_source);
            $this->assertInstanceOf(AuthUser::class, $user);
            $this->assertSame(2, $user->id);
            $this->assertSame('user', $user->role);

            $user = new AuthUser($user_source, ['role' => '@admin']);
            $this->assertSame('admin', $user->role);

            $user = new AuthUser($user_source, ['role' => 'user_id']);
            $this->assertSame(2, $user->role);

            $user = new AuthUser($user_source, ['role' => function ($user) { return $user ? strtoupper(Reflector::get($user, 'role')) : null ; }]);
            $this->assertSame('USER', $user->role);

            $user = new AuthUser($user_source, ['role' => 123]);
            $this->assertSame(123, $user->role);

            $user = new AuthUser($user_source, ['bank_name' => 'bank.name']);
            $this->assertSame('Sample Bank', $user->bank_name);

            $user = new AuthUser($user_source, ['name' => '!email', 'email' => '!name']);
            $this->assertSame('user@rebet.local', $user->name);
            $this->assertSame('User', $user->email);
        }
    }

    /**
     * @expectedException Rebet\Common\Exception\LogicException
     * @expectedExceptionMessage Too many (over 20) aliases recursion depth.
     */
    public function test___getInfiniteRecursionAliases()
    {
        $user = new AuthUser($this->object_user_source, ['name' => 'email', 'email' => 'name']);
        $name = $user->name;
    }

    public function test___set()
    {
        foreach ([$this->array_user_source, $this->object_user_source] as $user_source) {
            $user = new AuthUser($user_source);
            $this->assertInstanceOf(AuthUser::class, $user);
            $this->assertSame(2, $user->id);
            $this->assertSame(2, $user->user_id);
            $user->user_id = 99;
            $this->assertSame(99, $user->id);
            $this->assertSame(99, $user->user_id);
            $this->assertSame(99, Reflector::get($user->raw(), 'user_id'));

            $user->id = 2;
            $this->assertSame(2, $user->id);
            $this->assertSame(2, $user->user_id);
            $this->assertSame(2, Reflector::get($user->raw(), 'user_id'));
        }
    }
}
