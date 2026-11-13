<?php

declare(strict_types=1);

namespace Rebet\Tests\Tools\Enum;

use Override;
use Rebet\Application\App;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Enum\Enumerable;
use Rebet\Tools\Enum\Enumeration;
use Rebet\Tools\Enum\Meta;
use Rebet\Tools\Enum\Translatable;
use Rebet\Tools\Exception\LogicException;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Utility\ArrayKeyable;
use Rebet\Tools\Utility\Arrays;

/**
 * Test of Enumerable trait.
 *
 * The trait is tested by the following 3 implementation patterns.
 *
 *   1. Backed enum : EnumerableTest_Gender, EnumerableTest_AcceptStatus
 *   2. Pure enum   : EnumerableTest_Season
 *   3. DB reference: EnumerableTest_Prefecture (class that is built from the master table of database)
 */
class EnumerableTest extends RebetTestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        App::setLocale('ja');
    }

    //==================================================================
    // Backed enum (int) : EnumerableTest_Gender
    //==================================================================

    public function test_backed_nameAndValueAndLabel(): void
    {
        $male = EnumerableTest_Gender::Male;
        $this->assertInstanceOf(Enumeration::class, $male);
        $this->assertSame('Male', $male->name());
        $this->assertSame(1, $male->value());
        $this->assertSame('男性', $male->label());
        $this->assertSame('男性', $male->label('ja'));
        $this->assertSame('Male', $male->label('en'));
        $this->assertSame('Männlich', $male->label('de'));

        // native properties are available as it is
        $this->assertSame('Male', $male->name);
        $this->assertSame(1, $male->value);
    }

    public function test_backed_equals(): void
    {
        $male   = EnumerableTest_Gender::Male;
        $female = EnumerableTest_Gender::Female;

        $this->assertTrue($male->equals(1));
        $this->assertTrue($male->equals($male));
        $this->assertFalse($male->equals(2));
        $this->assertFalse($male->equals($female));

        // loose comparison of the value (compared as the stringified value, same as valueOf())
        $this->assertTrue($male->equals('1'));
        $this->assertFalse($male->equals('01'));
        $this->assertFalse($male->equals('2'));
        $this->assertFalse($male->equals(null));
    }

    public function test_backed_in(): void
    {
        $male = EnumerableTest_Gender::Male;

        $this->assertTrue($male->in(1, 2));
        $this->assertTrue($male->in(...EnumerableTest_Gender::lists()));

        $this->assertFalse($male->in(2, 3, 4));
        $this->assertFalse($male->in(EnumerableTest_Gender::Female));
    }

    public function test_backed_jsonSerialize(): void
    {
        $this->assertSame(1, EnumerableTest_Gender::Male->jsonSerialize());
        $this->assertSame('[1,2]', json_encode(EnumerableTest_Gender::lists()));
    }

    public function test_backed_lists(): void
    {
        $this->assertSame([EnumerableTest_Gender::Male, EnumerableTest_Gender::Female], EnumerableTest_Gender::lists());
        $this->assertSame([EnumerableTest_Gender::Female], EnumerableTest_Gender::lists(fn(EnumerableTest_Gender $g) => $g->value() === 2));
        $this->assertSame([], EnumerableTest_Gender::lists(fn() => false));
    }

    public function test_backed_maps(): void
    {
        $this->assertSame(
            [1 => EnumerableTest_Gender::Male, 2 => EnumerableTest_Gender::Female],
            EnumerableTest_Gender::maps(),
        );
        $this->assertSame(
            ['男性' => EnumerableTest_Gender::Male, '女性' => EnumerableTest_Gender::Female],
            EnumerableTest_Gender::maps('label'),
        );
        // The field given to the Translatable attribute is translated (the locale can be specified)
        $this->assertSame(
            ['Männlich' => EnumerableTest_Gender::Male, 'Weiblich' => EnumerableTest_Gender::Female],
            EnumerableTest_Gender::maps('label', null, 'de'),
        );
        // The original value is used by the field that is not given to the attribute, and the locale is ignored
        $this->assertSame(
            ['Male' => EnumerableTest_Gender::Male, 'Female' => EnumerableTest_Gender::Female],
            EnumerableTest_Gender::maps('name', null, 'de'),
        );
        $this->assertSame(
            ['Male' => EnumerableTest_Gender::Male, 'Female' => EnumerableTest_Gender::Female],
            EnumerableTest_Gender::maps('name'),
        );
        $this->assertSame(
            [2 => EnumerableTest_Gender::Female],
            EnumerableTest_Gender::maps('value', fn(EnumerableTest_Gender $g) => $g->value() === 2),
        );
    }

    public function test_backed_maps_invalid(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Undefined field or meta. " . EnumerableTest_Gender::class . "::Male has no field or meta 'invalid'.");

        EnumerableTest_Gender::maps('invalid');
    }

    public function test_backed_fieldOf(): void
    {
        $this->assertSame(EnumerableTest_Gender::Male, EnumerableTest_Gender::fieldOf('value', 1));
        $this->assertSame(EnumerableTest_Gender::Male, EnumerableTest_Gender::fieldOf('value', '1'));
        $this->assertSame(EnumerableTest_Gender::Male, EnumerableTest_Gender::fieldOf('name', 'Male'));
        // label is given to the Translatable attribute, so that the translated label is compared
        $this->assertSame(EnumerableTest_Gender::Male, EnumerableTest_Gender::fieldOf('label', '男性'));
        $this->assertNull(EnumerableTest_Gender::fieldOf('label', 'Male'));
        $this->assertSame(EnumerableTest_Gender::Female, EnumerableTest_Gender::fieldOf('label', '女性'));
        $this->assertSame(EnumerableTest_Gender::Male, EnumerableTest_Gender::fieldOf('label', 'Männlich', 'de'));
        $this->assertSame(EnumerableTest_Gender::Female, EnumerableTest_Gender::fieldOf('label', 'Weiblich', 'de'));
        $this->assertNull(EnumerableTest_Gender::fieldOf('value', 3));
        $this->assertNull(EnumerableTest_Gender::fieldOf('label', '不明'));

        // given enum is returned as it is
        $this->assertSame(EnumerableTest_Gender::Female, EnumerableTest_Gender::fieldOf('value', EnumerableTest_Gender::Female));
        // other object can not be found
        $this->assertNull(EnumerableTest_Gender::fieldOf('value', new \stdClass()));
    }

    public function test_backed_fieldOf_invalid(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Undefined field or meta. " . EnumerableTest_Gender::class . "::Male has no field or meta 'invalid'.");

        EnumerableTest_Gender::fieldOf('invalid', 1);
    }

    public function test_backed_valueOf(): void
    {
        $this->assertSame(EnumerableTest_Gender::Male, EnumerableTest_Gender::valueOf(1));
        $this->assertSame(EnumerableTest_Gender::Male, EnumerableTest_Gender::valueOf('1'));
        $this->assertSame(EnumerableTest_Gender::Female, EnumerableTest_Gender::valueOf(EnumerableTest_Gender::Female));

        $this->assertNull(EnumerableTest_Gender::valueOf('01'));
        $this->assertNull(EnumerableTest_Gender::valueOf(3));
    }

    public function test_backed_labelOf(): void
    {
        // The label is compared with the translated label
        $this->assertSame(EnumerableTest_Gender::Male, EnumerableTest_Gender::labelOf('男性'));
        $this->assertSame(EnumerableTest_Gender::Female, EnumerableTest_Gender::labelOf('女性', 'ja'));
        $this->assertSame(EnumerableTest_Gender::Male, EnumerableTest_Gender::labelOf('Male', 'en'));
        $this->assertSame(EnumerableTest_Gender::Male, EnumerableTest_Gender::labelOf('Männlich', 'de'));

        $this->assertNull(EnumerableTest_Gender::labelOf('Male'));
        $this->assertNull(EnumerableTest_Gender::labelOf('不明'));
    }

    public function test_backed_nameOf(): void
    {
        $this->assertSame(EnumerableTest_Gender::Male, EnumerableTest_Gender::nameOf('Male'));
        $this->assertSame(EnumerableTest_Gender::Female, EnumerableTest_Gender::nameOf('Female'));
        $this->assertNull(EnumerableTest_Gender::nameOf('MALE'));
    }

    public function test_backed_listOf(): void
    {
        $this->assertSame([1, 2], EnumerableTest_Gender::listOf('value'));
        $this->assertSame([2], EnumerableTest_Gender::listOf('value', fn(EnumerableTest_Gender $g) => $g->name() === 'Female'));
        $this->assertSame(['Male', 'Female'], EnumerableTest_Gender::listOf('name'));
        // label is given to the Translatable attribute, so that the translated labels are returned (original() returns the labels as they are)
        $this->assertSame(['男性', '女性'], EnumerableTest_Gender::listOf('label'));
        $this->assertSame(['Male', 'Female'], array_map(fn(EnumerableTest_Gender $g) => $g->original('label'), EnumerableTest_Gender::lists()));
        $this->assertSame(['Männlich', 'Weiblich'], EnumerableTest_Gender::listOf('label', locale: 'de'));
    }

    public function test_backed_listOf_invalid(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Undefined field or meta. " . EnumerableTest_Gender::class . "::Male has no field or meta 'invalid'.");

        EnumerableTest_Gender::listOf('invalid');
    }

    public function test_backed_values_labels_names(): void
    {
        $this->assertSame([1, 2], EnumerableTest_Gender::values());
        $this->assertSame([2], EnumerableTest_Gender::values(fn(EnumerableTest_Gender $g) => $g->name() === 'Female'));

        // labels() returns the translated labels
        $this->assertSame(['男性', '女性'], EnumerableTest_Gender::labels());
        $this->assertSame(['Male', 'Female'], EnumerableTest_Gender::labels(null, 'en'));
        $this->assertSame(['Männlich', 'Weiblich'], EnumerableTest_Gender::labels(null, 'de'));
        $this->assertSame(['男性'], EnumerableTest_Gender::labels(fn(EnumerableTest_Gender $g) => $g->value() === 1));

        $this->assertSame(['Male', 'Female'], EnumerableTest_Gender::names());
        $this->assertSame(['Female'], EnumerableTest_Gender::names(fn(EnumerableTest_Gender $g) => $g->value() === 2));
    }

    public function test_backed_field(): void
    {
        $male = EnumerableTest_Gender::Male;

        $this->assertSame(1, $male->original('value'));
        $this->assertSame('Male', $male->original('name'));

        // 'label' is not translated unless the translate option is given
        $this->assertSame('Male', $male->original('label'));
        $this->assertSame('男性', $male->translate('label'));
        $this->assertSame('Männlich', $male->translate('label', 'de'));

        // 'value' and 'name' are identifiers, so they are never translated
        $this->assertSame('1', $male->translate('value'));
        $this->assertSame('Male', $male->translate('name', 'de'));
    }

    public function test_backed_translate(): void
    {
        $male = EnumerableTest_Gender::Male;

        $this->assertSame('男性', $male->translate('label'));
        $this->assertSame('男性', $male->translate('label'));
        $this->assertSame('Male', $male->translate('label', 'en'));
        $this->assertSame('Männlich', $male->translate('label', 'de'));

        // translate() always returns a string
        $this->assertSame('1', $male->translate('value'));
        $this->assertSame('Male', $male->translate('name'));

        App::setLocale('de');
        $this->assertSame('Männlich', $male->translate('label'));
        $this->assertSame('Männlich', $male->label());
    }

    public function test_backed_has(): void
    {
        $waiting = EnumerableTest_AcceptStatus::Waiting;
        foreach (['name', 'value', 'label', 'color', 'tags'] as $field) {
            $this->assertTrue($waiting->has($field), $field);
        }
        $this->assertFalse($waiting->has('escape'));
        $this->assertFalse(EnumerableTest_Gender::Male->has('color'));
    }

    public function test_translate_undefinedField(): void
    {
        // The undefined field is always an error, regardless of the translation
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Undefined field or meta. " . EnumerableTest_Gender::class . "::Male has no field or meta 'nothing'.");

        EnumerableTest_Gender::Male->translate('nothing');
    }

    public function test_backed_call_undefined(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Undefined field or meta. " . EnumerableTest_Gender::class . "::Male has no field or meta 'nothing'.");

        EnumerableTest_Gender::Male->nothing();    // @phpstan-ignore method.notFound
    }

    public function test_backed_convertTo(): void
    {
        $male = EnumerableTest_Gender::Male;
        $this->assertSame(1, $male->convertTo('int'));
        $this->assertSame(null, $male->convertTo('float'));
        // The string conversion is the string of the value (not the label), the same as Reflector::convert()
        $this->assertSame('1', $male->convertTo('string'));
        $this->assertSame($male, $male->convertTo(EnumerableTest_Gender::class));
        $this->assertSame(null, $male->convertTo(EnumerableTest_AcceptStatus::class));

        $status = EnumerableTest_AcceptStatus::Rejected;
        $this->assertSame(null, $status->convertTo('int'));
        $this->assertSame('R', $status->convertTo('string'));
        $this->assertSame($status, $status->convertTo(EnumerableTest_AcceptStatus::class));
        $this->assertSame(null, $status->convertTo(EnumerableTest_Gender::class));
    }

    public function test_backed_reflectorConvert(): void
    {
        // Enumeration is Convertible, so that it is converted by valueOf()
        $this->assertSame(EnumerableTest_Gender::Male, Reflector::convert(1, EnumerableTest_Gender::class));
        $this->assertSame(EnumerableTest_Gender::Male, Reflector::convert('1', EnumerableTest_Gender::class));
        $this->assertSame(EnumerableTest_Gender::Female, Reflector::convert(2, EnumerableTest_Gender::class));
        $this->assertNull(Reflector::convert(3, EnumerableTest_Gender::class));
    }

    //==================================================================
    // Translatable attribute
    //==================================================================

    public function test_translatable_withoutAttribute(): void
    {
        // The class without the Translatable attribute is not translated at all (including label())
        $waiting = EnumerableTest_AcceptStatus::Waiting;
        $this->assertSame('待機中', $waiting->label());
        $this->assertSame('待機中', $waiting->label('en'));   // Meta label is just a string, so that it is not changed by the locale
        $this->assertSame('orange', $waiting->translate('color'));

        $tokyo = EnumerableTest_Prefecture::valueOf(13);
        $this->assertSame('東京都', $tokyo?->label('de'));
        $this->assertSame('tokyo', $tokyo?->translate('name'));

        // The case name is the label when Meta label is not defined
        $this->assertSame('One', EnumerableTest_Flag::One->name());
        $this->assertSame('One', EnumerableTest_Flag::One->label());
        $this->assertSame(['Blank', 'Zero', 'One'], EnumerableTest_Flag::labels());
    }

    public function test_translatable_group(): void
    {
        // The dictionary of the group of the attribute is used. (i18n/{locale}/enumerable.php)
        $this->assertSame('男性', EnumerableTest_Gender::Male->label());
        $this->assertSame('Männlich', EnumerableTest_Gender::Male->label('de'));
        $this->assertSame('男性', EnumerableTest_Gender::Male->translate('label'));
    }

    public function test_translatable_fields(): void
    {
        $sunday = EnumerableTest_Multi::Sunday;

        // The fields given to the attribute are translated
        $this->assertSame('日曜日', $sunday->label());
        $this->assertSame('日曜日', $sunday->translate('label'));
        $this->assertSame('日', $sunday->translate('label_short'));
        $this->assertSame('日', $sunday->label_short());      // @phpstan-ignore method.notFound
        $this->assertSame('月', EnumerableTest_Multi::Monday->label_short());    // @phpstan-ignore method.notFound

        // The field not given to the attribute is not translated even if the dictionary has it
        $this->assertSame('Su', $sunday->translate('label_min'));
        $this->assertSame('Su', $sunday->label_min());        // @phpstan-ignore method.notFound
        $this->assertSame('Su', $sunday->original('label_min'));

        // original() returns the value as it is
        $this->assertSame('Sunday', $sunday->original('label'));
        $this->assertSame('Sun', $sunday->original('label_short'));

        // The locale can be specified, but the dictionary of the locale is not found so that the field value is returned
        $this->assertSame('Sunday', $sunday->label('en'));
        $this->assertSame('Sun', $sunday->translate('label_short', 'en'));
    }

    public function test_call_locale(): void
    {
        $sunday = EnumerableTest_Multi::Sunday;

        // The locale can be specified by the first argument or the named argument
        $this->assertSame('日', $sunday->label_short());        // @phpstan-ignore method.notFound
        $this->assertSame('日', $sunday->label_short('ja'));    // @phpstan-ignore method.notFound
        $this->assertSame('Sun', $sunday->label_short('en'));   // @phpstan-ignore method.notFound
        $this->assertSame('Sun', $sunday->label_short(locale: 'en'));   // @phpstan-ignore method.notFound
        $this->assertSame('日', $sunday->label_short(null));    // @phpstan-ignore method.notFound
        $this->assertSame('日', $sunday->label_short(locale: null));    // @phpstan-ignore method.notFound

        // The locale does not depend on the current locale
        App::setLocale('de');
        $this->assertSame('日', $sunday->label_short('ja'));    // @phpstan-ignore method.notFound
        $this->assertSame('Sun', $sunday->label_short());      // @phpstan-ignore method.notFound
        App::setLocale('ja');

        // The locale is ignored for the field that is not given to the attribute (no notice)
        $this->assertSame('Su', $sunday->label_min('ja'));      // @phpstan-ignore method.notFound
        $this->assertSame('Su', $sunday->label_min(locale: 'ja'));      // @phpstan-ignore method.notFound
        $this->assertSame('orange', EnumerableTest_AcceptStatus::Waiting->color('ja'));     // @phpstan-ignore method.notFound
        $this->assertSame(3, EnumerableTest_AcceptStatus::Waiting->count('ja'));            // @phpstan-ignore method.notFound

        // The other arguments are ignored
        $this->assertSame('日', $sunday->label_short('ja', 'ignored', 123));    // @phpstan-ignore method.notFound
        $this->assertSame('日', $sunday->label_short('ja', unknown: 'x'));      // @phpstan-ignore method.notFound
    }

    public function test_call_locale_invalidType(): void
    {
        $sunday = EnumerableTest_Multi::Sunday;
        foreach ([[123], [['ja']], [true], [1.5], [new \stdClass()]] as $args) {
            try {
                $sunday->label_short(...$args);     // @phpstan-ignore method.notFound
                $this->fail('The locale that is not string or null should be rejected.');
            } catch (\TypeError $e) {
                $this->assertStringContainsString('label_short(): Argument #1 ($locale) must be of type ?string', $e->getMessage());
            }
        }

        try {
            $sunday->label_short(locale: 123);      // @phpstan-ignore method.notFound
            $this->fail('The locale that is not string or null should be rejected.');
        } catch (\TypeError $e) {
            $this->assertStringContainsString('must be of type ?string, int given', $e->getMessage());
        }

        // The type of the locale is checked even if the field is not translated
        $this->expectException(\TypeError::class);
        $sunday->label_min(123);        // @phpstan-ignore method.notFound
    }

    public function test_translatable_allEntrances(): void
    {
        // The attribute decides all of the translation entrances
        $this->assertSame(['日曜日', '月曜日'], EnumerableTest_Multi::labels());
        $this->assertSame(EnumerableTest_Multi::Monday, EnumerableTest_Multi::labelOf('月曜日'));
        $this->assertNull(EnumerableTest_Multi::labelOf('Monday'));
        $this->assertSame(['日', '月'], EnumerableTest_Multi::listOf('label_short'));
        $this->assertSame(['Su', 'Mo'], EnumerableTest_Multi::listOf('label_min'));
        $this->assertSame(EnumerableTest_Multi::Monday, EnumerableTest_Multi::fieldOf('label_short', '月'));
        $this->assertNull(EnumerableTest_Multi::fieldOf('label_min', '月(最小)'));
        $this->assertSame(EnumerableTest_Multi::Monday, EnumerableTest_Multi::fieldOf('label_min', 'Mo'));
        $this->assertSame(['日曜日', '月曜日'], EnumerableTest_Multi::nextLabels(null));
        $this->assertSame(['日', '月'], EnumerableTest_Multi::nextOf('label_short', null, null));
        $this->assertSame(['Su', 'Mo'], EnumerableTest_Multi::nextOf('label_min', null, null));

        // the label is translated too, but original() returns it as it is
        $this->assertSame(['日曜日', '月曜日'], EnumerableTest_Multi::listOf('label'));
        $this->assertSame(['Sunday', 'Monday'], array_map(fn(EnumerableTest_Multi $m) => $m->original('label'), EnumerableTest_Multi::lists()));
    }

    public function test_translatable_sameRuleOfAllEntrances(): void
    {
        // The method call, fieldOf(), listOf() and nextOf() follow the same rule: translated if the field is given to the attribute, otherwise as it is
        foreach (['label' => '日曜日', 'label_short' => '日', 'label_min' => 'Su'] as $field => $expect) {
            $sunday = EnumerableTest_Multi::Sunday;
            $this->assertSame($expect, $sunday->$field());
            $this->assertSame($expect, EnumerableTest_Multi::listOf($field)[0]);
            $this->assertSame($expect, EnumerableTest_Multi::nextOf($field, null)[0]);
            $this->assertSame(EnumerableTest_Multi::Sunday, EnumerableTest_Multi::fieldOf($field, $expect));
        }

        // The locale where the dictionary is not found returns the field value as it is
        $this->assertSame(['Sunday', 'Monday'], EnumerableTest_Multi::listOf('label', null, 'en'));
        $this->assertSame(['Sun', 'Mon'], EnumerableTest_Multi::nextOf('label_short', null, null, 'en'));
        $this->assertSame(EnumerableTest_Multi::Monday, EnumerableTest_Multi::fieldOf('label_short', 'Mon', 'en'));
    }

    public function test_translatable_emptyFields(): void
    {
        // No field is translated even if the dictionary of the group has it
        $this->assertSame('Sunday', EnumerableTest_EmptyFields::Sunday->label());
        $this->assertSame('Sunday', EnumerableTest_EmptyFields::Sunday->translate('label'));
        $this->assertSame(['Sunday'], EnumerableTest_EmptyFields::labels());
    }

    public function test_translatable_inheritance(): void
    {
        $base       = EnumerableTest_Base::valueOf(1);
        $child      = EnumerableTest_Child::valueOf(1);
        $grandchild = EnumerableTest_Grandchild::valueOf(1);
        $own        = EnumerableTest_Own::valueOf(1);
        $this->assertInstanceOf(EnumerableTest_Base::class, $base);
        $this->assertInstanceOf(EnumerableTest_Child::class, $child);
        $this->assertInstanceOf(EnumerableTest_Grandchild::class, $grandchild);
        $this->assertInstanceOf(EnumerableTest_Own::class, $own);

        // The attribute of the parent class is inherited (label is translated and label_short is not)
        $this->assertSame('基底', $base->label());
        $this->assertSame('子', $child->label());
        $this->assertSame('孫', $grandchild->label());
        $this->assertSame('O', $child->translate('label_short'));
        $this->assertSame('O', $grandchild->translate('label_short'));

        // The attribute of the child class takes precedence over the parent class (label_short is translated and label is not)
        $this->assertSame('One', $own->label());
        $this->assertSame('独自短', $own->translate('label_short'));
        $this->assertSame('独自短', $own->label_short());     // @phpstan-ignore method.notFound
    }

    public function test_translatable_attribute(): void
    {
        $default = new Translatable(['label', 'label_short']);
        $this->assertSame(['label', 'label_short'], $default->fields);
        $this->assertSame('enum', $default->group);
        $this->assertSame('enum', Translatable::DEFAULT_GROUP);

        $grouped = new Translatable(['label'], group: 'datetime');
        $this->assertSame(['label'], $grouped->fields);
        $this->assertSame('datetime', $grouped->group);

        $empty = new Translatable();
        $this->assertSame([], $empty->fields);
        $this->assertSame('enum', $empty->group);

        // the keys of the array are ignored (the fields are re-indexed), and the group can be given as the 2nd positional argument
        $this->assertSame(['label', 'label_short'], (new Translatable(['a' => 'label', 'b' => 'label_short']))->fields);
        $this->assertSame('datetime', (new Translatable(['label'], 'datetime'))->group);

        // duplicate fields are merged
        $this->assertSame(['label', 'label_short'], (new Translatable(['label', 'label_short', 'label']))->fields);

        // has() checks whether the field is translated
        $this->assertTrue($default->has('label'));
        $this->assertTrue($default->has('label_short'));
        $this->assertFalse($default->has('label_min'));
        $this->assertFalse($default->has('value'));
        $this->assertFalse($default->has('name'));
        $this->assertFalse($default->has(''));
        $this->assertTrue($grouped->has('label'));
        $this->assertFalse($grouped->has('label_short'));
        $this->assertFalse($empty->has('label'));
        $this->assertFalse((new Translatable(['label']))->has('Label'));    // case sensitive
        $this->assertTrue((new Translatable(['0']))->has('0'));            // the numeric string field is also available
    }

    public function test_translatable_attribute_reservedNames(): void
    {
        foreach (['value', 'name'] as $reserved) {
            try {
                new Translatable(['label', $reserved]);
                $this->fail("Translatable should not accept the reserved name '{$reserved}'.");
            } catch (LogicException $e) {
                $this->assertSame("Invalid translatable field. Reserved name({$reserved}) can not be translated.", $e->getMessage());
            }
        }
    }

    public function test_translatable_attribute_invalidArguments(): void
    {
        foreach ([
            fn() => new Translatable(['label'], group: ''),
            fn() => new Translatable(['label', 1]),
            fn() => new Translatable(['label', null]),
            fn() => new Translatable(['label', ['label_short']]),
        ] as $factory) {
            try {
                $factory();
                $this->fail('Translatable should not accept the invalid argument.');
            } catch (LogicException $e) {
                $this->assertNotSame('', $e->getMessage());
            }
        }
    }

    public function test_translatable_attribute_unknownNamedArgument(): void
    {
        // The signature is explicit, so that PHP rejects the unknown named argument (it is not LogicException)
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Unknown named parameter $unknown');

        new Translatable(['label'], unknown: 'x');    // @phpstan-ignore argument.unknown
    }

    public function test_translatable_attribute_fieldsMustBeArray(): void
    {
        // The fields should be given as the array (the variadic form is not supported)
        $this->expectException(\TypeError::class);

        new Translatable('label');    // @phpstan-ignore argument.type
    }

    public function test_translatable_invalidAttributeIsDetectedWhenUsed(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Invalid translatable field. Reserved name(value) can not be translated.');

        EnumerableTest_InvalidTranslatable::One->label();
    }

    //==================================================================
    // Backed enum (string) with Meta : EnumerableTest_AcceptStatus
    //==================================================================

    public function test_meta_field(): void
    {
        $status = EnumerableTest_AcceptStatus::Accepted;
        $this->assertSame('A', $status->value());
        $this->assertSame('Accepted', $status->name());

        // Meta label is used when the translation is not found
        $this->assertSame('受理', $status->label());
        $this->assertSame('受理', $status->original('label'));
        $this->assertSame('受理', $status->label('en'));

        $this->assertSame('green', $status->original('color'));
        $this->assertSame('fas fa-check-circle', $status->original('icon'));
        $this->assertSame('green', $status->color());
        $this->assertSame('fas fa-check-circle', $status->icon());
        $this->assertSame('green', $status->translate('color'));
    }

    public function test_meta_field_undefined(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Undefined field or meta. " . EnumerableTest_AcceptStatus::class . "::Waiting has no field or meta 'invalid'.");

        EnumerableTest_AcceptStatus::Waiting->original('invalid');
    }

    public function test_meta_reservedName(): void
    {
        $this->assertSame(['color' => 'red', 'label' => 'ラベル'], (new Meta(color: 'red', label: 'ラベル'))->data);

        foreach (['value', 'name'] as $reserved) {
            try {
                new Meta(...[$reserved => 'reserved']);
                $this->fail("Meta should not accept the reserved name '{$reserved}'.");
            } catch (LogicException $e) {
                $this->assertStringContainsString($reserved, $e->getMessage());
            }
        }

        try {
            new Meta(value: 1, name: 'x');
            $this->fail('Meta should not accept the reserved names.');
        } catch (LogicException $e) {
            $this->assertSame("Invalid meta name. Reserved name(value, name) can not be used as a metadata name.", $e->getMessage());
        }
    }

    public function test_meta_lists_maps_and_more(): void
    {
        $this->assertSame(
            [EnumerableTest_AcceptStatus::Waiting, EnumerableTest_AcceptStatus::Accepted, EnumerableTest_AcceptStatus::Rejected],
            EnumerableTest_AcceptStatus::lists(),
        );
        $this->assertSame(
            ['W' => EnumerableTest_AcceptStatus::Waiting, 'A' => EnumerableTest_AcceptStatus::Accepted, 'R' => EnumerableTest_AcceptStatus::Rejected],
            EnumerableTest_AcceptStatus::maps(),
        );
        $this->assertSame(
            ['orange' => EnumerableTest_AcceptStatus::Waiting, 'green' => EnumerableTest_AcceptStatus::Accepted, 'red' => EnumerableTest_AcceptStatus::Rejected],
            EnumerableTest_AcceptStatus::maps('color'),
        );

        $this->assertSame(EnumerableTest_AcceptStatus::Rejected, EnumerableTest_AcceptStatus::fieldOf('value', 'R'));
        $this->assertSame(EnumerableTest_AcceptStatus::Accepted, EnumerableTest_AcceptStatus::fieldOf('color', 'green'));
        $this->assertNull(EnumerableTest_AcceptStatus::fieldOf('value', 1));
        $this->assertSame(EnumerableTest_AcceptStatus::Rejected, EnumerableTest_AcceptStatus::valueOf('R'));
        $this->assertSame(EnumerableTest_AcceptStatus::Rejected, EnumerableTest_AcceptStatus::labelOf('却下'));
        $this->assertSame(EnumerableTest_AcceptStatus::Rejected, EnumerableTest_AcceptStatus::nameOf('Rejected'));

        $this->assertSame(['W', 'A', 'R'], EnumerableTest_AcceptStatus::values());
        $this->assertSame(['待機中', '受理', '却下'], EnumerableTest_AcceptStatus::labels());
        $this->assertSame(['Waiting', 'Accepted', 'Rejected'], EnumerableTest_AcceptStatus::names());
        $this->assertSame(['orange', 'green', 'red'], EnumerableTest_AcceptStatus::listOf('color'));
        $this->assertSame(['far fa-clock'], EnumerableTest_AcceptStatus::listOf('icon', fn(EnumerableTest_AcceptStatus $s) => $s->value() === 'W'));
    }

    public function test_meta_nexts(): void
    {
        $waiting  = EnumerableTest_AcceptStatus::Waiting;
        $accepted = EnumerableTest_AcceptStatus::Accepted;
        $rejected = EnumerableTest_AcceptStatus::Rejected;

        $this->assertSame([$waiting, $accepted, $rejected], EnumerableTest_AcceptStatus::nexts($waiting, ['role' => 'operator']));
        $this->assertSame([$accepted], EnumerableTest_AcceptStatus::nexts($accepted, ['role' => 'operator']));
        $this->assertSame([$rejected], EnumerableTest_AcceptStatus::nexts('R', ['role' => 'operator']));
        $this->assertSame([$waiting, $accepted, $rejected], EnumerableTest_AcceptStatus::nexts($rejected, ['role' => 'admin']));
        $this->assertSame([], EnumerableTest_AcceptStatus::nexts($waiting, ['role' => 'guest']));
    }

    public function test_meta_nextOf(): void
    {
        $waiting  = EnumerableTest_AcceptStatus::Waiting;
        $accepted = EnumerableTest_AcceptStatus::Accepted;
        $operator = ['role' => 'operator'];

        $this->assertSame(['W', 'A', 'R'], EnumerableTest_AcceptStatus::nextOf('value', $waiting, $operator));
        $this->assertSame(['A'], EnumerableTest_AcceptStatus::nextOf('value', $accepted, $operator));
        $this->assertSame(['orange', 'green', 'red'], EnumerableTest_AcceptStatus::nextOf('color', $waiting, $operator));
        $this->assertSame(['green'], EnumerableTest_AcceptStatus::nextOf('color', $accepted, $operator));
        $this->assertSame(['受理'], EnumerableTest_AcceptStatus::nextOf('label', $accepted, $operator));

        $this->assertSame(['W', 'A', 'R'], EnumerableTest_AcceptStatus::nextValues($waiting, $operator));
        $this->assertSame(['A'], EnumerableTest_AcceptStatus::nextValues($accepted, $operator));
        $this->assertSame(['待機中', '受理', '却下'], EnumerableTest_AcceptStatus::nextLabels($waiting, $operator));
        $this->assertSame(['受理'], EnumerableTest_AcceptStatus::nextLabels($accepted, $operator));
        $this->assertSame(['Waiting', 'Accepted', 'Rejected'], EnumerableTest_AcceptStatus::nextNames($waiting, $operator));
        $this->assertSame(['Accepted'], EnumerableTest_AcceptStatus::nextNames($accepted, $operator));
    }

    public function test_meta_nextOf_invalid(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Undefined field or meta. " . EnumerableTest_AcceptStatus::class . "::Waiting has no field or meta 'invalid'.");

        EnumerableTest_AcceptStatus::nextOf('invalid', EnumerableTest_AcceptStatus::Waiting, ['role' => 'operator']);
    }

    public function test_meta_nexts_default(): void
    {
        // nexts() returns all cases by default
        $this->assertSame(EnumerableTest_Gender::lists(), EnumerableTest_Gender::nexts(EnumerableTest_Gender::Male));
        $this->assertSame([1, 2], EnumerableTest_Gender::nextValues(1));
        $this->assertSame(['男性', '女性'], EnumerableTest_Gender::nextLabels(null));
        $this->assertSame(['Male', 'Female'], EnumerableTest_Gender::nextNames(null));
    }

    public function test_meta_field_translate_keepsType(): void
    {
        $waiting = EnumerableTest_AcceptStatus::Waiting;

        // original() returns the value as it is (the type is kept) and it is not translated
        $this->assertSame(['x', 'y'], $waiting->original('tags'));
        $this->assertSame(3, $waiting->original('count'));
        $this->assertSame('orange', $waiting->original('color'));

        // translate() always returns a string (the field value is stringified when the translation is not found)
        $this->assertSame('3', $waiting->translate('count'));
        $this->assertSame('orange', $waiting->translate('color', 'de'));
        $this->assertIsString($waiting->translate('tags'));
        // count is not given to the Translatable attribute (EnumerableTest_AcceptStatus has no attribute), so that the type is kept
        $this->assertSame(3, EnumerableTest_AcceptStatus::listOf('count', fn(EnumerableTest_AcceptStatus $s) => $s === EnumerableTest_AcceptStatus::Waiting)[0]);
    }

    public function test_meta_fieldOf_lastWins_and_cached(): void
    {
        // If there is an enum with the same field value, it wins after the lists()
        $this->assertSame(EnumerableTest_AcceptStatus::Rejected, EnumerableTest_AcceptStatus::fieldOf('group', 'closed'));
        $this->assertSame(EnumerableTest_AcceptStatus::Waiting, EnumerableTest_AcceptStatus::fieldOf('group', 'open'));
        $this->assertSame(EnumerableTest_AcceptStatus::Rejected, EnumerableTest_AcceptStatus::maps('group')['closed']);

        // same result by the 2nd call (cached index)
        $this->assertSame(EnumerableTest_AcceptStatus::Rejected, EnumerableTest_AcceptStatus::fieldOf('group', 'closed'));
        $this->assertSame(EnumerableTest_AcceptStatus::Accepted, EnumerableTest_AcceptStatus::valueOf('A'));
        $this->assertSame(EnumerableTest_AcceptStatus::Accepted, EnumerableTest_AcceptStatus::valueOf('A'));
        $this->assertNull(EnumerableTest_AcceptStatus::valueOf('X'));

        // cache is separated by the field
        $this->assertSame(EnumerableTest_AcceptStatus::Accepted, EnumerableTest_AcceptStatus::fieldOf('color', 'green'));
        $this->assertSame(EnumerableTest_AcceptStatus::Accepted, EnumerableTest_AcceptStatus::nameOf('Accepted'));

        // The translated label is not cached (depends on the locale)
        $this->assertSame(EnumerableTest_Gender::Male, EnumerableTest_Gender::labelOf('男性'));
        $this->assertNull(EnumerableTest_Gender::labelOf('男性', 'de'));
        $this->assertSame(EnumerableTest_Gender::Male, EnumerableTest_Gender::labelOf('Männlich', 'de'));
    }

    //==================================================================
    // Pure enum : EnumerableTest_Season
    //==================================================================

    public function test_pure_nameAndValueAndLabel(): void
    {
        $this->assertInstanceOf(Enumeration::class, EnumerableTest_Season::Spring);
        $this->assertSame('Spring', EnumerableTest_Season::Spring->name());

        // Pure enum has no value, so that the name is used as the value
        $this->assertSame('Spring', EnumerableTest_Season::Spring->value());
        $this->assertSame('Summer', EnumerableTest_Season::Summer->value());

        // label: translation > Meta label > name
        $this->assertSame('春', EnumerableTest_Season::Spring->label());
        $this->assertSame('夏', EnumerableTest_Season::Summer->label());
        $this->assertSame('Autumn', EnumerableTest_Season::Autumn->label());
        $this->assertSame('春', EnumerableTest_Season::Spring->label('en'));
        $this->assertSame('Summer', EnumerableTest_Season::Summer->label('en'));
    }

    public function test_pure_equals_in(): void
    {
        $spring = EnumerableTest_Season::Spring;

        $this->assertTrue($spring->equals('Spring'));
        $this->assertTrue($spring->equals($spring));
        $this->assertFalse($spring->equals('spring'));
        $this->assertFalse($spring->equals(EnumerableTest_Season::Summer));

        $this->assertTrue($spring->in('Spring', 'Summer'));
        $this->assertTrue($spring->in(EnumerableTest_Season::Winter, EnumerableTest_Season::Spring));
        $this->assertFalse($spring->in('Autumn', 'Winter'));
    }

    public function test_pure_jsonSerialize_convertTo(): void
    {
        $summer = EnumerableTest_Season::Summer;

        $this->assertSame('Summer', $summer->jsonSerialize());
        $this->assertSame('Summer', $summer->convertTo('string'));
        $this->assertSame(null, $summer->convertTo('int'));
        $this->assertSame($summer, $summer->convertTo(EnumerableTest_Season::class));
        $this->assertSame(null, $summer->convertTo(EnumerableTest_Gender::class));
    }

    public function test_pure_lists_maps(): void
    {
        $this->assertSame(
            [EnumerableTest_Season::Spring, EnumerableTest_Season::Summer, EnumerableTest_Season::Autumn, EnumerableTest_Season::Winter],
            EnumerableTest_Season::lists(),
        );
        $this->assertSame(
            ['Spring' => EnumerableTest_Season::Spring, 'Summer' => EnumerableTest_Season::Summer, 'Autumn' => EnumerableTest_Season::Autumn, 'Winter' => EnumerableTest_Season::Winter],
            EnumerableTest_Season::maps(),
        );
        $this->assertSame(
            ['春' => EnumerableTest_Season::Spring, '夏' => EnumerableTest_Season::Summer, 'Autumn' => EnumerableTest_Season::Autumn, 'Winter' => EnumerableTest_Season::Winter],
            EnumerableTest_Season::maps('label'),
        );
    }

    public function test_pure_finders(): void
    {
        $this->assertSame(EnumerableTest_Season::Summer, EnumerableTest_Season::valueOf('Summer'));
        $this->assertSame(EnumerableTest_Season::Summer, EnumerableTest_Season::fieldOf('value', 'Summer'));
        $this->assertSame(EnumerableTest_Season::Summer, EnumerableTest_Season::nameOf('Summer'));
        $this->assertSame(EnumerableTest_Season::Summer, EnumerableTest_Season::labelOf('夏'));
        $this->assertSame(EnumerableTest_Season::Spring, EnumerableTest_Season::labelOf('春'));
        $this->assertSame(EnumerableTest_Season::Winter, EnumerableTest_Season::labelOf('Winter'));
        $this->assertSame(EnumerableTest_Season::Summer, EnumerableTest_Season::labelOf('Summer', 'en'));
        $this->assertNull(EnumerableTest_Season::valueOf('summer'));
        $this->assertNull(EnumerableTest_Season::valueOf(1));
    }

    public function test_pure_listOf_and_more(): void
    {
        $this->assertSame(['Spring', 'Summer', 'Autumn', 'Winter'], EnumerableTest_Season::values());
        $this->assertSame(['Spring', 'Summer', 'Autumn', 'Winter'], EnumerableTest_Season::names());
        $this->assertSame(['春', '夏', 'Autumn', 'Winter'], EnumerableTest_Season::labels());
        $this->assertSame(['春', 'Summer', 'Autumn', 'Winter'], EnumerableTest_Season::labels(null, 'en'));
        $this->assertSame(['夏'], EnumerableTest_Season::labels(fn(EnumerableTest_Season $s) => $s === EnumerableTest_Season::Summer));

        $this->assertSame(['Spring', 'Summer', 'Autumn', 'Winter'], EnumerableTest_Season::listOf('value'));
        $this->assertSame(['春', '夏', 'Autumn', 'Winter'], EnumerableTest_Season::listOf('label'));
        $this->assertSame(['春', 'Summer', 'Autumn', 'Winter'], EnumerableTest_Season::listOf('label', null, 'en'));
    }

    public function test_pure_field_translate(): void
    {
        $summer = EnumerableTest_Season::Summer;

        $this->assertSame('Summer', $summer->original('value'));
        $this->assertSame('Summer', $summer->original('label'));
        $this->assertSame('夏', $summer->translate('label'));
        $this->assertSame('Summer', $summer->translate('value'));
    }

    public function test_pure_reflectorConvert(): void
    {
        $this->assertSame(EnumerableTest_Season::Autumn, Reflector::convert('Autumn', EnumerableTest_Season::class));
        $this->assertNull(Reflector::convert('autumn', EnumerableTest_Season::class));
    }

    public function test_pure_nexts(): void
    {
        $this->assertSame(EnumerableTest_Season::lists(), EnumerableTest_Season::nexts(EnumerableTest_Season::Spring));
        $this->assertSame(['Spring', 'Summer', 'Autumn', 'Winter'], EnumerableTest_Season::nextValues('Spring'));
        $this->assertSame(['春', '夏', 'Autumn', 'Winter'], EnumerableTest_Season::nextLabels('Spring'));
    }

    //==================================================================
    // DB reference : EnumerableTest_Prefecture
    //==================================================================

    public function test_db_nameAndValueAndLabel(): void
    {
        $tokyo = EnumerableTest_Prefecture::valueOf(13);
        $this->assertInstanceOf(EnumerableTest_Prefecture::class, $tokyo);
        $this->assertInstanceOf(Enumeration::class, $tokyo);

        $this->assertSame('tokyo', $tokyo->name());
        $this->assertSame(13, $tokyo->value());

        // This enum is not translatable, so that the label is used as it is
        $this->assertSame('東京都', $tokyo->label());
        $this->assertSame('東京都', $tokyo->label('en'));
        $this->assertSame('東京都', $tokyo->label('de'));
        $this->assertSame('東京都', $tokyo->translate('label', 'de'));
    }

    public function test_db_lists_maps(): void
    {
        $this->assertSame(['hokkaido', 'tokyo', 'osaka'], array_map(fn($p) => $p->name(), EnumerableTest_Prefecture::lists()));
        $this->assertSame([1, 13, 27], array_keys(EnumerableTest_Prefecture::maps()));
        $this->assertSame(['北海道', '東京都', '大阪府'], array_keys(EnumerableTest_Prefecture::maps('label')));
        $this->assertSame(['tokyo', 'osaka'], array_keys(EnumerableTest_Prefecture::maps('name', fn(EnumerableTest_Prefecture $p) => $p->value() >= 13)));

        // same instances are returned
        $this->assertSame(EnumerableTest_Prefecture::lists()[1], EnumerableTest_Prefecture::maps()[13]);
    }

    public function test_db_finders(): void
    {
        $this->assertSame('tokyo', EnumerableTest_Prefecture::valueOf(13)?->name());
        $this->assertSame('tokyo', EnumerableTest_Prefecture::valueOf('13')?->name());
        $this->assertSame('osaka', EnumerableTest_Prefecture::nameOf('osaka')?->name());
        $this->assertSame('osaka', EnumerableTest_Prefecture::labelOf('大阪府')?->name());
        $this->assertSame('osaka', EnumerableTest_Prefecture::fieldOf('label', '大阪府')?->name());
        $this->assertNull(EnumerableTest_Prefecture::valueOf(99));
        $this->assertNull(EnumerableTest_Prefecture::nameOf('kyoto'));
    }

    public function test_db_listOf_and_more(): void
    {
        $this->assertSame([1, 13, 27], EnumerableTest_Prefecture::values());
        $this->assertSame([13, 27], EnumerableTest_Prefecture::values(fn(EnumerableTest_Prefecture $p) => $p->value() > 1));
        $this->assertSame(['hokkaido', 'tokyo', 'osaka'], EnumerableTest_Prefecture::names());
        $this->assertSame(['北海道', '東京都', '大阪府'], EnumerableTest_Prefecture::labels());
        $this->assertSame(['北海道', '東京都', '大阪府'], EnumerableTest_Prefecture::labels(null, 'en'));
        $this->assertSame(['北海道', '東京都', '大阪府'], EnumerableTest_Prefecture::listOf('label', null, 'de'));
        $this->assertSame([13], EnumerableTest_Prefecture::listOf('value', fn(EnumerableTest_Prefecture $p) => $p->name() === 'tokyo'));
    }

    public function test_db_equals_in_json_convertTo(): void
    {
        $tokyo = EnumerableTest_Prefecture::valueOf(13);
        $osaka = EnumerableTest_Prefecture::valueOf(27);
        $this->assertNotNull($tokyo);
        $this->assertNotNull($osaka);

        $this->assertTrue($tokyo->equals(13));
        $this->assertTrue($tokyo->equals($tokyo));
        $this->assertTrue($tokyo->equals('13'));
        $this->assertFalse($tokyo->equals('013'));
        $this->assertFalse($tokyo->equals($osaka));
        $this->assertTrue($tokyo->in(1, 13));
        $this->assertFalse($tokyo->in($osaka));

        $this->assertSame(13, $tokyo->jsonSerialize());
        $this->assertSame('[1,13,27]', json_encode(EnumerableTest_Prefecture::lists()));
        $this->assertSame(13, $tokyo->convertTo('int'));
        $this->assertSame('13', $tokyo->convertTo('string'));
        $this->assertSame($tokyo, $tokyo->convertTo(EnumerableTest_Prefecture::class));
        $this->assertSame(null, $tokyo->convertTo(EnumerableTest_Gender::class));
    }

    public function test_db_notCached(): void
    {
        try {
            $this->assertSame('tokyo', EnumerableTest_Prefecture::valueOf(13)?->name());

            // The cases of the class that is not native enum may be changed, so that the index is not cached.
            EnumerableTest_Prefecture::replace([new EnumerableTest_Prefecture(99, 'kyoto', '京都府')]);
            $this->assertNull(EnumerableTest_Prefecture::valueOf(13));
            $this->assertSame('kyoto', EnumerableTest_Prefecture::valueOf(99)?->name());
            $this->assertSame('kyoto', EnumerableTest_Prefecture::nameOf('kyoto')?->name());
        } finally {
            EnumerableTest_Prefecture::replace();
        }

        $this->assertSame('tokyo', EnumerableTest_Prefecture::valueOf(13)?->name());
        $this->assertNull(EnumerableTest_Prefecture::valueOf(99));
    }

    public function test_db_noMeta(): void
    {
        $tokyo = EnumerableTest_Prefecture::valueOf(13);
        $this->assertNotNull($tokyo);

        // Non enum class has no Meta attribute
        $this->assertSame(13, $tokyo->original('value'));
        $this->assertSame('tokyo', $tokyo->original('name'));
        $this->assertSame('東京都', $tokyo->original('label'));
        $this->assertSame(13, Reflector::convert('13', EnumerableTest_Prefecture::class)?->value());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Undefined field or meta. " . EnumerableTest_Prefecture::class . "::tokyo has no field or meta 'color'.");

        $tokyo->original('color');
    }
    //==================================================================
    // Additional cases (boundary / combination)
    //==================================================================

    public static function isMale(EnumerableTest_Gender $gender): bool
    {
        return $gender === EnumerableTest_Gender::Male;
    }

    public function test_equals_sameClassDifferentKeyType(): void
    {
        // The key is normalized to the canonical form of the array key ('1' and 1 are the same key)
        $original = EnumerableTest_Prefecture::cases();
        try {
            EnumerableTest_Prefecture::replace([new EnumerableTest_Prefecture(13, 'tokyo', '東京都')]);
            $a = EnumerableTest_Prefecture::cases()[0];
            $this->assertTrue($a->equals(new EnumerableTest_Prefecture((int) '13', 'tokyo', '東京都')));
            $this->assertTrue($a->equals('13'));
        } finally {
            EnumerableTest_Prefecture::replace($original);
        }
    }

    public function test_equals_otherTypes(): void
    {
        $male = EnumerableTest_Gender::Male;

        $this->assertFalse($male->equals(new \stdClass()));
        $this->assertFalse($male->equals([1]));
        $this->assertFalse($male->equals(null));
        $this->assertFalse($male->equals(EnumerableTest_Season::Spring));
        $this->assertFalse($male->equals(EnumerableTest_AcceptStatus::Waiting));
    }

    public function test_equals_sameClassDifferentInstance(): void
    {
        // The class (not native enum) may return the different instances for the same case, so that it is compared by asArrayKey().
        $original = EnumerableTest_Prefecture::cases();
        try {
            EnumerableTest_Prefecture::replace([new EnumerableTest_Prefecture(13, 'tokyo', '東京都')]);
            $a = EnumerableTest_Prefecture::cases()[0];
            $b = new EnumerableTest_Prefecture(13, 'tokyo', '東京都');
            $c = new EnumerableTest_Prefecture(1, 'hokkaido', '北海道');
            $this->assertNotSame($a, $b);
            $this->assertTrue($a->equals($b));
            $this->assertTrue($a->in($c, $b));
            $this->assertFalse($a->equals($c));
        } finally {
            EnumerableTest_Prefecture::replace($original);
        }
    }

    public function test_reset(): void
    {
        $caches = fn(string $class) => (new \ReflectionMethod($class, 'caches'))->getStaticVariables()['CACHES'];

        EnumerableTest_Gender::Male->label();
        EnumerableTest_Gender::valueOf(1);
        EnumerableTest_AcceptStatus::Waiting->color();
        $this->assertNotEmpty($caches(EnumerableTest_Gender::class)['translation']);
        $this->assertNotEmpty($caches(EnumerableTest_Gender::class)['index']);
        $this->assertNotEmpty($caches(EnumerableTest_AcceptStatus::class)['metadata']);

        // The caches are managed for each using class
        EnumerableTest_Gender::reset();
        $this->assertSame(['translation' => [], 'metadata' => [], 'index' => []], $caches(EnumerableTest_Gender::class));
        $this->assertNotEmpty($caches(EnumerableTest_AcceptStatus::class)['metadata']);

        // The cache is rebuilt after the reset
        $this->assertSame(EnumerableTest_Gender::Male, EnumerableTest_Gender::valueOf(1));
        $this->assertSame('男性', EnumerableTest_Gender::Male->label('ja'));
    }

    public function test_in_empty(): void
    {
        $this->assertFalse(EnumerableTest_Gender::Male->in());
    }

    public function test_lists_callableMatcher(): void
    {
        // The matcher can be any callable, not only the Closure
        $this->assertSame([EnumerableTest_Gender::Male], EnumerableTest_Gender::lists([self::class, 'isMale']));
        $this->assertSame([EnumerableTest_Gender::Male], EnumerableTest_Gender::lists(self::class . '::isMale'));
        $this->assertSame([EnumerableTest_Gender::Male], EnumerableTest_Gender::lists(self::isMale(...)));
        $this->assertSame(EnumerableTest_Gender::lists(), EnumerableTest_Gender::lists('is_object'));
        $this->assertSame([1], EnumerableTest_Gender::values([self::class, 'isMale']));
        $this->assertSame(['男性'], EnumerableTest_Gender::labels([self::class, 'isMale']));
        $this->assertSame(['Male'], EnumerableTest_Gender::names([self::class, 'isMale']));
        $this->assertSame([1 => EnumerableTest_Gender::Male], EnumerableTest_Gender::maps('value', [self::class, 'isMale']));
    }

    public function test_listOf_emptyResult(): void
    {
        $this->assertSame([], EnumerableTest_Gender::listOf('value', fn() => false));
        $this->assertSame([], EnumerableTest_Gender::values(fn() => false));
        $this->assertSame([], EnumerableTest_Gender::labels(fn() => false));
        $this->assertSame([], EnumerableTest_Gender::maps('value', fn() => false));

        // The undefined field can not be detected when there is no target enum
        $this->assertSame([], EnumerableTest_Gender::listOf('nothing', fn() => false));
    }

    public function test_floatAndNull_areNotKeyable(): void
    {
        // float and null are not converted to the array key, so that they match nothing
        $this->assertNull(EnumerableTest_AcceptStatus::fieldOf('ratio', 0.5));
        $this->assertNull(EnumerableTest_AcceptStatus::fieldOf('ratio', '0.5'));
        $this->assertNull(EnumerableTest_AcceptStatus::fieldOf('ratio', 1));
        $this->assertNull(EnumerableTest_AcceptStatus::fieldOf('color', null));
        $this->assertNull(EnumerableTest_AcceptStatus::valueOf(1.0));
        $this->assertNull(EnumerableTest_Gender::valueOf(1.0));
        $this->assertNull(EnumerableTest_Gender::valueOf(null));
        $this->assertFalse(EnumerableTest_Gender::Male->equals(1.0));
        $this->assertFalse(EnumerableTest_Gender::Male->equals(null));
        $this->assertFalse(EnumerableTest_Flag::Blank->equals(null));

        // the field value of float is not included in the index
        $this->assertSame(0.5, EnumerableTest_AcceptStatus::Waiting->original('ratio'));

        // maps() requires the array key
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("The field 'ratio' of " . EnumerableTest_AcceptStatus::class . "::Waiting can not be used as the key of map.");

        EnumerableTest_AcceptStatus::maps('ratio');
    }

    public function test_index_isolatedBetweenEnums(): void
    {
        // The cached index is separated by the enum class
        $this->assertSame(EnumerableTest_Gender::Male, EnumerableTest_Gender::nameOf('Male'));
        $this->assertNull(EnumerableTest_AcceptStatus::nameOf('Male'));
        $this->assertSame(EnumerableTest_AcceptStatus::Waiting, EnumerableTest_AcceptStatus::nameOf('Waiting'));
        $this->assertNull(EnumerableTest_Gender::nameOf('Waiting'));

        $this->assertSame(EnumerableTest_Gender::Male, EnumerableTest_Gender::valueOf(1));
        $this->assertNull(EnumerableTest_AcceptStatus::valueOf(1));
        $this->assertSame(EnumerableTest_AcceptStatus::Waiting, EnumerableTest_AcceptStatus::valueOf('W'));
        $this->assertNull(EnumerableTest_Gender::valueOf('W'));
    }

    public function test_nextOf_translateAndLocale(): void
    {
        $this->assertSame(['男性', '女性'], EnumerableTest_Gender::nextLabels(null));
        $this->assertSame(['Male', 'Female'], EnumerableTest_Gender::nextLabels(null, null, 'en'));
        $this->assertSame(['Männlich', 'Weiblich'], EnumerableTest_Gender::nextLabels(1, null, 'de'));
        $this->assertSame(['Männlich', 'Weiblich'], EnumerableTest_Gender::nextOf('label', 1, null, 'de'));
        $this->assertSame(['男性', '女性'], EnumerableTest_Gender::nextOf('label', 1));
        $this->assertSame(['Male', 'Female'], EnumerableTest_Gender::nextOf('name', 1));
        $this->assertSame([1, 2], EnumerableTest_Gender::nextOf('value', 1));   // value is never translated, so that the type is kept
    }

    public function test_pure_jsonEncode(): void
    {
        $this->assertSame('"Spring"', json_encode(EnumerableTest_Season::Spring));
        $this->assertSame('["Spring","Summer","Autumn","Winter"]', json_encode(EnumerableTest_Season::lists()));
        $this->assertSame('{"season":"Summer"}', json_encode(['season' => EnumerableTest_Season::Summer]));
        $this->assertSame('{"gender":1}', json_encode(['gender' => EnumerableTest_Gender::Male]));
    }

    public function test_meta_positionalArguments(): void
    {
        // The positional argument is not the reserved name
        $meta = new Meta('x', color: 'red');
        $this->assertSame([0 => 'x', 'color' => 'red'], $meta->data);
    }

    public function test_db_empty(): void
    {
        try {
            EnumerableTest_Prefecture::replace([]);

            $this->assertSame([], EnumerableTest_Prefecture::lists());
            $this->assertSame([], EnumerableTest_Prefecture::maps());
            $this->assertSame([], EnumerableTest_Prefecture::values());
            $this->assertSame([], EnumerableTest_Prefecture::labels());
            $this->assertSame([], EnumerableTest_Prefecture::names());
            $this->assertSame([], EnumerableTest_Prefecture::nexts(null));
            $this->assertNull(EnumerableTest_Prefecture::valueOf(13));
            $this->assertNull(EnumerableTest_Prefecture::labelOf('東京都'));
            $this->assertNull(Reflector::convert('13', EnumerableTest_Prefecture::class));
        } finally {
            EnumerableTest_Prefecture::replace();
        }
        $this->assertCount(3, EnumerableTest_Prefecture::lists());
    }

    public function test_db_matcherAndNexts(): void
    {
        $tokyo = EnumerableTest_Prefecture::valueOf(13);
        $this->assertNotNull($tokyo);

        $this->assertSame(EnumerableTest_Prefecture::lists(), EnumerableTest_Prefecture::nexts($tokyo));
        $this->assertSame([1, 13, 27], EnumerableTest_Prefecture::nextValues($tokyo));
        $this->assertSame(['hokkaido', 'tokyo', 'osaka'], EnumerableTest_Prefecture::nextNames($tokyo));
        $this->assertSame(['北海道', '東京都', '大阪府'], EnumerableTest_Prefecture::nextLabels($tokyo, null, 'en'));
        $this->assertSame(['東京都', '大阪府'], EnumerableTest_Prefecture::labels(fn(EnumerableTest_Prefecture $p) => $p->value() >= 13));
    }
    //==================================================================
    // Array key conversion (Arrays::asKey() / Arrays::tryAsKey())
    //==================================================================

    public function test_valueOf_sameAsPhpArrayKey(): void
    {
        // bool is matched as the implicit conversion of PHP array key (true is 1 and false is 0), but null is not supported
        $this->assertSame(EnumerableTest_Gender::Male, EnumerableTest_Gender::valueOf(true));
        $this->assertSame(EnumerableTest_Gender::Male, Reflector::convert(true, EnumerableTest_Gender::class));
        $this->assertNull(EnumerableTest_Gender::valueOf(false));
        $this->assertNull(EnumerableTest_Gender::valueOf(null));
        $this->assertNull(EnumerableTest_Gender::valueOf([]));
        $this->assertNull(EnumerableTest_Gender::valueOf([1]));

        // float is not supported
        $this->assertNull(EnumerableTest_Gender::valueOf(1.0));
        $this->assertNull(EnumerableTest_Gender::valueOf(1.5));

        $this->assertTrue(EnumerableTest_Gender::Male->equals(true));
        $this->assertFalse(EnumerableTest_Gender::Female->equals(true));
        $this->assertFalse(EnumerableTest_Gender::Male->equals(1.0));
    }

    public function test_valueOf_emptyStringAndZero(): void
    {
        $this->assertSame(EnumerableTest_Flag::Blank, EnumerableTest_Flag::valueOf(''));
        $this->assertSame(EnumerableTest_Flag::Zero, EnumerableTest_Flag::valueOf('0'));
        $this->assertSame(EnumerableTest_Flag::Zero, EnumerableTest_Flag::valueOf(0));
        $this->assertNull(EnumerableTest_Flag::valueOf(0.0));
        $this->assertNull(EnumerableTest_Flag::valueOf(-0.0));
        $this->assertSame(EnumerableTest_Flag::One, EnumerableTest_Flag::valueOf(1));

        // bool is the same as the implicit conversion of PHP array key, but null is not supported
        $this->assertNull(EnumerableTest_Flag::valueOf(null));
        $this->assertSame(EnumerableTest_Flag::Zero, EnumerableTest_Flag::valueOf(false));
        $this->assertSame(EnumerableTest_Flag::One, EnumerableTest_Flag::valueOf(true));

        $this->assertTrue(EnumerableTest_Flag::Blank->equals(''));
        $this->assertFalse(EnumerableTest_Flag::Blank->equals(null));
        $this->assertFalse(EnumerableTest_Flag::Blank->equals(false));
        $this->assertTrue(EnumerableTest_Flag::Zero->equals(0));
        $this->assertTrue(EnumerableTest_Flag::Zero->equals(false));
        $this->assertFalse(EnumerableTest_Flag::Zero->equals(null));
        $this->assertSame(['' => EnumerableTest_Flag::Blank, 0 => EnumerableTest_Flag::Zero, 1 => EnumerableTest_Flag::One], EnumerableTest_Flag::maps());
    }

    public function test_asArrayKey(): void
    {
        $this->assertInstanceOf(ArrayKeyable::class, EnumerableTest_Gender::Male);
        $this->assertSame(1, EnumerableTest_Gender::Male->asArrayKey());
        $this->assertSame('W', EnumerableTest_AcceptStatus::Waiting->asArrayKey());
        $this->assertSame('Spring', EnumerableTest_Season::Spring->asArrayKey());
        $this->assertSame(13, EnumerableTest_Prefecture::valueOf(13)?->asArrayKey());

        $this->assertSame(1, Arrays::asKey(EnumerableTest_Gender::Male));
        $this->assertSame('W', Arrays::asKey(EnumerableTest_AcceptStatus::Waiting));
        $this->assertSame('Spring', Arrays::asKey(EnumerableTest_Season::Spring));
        $this->assertSame(13, Arrays::asKey(EnumerableTest_Prefecture::valueOf(13)));
        $this->assertSame(7, Arrays::asKey(EnumerableTest_Flag::One) + 6);
    }

    public function test_unsupportedKeyType(): void
    {
        $waiting = EnumerableTest_AcceptStatus::Waiting;

        // The value that can not be used as the array key (e.g. array) matches nothing
        $this->assertNull(EnumerableTest_AcceptStatus::fieldOf('aliases', ['a']));
        $this->assertNull(EnumerableTest_AcceptStatus::fieldOf('aliases', 'a'));
        $this->assertNull(EnumerableTest_AcceptStatus::fieldOf('color', ['orange']));
        $this->assertFalse($waiting->equals(['W']));

        // The field value that can not be used as the array key is not included in the index
        $this->assertSame(['a'], $waiting->original('aliases'));
        $this->assertSame(EnumerableTest_AcceptStatus::Waiting, EnumerableTest_AcceptStatus::valueOf('W'));

        // maps() requires the array key
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("The field 'aliases' of " . EnumerableTest_AcceptStatus::class . "::Waiting can not be used as the key of map.");

        EnumerableTest_AcceptStatus::maps('aliases');
    }
}


