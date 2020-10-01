<?php
namespace Rebet\Tools\DateTime;

/**
 * Date Class
 *
 * Note: Time will be truncated in this class.
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
    public function modify($modify)
    {
        $date = parent::modify($modify);
        return $date->format('H:i:s.u') === '00:00:00.000000' ? $date : $date->startsOfDay() ;
    }

    /**
     * {@inheritDoc}
     */
    public function setTime($hour, $minute, $second = null, $microseconds = null)
    {
        return parent::setTime($hour, $minute, $second, $microseconds)->startsOfDay();
    }

    /**
     * {@inheritDoc}
     */
    public function setTimestamp($unixtimestamp)
    {
        return parent::setTimestamp($unixtimestamp)->startsOfDay();
    }

    /**
     * {@inheritDoc}
     */
    public function setTimezone($timezone)
    {
        return parent::setTimezone($timezone)->startsOfDay();
    }

    /**
     * {@inheritDoc}
     */
    public function add($interval)
    {
        return parent::add($interval)->startsOfDay();
    }

    /**
     * {@inheritDoc}
     */
    public function sub($interval)
    {
        return parent::sub($interval)->startsOfDay();
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
