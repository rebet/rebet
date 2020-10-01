<?php
namespace Rebet\Tests\Tools\DateTime\Exception;

use Rebet\Tools\Reflection\Reflector;
use Rebet\Http\Exception\FallbackRedirectException;
use Rebet\Http\Response\ProblemResponse;
use Rebet\Http\Response\RedirectResponse;
use Rebet\Http\Session\Session;
use Rebet\Tests\RebetTestCase;

class FallbackRedirectExceptionTest extends RebetTestCase
{
    public function test___construct()
    {
        $e = new FallbackRedirectException('test');
        $this->assertInstanceOf(FallbackRedirectException::class, $e);
    }

    public function test_to()
    {
        $e = new FallbackRedirectException('test');
        $this->assertInstanceOf(FallbackRedirectException::class, $e->to('/redirect/path'));
        $this->assertSame('/redirect/path', Reflector::get($e, 'fallback', null, true));
    }

    public function test_with()
    {
        $e = new FallbackRedirectException('test');
        $this->assertInstanceOf(FallbackRedirectException::class, $e->with(['name' => 'rebet']));
        $this->assertSame(['name' => 'rebet'], Reflector::get($e, 'input', null, true));
    }

    public function test_errors()
    {
        $e = new FallbackRedirectException('test');
        $this->assertInstanceOf(FallbackRedirectException::class, $e->errors(['name' => ['failed']]));
        $this->assertSame(['name' => ['failed']], Reflector::get($e, 'errors', null, true));
    }

    public function test_redirect()
    {
        $session = new Session();
        $session->start();
        $e = (new FallbackRedirectException('test'))->to('/redirect/path')->with(['name' => 'rebet'])->errors(['name' => ['failed']]);

        $response = $e->redirect();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/redirect/path', $response->getTargetUrl());
        $this->assertSame(['name' => 'rebet'], $session->loadInheritData('input', '/redirect/path'));
        $this->assertSame(['name' => ['failed']], $session->loadInheritData('errors', '/redirect/path'));
    }

    public function test_problem()
    {
        $e = (new FallbackRedirectException('test'))->to('/redirect/path')->with(['name' => 'rebet'])->errors(['name' => ['failed']]);

        $response = $e->problem();
        $this->assertInstanceOf(ProblemResponse::class, $response);
        $this->assertSame(['name' => 'rebet'], $response->getProblem('input'));
        $this->assertSame(['name' => ['failed']], $response->getProblem('errors'));
    }
}
