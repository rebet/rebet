<?php
namespace Rebet\Http;

use DeviceDetector\DeviceDetector;

/**
 * User Agent Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class UserAgent extends DeviceDetector
{
    /**
     * Create UserAgent from given user agent text and parse it.
     *
     * @param string|DeviceDetector|UserAgent|null $ua
     * @return self|null
     */
    public static function valueOf($ua) : ?self
    {
        switch (true) {
            case $ua === null:
                return null;
            case $ua instanceof static:
                $ua->parse();
                return $ua;
            case $ua instanceof DeviceDetector: // Do not break.
            case is_string($ua):
                $ua = new static($ua);
                $ua->parse();
                return $ua;
        }
        return null;
    }

    /**
     * Create UserAgent device detector (without parse)
     *
     * @param string|DeviceDetector $ua
     */
    public function __construct($ua)
    {
        parent::__construct($ua instanceof DeviceDetector ? $ua->getUserAgent() : $ua);
    }

    /**
     * Convert UserAgent to string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getUserAgent();
    }
}
