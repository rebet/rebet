<?php
namespace Rebet\DateTime;

use Rebet\Tools\Path;
use Rebet\Enum\Enum;
use Rebet\Translation\FileDictionary;
use Rebet\Translation\Translator;

/**
 * DayOfWeek Enum Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class DayOfWeek extends Enum
{
    protected const TRANSLATION_GROUP = 'datetime';

    const SUNDAY    = [0, 'Sunday'   , 'Sun', 'Su'];
    const MONDAY    = [1, 'Monday'   , 'Mon', 'Mo'];
    const TUESDAY   = [2, 'Tuesday'  , 'Tue', 'Tu'];
    const WEDNESDAY = [3, 'Wednesday', 'Wed', 'We'];
    const THURSDAY  = [4, 'Thursday' , 'Thu', 'Th'];
    const FRIDAY    = [5, 'Friday'   , 'Fri', 'Fr'];
    const SATURDAY  = [6, 'Saturday' , 'Sat', 'Sa'];

    /**
     * @var string of short day of week label
     */
    public $label_short;

    /**
     * @var string of min day of week label
     */
    public $label_min;

    /**
     * Create a DayOfWeek.
     *
     * @param integer $value
     * @param string $label
     * @param string $label_short
     * @param string $label_min
     */
    protected function __construct(int $value, string $label, string $label_short, string $label_min)
    {
        parent::__construct($value, $label);
        $this->label_short = $label_short;
        $this->label_min   = $label_min;
    }

    /**
     * It checks the day of week is weekend (Sunday or Saturday)
     *
     * @return boolean
     */
    public function isWeekends() : bool
    {
        return $this->in(static::SUNDAY(), static::SATURDAY());
    }

    /**
     * It checks the day of week is weekday (Not weekend)
     *
     * @return boolean
     */
    public function isWeekdays() : bool
    {
        return !$this->isWeekends();
    }

    /**
     * It checks the day of week is Sunday.
     *
     * @return boolean
     */
    public function isSunday() : bool
    {
        return $this->equals(static::SUNDAY()) ;
    }

    /**
     * It checks the day of week is Monday.
     *
     * @return boolean
     */
    public function isMonday() : bool
    {
        return $this->equals(static::MONDAY()) ;
    }

    /**
     * It checks the day of week is Tuesday.
     *
     * @return boolean
     */
    public function isTuesday() : bool
    {
        return $this->equals(static::TUESDAY()) ;
    }

    /**
     * It checks the day of week is Wednesday.
     *
     * @return boolean
     */
    public function isWednesday() : bool
    {
        return $this->equals(static::WEDNESDAY()) ;
    }

    /**
     * It checks the day of week is Thursday.
     *
     * @return boolean
     */
    public function isThursday() : bool
    {
        return $this->equals(static::THURSDAY()) ;
    }

    /**
     * It checks the day of week is Friday.
     *
     * @return boolean
     */
    public function isFriday() : bool
    {
        return $this->equals(static::FRIDAY()) ;
    }

    /**
     * It checks the day of week is Saturday.
     *
     * @return boolean
     */
    public function isSaturday() : bool
    {
        return $this->equals(static::SATURDAY()) ;
    }
}

// ---------------------------------------------------------
// Add library default translation resource
// ---------------------------------------------------------
Translator::addResourceTo(FileDictionary::class, Path::normalize(__DIR__.'/i18n'), 'datetime');
