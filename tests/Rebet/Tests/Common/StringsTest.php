<?php
namespace Rebet\Tests\Common;

use Rebet\Tests\RebetTestCase;
use Rebet\Common\Strings;

class StringsTest extends RebetTestCase
{
    public function test_lbtrim()
    {
        $this->assertNull(Strings::lbtrim(null, '.'));
        $this->assertSame('', Strings::lbtrim('', '.'));
        $this->assertSame('2.3', Strings::lbtrim('1.2.3', '.'));
        $this->assertSame('1.2.3', Strings::lbtrim('1.2.3', ','));

        $this->assertNull(Strings::lbtrim(null, '.', false));
        $this->assertSame('', Strings::lbtrim('', '.', false));
        $this->assertSame('.2.3', Strings::lbtrim('1.2.3', '.', false));
        $this->assertSame('1.2.3', Strings::lbtrim('1.2.3', ',', false));
    }

    public function test_latrim()
    {
        $this->assertNull(Strings::latrim(null, '.'));
        $this->assertSame('', Strings::latrim('', '.'));
        $this->assertSame('1', Strings::latrim('1.2.3', '.'));
        $this->assertSame('1.2.3', Strings::latrim('1.2.3', ','));

        $this->assertNull(Strings::latrim(null, '.', false));
        $this->assertSame('', Strings::latrim('', '.', false));
        $this->assertSame('1.', Strings::latrim('1.2.3', '.', false));
        $this->assertSame('1.2.3', Strings::latrim('1.2.3', ',', false));
    }

    public function test_rbtrim()
    {
        $this->assertNull(Strings::rbtrim(null, '.'));
        $this->assertSame('', Strings::rbtrim('', '.'));
        $this->assertSame('3', Strings::rbtrim('1.2.3', '.'));
        $this->assertSame('1.2.3', Strings::rbtrim('1.2.3', ','));

        $this->assertNull(Strings::rbtrim(null, '.', false));
        $this->assertSame('', Strings::rbtrim('', '.', false));
        $this->assertSame('.3', Strings::rbtrim('1.2.3', '.', false));
        $this->assertSame('1.2.3', Strings::rbtrim('1.2.3', ',', false));
    }

    public function test_ratrim()
    {
        $this->assertNull(Strings::ratrim(null, '.'));
        $this->assertSame('', Strings::ratrim('', '.'));
        $this->assertSame('1.2', Strings::ratrim('1.2.3', '.'));
        $this->assertSame('1.2.3', Strings::ratrim('1.2.3', ','));

        $this->assertNull(Strings::ratrim(null, '.', false));
        $this->assertSame('', Strings::ratrim('', '.', false));
        $this->assertSame('1.2.', Strings::ratrim('1.2.3', '.', false));
        $this->assertSame('1.2.3', Strings::ratrim('1.2.3', ',', false));
    }

    public function test_ltrim()
    {
        $this->assertNull(Strings::ltrim(null));
        $this->assertSame('', Strings::ltrim(''));
        $this->assertSame('abc   ', Strings::ltrim('   abc   '));
        $this->assertSame('abc111', Strings::ltrim('111abc111', '1'));
        $this->assertSame('1abc21212', Strings::ltrim('12121abc21212', '12'));
        $this->assertSame('全角　　　', Strings::ltrim('　　　全角　　　', '　'));
    }

    public function test_rtrim()
    {
        $this->assertNull(Strings::rtrim(null));
        $this->assertSame('', Strings::rtrim(''));
        $this->assertSame('   abc', Strings::rtrim('   abc   '));
        $this->assertSame('111abc', Strings::rtrim('111abc111', '1'));
        $this->assertSame('12121abc2', Strings::rtrim('12121abc21212', '12'));
        $this->assertSame('　　　全角', Strings::rtrim('　　　全角　　　', '　'));
    }

    public function test_startWith()
    {
        $this->assertFalse(Strings::startWith(null, 'abc'));
        $this->assertFalse(Strings::startWith('', 'abc'));
        $this->assertFalse(Strings::startWith('123abc', 'abc'));
        $this->assertTrue(Strings::startWith('abc123', 'abc'));
    }

    public function test_endtWith()
    {
        $this->assertFalse(Strings::endWith(null, 'abc'));
        $this->assertFalse(Strings::endWith('', 'abc'));
        $this->assertTrue(Strings::endWith('123abc', 'abc'));
        $this->assertFalse(Strings::endWith('abc123', 'abc'));
    }

    public function test_checkDependenceChar()
    {
        $this->assertSame([], Strings::checkDependenceChar(null));
        $this->assertSame([2 => '♬', 4 => '♥'], Strings::checkDependenceChar('あ①♬㈱♥'));
        $this->assertSame([1 => '①', 2 => '♬', 3 => '㈱', 4 => '♥'], Strings::checkDependenceChar('あ①♬㈱♥', 'iso-2022-jp'));
        $this->assertSame([], Strings::checkDependenceChar('あ①♬㈱♥', 'UTF-8'));
    }

