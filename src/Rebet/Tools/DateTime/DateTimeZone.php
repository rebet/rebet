<?php
namespace Rebet\Tools\DateTime;

use Rebet\Tools\Reflection\Convertible;
use Rebet\Tools\Reflection\Reflector;

/**
 * Date Time Zone Class
 *
 * @todo Investigation as to whether singletonization or class itself should be deleted
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class DateTimeZone extends \DateTimeZone implements Convertible
{
    /**
     * Create DateTimeZone
     *
     * @param string|\DateTimeZone $timezone
     */
    public function __construct($timezone)
    {
        $timezone = $timezone instanceof \DateTimeZone ? $timezone->getName() : $timezone ;
        parent::__construct($timezone);
    }

    /**
     * {@inheritDoc}
     *
     * @see Reflector::convert()
     * @see Convertible
     *
     * @param string|\DateTimeZone $value
     * @return DateTimeZone
     */
    public static function valueOf($value) : DateTimeZone
    {
        return new DateTimeZone($value);
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return $this->getName();
    }

    /**
     * {@inheritDoc}
     *
     * @see Convertible
     *
     * @param string $type
     * @return void
     */
    public function convertTo(string $type)
    {
        if (Reflector::typeOf($this, $type)) {
            return $this;
        }
        switch ($type) {
            case 'string':
                return $this->__toString();
        }
        return null;
    }
}
