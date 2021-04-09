<?php
namespace Rebet\Tools\Testable;

/**
 * Abstract Capture Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class AbstractCapture extends \php_user_filter
{
    /**
     * output message is captured or not.
     *
     * @var bool
     */
    protected static $is_capture = false;

    /**
     * Captured output message.
     *
     * @var string
     */
    protected static $message = '';

    /**
     * Start output capture.
     *
     * @return void
     */
    public static function start() : void
    {
        self::$message    = '';
        self::$is_capture = true;
    }

    /**
     * Stop STDERR output capture and get captured text.
     *
     * @return string
     */
    public static function stop() : string
    {
        self::$is_capture = false;
        $captured         = self::$message;
        self::$message    = '';
        return $captured;
    }

    /**
     * Capture STDERR output via given process.
     *
     * @param \Closure $process
     * @return string
     */
    public static function via(\Closure $process) : string
    {
        static::start();
        $process();
        return static::stop();
    }

    /**
     * Stream filter for capture STDERR output message.
     *
     * @param resource $in
     * @param resource $out
     * @param mixed $consumed
     * @param mixed $closing
     * @return int
     */
    public function filter($in, $out, &$consumed, $closing)
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            if (self::$is_capture) {
                self::$message .= $bucket->data;
            } else {
                $consumed += $bucket->datalen;
                stream_bucket_append($out, $bucket);
            }
        }
        return PSFS_PASS_ON;
    }

    /**
     * Append Stderr capture filter to given resource.
     * PHP constant `STDERR` is defaultry captured, but you can not captured if you create new resource like `fopen('php://message', 'w');`.
     * In this case, you need to call StderrCapture::append() with created new resource.
     *
     * @param resource $resource
     * @return resource of given stderr as it is.
     */
    public static function append($resource)
    {
        stream_filter_append($resource, static::class);
        return $resource;
    }

    /**
     * Initialize the capture.
     * This method register stream filter for output capture and append filter to given $resource.
     *
     * @param resource $resource
     * @return void
     */
    public static function init($resource) : void
    {
        stream_filter_register(static::class, static::class) or die("Failed to register filter");
        static::append($resource);
    }
}
