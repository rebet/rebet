<?php
namespace Rebet\Tests\Common;

use Rebet\Tests\RebetTestCase;
use Rebet\Log\Plugin\WebDisplayPlugin;

use Rebet\Common\System;
use Rebet\Config\Config;
use Rebet\Config\App;
use Rebet\DateTime\DateTime;
use Rebet\Log\LogLevel;
use Rebet\Log\Handler\StderrHandler;

class WebDisplayPluginTest extends RebetTestCase {

    private $now;
    private $handler;
    private $plugin;

    public function setUp() {
        System::mock_init();
        Config::clear();
        App::setTimezone('UTC');
        DateTime::setTestNow('2010-10-20 10:20:30.040050');

        $this->now     = DateTime::now();
        $this->handler = StderrHandler::create();
        $this->plugin  = WebDisplayPlugin::create();
    }

    public function test_create() {
        $this->assertInstanceOf(WebDisplayPlugin::class, WebDisplayPlugin::create());
    }

    public function test_prehook() {
        $message = 'This is test';
        $context = [];
        $error   = null;
        $extra   = [];

        $this->assertTrue($this->plugin->prehook($this->handler, $this->now, LogLevel::ERROR(), $message, $context, $error, $extra));
    }

    public function test_posthook() {
        $extra   = [];

        \ob_start();
        $this->plugin->posthook($this->handler, $this->now, LogLevel::ERROR(), 'This is test', $extra);
        $this->plugin->shutdown();
        $html = \ob_get_contents();
        $this->assertContains('This&nbsp;is&nbsp;test', $html);
        \ob_end_clean();

        \ob_start();
        $this->plugin->posthook($this->handler, $this->now, LogLevel::ERROR(), 'This is test 1', $extra);
        $this->plugin->posthook($this->handler, $this->now, LogLevel::ERROR(), 'This is test 2', $extra);
        $this->plugin->shutdown();
        $html = \ob_get_contents();
        $this->assertContains('This&nbsp;is&nbsp;test&nbsp;1', $html);
        $this->assertContains('This&nbsp;is&nbsp;test&nbsp;2', $html);
        \ob_end_clean();
    }

    public function test_shutdown() {
        $extra   = [];

        \ob_start();
        $this->plugin->posthook($this->handler, $this->now, LogLevel::ERROR(), 'This is test', $extra);
        $this->plugin->shutdown();
        $html = \ob_get_contents();
        $this->assertContains('This&nbsp;is&nbsp;test', $html);
        \ob_end_clean();

        \ob_start();
        System::header('Content-Type: text/html; charset=UTF-8');
        $this->plugin->posthook($this->handler, $this->now, LogLevel::ERROR(), 'This is test', $extra);
        $this->plugin->shutdown();
        $html = \ob_get_contents();
        $this->assertContains('This&nbsp;is&nbsp;test', $html);
        System::mock_init();
        \ob_end_clean();

        \ob_start();
        System::header('Content-Type: text/json; charset=UTF-8');
        $this->plugin->posthook($this->handler, $this->now, LogLevel::ERROR(), 'This is test', $extra);
        $this->plugin->shutdown();
        $html = \ob_get_contents();
        $this->assertEmpty($html);
        System::mock_init();
        \ob_end_clean();
    }
}