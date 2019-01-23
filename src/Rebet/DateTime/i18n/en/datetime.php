<?php
use Rebet\DateTime\DayOfWeek;
use Rebet\DateTime\Month;

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
];
