<?php
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\DateTime\DayOfWeek;
use Rebet\Tools\DateTime\Month;

/**
 * DateTime translation settings for English (en_IE).
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
        'label'       => [null, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        'label_short' => [null, 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    ],
    DayOfWeek::class => [
        'label'       => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        'label_short' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        'label_min'   => ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
    ],
    '@meridiem' => function (DateTime $datetime, bool $uppercase) {
        return $datetime->getHour() < 12
                ? ($uppercase ? 'AM' : 'am')
                : ($uppercase ? 'PM' : 'pm')
                ;
    },
    '@formats' => [
        'Xt'   => 'H:i',
        'Xtt'  => 'H:i:s',
        'Xttt' => 'H:i:s.u',
        'Xd'   => 'd-m-Y',
        'Xdd'  => 'd xmmm Y',
        'Xddd' => 'xwww d xmmm Y',
    ],
];
