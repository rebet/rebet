<?php
use Rebet\DateTime\DateTime;
use Rebet\DateTime\DayOfWeek;
use Rebet\DateTime\Month;

/**
 * DateTime translation settings for Japanese (ja).
 *
 * Some fonctions implementation and translation text are borrowed from briannesbitt/Carbon ver 2.8 with some modifications.
 *
 * @see https://github.com/briannesbitt/Carbon/tree/version-2.8/src/Carbon/Lang
 * @see https://github.com/briannesbitt/Carbon/blob/version-2.8/LICENSE
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
return [
    Month::class => [
        'label'       => [null, '01月', '02月', '03月', '04月', '05月', '06月', '07月', '08月', '09月', '10月', '11月', '12月'],
        'label_short' => [null, '1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'],
    ],
    DayOfWeek::class => [
        'label'       => ['日曜日', '月曜日', '火曜日', '水曜日', '木曜日', '金曜日', '土曜日'],
        'label_short' => ['日', '月', '火', '水', '木', '金', '土'],
        'label_min'   => ['日', '月', '火', '水', '木', '金', '土'],
    ],
    '@meridiem' => function (DateTime $datetime, bool $uppercase) {
        return $datetime->getHour() < 12 ? '午前' : '午後' ;
    },
    '@formats' => [
        'Xt'   => 'H:i',
        'Xtt'  => 'H:i:s',
        'Xttt' => 'H:i:s.u',
        'Xd'   => 'Y/m/d',
        'Xdd'  => 'Y年m月d日',
        'Xddd' => 'Y年m月d日(xww)',
    ],
];
