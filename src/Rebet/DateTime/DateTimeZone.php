<?php
namespace Rebet\DateTime;

/**
 * タイムゾーン クラス
 * 
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class DateTimeZone extends \DateTimeZone {
    
    /**
     * @param string|\DateTimeZone $timezone タイムゾーン
     */
    public function __construct($timezone) {
        $timezone = $timezone instanceof \DateTimeZone ? $timezone->getName() : $timezone ;
        parent::__construct($timezone);
    }

    public function __toString() {
        return $this->getName();
    }
}
