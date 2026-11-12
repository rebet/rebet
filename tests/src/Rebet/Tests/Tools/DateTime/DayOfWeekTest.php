<?php

declare(strict_types=1);

namespace Rebet\Tests\Tools\DateTime;

use PHPUnit\Framework\Attributes\DataProvider;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\DateTime\DayOfWeek;
use Rebet\Tools\Enum\Enumeration;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Translation\Translator;

class DayOfWeekTest extends RebetTestCase
{
    public function test_enum(): void
    {
        $this->assertInstanceOf(\BackedEnum::class, DayOfWeek::Sunday);
        $this->assertInstanceOf(Enumeration::class, DayOfWeek::Sunday);
        $this->assertSame([0, 1, 2, 3, 4, 5, 6], DayOfWeek::values());
        $this->assertSame(DayOfWeek::Wednesday, DayOfWeek::valueOf(3));
        $this->assertSame(DayOfWeek::Wednesday, DayOfWeek::valueOf('3'));
        $this->assertSame(DayOfWeek::Wednesday, DayOfWeek::from(3));
        $this->assertSame(DayOfWeek::Wednesday, DayOfWeek::nameOf('Wednesday'));
        $this->assertNull(DayOfWeek::valueOf(7));
        $this->assertSame(DayOfWeek::Saturday, Reflector::convert('6', DayOfWeek::class));
    }

    #[DataProvider('dataDefinitions')]
    public function test_definition(DayOfWeek $dayOfWeek, $value, $label, $label_short, $label_min): void
    {
        $this->assertSame($value, $dayOfWeek->value);
        $this->assertSame($value, $dayOfWeek->value());
        $this->assertSame($label, $dayOfWeek->original('label'));
        $this->assertSame($label_short, $dayOfWeek->original('label_short'));
        $this->assertSame($label_min, $dayOfWeek->original('label_min'));
    }

    public static function dataDefinitions()
    {
        return [
            [DayOfWeek::Sunday, 0, 'Sunday', 'Sun', 'Su'],
            [DayOfWeek::Monday, 1, 'Monday', 'Mon', 'Mo'],
            [DayOfWeek::Tuesday, 2, 'Tuesday', 'Tue', 'Tu'],
            [DayOfWeek::Wednesday, 3, 'Wednesday', 'Wed', 'We'],
            [DayOfWeek::Thursday, 4, 'Thursday', 'Thu', 'Th'],
            [DayOfWeek::Friday, 5, 'Friday', 'Fri', 'Fr'],
            [DayOfWeek::Saturday, 6, 'Saturday', 'Sat', 'Sa'],
        ];
    }

    #[DataProvider('dataTranslations')]
    public function test_translation(DayOfWeek $dayOfWeek, $locale, $label, $label_short, $label_min): void
    {
        Translator::setLocale($locale);
        $this->assertSame($label, $dayOfWeek->translate('label'));
        $this->assertSame($label_short, $dayOfWeek->translate('label_short'));
        $this->assertSame($label_min, $dayOfWeek->translate('label_min'));
        $this->assertSame($label, $dayOfWeek->label());
        $this->assertSame($label_short, $dayOfWeek->label_short());
        $this->assertSame($label_min, $dayOfWeek->label_min());
    }

    public function test_translation_withLocale(): void
    {
        // The locale can be specified for the method call, and it does not depend on the current locale
        Translator::setLocale('en');
        $this->assertSame('日曜', DayOfWeek::Sunday->label_short('ja'));
        $this->assertSame('日', DayOfWeek::Sunday->label_min('ja'));
        $this->assertSame('Sun', DayOfWeek::Sunday->label_short());
        $this->assertSame('Sun', DayOfWeek::Sunday->label_short(locale: 'en'));
        $this->assertSame('日曜日', DayOfWeek::Sunday->label('ja'));
    }

    public static function dataTranslations()
    {
        return [
            [DayOfWeek::Sunday, 'en', 'Sunday', 'Sun', 'Su'],
            [DayOfWeek::Monday, 'en', 'Monday', 'Mon', 'Mo'],
            [DayOfWeek::Tuesday, 'en', 'Tuesday', 'Tue', 'Tu'],
            [DayOfWeek::Wednesday, 'en', 'Wednesday', 'Wed', 'We'],
            [DayOfWeek::Thursday, 'en', 'Thursday', 'Thu', 'Th'],
            [DayOfWeek::Friday, 'en', 'Friday', 'Fri', 'Fr'],
            [DayOfWeek::Saturday, 'en', 'Saturday', 'Sat', 'Sa'],

            [DayOfWeek::Sunday, 'ja', '日曜日', '日曜', '日'],
            [DayOfWeek::Monday, 'ja', '月曜日', '月曜', '月'],
            [DayOfWeek::Tuesday, 'ja', '火曜日', '火曜', '火'],
            [DayOfWeek::Wednesday, 'ja', '水曜日', '水曜', '水'],
            [DayOfWeek::Thursday, 'ja', '木曜日', '木曜', '木'],
            [DayOfWeek::Friday, 'ja', '金曜日', '金曜', '金'],
            [DayOfWeek::Saturday, 'ja', '土曜日', '土曜', '土'],
        ];
    }

    #[DataProvider('dataXxxxs')]
    public function test_isXxxx(DayOfWeek $dayOfWeek, $xxxx, $expect): void
    {
        $method = "is{$xxxx}";
        $this->assertSame($expect, $dayOfWeek->$method());
    }

    public static function dataXxxxs()
    {
        return [
            [DayOfWeek::Sunday, 'Sunday', true],
            [DayOfWeek::Monday, 'Monday', true],
            [DayOfWeek::Tuesday, 'Tuesday', true],
            [DayOfWeek::Wednesday, 'Wednesday', true],
            [DayOfWeek::Thursday, 'Thursday', true],
            [DayOfWeek::Friday, 'Friday', true],
            [DayOfWeek::Saturday, 'Saturday', true],

            [DayOfWeek::Monday, 'Sunday', false],
            [DayOfWeek::Tuesday, 'Monday', false],
            [DayOfWeek::Wednesday, 'Tuesday', false],
            [DayOfWeek::Thursday, 'Wednesday', false],
            [DayOfWeek::Friday, 'Thursday', false],
            [DayOfWeek::Saturday, 'Friday', false],
            [DayOfWeek::Sunday, 'Saturday', false],

            [DayOfWeek::Sunday, 'Weekends', true ],
            [DayOfWeek::Monday, 'Weekends', false],
            [DayOfWeek::Tuesday, 'Weekends', false],
            [DayOfWeek::Wednesday, 'Weekends', false],
            [DayOfWeek::Thursday, 'Weekends', false],
            [DayOfWeek::Friday, 'Weekends', false],
            [DayOfWeek::Saturday, 'Weekends', true ],

            [DayOfWeek::Sunday, 'Weekdays', false],
            [DayOfWeek::Monday, 'Weekdays', true ],
            [DayOfWeek::Tuesday, 'Weekdays', true ],
            [DayOfWeek::Wednesday, 'Weekdays', true ],
            [DayOfWeek::Thursday, 'Weekdays', true ],
            [DayOfWeek::Friday, 'Weekdays', true ],
            [DayOfWeek::Saturday, 'Weekdays', false],
        ];
    }
}
