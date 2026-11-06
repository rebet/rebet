<?php

declare(strict_types=1);

namespace Rebet\Tests\Tools\DateTime;

use PHPUnit\Framework\Attributes\DataProvider;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\DateTime\Month;
use Rebet\Tools\Enum\Enumeration;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Translation\Translator;

class MonthTest extends RebetTestCase
{
    public function test_enum(): void
    {
        $this->assertInstanceOf(\BackedEnum::class, Month::January);
        $this->assertInstanceOf(Enumeration::class, Month::January);
        $this->assertSame(range(1, 12), Month::values());
        $this->assertSame(Month::October, Month::valueOf(10));
        $this->assertSame(Month::October, Month::valueOf('10'));
        $this->assertSame(Month::October, Month::from(10));
        $this->assertSame(Month::September, Month::nameOf('September'));
        $this->assertNull(Month::valueOf(0));
        $this->assertNull(Month::valueOf(13));
        $this->assertSame(Month::December, Reflector::convert('12', Month::class));
    }

    #[DataProvider('dataDefinitions')]
    public function test_definition(Month $month, $value, $label, $label_short): void
    {
        $this->assertSame($value, $month->value);
        $this->assertSame($value, $month->value());
        $this->assertSame($label, $month->field('label'));
        $this->assertSame($label_short, $month->field('label_short'));
        $this->assertSame($label_short, $month->label_short());
    }

    public static function dataDefinitions()
    {
        return [
            [Month::January,  1, 'January', 'Jan'],
            [Month::February,  2, 'February', 'Feb'],
            [Month::March,  3, 'March', 'Mar'],
            [Month::April,  4, 'April', 'Apr'],
            [Month::May,  5, 'May', 'May'],
            [Month::June,  6, 'June', 'Jun'],
            [Month::July,  7, 'July', 'Jul'],
            [Month::August,  8, 'August', 'Aug'],
            [Month::September,  9, 'September', 'Sep'],
            [Month::October, 10, 'October', 'Oct'],
            [Month::November, 11, 'November', 'Nov'],
            [Month::December, 12, 'December', 'Dec'],
        ];
    }

    #[DataProvider('dataTranslations')]
    public function test_translation(Month $month, $locale, $label, $label_short): void
    {
        Translator::setLocale($locale);
        $this->assertSame($label, $month->translate('label'));
        $this->assertSame($label_short, $month->translate('label_short'));
    }

    public static function dataTranslations()
    {
        return [
            [Month::January, 'en', 'January', 'Jan'],
            [Month::February, 'en', 'February', 'Feb'],
            [Month::March, 'en', 'March', 'Mar'],
            [Month::April, 'en', 'April', 'Apr'],
            [Month::May, 'en', 'May', 'May'],
            [Month::June, 'en', 'June', 'Jun'],
            [Month::July, 'en', 'July', 'Jul'],
            [Month::August, 'en', 'August', 'Aug'],
            [Month::September, 'en', 'September', 'Sep'],
            [Month::October, 'en', 'October', 'Oct'],
            [Month::November, 'en', 'November', 'Nov'],
            [Month::December, 'en', 'December', 'Dec'],

            [Month::January, 'ja', '01月',  '1月'],
            [Month::February, 'ja', '02月',  '2月'],
            [Month::March, 'ja', '03月',  '3月'],
            [Month::April, 'ja', '04月',  '4月'],
            [Month::May, 'ja', '05月',  '5月'],
            [Month::June, 'ja', '06月',  '6月'],
            [Month::July, 'ja', '07月',  '7月'],
            [Month::August, 'ja', '08月',  '8月'],
            [Month::September, 'ja', '09月',  '9月'],
            [Month::October, 'ja', '10月', '10月'],
            [Month::November, 'ja', '11月', '11月'],
            [Month::December, 'ja', '12月', '12月'],
        ];
    }
}