    public function test_toCharArray()
    {
        $this->assertSame([], Strings::toCharArray(null));
        $this->assertSame([], Strings::toCharArray(''));
        $this->assertSame(['a','b','c'], Strings::toCharArray('abc'));
        $this->assertSame(['あ','い','う'], Strings::toCharArray('あいう'));
    }

    public function test_camelize()
    {
        $this->assertNull(Strings::camelize(null));
        $this->assertSame('', Strings::camelize(''));
        $this->assertSame('SnakeCase', Strings::camelize('snake_case'));
        $this->assertSame('CamelCase', Strings::camelize('CamelCase'));
        $this->assertSame('CamelCase', Strings::camelize('camelCase'));
    }

    public function test_snakize()
    {
        $this->assertNull(Strings::snakize(null));
        $this->assertSame('', Strings::snakize(''));
        $this->assertSame('snake_case', Strings::snakize('snake_case'));
        $this->assertSame('camel_case', Strings::snakize('CamelCase'));
        $this->assertSame('camel_case', Strings::snakize('camelCase'));
    }

    public function test_capitalize()
    {
        $this->assertNull(Strings::capitalize(null));
        $this->assertSame('', Strings::capitalize(''));
        $this->assertSame('Snake_case', Strings::capitalize('snake_case'));
        $this->assertSame('CamelCase', Strings::capitalize('CamelCase'));
        $this->assertSame('CamelCase', Strings::capitalize('camelCase'));
    }

    public function test_uncapitalize()
    {
        $this->assertNull(Strings::uncapitalize(null));
        $this->assertSame('', Strings::uncapitalize(''));
        $this->assertSame('snake_case', Strings::uncapitalize('snake_case'));
        $this->assertSame('camelCase', Strings::uncapitalize('CamelCase'));
        $this->assertSame('camelCase', Strings::uncapitalize('camelCase'));
    }

    public function test_indent()
    {
        $this->assertSame("\t", Strings::indent(null));
        $this->assertSame("\t", Strings::indent(''));

        $this->assertSame("\t1st", Strings::indent("1st"));
        $this->assertSame("\t1st\n\t2nd\n\t3rd", Strings::indent("1st\n2nd\n3rd"));
        $this->assertSame("    1st\n    2nd\n    3rd", Strings::indent("1st\n2nd\n3rd", 1, '    '));
        $this->assertSame(">>1st\n>>2nd\n>>3rd", Strings::indent("1st\n2nd\n3rd", 1, '>>'));
        $this->assertSame(">>>>1st\n>>>>2nd\n>>>>3rd", Strings::indent("1st\n2nd\n3rd", 2, '>>'));

        $this->assertSame("\n\t1st", Strings::indent("\n1st"));
        $this->assertSame("\n\t1st\n\t2nd\n\t3rd", Strings::indent("\n1st\n2nd\n3rd"));
        $this->assertSame("\n    1st\n    2nd\n    3rd", Strings::indent("\n1st\n2nd\n3rd", 1, '    '));
        $this->assertSame("\n>>1st\n>>2nd\n>>3rd", Strings::indent("\n1st\n2nd\n3rd", 1, '>>'));
        $this->assertSame("\n>>>>1st\n>>>>2nd\n>>>>3rd", Strings::indent("\n1st\n2nd\n3rd", 2, '>>'));

        $this->assertSame("\t1st\n", Strings::indent("1st\n"));
        $this->assertSame("\t1st\n\t2nd\n\t3rd\n", Strings::indent("1st\n2nd\n3rd\n"));
        $this->assertSame("    1st\n    2nd\n    3rd\n", Strings::indent("1st\n2nd\n3rd\n", 1, '    '));
        $this->assertSame(">>1st\n>>2nd\n>>3rd\n", Strings::indent("1st\n2nd\n3rd\n", 1, '>>'));
        $this->assertSame(">>>>1st\n>>>>2nd\n>>>>3rd\n", Strings::indent("1st\n2nd\n3rd\n", 2, '>>'));
    }

    public function test_contains()
    {
        $this->assertFalse(Strings::contains(null, ''));
        $this->assertTrue(Strings::contains('', ''));
        $this->assertFalse(Strings::contains('', 'a'));
        $this->assertTrue(Strings::contains('a', ''));
        $this->assertTrue(Strings::contains('fooabcbar', 'abc'));
        $this->assertTrue(Strings::contains('fooabcbar', 'ooabcb'));
        $this->assertFalse(Strings::contains('fooabcbar', 'ABC'));
    }
}
