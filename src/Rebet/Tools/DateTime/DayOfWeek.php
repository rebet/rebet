<?php

declare(strict_types=1);

namespace Rebet\Tools\DateTime;

use Rebet\Tools\Enum\Enumerable;
use Rebet\Tools\Enum\Enumeration;
use Rebet\Tools\Enum\Meta;
use Rebet\Tools\Enum\Translatable;
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
 * The metadata (label, label_short and label_min) are translated by the Translatable attribute,
 * so that the method call of them (e.g. label_short() or label_short('en') with the locale) returns the translated one.
 * Use original() (e.g. original('label_short')) if you need the English name as it is.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 *
 * @method string label_short(string|null $locale = null) The translated short name (e.g. Sun).
 * @method string label_min(string|null $locale = null)   The translated min name (e.g. Su).
 *
 * @implements Enumeration<DayOfWeek, int>
 */
#[Translatable(['label', 'label_short', 'label_min'], group: 'datetime')]
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
