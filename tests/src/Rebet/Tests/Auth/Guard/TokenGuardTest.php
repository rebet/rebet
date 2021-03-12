<?php
namespace Rebet\Tests\Auth\Guard;

use Rebet\Auth\Auth;
use Rebet\Auth\Guard\TokenGuard;
use Rebet\Http\Responder;
use Rebet\Http\Response\ProblemResponse;
use Rebet\Tests\RebetTestCase;

class TokenGuardTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(TokenGuard::class, new TokenGuard('user'));
    }

    public function test_authenticate()
    {
        $guard = (new TokenGuard('user', 'api_token', $request = $this->createRequestMock('/user/mypage', 'user')))->name('web');

        $fallback = $guard->authenticate();
        $this->assertNotNull($fallback);
        $this->assertInstanceOf(ProblemResponse::class, $fallback);
        $this->assertEquals(Responder::problem(403)->getProblem(), $fallback->getProblem());
        $this->assertTrue($guard->user()->isGuest());

        $request->headers->set('PHP_AUTH_PW', 'token_1');
        $fallback = $guard->authenticate();
        $this->assertNotNull($fallback);
        $this->assertInstanceOf(ProblemResponse::class, $fallback);
        $this->assertEquals(Responder::problem(403)->getProblem(), $fallback->getProblem());
        $this->assertSame(1, $guard->user()->id);
        $this->assertSame('admin', $guard->user()->role);
        $this->assertEquals(Auth::provider('user')->findById(1)->raw(), $guard->user()->raw());

        $request->headers->set('Authorization', 'Bearer token_2');
        $fallback = $guard->authenticate();
        $this->assertNull($fallback);
        $this->assertSame(2, $guard->user()->id);
        $this->assertSame('user', $guard->user()->role);
        $this->assertEquals(Auth::provider('user')->findById(2)->raw(), $guard->user()->raw());

        $request->query->set('api_token', 'token_3');
        $fallback = $guard->authenticate();
        $this->assertNotNull($fallback);
        $this->assertInstanceOf(ProblemResponse::class, $fallback);
        $this->assertEquals(Responder::problem(403)->getProblem(), $fallback->getProblem());
        $this->assertTrue($guard->user()->isGuest());

        $request->request->set('api_token', 'token_4');
        $fallback = $guard->authenticate();
        $this->assertNull($fallback);
        $this->assertSame(4, $guard->user()->id);
        $this->assertSame('user', $guard->user()->role);
        $this->assertEquals(Auth::provider('user')->findById(4)->raw(), $guard->user()->raw());
    }
}
