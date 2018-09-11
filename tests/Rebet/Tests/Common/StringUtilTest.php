<?php
namespace Rebet\Tests\Common;

use Rebet\Tests\RebetTestCase;
use Rebet\Common\StringUtil;

class StringUtilTest extends RebetTestCase {
    public function test_lbtrim() {
        $this->assertNull(StringUtil::lbtrim(null, '.'));
        $this->assertSame('', StringUtil::lbtrim('', '.'));
        $this->assertSame('2.3', StringUtil::lbtrim('1.2.3', '.'));
        $this->assertSame('1.2.3', StringUtil::lbtrim('1.2.3', ','));

        $this->assertNull(StringUtil::lbtrim(null, '.', false));
        $this->assertSame('', StringUtil::lbtrim('', '.', false));
        $this->assertSame('.2.3', StringUtil::lbtrim('1.2.3', '.', false));
        $this->assertSame('1.2.3', StringUtil::lbtrim('1.2.3', ',', false));
    }

    public function test_latrim() {
        $this->assertNull(StringUtil::latrim(null, '.'));
        $this->assertSame('', StringUtil::latrim('', '.'));
        $this->assertSame('1', StringUtil::latrim('1.2.3', '.'));
        $this->assertSame('1.2.3', StringUtil::latrim('1.2.3', ','));

        $this->assertNull(StringUtil::latrim(null, '.', false));
        $this->assertSame('', StringUtil::latrim('', '.', false));
        $this->assertSame('1.', StringUtil::latrim('1.2.3', '.', false));
        $this->assertSame('1.2.3', StringUtil::latrim('1.2.3', ',', false));
    }

    public function test_rbtrim() {
        $this->assertNull(StringUtil::rbtrim(null, '.'));
        $this->assertSame('', StringUtil::rbtrim('', '.'));
        $this->assertSame('3', StringUtil::rbtrim('1.2.3', '.'));
        $this->assertSame('1.2.3', StringUtil::rbtrim('1.2.3', ','));

        $this->assertNull(StringUtil::rbtrim(null, '.', false));
        $this->assertSame('', StringUtil::rbtrim('', '.', false));
        $this->assertSame('.3', StringUtil::rbtrim('1.2.3', '.', false));
        $this->assertSame('1.2.3', StringUtil::rbtrim('1.2.3', ',', false));
    }

    public function test_ratrim() {
        $this->assertNull(StringUtil::ratrim(null, '.'));
        $this->assertSame('', StringUtil::ratrim('', '.'));
        $this->assertSame('1.2', StringUtil::ratrim('1.2.3', '.'));
        $this->assertSame('1.2.3', StringUtil::ratrim('1.2.3', ','));

        $this->assertNull(StringUtil::ratrim(null, '.', false));
        $this->assertSame('', StringUtil::ratrim('', '.', false));
        $this->assertSame('1.2.', StringUtil::ratrim('1.2.3', '.', false));
        $this->assertSame('1.2.3', StringUtil::ratrim('1.2.3', ',', false));
    }

    public function test_ltrim() {
        $this->assertNull(StringUtil::ltrim(null));
        $this->assertSame('', StringUtil::ltrim(''));
        $this->assertSame('abc   ', StringUtil::ltrim('   abc   '));
        $this->assertSame('abc111', StringUtil::ltrim('111abc111','1'));
        $this->assertSame('1abc21212', StringUtil::ltrim('12121abc21212','12'));
        $this->assertSame('全角　　　', StringUtil::ltrim('　　　全角　　　','　'));
    }

    public function test_rtrim() {
        $this->assertNull(StringUtil::rtrim(null));
        $this->assertSame('', StringUtil::rtrim(''));
        $this->assertSame('   abc', StringUtil::rtrim('   abc   '));
        $this->assertSame('111abc', StringUtil::rtrim('111abc111','1'));
        $this->assertSame('12121abc2', StringUtil::rtrim('12121abc21212','12'));
        $this->assertSame('　　　全角', StringUtil::rtrim('　　　全角　　　','　'));
    }

    public function test_startWith() {
        $this->assertFalse(StringUtil::startWith(null, 'abc'));
        $this->assertFalse(StringUtil::startWith('', 'abc'));
        $this->assertFalse(StringUtil::startWith('123abc', 'abc'));
        $this->assertTrue(StringUtil::startWith('abc123', 'abc'));
    }

    public function test_endtWith() {
        $this->assertFalse(StringUtil::endWith(null, 'abc'));
        $this->assertFalse(StringUtil::endWith('', 'abc'));
        $this->assertTrue(StringUtil::endWith('123abc', 'abc'));
        $this->assertFalse(StringUtil::endWith('abc123', 'abc'));
    }

