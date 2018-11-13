<?php
namespace Rebet\Tests\Log\Middleware;

use Rebet\Common\System;
use Rebet\DateTime\DateTime;

use Rebet\Log\LogContext;
use Rebet\Log\LogLevel;
use Rebet\Log\Middleware\WebDisplay;
use Rebet\Tests\RebetTestCase;

class WebDisplayTest extends RebetTestCase
{
    private $context;
    private $middleware;
    private $echoback;

    public function setUp()
    {
        parent::setUp();
        DateTime::setTestNow('2010-10-20 10:20:30.040050');

        $this->context    = new LogContext(DateTime::now(), LogLevel::TRACE(), null);
        $this->middleware = new WebDisplay();
        $this->echoback   = function (LogContext $context) {
            return $context->message;
        };
    }

    public function test_construct()
    {
        $this->assertInstanceOf(WebDisplay::class, new WebDisplay());
    }

    public function test_handle()
    {
        $this->context->level   = LogLevel::ERROR();
        $this->context->message = 'This is test';

        $this->assertContainsOutbuffer(
            'This&nbsp;is&nbsp;test',
            function () {
                $this->middleware->handle($this->context, $this->echoback);
                $this->middleware->terminate();
            }
        );

        $this->assertContainsOutbuffer(
            [
                'This&nbsp;is&nbsp;test&nbsp;1',
                'This&nbsp;is&nbsp;test&nbsp;2',
            ],
            function () {
                $this->context->level   = LogLevel::ERROR();
                $this->context->message = 'This is test 1';
                $this->middleware->handle($this->context, $this->echoback);
                $this->context->level   = LogLevel::ERROR();
                $this->context->message = 'This is test 2';
                $this->middleware->handle($this->context, $this->echoback);
                $this->middleware->terminate();
            }
        );
    }

    public function test_terminate()
    {
        $this->context->level   = LogLevel::ERROR();
        $this->context->message = 'This is test';

        $this->assertContainsOutbuffer(
            'This&nbsp;is&nbsp;test',
            function () {
                $this->middleware->handle($this->context, $this->echoback);
                $this->middleware->terminate();
            }
        );

        System::header('Content-Type: text/html; charset=UTF-8');
        $this->assertContainsOutbuffer(
            'This&nbsp;is&nbsp;test',
            function () {
                $this->middleware->handle($this->context, $this->echoback);
                $this->middleware->terminate();
            }
        );
        System::initMock();
        
        System::header('Content-Type: text/json; charset=UTF-8');
        $this->assertSameOutbuffer(
            '',
            function () {
                $this->middleware->handle($this->context, $this->echoback);
                $this->middleware->terminate();
            }
        );
        System::initMock();
    }
}
