<?php

declare(strict_types=1);

namespace Rebet\Tools\DateTime;

use Override;

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
    /**
     * @return \Rebet\Tools\Config\ConfigPromise
     */
    #[Override]
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
     * @param string|\DateTimeZone          $timezone (default: depend on configure)
     */
    public function __construct($time = 'today', $timezone = null)
    {
        $time = $time instanceof DateTime ? $time : new DateTime($time, $timezone) ;
        parent::__construct($time->startsOfDay(), $timezone);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function modify(string $modify): static
    {
        $date = parent::modify($modify);
        return $date->format('H:i:s.u') === '00:00:00.000000' ? $date : $date->startsOfDay() ;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function setTime($hour, $minute, $second = 0, $microseconds = 0): static
    {
        return parent::setTime($hour, $minute, $second, $microseconds)->startsOfDay();
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function setTimestamp($unixtimestamp): static
    {
        return parent::setTimestamp($unixtimestamp)->startsOfDay();
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function setTimezone($timezone): static
    {
        return parent::setTimezone($timezone)->startsOfDay();
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function add($interval): static
    {
        return parent::add($interval)->startsOfDay();
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function sub($interval): static
    {
        return parent::sub($interval)->startsOfDay();
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function toDate(): Date
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function toDateTime(): DateTime
    {
        return new DateTime($this);
    }
}
