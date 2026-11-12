<?php

declare(strict_types=1);

namespace Rebet\Http;

use DeviceDetector\DeviceDetector;

/**
 * User Agent Class
 *
 * @phpstan-consistent-constructor
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
     * @param  string|DeviceDetector|UserAgent|null $ua
     * @return self|null
     */
    public static function valueOf($ua): self|null
    {
        $ua = match (true) {
            $ua === null          => null,
            $ua instanceof static => $ua,
            $ua instanceof DeviceDetector,
            is_string($ua)        => new static($ua), // @phpstan-ignore function.alreadyNarrowedType (guard for the value that does not follow the documented type)
            default               => null,
        };
        $ua?->parse();
        return $ua;
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
