<?php
namespace Rebet\DateTime;

use Rebet\Common\Convertible;
use Rebet\Common\Exception\LogicException;
use Rebet\Common\Reflector;
use Rebet\Common\Strings;
use Rebet\Config\Config;
use Rebet\Config\Configurable;

/**
 * Date Time Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class DateTime extends \DateTimeImmutable implements \JsonSerializable, Convertible
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'default_format'             => 'Y-m-d H:i:s',
            'default_timezone'           => date_default_timezone_get() ?: 'UTC',
            'acceptable_datetime_format' => [
                'Y-m-d H:i:s',
                'Y/m/d H:i:s',
                'YmdHis',
                'Y-m-d H:i',
                'Y/m/d H:i',
                'YmdHi',
                'Y-m-d',
                'Y/m/d',
                'Ymd',
            ],
            'test_now'                   => null,
            'test_now_timezone'          => null,
            'test_now_format'            => ['Y#m#d H:i:s.u', 'Y#m#d H:i:s', 'Y#m#d H:i', 'Y#m#d'],
        ];
    }

    /**
     * Default format of this DateTime.
     *
     * @var string
     */
    protected $default_format;

    /**
     * Set the current date time for testing and mock this class.
     *
     * @param string $now
     * @param string $timezone (default: UTC)
     */
    public static function setTestNow(string $now, string $timezone = 'UTC') : void
    {
        self::setConfig(['test_now' => $now, 'test_now_timezone' => $timezone]);
    }

    /**
     * Get the current test date time
     *
     * @return string|null
     */
    public static function getTestNow() : ?string
    {
        return self::config('test_now', false);
    }

    /**
     * Get the time zone of the current test date time.
     *
     * @return string|null
     */
    public static function getTestNowTimezone() : ?string
    {
        return self::config('test_now_timezone', false);
    }

    /**
     * Delete the current test date time.
     */
    public static function removeTestNow() : void
    {
        self::setConfig(['test_now' => null, 'test_now_timezone' => null]);
    }

    /**
     * Converts the given value to DateTime.
     * This method is an I/F for Reflector::convert().
     *
     * For more detailed DateTime conversion please refe / use below.
     *
     * @see static::createDateTime()
     * @see static::analyzeDateTime()
     * @see static::__construct()
     *
     * @param string|\DateTimeInterface|int|null $from
     * @return DateTime|null
     */
    public static function valueOf($from) : ?DateTime
    {
        try {
            return static::createDateTime($from) ?? new static($from) ;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parses a objects representing DateTime.
     * This method is a convenience method which excludes date format information from analyzeDateTime() and returns only the DateTime.
     *
     * @see DateTime::analyzeDateTime()
     *
     * @param string|\DateTimeInterface|int|float|null $value
     * @param string|array $main_format for primary analyze (default: [])
     * @param string|\DateTimezone|null $timezone (default: depend on configure)
     * @return static|null
     */
    public static function createDateTime($value, $main_format = [], $timezone = null) : ?DateTime
    {
        [$date, ] = self::analyzeDateTime($value, $main_format, $timezone);
        return $date;
    }

    /**
     * Parses a objects representing DateTime.
     * The objects that can be analyzed are as follows.
     *
     *   \DateTimeInterface:
     *    Convert to DateTime from given \DateTimeInterface with new timezone if given
     *   int:
     *    Analyze by int as millisecond of Epoch time
     *   float:
     *    Analyze by float as microsecond of Epoch time
     *   string:
     * 　 1st. Analyze by formats that given $main_format
     * 　 2nd. Analyze by formats that the 'acceptable_date_format' config settings
     * 　 3rd. Analyze by format that the default format.
     *
     * Note:
     *  This method also returns the date format that succeeded in analysis.
     *  The default time zone is used for the time zone.
     *
     * @param string|\DateTimeInterface|int|float|null $value
     * @param string|array $main_format for primary analyze (default: [])
     * @param string|\DateTimezone|null $timezone (default: depend on configure)
     * @return array [DateTime|null, apply_format|null] or null
     */
    public static function analyzeDateTime($value, $main_format = [], $timezone = null) : array
    {
        if ($value === null || $value === '') {
            return [null, null];
        }
        if ($value instanceof \DateTimeInterface || is_int($value) || is_float($value)) {
            return [new static($value, $timezone), self::config('default_format')];
        }
        
        $formats   = ((array)$main_format) + self::config('acceptable_datetime_format');
        $formats[] = self::config('default_format');
        
        $date         = null;
        $apply_format = null;
        foreach ($formats as $format) {
            $date = self::tryToParseDateTime($value, "!{$format}", $timezone);
            if (!empty($date)) {
                $apply_format = $format;
                break;
            }
        }
        
        return [$date, $apply_format];
    }

    /**
     * Try to parse DateTime
     *
     * @param string $value
     * @param string $format
     * @param string|\DateTimezone|null $timezone (default: depend on configure)
     * @return static|null
     */
    private static function tryToParseDateTime($value, $format, $timezone = null)
    {
        $date = static::createFromFormat($format, $value, $timezone);
        $le   = static::getLastErrors();
        return $date === false || !empty($le['errors']) || !empty($le['warnings']) ? null : $date ;
    }

    /**
     * Create new DateTime object.
     * This method is a method for \DateTime compatibility.
     *
     * @param string $format
     * @param string|\DateTimeInterface|null $value
     * @param string|\DateTimezone|null $timezone (default: depend on confiure)
     * @return DateTime|bool
     */
    public static function createFromFormat($format, $value, $timezone = null)
    {
        if ($value === null || $value === '') {
            return false;
        }
        if ($value instanceof \DateTimeInterface) {
            return new static($value, $timezone);
        }
        $value = is_string($value) ? $value : (string)$value ;
        
        $date = parent::createFromFormat($format, $value, self::adoptTimezone($timezone));
        $le   = static::getLastErrors();
        return $date === false || !empty($le['errors']) || !empty($le['warnings']) ? false : new static($date) ;
    }

    /**
     * Adopt the time zone
     *
     * @param string|\DateTimeZone|null $timezone
     * @return DateTimeZone
     */
    private static function adoptTimezone($timezone) : DateTimeZone
    {
        $adopt_timezone = $timezone ?? self::config('default_timezone');
        return $adopt_timezone instanceof DateTimeZone ? $adopt_timezone : new DateTimeZone($adopt_timezone);
    }
    
    /**
     * Create the DateTime objects.
     *
     * @param string|\DateTimeInterface|int $time
     * @param string|\DateTimeZone $timezone (default: depend on configure)
     */
    public function __construct($time = 'now', $timezone = null)
    {
        $adopt_time     = null;
        $adopt_timezone = null;

        $adopt_timezone = self::adoptTimezone($timezone);

        if ($time instanceof \DateTimeInterface) {
            if ($timezone === null) {
                $adopt_timezone = new DateTimeZone($time->getTimezone());
            } else {
                $time = $time->setTimezone($adopt_timezone);
            }
            $adopt_time = $time->format('Y-m-d H:i:s.u');
        } elseif (is_int($time)) {
            $adopt_time = static::createDateTime((string)$time, ['U'])->format('Y-m-d H:i:s.u');
        } elseif (is_float($time)) {
            [$second, $milli_micro] = Strings::split((string)$time, '.', 2, 0);
            $adopt_time             = static::createDateTime($second, ['U'])->setMilliMicro((int) str_pad(substr($milli_micro, 0, 6), 6, '0'))->format('Y-m-d H:i:s.u');
        } else {
            $test_now = self::getTestNow();
            if ($test_now) {
                $parsed_test_now = null;
                foreach (self::config('test_now_format') as $format) {
                    $parsed_test_now = \DateTime::createFromFormat("!{$format}", $test_now, new DateTimeZone(self::config('test_now_timezone')));
                    if ($parsed_test_now) {
                        $parsed_test_now->setTimezone($adopt_timezone);
                        break;
                    }
                }
                if (!$parsed_test_now) {
                    throw DateTimeFormatException::by("Invalid date time format for `test now`. Acceptable format are [".join(',', self::config('test_now_format').']'));
                }
                $parsed_test_now = $parsed_test_now->modify($time);
                if (!$parsed_test_now) {
                    throw DateTimeFormatException::by("Invalid date time format [{$time}] given for modify.");
                }
                $adopt_time = $parsed_test_now->format('Y-m-d H:i:s.u');
            }
        }

        parent::__construct($adopt_time, $adopt_timezone);

        $this->default_format = static::config('default_format');
    }

    /**
     * Set the default format of this DateTime.
     *
     * @param string $default_format
     * @return self
     */
    public function setDefaultFormat(string $default_format) : self
    {
        $this->default_format = $default_format;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function modify($modify)
    {
        $modified = parent::modify($modify);
        if (!$modified) {
            throw DateTimeFormatException::by("Invalid date time format [{$modify}] given for modify.");
        }
        return $modified;
    }

    /**
     * Set timezone
     *
     * @param string|\DateTimeZone|null (default: depend on confige)
     * @return static
     */
    public function setTimezone($timezone)
    {
        return parent::setTimezone(self::adoptTimezone($timezone));
    }

    /**
     * It returns a string according to the default format.
     *
     * @return string
     */
    public function __toString() : string
    {
        return $this->format($this->default_format);
    }

    /**
     * It returns a string according to the default format.
     *
     * @return string
     */
    public function jsonSerialize() : string
    {
        return $this->format($this->default_format);
    }

    /**
     * Get DateTime of now.
     *
     * @param string|\DateTimeZone|null (default: depend on configure)
     * @return static
     */
    public static function now($timezone = null) : DateTime
    {
        return new static('now', $timezone);
    }

    /**
     * Get DateTime of today.
     *
     * @param string|\DateTimeZone|null (default: depend on configure)
     * @return static
     */
    public static function today($timezone = null) : DateTime
    {
        return new static('today', $timezone);
    }

    /**
     * Get DateTime of yesterday.
     *
     * @param string|\DateTimeZone|null (default: depend on configure)
     * @return static
     */
    public static function yesterday($timezone = null) : DateTime
    {
        return new static('yesterday', $timezone);
    }
    
    /**
     * Add year
     *
     * @param int $year
     * @return static
     */
    public function addYear(int $year) : DateTime
    {
        return $this->modify("{$year} year");
    }
    
    /**
     * Get year
     *
     * @return int
     */
    public function getYear() : int
    {
        return (int)$this->format('Y');
    }
    
    /**
     * Set year
     *
     * @param int $year
     * @return static
     */
    public function setYear(int $year) : DateTime
    {
        return $this->setDate($year, $this->getMonth(), $this->getDay());
    }
    
    /**
     * Add month
     *
     * @param int $month
     * @return static
     */
    public function addMonth(int $month) : DateTime
    {
        return $this->modify("{$month} month");
    }
    
    /**
     * Get month
     *
     * @return int
     */
    public function getMonth() : int
    {
        return (int)$this->format('m');
    }
    
    /**
     * Set month
     *
     * @param int $month
     * @return static
     */
    public function setMonth(int $month) : DateTime
    {
        return $this->setDate($this->getYear(), $month, $this->getDay());
    }
    
    /**
     * Add day
     *
     * @param int $day
     * @return static
     */
    public function addday(int $day) : DateTime
    {
        return $this->modify("{$day} day");
    }
    
    /**
     * Set Day
     *
     * @param int $day
     * @return static
     */
    public function setDay(int $day) : DateTime
    {
        return $this->setDate($this->getYear(), $this->getMonth(), $day);
    }
    
    /**
     * Get day
     *
     * @return int
     */
    public function getDay() : int
    {
        return (int)$this->format('d');
    }
    
    /**
     * Add hour
     *
     * @param int $hour
     * @return static
     */
    public function addHour(int $hour) : DateTime
    {
        return $this->modify("{$hour} hour");
    }
    
    /**
     * Set hour
     *
     * @param int $hour
     * @return static
     */
    public function setHour(int $hour) : DateTime
    {
        return $this->setTime($hour, $this->getMinute(), $this->getSecond());
    }
    
    /**
     * Get hour
     *
     * @return int
     */
    public function getHour() : int
    {
        return (int)$this->format('H');
    }

    /**
     * Add minute
     *
     * @param int $minute
     * @return static
     */
    public function addMinute(int $minute) : DateTime
    {
        return $this->modify("{$minute} minute");
    }

    /**
     * Set minute
     *
     * @param int $minute
     * @return static
     */
    public function setMinute(int $minute) : DateTime
    {
        return $this->setTime($this->getHour(), $minute, $this->getSecond());
    }

    /**
     * Get minute
     *
     * @return int
     */
    public function getMinute() : int
    {
        return (int)$this->format('i');
    }

    /**
     * Add second
     *
     * @param int $second
     * @return static
     */
    public function addSecond(int $second) : DateTime
    {
        return $this->modify("{$second} second");
    }

    /**
     * Set second
     *
     * @param int $second
     * @return static
     */
    public function setSecond(int $second) : DateTime
    {
        return $this->setTime($this->getHour(), $this->getMinute(), $second);
    }

    /**
     * Get second
     *
     * @return int
     */
    public function getSecond() : int
    {
        return (int)$this->format('s');
    }

    /**
     * Add micro precision seconds (6 digits) including milliseconds (3 digits)
     *
     * @param int $milli_micro
     * @return static
     */
    public function addMilliMicro(int $milli_micro) : DateTime
    {
        return $this->setMilliMicro($this->getMilliMicro() + $milli_micro);
    }

    /**
     * Set micro precision seconds (6 digits) including milliseconds (3 digits)
     *
     * @param int $milli_micro
     * @return static
     */
    public function setMilliMicro(int $milli_micro) : DateTime
    {
        if ($milli_micro >= 0) {
            $sec = floor($milli_micro / 1000000);
            $u   = str_pad($milli_micro % 1000000, 6, '0', STR_PAD_LEFT);
        } else {
            $sec = ceil(abs($milli_micro) / 1000000) * -1;
            $u   = str_pad(((abs($sec) * 1000000) + $milli_micro) % 1000000, 6, '0', STR_PAD_LEFT);
        }
        return $this->modify($this->format("H:i:s.{$u}"))->addSecond($sec);
    }

    /**
     * Get micro precision seconds (6 digits) including milliseconds (3 digits)
     *
     * @return int
     */
    public function getMilliMicro() : int
    {
        return (int)$this->format('u');
    }

    /**
     * Add millisecond
     *
     * @param int $millis
     * @return static
     */
    public function addMilli(int $milli) : DateTime
    {
        return $this->addMilliMicro($milli * 1000);
    }

    /**
     * Set millisecond
     *
     * @param int $milli
     * @return static
     */
    public function setMilli(int $milli) : DateTime
    {
        return $this->setMilliMicro($milli * 1000 + $this->getMicro());
    }

    /**
     * Get millisecond
     *
     * @return int
     */
    public function getMilli() : int
    {
        return (int)floor($this->getMilliMicro() / 1000);
    }

    /**
     * Add microsecond
     *
     * @param int $micro
     * @return static
     */
    public function addMicro(int $micro) : DateTime
    {
        return $this->addMilliMicro($micro);
    }

    /**
     * Set microsecond
     *
     * @param int $micro
     * @return static
     */
    public function setMicro(int $micro) : DateTime
    {
        return $this->setMilliMicro($this->getMilli() * 1000 + $micro);
    }

    /**
     * Get microsecond
     *
     * @return int
     */
    public function getMicro() : int
    {
        return (int)($this->getMilliMicro() % 1000);
    }

    /**
     * Get the Unix Epoch time including microseconds after the decimal point.
     * Note: The microsecond after the decimal point of micro time stamp are affected by rounding error of float and will not return accurate value.
     *
     * @return float
     */
    public function getMicroTimestamp() : float
    {
        return floatval($this->format('U.u')) ;
    }

    /**
     * Convert type to given type.
     *
     * @see Convertible
     * @param string $type
     * @return void
     */
    public function convertTo(string $type)
    {
        if (Reflector::typeOf($this, $type)) {
            return $this;
        }
        switch ($type) {
            case \DateTime::class:
                return \DateTime::createFromFormat("Y-m-d H:i:s.u", $this->format("Y-m-d H:i:s.u"), $this->getTimezone());
            case 'string':
                return $this->jsonSerialize();
            case 'int':
                return $this->getTimestamp();
            case 'float':
                return $this->getMicroTimestamp();
        }
        return null;
    }

    /**
     * Get the formatted datetime.
     * If null given as format then use default_format to format.
     *
     * @param string|null $format (default: null)
     * @return void
     */
    public function format($format = null)
    {
        return $format ? parent::format($format) : parent::format($this->default_format);
    }

    /**
     * Get age of this date time as of given at time.
     *
     * @param string $at_time (default: 'today')
     * @return integer
     */
    public function age($at_time = 'today') : int
    {
        $at_time = static::valueOf($at_time);
        if ($at_time === null) {
            throw LogicException::by("Invalid datetime format of given at time '{$at_time}'.");
        }
        return floor(($at_time->format('Ymd') - $this->format('Ymd')) / 10000);
    }
}