enum EnumerableTest_Flag: string implements Enumeration
{
    use Enumerable;

    case Blank = '';
    case Zero  = '0';
    case One   = '1';
}

#[Translatable(['label'], group: 'enumerable')]
enum EnumerableTest_Gender: int implements Enumeration
{
    use Enumerable;

    #[Meta(label: 'Male')]
    case Male = 1;

    #[Meta(label: 'Female')]
    case Female = 2;
}

enum EnumerableTest_AcceptStatus: string implements Enumeration
{
    use Enumerable;

    #[Meta(label: '待機中', color: 'orange', icon: 'far fa-clock', group: 'open', tags: ['x', 'y'], count: 3, ratio: 0.5, aliases: ['a'])]
    case Waiting = 'W';

    #[Meta(label: '受理', color: 'green', icon: 'fas fa-check-circle', group: 'closed', ratio: 1.0, aliases: ['b'])]
    case Accepted = 'A';

    #[Meta(label: '却下', color: 'red', icon: 'fas fa-times-circle', group: 'closed', ratio: 0.25, aliases: ['c'])]
    case Rejected = 'R';

    /**
     * @param  mixed             $current
     * @param  array<mixed>|null $context
     * @return array<int, self>
     */
    public static function nexts(mixed $current, array|null $context = null): array
    {
        switch ($context['role'] ?? null) {
            case 'operator':
                $current = self::valueOf($current);
                if ($current === self::Waiting) {
                    return self::cases();
                }
                return $current === null ? [] : [$current];

            case 'admin':
                return self::lists();
        }

        return [];
    }
}

