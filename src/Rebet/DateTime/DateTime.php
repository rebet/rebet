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
            'default_format'   => self::FORMAT_DB,
            'default_timezone' => Config::refer(App::class, 'timezone', Util::evl(date_default_timezone_get(), 'UTC')),
            'test_now'         => null,
            'test_now_format'  => ['!Y#m#d H:i:s.u', '!Y#m#d H:i:s', '!Y#m#d H:i', '!Y#m#d']
        ];
    }

    const FORMAT_ATOM    = "Y-m-d\TH:i:sP";
    const FORMAT_COOKIE  = "l, d-M-Y H:i:s T";
    const FORMAT_ISO8601 = "Y-m-d\TH:i:sO";
    const FORMAT_RFC822  = "D, d M y H:i:s O";
    const FORMAT_RFC850  = "l, d-M-y H:i:s T";
    const FORMAT_RFC1036 = "D, d M y H:i:s O";
    const FORMAT_RFC1123 = "D, d M Y H:i:s O";
    const FORMAT_RFC2822 = "D, d M Y H:i:s O";
    const FORMAT_RFC3339 = "Y-m-d\TH:i:sP";
    const FORMAT_RSS     = "D, d M Y H:i:s O";
    const FORMAT_W3C     = "Y-m-d\TH:i:sP";
    const FORMAT_DB      = "Y-m-d H:i:s";

    /**
     * テスト用の現在日時を設定し、本クラスをモック化します。
     * 
     * @param string $now
     */
    public static function setTestNow(string $now) : void {
        self::setConfig(['test_now' => $now]);
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
     * 現在のテスト用日時を削除します。
     */
    public static function removeTestNow() : void {
        self::setConfig(['test_now' => null]);
    }
    
    /**
     * @param string|\DateTimeInterface $time
     * @param string|\DateTimeZone $timezone タイムゾーン（デフォオルト：コンフィグ設定依存）
     */
    public function __construct($time = 'now', $timezone = null) {
        $adopt_time     = null;
        $adopt_timezone = null;

        $adopt_timezone = Util::nvl($timezone, self::config('default_timezone'));
        $adopt_timezone = $adopt_timezone instanceof DateTimeZone ? $adopt_timezone : new DateTimeZone($adopt_timezone);

        if($time instanceof \DateTimeInterface) {
            if($timezone === null) {
                $adopt_timezone = new DateTimeZone($time->getTimezone());
            } else {
                $time->setTimezone($adopt_timezone);
            }
            $adopt_time = $time->format('Y-m-d H:i:s.u');
        } else {
            $test_now = self::getTestNow();
            if($test_now) {
                $parsed_test_now = null;
                foreach (self::config('test_now_format') as $format) {
                    $parsed_test_now = \DateTime::createFromFormat($format, $test_now, $adopt_timezone);
                    if($parsed_test_now) { break; }
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
     * デフォルトフォーマットに従って文字列を返します。
     */
    public function __toString() {
        return $this->format(self::config('default_format'));
    }
}
