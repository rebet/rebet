<?php

declare(strict_types=1);

namespace Rebet\Tools\DateTime;

use Rebet\Tools\Enum\Enumerable;
use Rebet\Tools\Enum\Enumeration;
use Rebet\Tools\Enum\Meta;
use Rebet\Tools\Translation\FileDictionary;
use Rebet\Tools\Translation\Translator;
use Rebet\Tools\Utility\Path;

/**
 * DayOfWeek Enum
 *
 * The value is the same as the 'w' format of date() (Sunday is 0 and Saturday is 6).
 * The labels of the metadata are the English names, and they are translated by the 'datetime' translation group.
 *
 * - label       : The full name (e.g. Sunday)
 * - label_short : The short name (e.g. Sun)
 * - label_min   : The min name (e.g. Su)
 *
 * The metadata (label_short and label_min) can be read by the method call (not translated),
 * and translate() returns the translated one. (e.g. translate('label_short'))
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 *
 * @method string label_short() The short name (e.g. Sun), which is not translated.
 * @method string label_min()   The min name (e.g. Su), which is not translated.
 *
 * @implements Enumeration<DayOfWeek, int>
 */
enum DayOfWeek: int implements Enumeration
{
    /** @use Enumerable<DayOfWeek, int> */
    use Enumerable;

    #[Meta(label: 'Sunday', label_short: 'Sun', label_min: 'Su')]
    case Sunday = 0;

    #[Meta(label: 'Monday', label_short: 'Mon', label_min: 'Mo')]
    case Monday = 1;

    #[Meta(label: 'Tuesday', label_short: 'Tue', label_min: 'Tu')]
    case Tuesday = 2;

    #[Meta(label: 'Wednesday', label_short: 'Wed', label_min: 'We')]
    case Wednesday = 3;

    #[Meta(label: 'Thursday', label_short: 'Thu', label_min: 'Th')]
    case Thursday = 4;

    #[Meta(label: 'Friday', label_short: 'Fri', label_min: 'Fr')]
    case Friday = 5;

    #[Meta(label: 'Saturday', label_short: 'Sat', label_min: 'Sa')]
    case Saturday = 6;

    /**
     * The translation group of DayOfWeek.
     *
     * @return string
     */
    protected static function translationGroup(): string
    {
        return 'datetime';
    }

    /**
     * It checks the day of week is weekend (Sunday or Saturday)
     *
     * @return boolean
     */
    public function isWeekends(): bool
    {
        return $this === self::Sunday || $this === self::Saturday;
    }

    /**
     * It checks the day of week is weekday (Not weekend)
     *
     * @return boolean
     */
    public function isWeekdays(): bool
    {
        return !$this->isWeekends();
    }

    /**
     * It checks the day of week is Sunday.
     *
     * @return boolean
     */
    public function isSunday(): bool
    {
        return $this === self::Sunday;
    }

    /**
     * It checks the day of week is Monday.
     *
     * @return boolean
     */
    public function isMonday(): bool
    {
        return $this === self::Monday;
    }

    /**
     * It checks the day of week is Tuesday.
     *
     * @return boolean
     */
    public function isTuesday(): bool
    {
        return $this === self::Tuesday;
    }

    /**
     * It checks the day of week is Wednesday.
     *
     * @return boolean
     */
    public function isWednesday(): bool
    {
        return $this === self::Wednesday;
    }

    /**
     * It checks the day of week is Thursday.
     *
     * @return boolean
     */
    public function isThursday(): bool
    {
        return $this === self::Thursday;
    }

    /**
     * It checks the day of week is Friday.
     *
     * @return boolean
     */
    public function isFriday(): bool
    {
        return $this === self::Friday;
    }

    /**
     * It checks the day of week is Saturday.
     *
     * @return boolean
     */
    public function isSaturday(): bool
    {
        return $this === self::Saturday;
    }
}

// ---------------------------------------------------------
// Add library default translation resource
// ---------------------------------------------------------
Translator::addResourceTo(FileDictionary::class, Path::normalize(__DIR__ . '/i18n'), 'datetime');
