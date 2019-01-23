<?php
namespace Rebet\Tests\DateTime;

use Rebet\DateTime\DayOfWeek;
use Rebet\Tests\RebetTestCase;
use Rebet\Translation\Translator;

class DayOfWeekTest extends RebetTestCase
{
    /**
     * @dataProvider dataDefinitions
     */
    public function test_definition(DayOfWeek $dayOfWeek, $value, $label, $label_short, $label_min)
    {
        $this->assertSame($value, $dayOfWeek->value);
        $this->assertSame($label, $dayOfWeek->label);
        $this->assertSame($label_short, $dayOfWeek->label_short);
        $this->assertSame($label_min, $dayOfWeek->label_min);
    }

    public function dataDefinitions()
    {
        return [
            [DayOfWeek::SUNDAY()   , 0, 'Sunday'   , 'Sun', 'Su'],
            [DayOfWeek::MONDAY()   , 1, 'Monday'   , 'Mon', 'Mo'],
            [DayOfWeek::TUESDAY()  , 2, 'Tuesday'  , 'Tue', 'Tu'],
            [DayOfWeek::WEDNESDAY(), 3, 'Wednesday', 'Wed', 'We'],
            [DayOfWeek::THURSDAY() , 4, 'Thursday' , 'Thu', 'Th'],
            [DayOfWeek::FRIDAY()   , 5, 'Friday'   , 'Fri', 'Fr'],
            [DayOfWeek::SATURDAY() , 6, 'Saturday' , 'Sat', 'Sa'],
        ];
    }

    /**
     * @dataProvider dataTranslations
     */
    public function test_translation(DayOfWeek $dayOfWeek, $locale, $label, $label_short, $label_min)
    {
        Translator::setLocale($locale);
        $this->assertSame($label, $dayOfWeek->translate('label'));
        $this->assertSame($label_short, $dayOfWeek->translate('label_short'));
        $this->assertSame($label_min, $dayOfWeek->translate('label_min'));
    }

    public function dataTranslations()
    {
        return [
            [DayOfWeek::SUNDAY()   , 'en', 'Sunday'   , 'Sun', 'Su'],
            [DayOfWeek::MONDAY()   , 'en', 'Monday'   , 'Mon', 'Mo'],
            [DayOfWeek::TUESDAY()  , 'en', 'Tuesday'  , 'Tue', 'Tu'],
            [DayOfWeek::WEDNESDAY(), 'en', 'Wednesday', 'Wed', 'We'],
            [DayOfWeek::THURSDAY() , 'en', 'Thursday' , 'Thu', 'Th'],
            [DayOfWeek::FRIDAY()   , 'en', 'Friday'   , 'Fri', 'Fr'],
            [DayOfWeek::SATURDAY() , 'en', 'Saturday' , 'Sat', 'Sa'],

            [DayOfWeek::SUNDAY()   , 'ja', '日曜日', '日', '日'],
            [DayOfWeek::MONDAY()   , 'ja', '月曜日', '月', '月'],
            [DayOfWeek::TUESDAY()  , 'ja', '火曜日', '火', '火'],
            [DayOfWeek::WEDNESDAY(), 'ja', '水曜日', '水', '水'],
            [DayOfWeek::THURSDAY() , 'ja', '木曜日', '木', '木'],
            [DayOfWeek::FRIDAY()   , 'ja', '金曜日', '金', '金'],
            [DayOfWeek::SATURDAY() , 'ja', '土曜日', '土', '土'],
        ];
    }

    /**
     * @dataProvider dataXxxxs
     */
    public function test_isXxxx(DayOfWeek $dayOfWeek, $xxxx, $expect)
    {
        $method = "is{$xxxx}";
        $this->assertSame($expect, $dayOfWeek->$method());
    }

    public function dataXxxxs()
    {
        return [
            [DayOfWeek::SUNDAY()   , 'Sunday'   , true],
            [DayOfWeek::MONDAY()   , 'Monday'   , true],
            [DayOfWeek::TUESDAY()  , 'Tuesday'  , true],
            [DayOfWeek::WEDNESDAY(), 'Wednesday', true],
            [DayOfWeek::THURSDAY() , 'Thursday' , true],
            [DayOfWeek::FRIDAY()   , 'Friday'   , true],
            [DayOfWeek::SATURDAY() , 'Saturday' , true],

            [DayOfWeek::MONDAY()   , 'Sunday'   , false],
            [DayOfWeek::TUESDAY()  , 'Monday'   , false],
            [DayOfWeek::WEDNESDAY(), 'Tuesday'  , false],
            [DayOfWeek::THURSDAY() , 'Wednesday', false],
            [DayOfWeek::FRIDAY()   , 'Thursday' , false],
            [DayOfWeek::SATURDAY() , 'Friday'   , false],
            [DayOfWeek::SUNDAY()   , 'Saturday' , false],

            [DayOfWeek::SUNDAY()   , 'Weekends', true ],
            [DayOfWeek::MONDAY()   , 'Weekends', false],
            [DayOfWeek::TUESDAY()  , 'Weekends', false],
            [DayOfWeek::WEDNESDAY(), 'Weekends', false],
            [DayOfWeek::THURSDAY() , 'Weekends', false],
            [DayOfWeek::FRIDAY()   , 'Weekends', false],
            [DayOfWeek::SATURDAY() , 'Weekends', true ],

            [DayOfWeek::SUNDAY()   , 'Weekdays', false],
            [DayOfWeek::MONDAY()   , 'Weekdays', true ],
            [DayOfWeek::TUESDAY()  , 'Weekdays', true ],
            [DayOfWeek::WEDNESDAY(), 'Weekdays', true ],
            [DayOfWeek::THURSDAY() , 'Weekdays', true ],
            [DayOfWeek::FRIDAY()   , 'Weekdays', true ],
            [DayOfWeek::SATURDAY() , 'Weekdays', false],
        ];
    }
}
