<?php

declare(strict_types=1);

namespace Rebet\Tests\Tools\Translation;

use Override;
use Rebet\Application\App;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Translation\FileDictionary;
use Rebet\Tools\Translation\Translator;

class TranslatorTest extends RebetTestCase
{
    #[Override]
    public function setUp(): void
    {
        parent::setUp();
        App::setLocale('ja');
    }

    public function test_addResourceTo(): void
    {
        $this->assertFalse(in_array('/path/to/resource', FileDictionary::config('resources.i18n'), true));
        Translator::addResourceTo(FileDictionary::class, '/path/to/resource');
        $this->assertTrue(in_array('/path/to/resource', FileDictionary::config('resources.i18n'), true));
    }

    public function test_dictionary(): void
    {
        $this->assertInstanceOf(FileDictionary::class, Translator::dictionary());
    }

    public function test_getLocale(): void
    {
        $this->assertSame('ja', Translator::getLocale());
        App::setLocale('en');
        $this->assertSame('en', Translator::getLocale());
        App::setLocale('de');
        $this->assertSame('de', Translator::getLocale());
    }

    public function test_setLocale(): void
    {
        $this->assertSame('ja', Translator::getLocale());
        Translator::setLocale('en');
        $this->assertSame('en', Translator::getLocale());
        App::setLocale('de');
        $this->assertSame('en', Translator::getLocale());
    }

    public function test_getFallbackLocale(): void
    {
        $this->assertSame('en', Translator::getFallbackLocale());
        App::setLocale('ja', 'ja');
        $this->assertSame('ja', Translator::getFallbackLocale());
        App::setLocale('de', 'en');
        $this->assertSame('en', Translator::getFallbackLocale());
        Translator::setLocale('en_AU', 'en');
        $this->assertSame('en', Translator::getFallbackLocale());
        App::setLocale('ja', 'ja');
        $this->assertSame('en', Translator::getFallbackLocale());
    }

    public function test_clear(): void
    {
        $old = Translator::dictionary();
        Translator::reset();
        $new = Translator::dictionary();
        $this->assertNotSame($old, $new);
    }

    public function test_grammar(): void
    {
        $this->assertSame(':last_name :first_name', Translator::grammar('message', 'full_name'));
        $this->assertSame(':first_name :last_name', Translator::grammar('message', 'full_name', null, 'en'));
        $this->assertSame(':first_name :last_name', Translator::grammar('message', 'full_name', null, 'en_AU'));
    }

    public function test_get(): void
    {
        $this->assertNull(Translator::get(null));
        $this->assertNull(Translator::get('message'));
        $this->assertNull(Translator::get('message.nothing'));
        $this->assertSame('ようこそ、:name様', Translator::get('message.welcome'));
        $this->assertSame('ようこそ、太郎様', Translator::get('message.welcome', ['name' => '太郎']));
        $this->assertSame('Hello, Bob.', Translator::get('message.welcome', ['name' => 'Bob'], null, true, 'en'));
        $this->assertSame('Hello, Bob.', Translator::get('message.welcome', ['name' => 'Bob'], null, true, 'de'));
    }

    public function test_addResourceTo_otherDictionary(): void
    {
        // The resource is not added when the dictionary is not the given class
        $before = FileDictionary::config('resources.i18n');
        Translator::addResourceTo(TranslatorTest_OtherDictionary::class, '/path/to/other');
        $this->assertSame($before, FileDictionary::config('resources.i18n'));
    }

    public function test_grammar_nestedAndDefault(): void
    {
        $this->assertSame('is-danger', Translator::grammar('message', 'errors.class'));
        $this->assertSame(['red', '#333'], Translator::grammar('message', 'errors.color'));
        $this->assertSame('default', Translator::grammar('message', 'nothing', 'default'));
        $this->assertSame('default', Translator::grammar('nothing_group', 'nothing', 'default'));
    }

    public function test_get_selectorAndReplacement(): void
    {
        $this->assertSame('これはリンゴです。', Translator::get('message.sample', ['amount' => 1], 1));
        $this->assertSame('これらは3個のリンゴです。', Translator::get('message.sample', ['amount' => 3], 3));
        $this->assertSame('There are 3 apples.', Translator::get('message.sample', ['amount' => 3], 3, true, 'en'));
        $this->assertNull(Translator::get('message.sample'));
    }

    public function test_get_arrayReplacementUsesDelimiterOfLocale(): void
    {
        $this->assertSame('ようこそ、A／B様', Translator::get('message.welcome', ['name' => ['A', 'B']]));
        $this->assertSame('Hello, A, B.', Translator::get('message.welcome', ['name' => ['A', 'B']], null, true, 'en'));
    }

    public function test_get_recursive(): void
    {
        $this->assertSame('指定のページが見つかりません', Translator::get('message.http.404.title'));
        $this->assertSame('ようこそ、:name様', Translator::get('message.custom.welcome'));
        $this->assertNull(Translator::get('message.custom.welcome', [], null, false));
        $this->assertSame('指定のページが見つかりません', Translator::get('message.http.404.title', [], null, false));
        $this->assertNull(Translator::get('nothing_group.key'));
    }

