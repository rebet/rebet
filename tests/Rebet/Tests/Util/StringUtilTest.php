<?php
namespace Rebet\Tests\Util;

use PHPUnit\Framework\TestCase;
use Rebet\Util\StringUtil;

class StringUtilTest extends TestCase {
    public function test_lbtrim() {
        $this->assertNull(StringUtil::lbtrim(null, '.'));
        $this->assertSame(StringUtil::lbtrim('', '.'), '');
        $this->assertSame(StringUtil::lbtrim('1.2.3', '.'), '2.3');
        $this->assertSame(StringUtil::lbtrim('1.2.3', ','), '1.2.3');

        $this->assertNull(StringUtil::lbtrim(null, '.', false));
        $this->assertSame(StringUtil::lbtrim('', '.', false), '');
        $this->assertSame(StringUtil::lbtrim('1.2.3', '.', false), '.2.3');
        $this->assertSame(StringUtil::lbtrim('1.2.3', ',', false), '1.2.3');
    }

    public function test_latrim() {
        $this->assertNull(StringUtil::latrim(null, '.'));
        $this->assertSame(StringUtil::latrim('', '.'), '');
        $this->assertSame(StringUtil::latrim('1.2.3', '.'), '1');
        $this->assertSame(StringUtil::latrim('1.2.3', ','), '1.2.3');

        $this->assertNull(StringUtil::latrim(null, '.', false));
        $this->assertSame(StringUtil::latrim('', '.', false), '');
        $this->assertSame(StringUtil::latrim('1.2.3', '.', false), '1.');
        $this->assertSame(StringUtil::latrim('1.2.3', ',', false), '1.2.3');
    }

    public function test_rbtrim() {
        $this->assertNull(StringUtil::rbtrim(null, '.'));
        $this->assertSame(StringUtil::rbtrim('', '.'), '');
        $this->assertSame(StringUtil::rbtrim('1.2.3', '.'), '3');
        $this->assertSame(StringUtil::rbtrim('1.2.3', ','), '1.2.3');

        $this->assertNull(StringUtil::rbtrim(null, '.', false));
        $this->assertSame(StringUtil::rbtrim('', '.', false), '');
        $this->assertSame(StringUtil::rbtrim('1.2.3', '.', false), '.3');
        $this->assertSame(StringUtil::rbtrim('1.2.3', ',', false), '1.2.3');
    }

    public function test_ratrim() {
        $this->assertNull(StringUtil::ratrim(null, '.'));
        $this->assertSame(StringUtil::ratrim('', '.'), '');
        $this->assertSame(StringUtil::ratrim('1.2.3', '.'), '1.2');
        $this->assertSame(StringUtil::ratrim('1.2.3', ','), '1.2.3');

        $this->assertNull(StringUtil::ratrim(null, '.', false));
        $this->assertSame(StringUtil::ratrim('', '.', false), '');
        $this->assertSame(StringUtil::ratrim('1.2.3', '.', false), '1.2.');
        $this->assertSame(StringUtil::ratrim('1.2.3', ',', false), '1.2.3');
    }

    public function test_ltrim() {
        $this->assertNull(StringUtil::ltrim(null));
        $this->assertSame(StringUtil::ltrim(''), '');
        $this->assertSame(StringUtil::ltrim('   abc   '), 'abc   ');
        $this->assertSame(StringUtil::ltrim('111abc111','1'), 'abc111');
        $this->assertSame(StringUtil::ltrim('12121abc21212','12'), '1abc21212');
        $this->assertSame(StringUtil::ltrim('　　　全角　　　','　'), '全角　　　');
    }

    public function test_rtrim() {
        $this->assertNull(StringUtil::rtrim(null));
        $this->assertSame(StringUtil::rtrim(''), '');
        $this->assertSame(StringUtil::rtrim('   abc   '), '   abc');
        $this->assertSame(StringUtil::rtrim('111abc111','1'), '111abc');
        $this->assertSame(StringUtil::rtrim('12121abc21212','12'), '12121abc2');
        $this->assertSame(StringUtil::rtrim('　　　全角　　　','　'), '　　　全角');
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
        $this->assertSame(StringUtil::checkDependenceChar(null), []);
        $this->assertSame(StringUtil::checkDependenceChar('あ①♬㈱♥'), [2 => '♬', 4 => '♥']);
        $this->assertSame(StringUtil::checkDependenceChar('あ①♬㈱♥', 'iso-2022-jp'), [1 => '①', 2 => '♬', 3 => '㈱', 4 => '♥']);
        $this->assertSame(StringUtil::checkDependenceChar('あ①♬㈱♥', 'UTF-8'), []);
    }

    public function test_toCharArray() {
        $this->assertSame(StringUtil::toCharArray(null), []);
        $this->assertSame(StringUtil::toCharArray(''), []);
        $this->assertSame(StringUtil::toCharArray('abc'), ['a','b','c']);
        $this->assertSame(StringUtil::toCharArray('あいう'), ['あ','い','う']);
    }

    public function test_camelize() {
        $this->assertNull(StringUtil::camelize(null));
        $this->assertSame(StringUtil::camelize(''), '');
        $this->assertSame(StringUtil::camelize('snake_case'), 'SnakeCase');
        $this->assertSame(StringUtil::camelize('CamelCase'), 'CamelCase');
        $this->assertSame(StringUtil::camelize('camelCase'), 'CamelCase');
    }

    public function test_snakize() {
        $this->assertNull(StringUtil::snakize(null));
        $this->assertSame(StringUtil::snakize(''), '');
        $this->assertSame(StringUtil::snakize('snake_case'), 'snake_case');
        $this->assertSame(StringUtil::snakize('CamelCase'), 'camel_case');
        $this->assertSame(StringUtil::snakize('camelCase'), 'camel_case');
    }

    public function test_capitalize() {
        $this->assertNull(StringUtil::capitalize(null));
        $this->assertSame(StringUtil::capitalize(''), '');
        $this->assertSame(StringUtil::capitalize('snake_case'), 'Snake_case');
        $this->assertSame(StringUtil::capitalize('CamelCase'), 'CamelCase');
        $this->assertSame(StringUtil::capitalize('camelCase'), 'CamelCase');
    }

    public function test_uncapitalize() {
        $this->assertNull(StringUtil::uncapitalize(null));
        $this->assertSame(StringUtil::uncapitalize(''), '');
        $this->assertSame(StringUtil::uncapitalize('snake_case'), 'snake_case');
        $this->assertSame(StringUtil::uncapitalize('CamelCase'), 'camelCase');
        $this->assertSame(StringUtil::uncapitalize('camelCase'), 'camelCase');
    }
}
