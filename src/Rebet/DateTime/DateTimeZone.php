<?php
namespace Rebet\DateTime;

use Rebet\Common\Convertible;

/**
 * タイムゾーン クラス
 *
 * @todo シングルトン化 or クラス自体削除するか否かの検討
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class DateTimeZone extends \DateTimeZone implements Convertible
{
    /**
     * タイムゾーンを構築します。
     *
     * @param string|\DateTimeZone $timezone タイムゾーン
     */
    public function __construct($timezone)
    {
        $timezone = $timezone instanceof \DateTimeZone ? $timezone->getName() : $timezone ;
        parent::__construct($timezone);
    }

    /**
     * 指定の値を DateTimeZone に変換します。
     *
     * @see Reflector::convert()
     * @param [type] $from
     * @return DateTimeZone
     */
    public static function valueOf($from) : DateTimeZone
    {
        return new DateTimeZone($from);
    }
    
    /**
     * 文字列に変換します。
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }
}
