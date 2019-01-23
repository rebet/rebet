<?php
namespace Rebet\DateTime;

/**
 * Date Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Date extends DateTime
{
    public static function defaultConfig()
    {
        return static::shareConfigWith(parent::class, [
            'default_format' => 'Y-m-d',
        ]);
    }

    /**
     * Create the Date objects.
     *
     * @param string|\DateTimeInterface|int $time
     * @param string|\DateTimeZone $timezone (default: depend on configure)
     */
    public function __construct($time = 'today', $timezone = null)
    {
        $time = $time instanceof DateTime ? $time : new DateTime($time, $timezone) ;
        parent::__construct($time->startsOfDay(), $timezone);
    }

    /**
     * {@inheritDoc}
     */
    public function toDate() : Date
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function toDateTime() : DateTime
    {
        return new DateTime($this);
    }
}