#[Translatable(['label'], group: 'enumerable')]
enum EnumerableTest_Season implements Enumeration
{
    use Enumerable;

    #[Meta(label: '春')]
    case Spring;

    #[Meta(label: 'Summer')]
    case Summer;

    #[Meta(label: 'Autumn')]
    case Autumn;

    #[Meta(label: 'Winter')]
    case Winter;
}

/**
 * Sample of the enumeration that is built from the master table of the database.
 */
class EnumerableTest_Prefecture implements Enumeration
{
    use Enumerable;

    /** @var array<int, self>|null */
    private static array|null $cache = null;

    /**
     * Replace the cases (that simulates the master table was changed).
     *
     * @param array<int, self>|null $cases null for reload from database
     */
    public static function replace(array|null $cases = null): void
    {
        self::$cache = $cases;
    }

    final public function __construct(
        public readonly int $value,
        public readonly string $name,
        public readonly string $label,
    ) {}

    /**
     * @return array<int, self>
     */
    public static function cases(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $pdo = new \PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE prefecture (prefecture_id INTEGER PRIMARY KEY, code TEXT NOT NULL, name TEXT NOT NULL)');
        $pdo->exec("INSERT INTO prefecture (prefecture_id, code, name) VALUES (27, 'osaka', '大阪府'), (1, 'hokkaido', '北海道'), (13, 'tokyo', '東京都')");

        $cases = [];
        foreach ($pdo->query('SELECT prefecture_id AS value, code AS name, name AS label FROM prefecture ORDER BY prefecture_id ASC', \PDO::FETCH_ASSOC) ?: [] as $row) {
            $cases[] = new self((int) $row['value'], (string) $row['name'], (string) $row['label']);
        }
        return self::$cache = $cases;
    }
}