    public function test_checkDependenceChar() {
        $this->assertSame([], StringUtil::checkDependenceChar(null));
        $this->assertSame([2 => '♬', 4 => '♥'], StringUtil::checkDependenceChar('あ①♬㈱♥'));
        $this->assertSame([1 => '①', 2 => '♬', 3 => '㈱', 4 => '♥'], StringUtil::checkDependenceChar('あ①♬㈱♥', 'iso-2022-jp'));
        $this->assertSame([], StringUtil::checkDependenceChar('あ①♬㈱♥', 'UTF-8'));
    }

    public function test_toCharArray() {
        $this->assertSame([], StringUtil::toCharArray(null));
        $this->assertSame([], StringUtil::toCharArray(''));
        $this->assertSame(['a','b','c'], StringUtil::toCharArray('abc'));
        $this->assertSame(['あ','い','う'], StringUtil::toCharArray('あいう'));
    }

    public function test_camelize() {
        $this->assertNull(StringUtil::camelize(null));
        $this->assertSame('', StringUtil::camelize(''));
        $this->assertSame('SnakeCase', StringUtil::camelize('snake_case'));
        $this->assertSame('CamelCase', StringUtil::camelize('CamelCase'));
        $this->assertSame('CamelCase', StringUtil::camelize('camelCase'));
    }

    public function test_snakize() {
        $this->assertNull(StringUtil::snakize(null));
        $this->assertSame('', StringUtil::snakize(''));
        $this->assertSame('snake_case', StringUtil::snakize('snake_case'));
        $this->assertSame('camel_case', StringUtil::snakize('CamelCase'));
        $this->assertSame('camel_case', StringUtil::snakize('camelCase'));
    }

    public function test_capitalize() {
        $this->assertNull(StringUtil::capitalize(null));
        $this->assertSame('', StringUtil::capitalize(''));
        $this->assertSame('Snake_case', StringUtil::capitalize('snake_case'));
        $this->assertSame('CamelCase', StringUtil::capitalize('CamelCase'));
        $this->assertSame('CamelCase', StringUtil::capitalize('camelCase'));
    }

    public function test_uncapitalize() {
        $this->assertNull(StringUtil::uncapitalize(null));
        $this->assertSame('', StringUtil::uncapitalize(''));
        $this->assertSame('snake_case', StringUtil::uncapitalize('snake_case'));
        $this->assertSame('camelCase', StringUtil::uncapitalize('CamelCase'));
        $this->assertSame('camelCase', StringUtil::uncapitalize('camelCase'));
    }

    public function test_indent() {
        $this->assertNull(StringUtil::indent(null));
        $this->assertSame('', StringUtil::indent(''));

        $this->assertSame("\t1st", StringUtil::indent("1st"));
        $this->assertSame("\t1st\n\t2nd\n\t3rd", StringUtil::indent("1st\n2nd\n3rd"));
        $this->assertSame("    1st\n    2nd\n    3rd", StringUtil::indent("1st\n2nd\n3rd", 1, '    '));
        $this->assertSame(">>1st\n>>2nd\n>>3rd", StringUtil::indent("1st\n2nd\n3rd", 1, '>>'));
        $this->assertSame(">>>>1st\n>>>>2nd\n>>>>3rd", StringUtil::indent("1st\n2nd\n3rd", 2, '>>'));

        $this->assertSame("\n\t1st", StringUtil::indent("\n1st"));
        $this->assertSame("\n\t1st\n\t2nd\n\t3rd", StringUtil::indent("\n1st\n2nd\n3rd"));
        $this->assertSame("\n    1st\n    2nd\n    3rd", StringUtil::indent("\n1st\n2nd\n3rd", 1, '    '));
        $this->assertSame("\n>>1st\n>>2nd\n>>3rd", StringUtil::indent("\n1st\n2nd\n3rd", 1, '>>'));
        $this->assertSame("\n>>>>1st\n>>>>2nd\n>>>>3rd", StringUtil::indent("\n1st\n2nd\n3rd", 2, '>>'));

        $this->assertSame("\t1st\n", StringUtil::indent("1st\n"));
        $this->assertSame("\t1st\n\t2nd\n\t3rd\n", StringUtil::indent("1st\n2nd\n3rd\n"));
        $this->assertSame("    1st\n    2nd\n    3rd\n", StringUtil::indent("1st\n2nd\n3rd\n", 1, '    '));
        $this->assertSame(">>1st\n>>2nd\n>>3rd\n", StringUtil::indent("1st\n2nd\n3rd\n", 1, '>>'));
        $this->assertSame(">>>>1st\n>>>>2nd\n>>>>3rd\n", StringUtil::indent("1st\n2nd\n3rd\n", 2, '>>'));
    }
}
