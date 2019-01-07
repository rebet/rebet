<?php
namespace Rebet\Tests\Auth\Guard;

use Rebet\Auth\AuthUser;
use Rebet\Auth\Guard\TokenGuard;
use Rebet\Auth\Provider\ArrayProvider;
use Rebet\Http\Request;
use Rebet\Tests\RebetTestCase;

class TokenGuardTest extends RebetTestCase
{
    private $guard;
    private $provider;
    private $request;

    public function setUp()
    {
        parent::setUp();

        $authenticator = 'api';
        $this->guard   = new TokenGuard();
        $this->guard->authenticator($authenticator);

        $this->provider = new ArrayProvider(
            [
                ['id' => 1, 'api_token' => 'token_1'],
                ['id' => 2, 'api_token' => 'token_2'],
                ['id' => 3, 'api_token' => 'token_3'],
                ['id' => 4, 'api_token' => 'token_4'],
            ]
        );
        $this->provider->authenticator($authenticator);

        $this->request = Request::create('/');
    }

    public function test___construct()
    {
        $guard = new TokenGuard();
        $this->assertInstanceOf(TokenGuard::class, $guard);
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage TokenGuard not supported signin() function.
     */
    public function test_signin()
    {
        $this->guard->signin($this->request, AuthUser::guest());
        $this->fail('Never execute');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage TokenGuard not supported signout() function.
     */
    public function test_signout()
    {
        $response = $this->guard->signout($this->request, AuthUser::guest(), '/signouted');
        $this->fail('Never execute');
    }

    public function test_authenticate()
    {
        $user = $this->guard->authenticate($this->request, $this->provider);
        $this->assertTrue($user->isGuest());

        $this->request->headers->set('PHP_AUTH_PW', 'token_1');
        $user = $this->guard->authenticate($this->request, $this->provider);
        $this->assertSame(1, $user->id);

        $this->request->headers->set('Authorization', 'Bearer token_2');
        $user = $this->guard->authenticate($this->request, $this->provider);
        $this->assertSame(2, $user->id);

        $this->request->query->set('api_token', 'token_3');
        $user = $this->guard->authenticate($this->request, $this->provider);
        $this->assertSame(3, $user->id);

        $this->request->request->set('api_token', 'token_4');
        $user = $this->guard->authenticate($this->request, $this->provider);
        $this->assertSame(4, $user->id);
    }
}