/**
 * Translatable: some fields (label and label_short) are translated, but label_min is not translated even if the dictionary has it.
 */
#[Translatable(['label', 'label_short'], group: 'enumerable')]
enum EnumerableTest_Multi: int implements Enumeration
{
    use Enumerable;

    #[Meta(label: 'Sunday', label_short: 'Sun', label_min: 'Su')]
    case Sunday = 0;

    #[Meta(label: 'Monday', label_short: 'Mon', label_min: 'Mo')]
    case Monday = 1;
}

/**
 * Translatable: no field is translated even if the dictionary has it.
 */
#[Translatable(group: 'enumerable')]
enum EnumerableTest_EmptyFields: int implements Enumeration
{
    use Enumerable;

    #[Meta(label: 'Sunday')]
    case Sunday = 0;
}

/**
 * Translatable: the attribute is invalid, so that the exception is thrown when it is used.
 */
#[Translatable(['label', 'value'])]
enum EnumerableTest_InvalidTranslatable: int implements Enumeration
{
    use Enumerable;

    case One = 1;
}

/**
 * The class that has the Translatable attribute (for the test of the inheritance).
 */
#[Translatable(['label'], group: 'enumerable')]
class EnumerableTest_Base implements Enumeration
{
    use Enumerable;

    final public function __construct(
        public readonly int $value,
        public readonly string $name,
        public readonly string $label,
        public readonly string $label_short,
    ) {}

    /**
     * @return array<int, static>
     */
    public static function cases(): array
    {
        return [new static(1, 'one', 'One', 'O')];
    }
}

/**
 * The child class inherits the Translatable attribute of the parent class.
 */
class EnumerableTest_Child extends EnumerableTest_Base {}

/**
 * The grandchild class inherits the Translatable attribute of the parent class through the child class.
 */
class EnumerableTest_Grandchild extends EnumerableTest_Child {}

/**
 * The child class has own Translatable attribute, so that the attribute of the parent class is not used.
 */
#[Translatable(['label_short'], group: 'enumerable')]
class EnumerableTest_Own extends EnumerableTest_Base {}
