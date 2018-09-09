<?php
namespace Rebet\DateTime;

use Rebet\Common\Util;
use Rebet\Config\Config;
use Rebet\Config\Configable;
use Rebet\Config\App;

/**
 * 日付 クラス
 * 
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class DateTime extends \DateTimeImmutable {
    use Configable;
    public static function defaultConfig() {
        return [
            'default_format'             => 'Y-m-d H:i:s',
            'default_timezone'           => Config::refer(App::class, 'timezone', Util::evl(date_default_timezone_get(), 'UTC')),
            'acceptable_datetime_format' => [
                'Y年m月d日 H時i分s秒',
                'Y年m月d日 H:i:s',
                'Y-m-d H:i:s',
                'Y/m/d H:i:s',
                'YmdHis',
                'Y年m月d日 H時i分',
                'Y年m月d日 H:i',
                'Y-m-d H:i',
                'Y/m/d H:i',
                'YmdHi',
                'Y年m月d日',
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
    public static function setTestNow(string $now, string $timezone = 'UTC') : void {
        self::setConfig(['test_now' => $now, 'test_now_timezone' => $timezone]);
    }
    
    /**
     * 現在のテスト用日時を取得します
     * 。
     * @return ?string テスト用日時
     */
    public static function getTestNow() : ?string {
        return self::config('test_now', false);
    }
    
    /**
     * 現在のテスト用日時のタイムゾーンを取得します
     * 。
     * @return ?string テスト用日時のタイムゾーン
     */
    public static function getTestNowTimezone() : ?string {
        return self::config('test_now_timezone', false);
    }
    
    /**
     * 現在のテスト用日時を削除します。
     */
    public static function removeTestNow() : void {
        self::setConfig(['test_now' => null, 'test_now_timezone' => null]);
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
	 * @param string|\DateTimeInterface|null $value 日時文字列
	 * @param array $main_format 優先解析フォーマット（デフォルト：[]）
	 * @param string|\DateTimezone|null $timezone タイムゾーン（デフォルト：コンフィグ設定依存）
	 * @return static|null 解析結果
	 */
	public static function createDateTime($value, array $main_format = [], $timezone = null) : ?DateTime {
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
	 * @param string|\DateTimeInterface|null $value 日時文字列
	 * @param array $main_format 優先解析フォーマット（デフォルト：[]）
	 * @param string|\DateTimezone|null $timezone タイムゾーン（デフォルト：コンフィグ設定依存）
	 * @return array [DateTime|null, apply_format|null] or null 解析結果
	 */
	public static function analyzeDateTime($value, array $main_format = [], $timezone = null) : array {
		if($value === null || $value === '') { return [null, null]; }
		if($value instanceof \DateTimeInterface) { return [new static($value, $timezone), self::config('default_format')]; }
		
		$formats   = $main_format + self::config('acceptable_datetime_format');
        $formats[] = self::config('default_format');
		
		$date         = null;
		$apply_format = null;
		foreach ($formats AS $format) {
			$date = self::tryToParseDateTime($value, "!{$format}", $timezone);
			if(!empty($date)) {
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
	private static function tryToParseDateTime($value, $format, $timezone = null) {
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
    public static function createFromFormat($format, $value, $timezone = null) {
		if($value === null || $value === '') { return false; }
        if($value instanceof \DateTimeInterface) { return new static($value, $timezone); }
        $value = is_string($value) ? $value : (string)$value ;
        
        $date = parent::createFromFormat($format, $value, self::adoptTimezone($timezone));
        return $date ? new static($date) : $date ;
    }

    /**
     * タイムゾーンを決定します
     */
    private static function adoptTimezone($timezone) : DateTimeZone {
        $adopt_timezone = Util::nvl($timezone, self::config('default_timezone'));
        return $adopt_timezone instanceof DateTimeZone ? $adopt_timezone : new DateTimeZone($adopt_timezone);
    }
    
    /**
     * @param string|\DateTimeInterface $time
     * @param string|\DateTimeZone $timezone タイムゾーン（デフォオルト：コンフィグ設定依存）
     */
    public function __construct($time = 'now', $timezone = null) {
        $adopt_time     = null;
        $adopt_timezone = null;

        $adopt_timezone = self::adoptTimezone($timezone);

        if($time instanceof \DateTimeInterface) {
            if($timezone === null) {
                $adopt_timezone = new DateTimeZone($time->getTimezone());
            } else {
                $time = $time->setTimezone($adopt_timezone);
            }
            $adopt_time = $time->format('Y-m-d H:i:s.u');
        } else {
            $test_now = self::getTestNow();
            if($test_now) {
                $parsed_test_now = null;
                foreach (self::config('test_now_format') as $format) {
                    $parsed_test_now = \DateTime::createFromFormat("!{$format}", $test_now, new DateTimeZone(self::config('test_now_timezone')));
                    if($parsed_test_now) {
                        $parsed_test_now->setTimezone($adopt_timezone);
                        break;
                    }
                }
                if($parsed_test_now === null) {
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
    public function setTimezone($timezone) {
        return parent::setTimezone(self::adoptTimezone($timezone));
    }
    
    /**
     * デフォルトフォーマットに従って文字列を返します。
     * @return string
     */
    public function __toString() : string {
        return $this->format(self::config('default_format'));
    }
    
    /**
     * 現在時刻を取得します
     * 
     * @param string|\DateTimeZone|null タイムゾーン（デフォルト：コンフィグ設定依存）
     * @return static
     */
    public static function now($timezone = null) : DateTime {
        return new static('now', $timezone);
    }
    
    /**
     * 本日を取得します
     * 
     * @param string|\DateTimeZone|null タイムゾーン（デフォルト：コンフィグ設定依存）
     * @return static
     */
    public static function today($timezone = null) : DateTime {
        return new static('today', $timezone);
    }
}