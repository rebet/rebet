<?php
namespace Rebet\Tests;

use PHPUnit\Framework\TestCase;


use Rebet\Common\Securities;
use Rebet\Common\System;
use Rebet\Config\Config;
use Rebet\Enum\Enum;
use Rebet\Event\Event;
use Rebet\Foundation\App;
use Rebet\Http\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Rebet用の基底テストケースクラス。
 *
 * テストの手間を軽減するための各種ヘルパーメソッドを定義します。
 */
abstract class RebetTestCase extends TestCase
{
    private static $start_at;
    
    public function setUp()
    {
        System::initMock();
        Config::clear();
        App::initFrameworkConfig();
        App::setRoot(__DIR__.'/../../');
        Config::application([
            App::class => [
                'timezone'  => 'UTC',
                'locale'    => 'ja',
                'resources' => [
                    'i18n' => App::path('/resources/i18n'),
                ],
            ],
            Session::class => [
                'storage' => MockArraySessionStorage::class,
            ],
        ]);
        Enum::clear();
        Event::clear();
        StderrCapture::clear();
    }
    
    public static function setUpBeforeClass()
    {
        self::$start_at = microtime(true);
    }
    
    public static function tearDownAfterClass()
    {
        if (in_array('--debug', $_SERVER['argv'], true)) {
            $spend = (microtime(true) - self::$start_at);
            printf(" ... Time: %f [ms]\n", $spend * 1000);
        }
    }
    
    protected function assertSameStderr($expects, callable $test)
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        StderrCapture::clearStart();
        $test();
        $actual = StderrCapture::stopGetClear();
        foreach ($expects as $expect) {
            $this->assertSame($expect, $actual);
        }
    }

    protected function assertContainsStderr($expects, callable $test)
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        StderrCapture::clearStart();
        $test();
        $actual = StderrCapture::stopGetClear();
        foreach ($expects as $expect) {
            $this->assertContains($expect, $actual);
        }
    }
    
    protected function assertRegExpStderr($expects, callable $test)
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        StderrCapture::clearStart();
        $test();
        $actual = StderrCapture::stopGetClear();
        foreach ($expects as $expect) {
            $this->assertRegExp($expect, $actual);
        }
    }

    protected function assertSameOutbuffer($expects, callable $test)
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        \ob_start();
        $test();
        $actual = \ob_get_clean();
        foreach ($expects as $expect) {
            $this->assertSame($expect, $actual);
        }
    }

    protected function assertContainsOutbuffer($expects, callable $test)
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        \ob_start();
        $test();
        $actual = \ob_get_clean();
        foreach ($expects as $expect) {
            $this->assertContains($expect, $actual);
        }
    }
    
    protected function assertRegExpOutbuufer($expects, callable $test)
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        \ob_start();
        $test();
        $actual = \ob_get_clean();
        foreach ($expects as $expect) {
            $this->assertRegExp($expect, $actual);
        }
    }
    
    protected function _randomCode(int $min_length, ?int $max_length = null, string $chars = "1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890") : string
    {
        if ($max_length == null) {
            $max_length = $min_length;
        }
        return Securities::randomCode(mt_rand($min_length, $max_length), $chars);
    }
}
