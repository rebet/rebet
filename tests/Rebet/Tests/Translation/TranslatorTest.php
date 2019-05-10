<?php
namespace Rebet\Tests\Translation;

use Rebet\Foundation\App;
use Rebet\Tests\RebetTestCase;
use Rebet\Translation\FileDictionary;
use Rebet\Translation\Translator;

class TranslatorTest extends RebetTestCase
{
    public function test_addResourceTo()
    {
        $this->assertFalse(in_array('/path/to/resource', FileDictionary::config('resources.i18n'), true));
        Translator::addResourceTo(FileDictionary::class, '/path/to/resource');
        $this->assertTrue(in_array('/path/to/resource', FileDictionary::config('resources.i18n'), true));
    }

    public function test_dictionary()
    {
        $this->assertInstanceOf(FileDictionary::class, Translator::dictionary());
    }

    public function test_getLocale()
    {
        $this->assertSame('ja', Translator::getLocale());
        App::setLocale('en');
        $this->assertSame('en', Translator::getLocale());
        App::setLocale('de');
        $this->assertSame('de', Translator::getLocale());
    }

    public function test_setLocale()
    {
        $this->assertSame('ja', Translator::getLocale());
        Translator::setLocale('en');
        $this->assertSame('en', Translator::getLocale());
        App::setLocale('de');
        $this->assertSame('en', Translator::getLocale());
    }

    public function test_getFallbackLocale()
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

    public function test_clear()
    {
        $old = Translator::dictionary();
        Translator::clear();
        $new = Translator::dictionary();
        $this->assertNotSame($old, $new);
    }

    public function test_grammar()
    {
        $this->assertSame(':last_name :first_name', Translator::grammar('message', 'full_name'));
        $this->assertSame(':first_name :last_name', Translator::grammar('message', 'full_name', null, 'en'));
        $this->assertSame(':first_name :last_name', Translator::grammar('message', 'full_name', null, 'en_AU'));
    }

    public function test_get()
    {
        $this->assertNull(Translator::get(null));
        $this->assertNull(Translator::get('message'));
        $this->assertNull(Translator::get('message.nothing'));
        $this->assertSame('ようこそ、:name様', Translator::get('message.welcome'));
        $this->assertSame('ようこそ、太郎様', Translator::get('message.welcome', ['name' => '太郎']));
        $this->assertSame('Hello, Bob.', Translator::get('message.welcome', ['name' => 'Bob'], null, true, 'en'));
        $this->assertSame('Hello, Bob.', Translator::get('message.welcome', ['name' => 'Bob'], null, true, 'de'));
    }

    public function test_replace()
    {
        $this->assertSame(':last_name :first_name', Translator::replace(':last_name :first_name', []));
        $this->assertSame('山田 太郎', Translator::replace(':last_name :first_name', ['first_name' => '太郎', 'last_name' => '山田']));
        $this->assertSame('John Smith', Translator::replace(':first_name :last_name', ['first_name' => 'John', 'last_name' => 'Smith']));

        $this->assertSame('1, 2, 3', Translator::replace(':array', ['array' => [1, 2, 3]]));
        $this->assertSame('1／2／3', Translator::replace(':array', ['array' => [1, 2, 3]], '／'));
        $this->assertSame('1, 2, 3', Translator::replace(':array', ['array' => new \ArrayObject([1, 2, 3])]));
    }

    public function test_setOrdinalize()
    {
        $this->assertSame('1', Translator::ordinalize(1));
        $this->assertSame('2', Translator::ordinalize(2));
        $this->assertSame('3', Translator::ordinalize(3));
        Translator::setOrdinalize('ja', function (int $num) { return "{$num}番目"; });
        $this->assertSame('1番目', Translator::ordinalize(1));
        $this->assertSame('2番目', Translator::ordinalize(2));
        $this->assertSame('3番目', Translator::ordinalize(3));
    }

    public function test_ordinalize()
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
