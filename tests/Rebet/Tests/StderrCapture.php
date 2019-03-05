<?php
namespace Rebet\Tests;

/**
 * STDERR の出力内容をキャプチャするクラス。
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class StderrCapture extends \php_user_filter
{
    private static $is_capture = false;
    private static $stderr     = '';

    public static function start()
    {
        self::$is_capture = true;
    }

    public static function stop()
    {
        self::$is_capture = false;
    }

    public static function get()
    {
        return self::$stderr;
    }

    public static function clear()
    {
        self::$stderr = '';
    }

    public static function getClear()
    {
        $stderr = self::get();
        self::clear();
        return $stderr;
    }

    public static function clearStart()
    {
        self::clear();
        self::start();
    }

    public static function stopGet()
    {
        self::stop();
        return self::get();
    }

    public static function stopGetClear()
    {
        self::stop();
        $stderr = self::get();
        self::clear();
        return $stderr;
    }

    public function filter($in, $out, &$consumed, $closing)
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            if (self::$is_capture) {
                self::$stderr .= $bucket->data;
            } else {
                $consumed += $bucket->datalen;
                stream_bucket_append($out, $bucket);
            }
        }
        return PSFS_PASS_ON;
    }
}

stream_filter_register("stderr_capture", "Rebet\Tests\StderrCapture")
    or die("Failed to register filter");

stream_filter_append(STDERR, "stderr_capture");
