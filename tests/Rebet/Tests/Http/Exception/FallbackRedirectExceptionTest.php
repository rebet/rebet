<?php
namespace Rebet\Tests\DateTime\Exception;

use Rebet\Common\Reflector;
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
        $e = FallbackRedirectException::by('test')->to('/redirect/path')->with(['name' => 'rebet'])->errors(['name' => ['failed']]);

        $responce = $e->redirect();
        $this->assertInstanceOf(RedirectResponse::class, $responce);
        $this->assertSame('/redirect/path', $responce->getTargetUrl());
        $session = Session::current();
        $this->assertSame(['name' => 'rebet'], $session->loadInheritData('input', '/redirect/path'));
        $this->assertSame(['name' => ['failed']], $session->loadInheritData('errors', '/redirect/path'));
    }

    public function test_problem()
    {
        $e = FallbackRedirectException::by('test')->to('/redirect/path')->with(['name' => 'rebet'])->errors(['name' => ['failed']]);

        $responce = $e->problem();
        $this->assertInstanceOf(ProblemResponse::class, $responce);
        $this->assertSame(['name' => 'rebet'], $responce->getProblem('input'));
        $this->assertSame(['name' => ['failed']], $responce->getProblem('errors'));
    }
}
