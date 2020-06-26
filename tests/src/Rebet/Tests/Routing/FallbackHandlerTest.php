<?php
namespace Rebet\Tests\Routing;

use Rebet\Auth\Exception\AuthenticateException;
use Rebet\Config\Config;
use Rebet\Application\App;
use Rebet\Http\Exception\FallbackRedirectException;
use Rebet\Http\Exception\HttpException;
use Rebet\Http\Exception\TokenMismatchException;
use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\Http\Response\BasicResponse;
use Rebet\Http\Response\ProblemResponse;
use Rebet\Http\Response\RedirectResponse;
use Rebet\Routing\Exception\RouteNotFoundException;
use Rebet\Routing\FallbackHandler;
use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Blade\Blade;
use Rebet\View\Engine\Twig\Twig;
use Rebet\View\View;

class FallbackHandlerTest extends RebetTestCase
{
    /** @var FallbackHandler */
    public $handler;

    public function setUp()
    {
        parent::setUp();
        $this->vfs([
            'cache' => [],
        ]);
        Config::application([
            View::class => [
                'engine' => Blade::class,
            ],
            Blade::class => [
                'view_path'  => [App::structure()->views('/blade')],
                'cache_path' => 'vfs://root/cache',
            ],
        ]);

        $this->handler             = new class extends FallbackHandler {
            public $reported_count = 0;

            protected function report(Request $request, Response $response, \Throwable $e) : void
            {
                $this->reported_count++;
            }
        };
    }

    public function test_handle()
    {
        $reported_count = 0;
        $this->assertSame($reported_count++, $this->handler->reported_count);

        $request  = $this->createJsonRequestMock('/');
        $response = $this->handler->handle($request, new \Exception('Detail message'));
        $this->assertSame($reported_count++, $this->handler->reported_count);
        $this->assertInstanceOf(ProblemResponse::class, $response);
        $this->assertSame([
            'status' => 500,
            'title'  => 'Internal Server Error',
            'type'   => 'about:blank',
            'detail' => 'Detail message',
        ], $response->getProblem());

        $request  = $this->createJsonRequestMock('/');
        $response = $this->handler->handle($request, RouteNotFoundException::by('Detail message'));
        $this->assertSame($reported_count++, $this->handler->reported_count);
        $this->assertInstanceOf(ProblemResponse::class, $response);
        $this->assertSame([
            'status' => 404,
            'title'  => '指定のページが見つかりません',
            'type'   => 'about:blank',
            'detail' => 'ご指定のページは見つかりませんでした。ご指定のURLが間違っているか、既にページが削除／移動された可能性があります。',
        ], $response->getProblem());


        $request  = $this->createRequestMock('/');
        $response = $this->handler->handle($request, FallbackRedirectException::by('Detail message')->to('/'));
        $this->assertSame($reported_count++, $this->handler->reported_count);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertContains('/', $response->getTargetUrl());

        $request  = $this->createRequestMock('/');
        $response = $this->handler->handle($request, HttpException::by(403));
        $this->assertSame($reported_count++, $this->handler->reported_count);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertContains('<h2 class="title"><span class="status">403</span>Forbidden</h2>', $response->getContent());

        $request  = $this->createRequestMock('/');
        $response = $this->handler->handle($request, HttpException::by(403)->title('Custom Title'));
        $this->assertSame($reported_count++, $this->handler->reported_count);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertContains('<h2 class="title">Custom Title</h2>', $response->getContent());

        $request  = $this->createRequestMock('/');
        $response = $this->handler->handle($request, AuthenticateException::by('Detail message'));
        $this->assertSame($reported_count++, $this->handler->reported_count);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertContains('<h2 class="title"><span class="status">403</span>Forbidden</h2>', $response->getContent());

        $request  = $this->createRequestMock('/');
        $response = $this->handler->handle($request, RouteNotFoundException::by('Detail message'));
        $this->assertSame($reported_count++, $this->handler->reported_count);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertContains('<h2 class="title">指定のページが見つかりません</h2>', $response->getContent());

        $request  = $this->createRequestMock('/');
        $response = $this->handler->handle($request, TokenMismatchException::by('Detail message'));
        $this->assertSame($reported_count++, $this->handler->reported_count);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertContains('<h2 class="title">指定のページが見つかりません</h2>', $response->getContent());

        $request  = $this->createRequestMock('/');
        $response = $this->handler->handle($request, HttpException::by(400)->title('Use fallbacks custom view template'));
        $this->assertSame($reported_count++, $this->handler->reported_count);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertContains('400 Bad Request Custom Error View Template', $response->getContent());
        $this->assertContains('Use fallbacks custom view template', $response->getContent());

        $request  = $this->createRequestMock('/');
        $response = $this->handler->handle($request, new \Exception());
        $this->assertSame($reported_count++, $this->handler->reported_count);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertContains('<h2 class="title"><span class="status">500</span>Internal Server Error</h2>', $response->getContent());

        Config::application([
            View::class => [
                'engine' => Twig::class,
            ],
            Twig::class => [
                'template_dir' => [App::structure()->views('/twig')],
                'options'      => [
                    // 'cache' => 'vfs://root/cache',
                ],
            ],
        ]);

        $request  = $this->createRequestMock('/');
        $response = $this->handler->handle($request, new \Exception());
        $this->assertSame($reported_count++, $this->handler->reported_count);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertContains('Default Custom Error View Template (Only Twig)', $response->getContent());
        $this->assertContains('title     : Internal Server Error', $response->getContent());
    }

    public function test___invoke()
    {
        $request  = $this->createRequestMock('/');
        $response = $this->handler->__invoke($request, new \Exception());
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertContains('<h2 class="title"><span class="status">500</span>Internal Server Error</h2>', $response->getContent());
    }
}