    public function test_get_fallbackLocale(): void
    {
        // The fallback locale is used when the translation is not found in the locale
        $this->assertSame('Hello, Bob.', Translator::get('message.welcome', ['name' => 'Bob'], null, true, 'de'));
        App::setLocale('de', 'ja');
        $this->assertSame('ようこそ、Bob様', Translator::get('message.welcome', ['name' => 'Bob']));
        App::setLocale('ja', 'ja');
        $this->assertSame('ようこそ、Bob様', Translator::get('message.welcome', ['name' => 'Bob']));
    }

    public function test_replace_order(): void
    {
        // The longer key is replaced first, so that ':name' does not break ':name_kana'
        $this->assertSame('太郎 タロウ', Translator::replace(':name :name_kana', ['name' => '太郎', 'name_kana' => 'タロウ']));
        $this->assertSame('タロウ 太郎', Translator::replace(':name_kana :name', ['name' => '太郎', 'name_kana' => 'タロウ']));
    }

    public function test_replace_valueIsNotReplacedAgain(): void
    {
        // The replaced value is never replaced again even if it contains the placeholder
        $this->assertSame('A=:b, B=b', Translator::replace('A=:a, B=:b', ['a' => ':b', 'b' => 'b']));
        $this->assertSame('ようこそ、:name様', Translator::get('message.welcome', ['name' => ':name']));
    }

    public function test_get_iterableReplacementUsesDelimiterOfLocale(): void
    {
        $this->assertSame('ようこそ、A／B様', Translator::get('message.welcome', ['name' => new \ArrayObject(['A', 'B'])]));
        $this->assertSame('Hello, A, B.', Translator::get('message.welcome', ['name' => new \ArrayObject(['A', 'B'])], null, true, 'en'));
        // The scalar replacement does not need the delimiter
        $this->assertSame('ようこそ、A様', Translator::get('message.welcome', ['name' => 'A']));
    }

    public function test_replace_scalarValues(): void
    {
        $this->assertSame('null=[], int=[1], float=[1.5], true=[1], false=[]', Translator::replace(
            'null=[:null], int=[:int], float=[:float], true=[:true], false=[:false]',
            ['null' => null, 'int' => 1, 'float' => 1.5, 'true' => true, 'false' => false],
        ));
        $this->assertNull(Translator::replace(null, ['name' => 'foo']));
        $this->assertSame('no placeholder', Translator::replace('no placeholder', ['name' => 'foo']));
    }

    public function test_ordinalize_locale(): void
    {
        // The locale can be given explicitly, and the number is returned as it is for the locale without ordinalize
        $this->assertSame('2nd', Translator::ordinalize(2, 'en'));
        $this->assertSame('2', Translator::ordinalize(2, 'xx'));
        $this->assertSame('2', Translator::ordinalize(2));
    }

    public function test_replace(): void
    {
        $this->assertSame(':last_name :first_name', Translator::replace(':last_name :first_name', []));
        $this->assertSame('山田 太郎', Translator::replace(':last_name :first_name', ['first_name' => '太郎', 'last_name' => '山田']));
        $this->assertSame('John Smith', Translator::replace(':first_name :last_name', ['first_name' => 'John', 'last_name' => 'Smith']));

        $this->assertSame('1, 2, 3', Translator::replace(':array', ['array' => [1, 2, 3]]));
        $this->assertSame('1／2／3', Translator::replace(':array', ['array' => [1, 2, 3]], '／'));
        $this->assertSame('1, 2, 3', Translator::replace(':array', ['array' => new \ArrayObject([1, 2, 3])]));
    }

    public function test_setOrdinalize(): void
    {
        $this->assertSame('1', Translator::ordinalize(1));
        $this->assertSame('2', Translator::ordinalize(2));
        $this->assertSame('3', Translator::ordinalize(3));
        Translator::setOrdinalize('ja', fn(int $num) => "{$num}番目");
        $this->assertSame('1番目', Translator::ordinalize(1));
        $this->assertSame('2番目', Translator::ordinalize(2));
        $this->assertSame('3番目', Translator::ordinalize(3));
    }

    public function test_ordinalize(): void
    {
        //en
        App::setLocale('en');
        $this->assertSame('1st', Translator::ordinalize(1));
        $this->assertSame('2nd', Translator::ordinalize(2));
        $this->assertSame('3rd', Translator::ordinalize(3));
        $this->assertSame('4th', Translator::ordinalize(4));
        $this->assertSame('11th', Translator::ordinalize(11));
        $this->assertSame('12th', Translator::ordinalize(12));
        $this->assertSame('13th', Translator::ordinalize(13));
        $this->assertSame('14th', Translator::ordinalize(14));
        $this->assertSame('21st', Translator::ordinalize(21));
        $this->assertSame('22nd', Translator::ordinalize(22));
        $this->assertSame('23rd', Translator::ordinalize(23));
        $this->assertSame('24th', Translator::ordinalize(24));
        $this->assertSame('111th', Translator::ordinalize(111));
        $this->assertSame('112th', Translator::ordinalize(112));
        $this->assertSame('113th', Translator::ordinalize(113));
        $this->assertSame('114th', Translator::ordinalize(114));
        $this->assertSame('121st', Translator::ordinalize(121));
        $this->assertSame('122nd', Translator::ordinalize(122));
        $this->assertSame('123rd', Translator::ordinalize(123));
        $this->assertSame('124th', Translator::ordinalize(124));
    }
}

/**
 * The other dictionary class for the test of addResourceTo().
 */
abstract class TranslatorTest_OtherDictionary implements \Rebet\Tools\Translation\Dictionary {}
