<?php
namespace Rebet\Tests\Log\Middleware;

use Rebet\Tests\RebetTestCase;
use Rebet\Log\Middleware\WebDisplayMiddleware;

use Rebet\Common\System;
use Rebet\Config\App;
use Rebet\Config\Config;
use Rebet\DateTime\DateTime;
use Rebet\Log\LogLevel;
use Rebet\Log\LogContext;

class WebDisplayMiddlewareTest extends RebetTestCase
{
    private $context;
    private $middleware;
    private $echoback;

    public function setUp()
    {
        System::mock_init();
        Config::clear();
        App::setTimezone('UTC');
        DateTime::setTestNow('2010-10-20 10:20:30.040050');

        $this->context    = new LogContext(DateTime::now(), LogLevel::TRACE(), null);
        $this->middleware = new WebDisplayMiddleware();
        $this->echoback   = function (LogContext $context) {
            return $context->message;
        };
    }

    public function test_construct()
    {
        $this->assertInstanceOf(WebDisplayMiddleware::class, new WebDisplayMiddleware());
    }

    public function test_handle()
    {
        $this->context->level   = LogLevel::ERROR();
        $this->context->message = 'This is test';

        \ob_start();
        $this->middleware->handle($this->context, $this->echoback);
        $this->middleware->shutdown();
        $html = \ob_get_contents();
        $this->assertContains('This&nbsp;is&nbsp;test', $html);
        \ob_end_clean();

        \ob_start();
        $this->context->level   = LogLevel::ERROR();
        $this->context->message = 'This is test 1';
        $this->middleware->handle($this->context, $this->echoback);
        $this->context->level   = LogLevel::ERROR();
        $this->context->message = 'This is test 2';
        $this->middleware->handle($this->context, $this->echoback);
        $this->middleware->shutdown();
        $html = \ob_get_contents();
        $this->assertContains('This&nbsp;is&nbsp;test&nbsp;1', $html);
        $this->assertContains('This&nbsp;is&nbsp;test&nbsp;2', $html);
        \ob_end_clean();
    }

    public function test_shutdown()
    {
        $this->context->level   = LogLevel::ERROR();
        $this->context->message = 'This is test';

        \ob_start();
        $this->middleware->handle($this->context, $this->echoback);
        $this->middleware->shutdown();
        $html = \ob_get_contents();
        $this->assertContains('This&nbsp;is&nbsp;test', $html);
        \ob_end_clean();

        \ob_start();
        System::header('Content-Type: text/html; charset=UTF-8');
        $this->middleware->handle($this->context, $this->echoback);
        $this->middleware->shutdown();
        $html = \ob_get_contents();
        $this->assertContains('This&nbsp;is&nbsp;test', $html);
        System::mock_init();
        \ob_end_clean();

        \ob_start();
        System::header('Content-Type: text/json; charset=UTF-8');
        $this->middleware->handle($this->context, $this->echoback);
        $this->middleware->shutdown();
        $html = \ob_get_contents();
        $this->assertEmpty($html);
        System::mock_init();
        \ob_end_clean();
    }
}
