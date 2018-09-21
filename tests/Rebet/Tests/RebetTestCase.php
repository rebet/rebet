<?php
namespace Rebet\Tests;

use PHPUnit\Framework\TestCase;

use Rebet\Tests\StderrCapture;

use Rebet\Common\ArrayUtil;
use Rebet\Common\SecurityUtil;

/**
 * Rebet用の基底テストケースクラス。
 *
 * テストの手間を軽減するための各種ヘルパーメソッドを定義します。
 */
abstract class RebetTestCase extends TestCase
{
    private static $START_AT;
    
    public static function setUpBeforeClass()
    {
        self::$START_AT = microtime(true);
    }
    
    public static function tearDownAfterClass()
    {
        if (in_array('--debug', $_SERVER['argv'], true)) {
            $spend = (microtime(true) - self::$START_AT);
            printf(" ... Time: %f [ms]\n", $spend * 1000);
        }
    }
    
    protected function assertSameStderr($expect, callable $test)
    {
        StderrCapture::clear();
        StderrCapture::start();
        $test();
        StderrCapture::end();
        $this->assertSame($expect, StderrCapture::$STDERR);
    }

    protected function _remap(?array $list, $key_field, $value_field) : array
    {
        return ArrayUtil::remap($list, $key_field, $value_field);
    }

    protected function _randomCode(int $min_length, ?int $max_length = null, string $chars = "1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890") : string
    {
        if ($max_length == null) {
            $max_length = $min_length;
        }
        return SecurityUtil::randomCode(mt_rand($min_length, $max_length), $chars);
    }
}
