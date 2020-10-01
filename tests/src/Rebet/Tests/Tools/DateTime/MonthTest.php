<?php
namespace Rebet\Tests\Tools\DateTime;

use Rebet\Tools\DateTime\Month;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Translation\Translator;

class MonthTest extends RebetTestCase
{
    /**
     * @dataProvider dataDefinitions
     */
    public function test_definition(Month $month, $value, $label, $label_short)
    {
        $this->assertSame($value, $month->value);
        $this->assertSame($label, $month->label);
        $this->assertSame($label_short, $month->label_short);
    }

    public function dataDefinitions()
    {
        return [
            [Month::JANUARY() ,  1, 'January'  , 'Jan'],
            [Month::FEBRUARY(),  2, 'February' , 'Feb'],
            [Month::MARCH()   ,  3, 'March'    , 'Mar'],
            [Month::APRIL()   ,  4, 'April'    , 'Apr'],
            [Month::MAY()     ,  5, 'May'      , 'May'],
            [Month::JUNE()    ,  6, 'June'     , 'Jun'],
            [Month::JULY()    ,  7, 'July'     , 'Jul'],
            [Month::AUGUST()  ,  8, 'August'   , 'Aug'],
            [Month::SEPTEMBE(),  9, 'September', 'Sep'],
            [Month::OCTOBER() , 10, 'October'  , 'Oct'],
            [Month::NOVEMBER(), 11, 'November' , 'Nov'],
            [Month::DECEMBER(), 12, 'December' , 'Dec'],
        ];
    }

    /**
     * @dataProvider dataTranslations
     */
    public function test_translation(Month $month, $locale, $label, $label_short)
    {
        Translator::setLocale($locale);
        $this->assertSame($label, $month->translate('label'));
        $this->assertSame($label_short, $month->translate('label_short'));
    }

    public function dataTranslations()
    {
        return [
            [Month::JANUARY() , 'en', 'January'  , 'Jan'],
            [Month::FEBRUARY(), 'en', 'February' , 'Feb'],
            [Month::MARCH()   , 'en', 'March'    , 'Mar'],
            [Month::APRIL()   , 'en', 'April'    , 'Apr'],
            [Month::MAY()     , 'en', 'May'      , 'May'],
            [Month::JUNE()    , 'en', 'June'     , 'Jun'],
            [Month::JULY()    , 'en', 'July'     , 'Jul'],
            [Month::AUGUST()  , 'en', 'August'   , 'Aug'],
            [Month::SEPTEMBE(), 'en', 'September', 'Sep'],
            [Month::OCTOBER() , 'en', 'October'  , 'Oct'],
            [Month::NOVEMBER(), 'en', 'November' , 'Nov'],
            [Month::DECEMBER(), 'en', 'December' , 'Dec'],

            [Month::JANUARY() , 'ja', '01月',  '1月'],
            [Month::FEBRUARY(), 'ja', '02月',  '2月'],
            [Month::MARCH()   , 'ja', '03月',  '3月'],
            [Month::APRIL()   , 'ja', '04月',  '4月'],
            [Month::MAY()     , 'ja', '05月',  '5月'],
            [Month::JUNE()    , 'ja', '06月',  '6月'],
            [Month::JULY()    , 'ja', '07月',  '7月'],
            [Month::AUGUST()  , 'ja', '08月',  '8月'],
            [Month::SEPTEMBE(), 'ja', '09月',  '9月'],
            [Month::OCTOBER() , 'ja', '10月', '10月'],
            [Month::NOVEMBER(), 'ja', '11月', '11月'],
            [Month::DECEMBER(), 'ja', '12月', '12月'],
        ];
    }
}
