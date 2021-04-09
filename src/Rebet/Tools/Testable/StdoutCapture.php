<?php
namespace Rebet\Tools\Testable;

/**
 * Stdout Capture Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class StdoutCapture extends AbstractCapture
{
    /**
     * {@inheritDoc}
     */
    public static function start() : void
    {
        \ob_start();
        \ob_clean();
        parent::start();
    }

    /**
     * {@inheritDoc}
     */
    public static function stop() : string
    {
        return parent::stop().\ob_get_clean();
    }

    /**
     * {@inheritDoc}
     */
    public function filter($in, $out, &$consumed, $closing)
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            if (self::$is_capture) {
                self::$message .= \ob_get_clean().$bucket->data;
                \ob_start();
            } else {
                $consumed += $bucket->datalen;
                stream_bucket_append($out, $bucket);
            }
        }
        return PSFS_PASS_ON;
    }
}
StdoutCapture::init(STDOUT);
