<?php
namespace Rebet\DateTime;

use Rebet\Common\Convertible;
use Rebet\Config\Config;
use Rebet\Config\Configurable;
use Rebet\Common\Reflector;

/**
 * 日付 クラス
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
     * テスト用の現在日時を設定し、本クラスをモック化します。
     *
     * @param string $now テスト用の現在時刻
     * @param string $timezone タイムゾーン（デフォルト：UTC）
     */
    public static function setTestNow(string $now, string $timezone = 'UTC') : void
    {
        self::setConfig(['test_now' => $now, 'test_now_timezone' => $timezone]);
    }
    
    /**
     * 現在のテスト用日時を取得します
     * 。
     * @return ?string テスト用日時
     */
    public static function getTestNow() : ?string
    {
        return self::config('test_now', false);
    }
    
    /**
     * 現在のテスト用日時のタイムゾーンを取得します
     * 。
     * @return ?string テスト用日時のタイムゾーン
     */
    public static function getTestNowTimezone() : ?string
    {
        return self::config('test_now_timezone', false);
    }
    
    /**
     * 現在のテスト用日時を削除します。
     */
    public static function removeTestNow() : void
    {
        self::setConfig(['test_now' => null, 'test_now_timezone' => null]);
    }

    /**
     * 指定の値を DateTime に変換します。
     * 本メソッドは Reflector::convert() 用の I/F となります。
     *
     * より詳細な DateTime 変換に関しては下記を参照／利用してください。
     *
     * @see static::createDateTime()
     * @see static::analyzeDateTime()
     *
     * @param string|\DateTimeInterface|int|null $from
     * @return DateTime|null
     */
    public static function valueOf($from) : ?DateTime
    {
        return static::createDateTime($from);
    }
    
    /**
     * DateTime オブジェクトを解析します。
     * 解析可能な文字列は以下の通りです。
     *
     * 　1. 引数 $main_format で指定したフォーマット
     * 　2. コンフィグ設定 'acceptable_date_format' に定義されているフォーマット
     * 　3. コンフィグ設定 'default_format' にフォーマットされているフォーマット
     *
     * ※本メソッドは analyzeDateTime() から日付フォーマット情報を除外して日付のみを返す簡易メソッドです。
     * ※タイムゾーンはデフォルトタイムゾーンが使用されます。
     *
     * @param string|\DateTimeInterface|int|null $value 日時文字列
     * @param string|array $main_format 優先解析フォーマット（デフォルト：[]）
     * @param string|\DateTimezone|null $timezone タイムゾーン（デフォルト：コンフィグ設定依存）
     * @return static|null 解析結果
     */
    public static function createDateTime($value, $main_format = [], $timezone = null) : ?DateTime
    {
        [$date, ] = self::analyzeDateTime($value, $main_format, $timezone);
        return $date;
    }
    
    /**
     * DateTime オブジェクトを解析します。
     * 解析可能な文字列は以下の通りです。
     *
     * 　1. 引数 $main_format で指定したフォーマット
     * 　2. コンフィグ設定 'acceptable_date_format' に定義されているフォーマット
     * 　3. コンフィグ設定 'default_format' にフォーマットされているフォーマット
     *
     * ※本メソッドは解析に成功した日付フォーマットも返します。
     * ※タイムゾーンはデフォルトタイムゾーンが使用されます。
     *
     * @param string|\DateTimeInterface|int|null $value 日時文字列
     * @param string|array $main_format 優先解析フォーマット（デフォルト：[]）
     * @param string|\DateTimezone|null $timezone タイムゾーン（デフォルト：コンフィグ設定依存）
     * @return array [DateTime|null, apply_format|null] or null 解析結果
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
     * DateTime オブジェクトを生成を試みます。
     *
     * @param string $value 日時文字列
     * @param string $format フォーマット
     * @param string|\DateTimezone|null $timezone タイムゾーン（デフォルト：コンフィグ設定依存）
     * @return static|null
     */
    private static function tryToParseDateTime($value, $format, $timezone = null)
    {
        $date = static::createFromFormat($format, $value, $timezone);
        $le   = static::getLastErrors();
        return $date === false || !empty($le['errors']) || !empty($le['warnings']) ? null : $date ;
    }
    
    /**
     * 新しい DateTime オブジェクトを返します。
     * このオブジェクトは、time で指定した文字列を format で指定した書式に沿って解釈した時刻を表します。
     *
     * なお、本メソッドは \DateTime 互換用のメソッドとなります。
     * 文字列から日付型への変換に関してはより便利な
     *
     *   DateTime::analyzeDateTime()
     *   DateTime::createDateTime()
     *   DateTime::valueOf()
     *
     * をご利用下さい。
     *
     * @param string $format フォーマット
     * @param string|\DateTimeInterface|null $value 日時文字列
     * @param string|\DateTimezone|null $timezone タイムゾーン（デフォルト：コンフィグ設定依存）
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
     * タイムゾーンを決定します
     */
    private static function adoptTimezone($timezone) : DateTimeZone
    {
        $adopt_timezone = $timezone ?? self::config('default_timezone');
        return $adopt_timezone instanceof DateTimeZone ? $adopt_timezone : new DateTimeZone($adopt_timezone);
    }
    
    /**
     * @param string|\DateTimeInterface|int $time
     * @param string|\DateTimeZone $timezone タイムゾーン（デフォオルト：コンフィグ設定依存）
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
            [$second, $milli_micro] = array_pad(explode('.', (string)$time), 2, 0);
            $adopt_time = static::createDateTime($second, ['U'])->setMilliMicro((int) str_pad(substr($milli_micro, 0, 6), 6, '0'))->format('Y-m-d H:i:s.u');
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
                    throw new DateTimeFormatException("Invalid date time format for `test now`. Acceptable format are [".join(',', self::config('test_now_format').']'));
                }
                $adopt_time = $parsed_test_now->modify($time)->format('Y-m-d H:i:s.u');
            }
        }

        parent::__construct($adopt_time, $adopt_timezone);
    }

    /**
     * タイムゾーンを設定します。
     *
     * @param string|\DateTimeZone|null タイムゾーン（デフォルト：コンフィグ設定依存）
     * @return static
     */
    public function setTimezone($timezone)
    {
        return parent::setTimezone(self::adoptTimezone($timezone));
    }
    
    /**
     * デフォルトフォーマットに従って文字列を返します。
     * @return string
     */
    public function __toString() : string
    {
        return $this->format(self::config('default_format'));
    }
    
    /**
     * デフォルトフォーマットに従って文字列を返します。
     * @return string
     */
    public function jsonSerialize() : string
    {
        return $this->format(self::config('default_format'));
    }

    /**
     * 現在時刻を取得します
     *
     * @param string|\DateTimeZone|null タイムゾーン（デフォルト：コンフィグ設定依存）
     * @return static
     */
    public static function now($timezone = null) : DateTime
    {
        return new static('now', $timezone);
    }
    
    /**
     * 本日を取得します
     *
     * @param string|\DateTimeZone|null タイムゾーン（デフォルト：コンフィグ設定依存）
     * @return static
     */
    public static function today($timezone = null) : DateTime
    {
        return new static('today', $timezone);
    }
    
    /**
     * 昨日を取得します
     *
     * @param string|\DateTimeZone|null タイムゾーン（デフォルト：コンフィグ設定依存）
     * @return static
     */
    public static function yesterday($timezone = null) : DateTime
    {
        return new static('yesterday', $timezone);
    }
    
    /**
     * 年を加算します
     * @param int $year 年
     * @return static
     */
    public function addYear(int $year) : DateTime
    {
        return $this->modify("{$year} year");
    }
    
    /**
     * 年を取得します
     * @return int 年
     */
    public function getYear() : int
    {
        return (int)$this->format('Y');
    }
    
    /**
     * 年を設定します
     * @param int $year 年
     * @return static
     */
    public function setYear(int $year) : DateTime
    {
        return $this->setDate($year, $this->getMonth(), $this->getDay());
    }
    
    /**
     * 月を加算します
     * @param int $month 月
     * @return static
     */
    public function addMonth(int $month) : DateTime
    {
        return $this->modify("{$month} month");
    }
    
    /**
     * 月を取得します
     * @return int 月
     */
    public function getMonth() : int
    {
        return (int)$this->format('m');
    }
    
    /**
     * 月を設定します
     * @param int $month 月
     * @return static
     */
    public function setMonth(int $month) : DateTime
    {
        return $this->setDate($this->getYear(), $month, $this->getDay());
    }
    
    /**
     * 日を加算します
     * @param int $day 日
     * @return static
     */
    public function addday(int $day) : DateTime
    {
        return $this->modify("{$day} day");
    }
    
    /**
     * 日を設定します
     * @param int $day 日
     * @return static
     */
    public function setDay(int $day) : DateTime
    {
        return $this->setDate($this->getYear(), $this->getMonth(), $day);
    }
    
    /**
     * 日を取得します
     * @return int 日
     */
    public function getDay() : int
    {
        return (int)$this->format('d');
    }
    
    /**
     * 時を加算します
     * @param int $minute 時
     * @return static
     */
    public function addHour(int $hour) : DateTime
    {
        return $this->modify("{$hour} hour");
    }
    
    /**
     * 時を設定します
     * @param int $hour 時
     * @return static
     */
    public function setHour(int $hour) : DateTime
    {
        return $this->setTime($hour, $this->getMinute(), $this->getSecond());
    }
    
    /**
     * 時を取得します
     * @return int 時
     */
    public function getHour() : int
    {
        return (int)$this->format('H');
    }
    
    /**
     * 分を加算します
     * @param int $minute 分
     * @return static
     */
    public function addMinute(int $minute) : DateTime
    {
        return $this->modify("{$minute} minute");
    }
    
    /**
     * 分を設定します
     * @param int $minute 分
     * @return static
     */
    public function setMinute(int $minute) : DateTime
    {
        return $this->setTime($this->getHour(), $minute, $this->getSecond());
    }
    
    /**
     * 分を取得します
     * @return int 分
     */
    public function getMinute() : int
    {
        return (int)$this->format('i');
    }
    
    /**
     * 秒を加算します
     * @param int $second 秒
     * @return static
     */
    public function addSecond(int $second) : DateTime
    {
        return $this->modify("{$second} second");
    }
    
    /**
     * 秒を設定します
     * @param int $second 秒
     * @return static
     */
    public function setSecond(int $second) : DateTime
    {
        return $this->setTime($this->getHour(), $this->getMinute(), $second);
    }
    
    /**
     * 秒を取得します
     * @return int 秒
     */
    public function getSecond() : int
    {
        return (int)$this->format('s');
    }
    
    /**
     * ミリ秒(3桁)を含むマイクロ精度秒（6桁）を加算します
     * @param int $milli_micro マイクロ秒精度
     * @return static
     */
    public function addMilliMicro(int $milli_micro) : DateTime
    {
        return $this->setMilliMicro($this->getMilliMicro() + $milli_micro);
    }
    
    /**
     * ミリ秒(3桁)を含むマイクロ精度秒（6桁）を設定します
     * @param int $milli_micro マイクロ秒精度
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
     * ミリ秒(3桁)を含むマイクロ精度秒（6桁）を取得します
     * @return int マイクロ精度秒 を取得します。
     */
    public function getMilliMicro() : int
    {
        return (int)$this->format('u');
    }

    /**
     * ミリ秒を加算します
     * @param int $millis ミリ秒
     * @return static
     */
    public function addMilli(int $milli) : DateTime
    {
        return $this->addMilliMicro($milli * 1000);
    }
    
    /**
     * ミリ秒を設定します
     * @param int $milli ミリ秒
     * @return static
     */
    public function setMilli(int $milli) : DateTime
    {
        return $this->setMilliMicro($milli * 1000 + $this->getMicro());
    }
    
    /**
     * ミリ秒を取得します
     * @return int ミリ秒
     */
    public function getMilli() : int
    {
        return (int)floor($this->getMilliMicro() / 1000);
    }
    
    /**
     * マイクロ秒を加算します
     * @param int $millis マイクロ秒
     * @return static
     */
    public function addMicro(int $micro) : DateTime
    {
        return $this->addMilliMicro($micro);
    }
    
    /**
     * マイクロ秒を設定します
     * @param int $micro マイクロ秒
     * @return static
     */
    public function setMicro(int $micro) : DateTime
    {
        return $this->setMilliMicro($this->getMilli() * 1000 + $micro);
    }
    
    /**
     * マイクロ秒を取得します
     * @return int マイクロ秒
     */
    public function getMicro() : int
    {
        return (int)($this->getMilliMicro() % 1000);
    }

    /**
     * 小数点以下にマイクロ秒を含む Unix Epoch 時間を返します。
     *
     * 【注意】
     * 小数点以下のマイクロ秒は float の丸め誤差の影響をうけるため正確な値を返さないことに注意して下さい。
     *
     * @return float
     */
    public function getMicroTimestamp() : float
    {
        return floatval($this->format('U.u')) ;
    }

    /**
     * 型変換をします。
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
}
