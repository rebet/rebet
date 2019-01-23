<?php
use Rebet\DateTime\DateTime;
use Rebet\DateTime\DayOfWeek;
use Rebet\DateTime\Month;

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
];
