<?php
namespace Rebet\Tests\Auth\Event;

use Rebet\Auth\Password;
use Rebet\Auth\Provider\ArrayProvider;
use Rebet\Common\Strings;
use Rebet\DateTime\DateTime;
use Rebet\Tests\RebetTestCase;

class ArrayProviderTest extends RebetTestCase
{
    private $users;
    private $provider;
    private $provider_by_signin_id;
    private $provider_exclude_resigned;

    protected function setUp() : void
    {
        $this->users = [
            ['user_id' => 1, 'role' => 'admin', 'name' => 'Admin'       , 'signin_id' => 'admin'       , 'email' => 'admin@rebet.local'        , 'password' => '$2y$04$68GZ8.IwFPFiVsae03fP7uMD76RYsEp9WunbITtrdRgvtJO1DGrim', 'api_token' => 'token_1', 'resigned_at' => null], // password: admin
            ['user_id' => 2, 'role' => 'user' , 'name' => 'User'        , 'signin_id' => 'user'        , 'email' => 'user@rebet.local'         , 'password' => '$2y$04$o9wMO8hXHHFpoNdLYRBtruWIUjPMU3Jqw9JAS0Oc7LOXiHFfn.7F2', 'api_token' => 'token_2', 'resigned_at' => null], // password: user
            ['user_id' => 3, 'role' => 'user' , 'name' => 'Resignd User', 'signin_id' => 'user.resignd', 'email' => 'user.resignd@rebet.local' , 'password' => '$2y$04$GwwjNndAojOi8uFu6xwFHe6L6Q/v6/7VynBatMHhCyfNt7momtiqK', 'api_token' => 'token_3', 'resigned_at' => DateTime::createDateTime('2001-01-01 12:34:56')], // password: user.resignd
        ];

        $this->provider                  = new ArrayProvider($this->users);
        $this->provider_by_signin_id     = new ArrayProvider($this->users, 'signin_id');
        $this->provider_exclude_resigned = new ArrayProvider($this->users, 'email', function ($user) { return !isset($user['resigned_at']); });
    }

    public function test___construct()
    {
        $this->assertInstanceOf(ArrayProvider::class, new ArrayProvider([]));
    }

    public function test_findById()
    {
        $user = $this->provider->findById(null);
        $this->assertNull($user);

        $user = $this->provider->findById(0);
        $this->assertNull($user);

        foreach ($this->users as $expect_user) {
            $id = $expect_user['user_id'];

            $user = $this->provider->findById($id);
            $this->assertSame($expect_user, $user->raw());

            $user = $this->provider_by_signin_id->findById($id);
            $this->assertSame($expect_user, $user->raw());

            $user = $this->provider_exclude_resigned->findById($id);
            $this->assertSame($expect_user, $user->raw());
        }
    }

    public function test_findByToken()
    {
        $user = $this->provider->findByToken('api_token', null);
        $this->assertNull($user);

        $user = $this->provider->findByToken('api_token', 'invalid_token');
        $this->assertNull($user);

        foreach ($this->users as $expect_user) {
            $token = $expect_user['api_token'];

            $user = $this->provider->findByToken('api_token', $token);
            $this->assertSame($expect_user, $user->raw());

            $user = $this->provider_by_signin_id->findByToken('api_token', $token);
            $this->assertSame($expect_user, $user->raw());
        }

        $user = $this->provider_exclude_resigned->findByToken('api_token', 'token_1');
        $this->assertSame(1, $user->id);

        $user = $this->provider_exclude_resigned->findByToken('api_token', 'token_2');
        $this->assertSame(2, $user->id);

        $user = $this->provider_exclude_resigned->findByToken('api_token', 'token_3');
        $this->assertNull($user);


        $precondition = function ($user) { return $user['role'] === 'admin'; };
        $user = $this->provider->findByToken('api_token', 'token_1', $precondition);
        $this->assertSame(1, $user->id);

        $user = $this->provider->findByToken('api_token', 'token_2', $precondition);
        $this->assertNull($user);

        $user = $this->provider->findByToken('api_token', 'token_3', $precondition);
        $this->assertNull($user);
    }

    public function test_findByCredentials()
    {
        $user = $this->provider->findByCredentials(null, null);
        $this->assertNull($user);

        $user = $this->provider->findByCredentials('invalid_signin_id', 'invalid_password');
        $this->assertNull($user);

        foreach ($this->users as $expect_user) {
            $signin_id = $expect_user['email'];
            $passowd   = Strings::latrim($signin_id, '@');

            $user = $this->provider->findByCredentials($signin_id, $passowd);
            $this->assertSame($expect_user, $user->raw());

            $user = $this->provider->findByCredentials($signin_id, 'invalid_password');
            $this->assertNull($user);

            $user = $this->provider->findByCredentials('invalid_signin_id', $passowd);
            $this->assertNull($user);
        }

        foreach ($this->users as $expect_user) {
            $signin_id = $expect_user['signin_id'];
            $passowd   = $signin_id;

            $user = $this->provider_by_signin_id->findByCredentials($signin_id, $passowd);
            $this->assertSame($expect_user, $user->raw());

            $user = $this->provider_by_signin_id->findByCredentials($signin_id, 'invalid_password');
            $this->assertNull($user);

            $user = $this->provider_by_signin_id->findByCredentials('invalid_signin_id', $passowd);
            $this->assertNull($user);
        }

        $user = $this->provider_exclude_resigned->findByCredentials('admin@rebet.local', 'admin');
        $this->assertSame(1, $user->id);

        $user = $this->provider_exclude_resigned->findByCredentials('user@rebet.local', 'user');
        $this->assertSame(2, $user->id);

        $user = $this->provider_exclude_resigned->findByCredentials('user.resignd@rebet.local', 'user.resignd');
        $this->assertNull($user);


        $precondition = function ($user) { return $user['role'] === 'admin'; };
        $user = $this->provider->findByCredentials('admin@rebet.local', 'admin', $precondition);
        $this->assertSame(1, $user->id);

        $user = $this->provider->findByCredentials('user@rebet.local', 'user', $precondition);
        $this->assertNull($user);

        $user = $this->provider->findByCredentials('user.resignd@rebet.local', 'user.resignd', $precondition);
        $this->assertNull($user);
    }

    public function test_rehashPassword()
    {
        $user         = $this->provider->findById(1);
        $old_password = $user->password;

        // rehashPassword() is not supported.
        $this->provider->rehashPassword(1, Password::hash('new'));

        $user         = $this->provider->findById(1);
        $new_password = $user->password;

        $this->assertSame($old_password, $new_password);
    }

    public function test_supportRememberToken()
    {
        // supportRememberToken() is not supported.
        $this->assertFalse($this->provider->supportRememberToken());
    }

    public function test_findByRememberToken()
    {
        // findByRememberToken() is not supported.
        $this->assertNull($this->provider->findByRememberToken(null));
        $this->assertNull($this->provider->findByRememberToken('remember_token'));
    }

    public function test_issuingRememberToken()
    {
        // issuingRememberToken() is not supported.
        $this->assertNull($this->provider->issuingRememberToken(1, 30));
    }

    public function test_removeRememberToken()
    {
        // removeRememberToken() is not supported.
        $this->provider->removeRememberToken('remember_token');
        $this->assertTrue(true);
    }

    public function test_authenticator()
    {
        $this->assertNull($this->provider->authenticator());
        $this->assertInstanceOf(ArrayProvider::class, $this->provider->authenticator('web'));
        $this->assertSame('web', $this->provider->authenticator());
    }
}
