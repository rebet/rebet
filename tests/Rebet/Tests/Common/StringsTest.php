<?php
namespace Rebet\Tests\Common;

use Rebet\Common\Strings;
use Rebet\Tests\RebetTestCase;

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
        $this->assertSame('path/to/test/', Strings::ltrim('/path/to/test/', '/'));
        $this->assertSame('121abc21212', Strings::ltrim('12121abc21212', '12', 1));
        $this->assertSame('　全角　　　', Strings::ltrim('　　　全角　　　', '　', 2));
    }

    public function test_rtrim()
    {
        $this->assertNull(Strings::rtrim(null));
        $this->assertSame('', Strings::rtrim(''));
        $this->assertSame('   abc', Strings::rtrim('   abc   '));
        $this->assertSame('111abc', Strings::rtrim('111abc111', '1'));
        $this->assertSame('12121abc2', Strings::rtrim('12121abc21212', '12'));
        $this->assertSame('　　　全角', Strings::rtrim('　　　全角　　　', '　'));
        $this->assertSame('/path/to/test', Strings::rtrim('/path/to/test/', '/'));
        $this->assertSame('12121abc212', Strings::rtrim('12121abc21212', '12', 1));
        $this->assertSame('　　　全角　', Strings::rtrim('　　　全角　　　', '　', 2));
    }

    public function test_trim()
    {
        $this->assertNull(Strings::trim(null));
        $this->assertSame('', Strings::trim(''));
        $this->assertSame('abc', Strings::trim('   abc   '));
        $this->assertSame('abc', Strings::trim('111abc111', '1'));
        $this->assertSame('1abc2', Strings::trim('12121abc21212', '12'));
        $this->assertSame('全角', Strings::trim('　　　全角　　　', '　'));
        $this->assertSame('path/to/test', Strings::trim('/path/to/test/', '/'));
        $this->assertSame('121abc212', Strings::trim('12121abc21212', '12', 1));
        $this->assertSame('　全角　', Strings::trim('　　　全角　　　', '　', 2));
    }

    public function test_mbtrim()
    {
        $this->assertNull(Strings::mbtrim(null));
        $this->assertSame('', Strings::mbtrim(''));
        $this->assertSame('a b　c', Strings::mbtrim('   a b　c   '));
        $this->assertSame('a b　c', Strings::mbtrim(' 　 a b　c 　 '));
        $this->assertSame('a b　c', Strings::mbtrim('     　 a b　c 　    '));
    }

    public function test_startsWith()
    {
        $this->assertFalse(Strings::startsWith(null, 'abc'));
        $this->assertFalse(Strings::startsWith('', 'abc'));
        $this->assertFalse(Strings::startsWith('123abc', 'abc'));
        $this->assertTrue(Strings::startsWith('abc123', 'abc'));
    }

    public function test_endsWith()
    {
        $this->assertFalse(Strings::endsWith(null, 'abc'));
        $this->assertFalse(Strings::endsWith('', 'abc'));
        $this->assertTrue(Strings::endsWith('123abc', 'abc'));
        $this->assertFalse(Strings::endsWith('abc123', 'abc'));
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
        $this->assertSame(['a', 'b', 'c'], Strings::toCharArray('abc'));
        $this->assertSame(['あ', 'い', 'う'], Strings::toCharArray('あいう'));
    }

    public function test_indent()
    {
        $this->assertNull(Strings::indent(null));
        $this->assertSame("\t", Strings::indent(''));

        $this->assertSame("\t1st", Strings::indent("1st"));
        $this->assertSame("\t1st\n\t2nd\n\t3rd", Strings::indent("1st\n2nd\n3rd"));
        $this->assertSame("    1st\n    2nd\n    3rd", Strings::indent("1st\n2nd\n3rd", '    '));
        $this->assertSame(">>1st\n>>2nd\n>>3rd", Strings::indent("1st\n2nd\n3rd", '>>'));
        $this->assertSame(">>>>1st\n>>>>2nd\n>>>>3rd", Strings::indent("1st\n2nd\n3rd", '>>', 2));

        $this->assertSame("\n\t1st", Strings::indent("\n1st"));
        $this->assertSame("\n\t1st\n\t2nd\n\t3rd", Strings::indent("\n1st\n2nd\n3rd"));
        $this->assertSame("\n    1st\n    2nd\n    3rd", Strings::indent("\n1st\n2nd\n3rd", '    '));
        $this->assertSame("\n>>1st\n>>2nd\n>>3rd", Strings::indent("\n1st\n2nd\n3rd", '>>'));
        $this->assertSame("\n>>>>1st\n>>>>2nd\n>>>>3rd", Strings::indent("\n1st\n2nd\n3rd", '>>', 2));

        $this->assertSame("\t1st\n", Strings::indent("1st\n"));
        $this->assertSame("\t1st\n\t2nd\n\t3rd\n", Strings::indent("1st\n2nd\n3rd\n"));
        $this->assertSame("    1st\n    2nd\n    3rd\n", Strings::indent("1st\n2nd\n3rd\n", '    '));
        $this->assertSame(">>1st\n>>2nd\n>>3rd\n", Strings::indent("1st\n2nd\n3rd\n", '>>'));
        $this->assertSame(">>>>1st\n>>>>2nd\n>>>>3rd\n", Strings::indent("1st\n2nd\n3rd\n", '>>', 2));
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
        $this->assertTrue(Strings::contains('123abcABC', ['123', 'ABC']));
        $this->assertFalse(Strings::contains('123abcABC', ['123', 'DEF']));
        $this->assertTrue(Strings::contains('123abcABC', ['123', 'DEF'], 1));
        $this->assertFalse(Strings::contains('123abcABC', ['234', 'DEF'], 1));
        $this->assertTrue(Strings::contains('123abcABC', ['1', 'd', 'D'], 1));
        $this->assertFalse(Strings::contains('123abcABC', ['1', 'd', 'D'], 2));
        $this->assertFalse(Strings::contains('123abcABC', ['234', 'DEF']));
    }

    public function test_lcut()
    {
        $this->assertNull(Strings::lcut(null, 10));
        $this->assertSame('', Strings::lcut('', 10));
        $this->assertSame('12345', Strings::lcut('12345', -1));
        $this->assertSame('12345', Strings::lcut('12345', 0));
        $this->assertSame('2345', Strings::lcut('12345', 1));
        $this->assertSame('345', Strings::lcut('12345', 2));
        $this->assertSame('45', Strings::lcut('12345', 3));
        $this->assertSame('5', Strings::lcut('12345', 4));
        $this->assertSame('', Strings::lcut('12345', 5));
        $this->assertSame('', Strings::lcut('12345', 6));
    }

    public function test_rcut()
    {
        $this->assertNull(Strings::rcut(null, 10));
        $this->assertSame('', Strings::rcut('', 10));
        $this->assertSame('12345', Strings::rcut('12345', -1));
        $this->assertSame('12345', Strings::rcut('12345', 0));
        $this->assertSame('1234', Strings::rcut('12345', 1));
        $this->assertSame('123', Strings::rcut('12345', 2));
        $this->assertSame('12', Strings::rcut('12345', 3));
        $this->assertSame('1', Strings::rcut('12345', 4));
        $this->assertSame('', Strings::rcut('12345', 5));
        $this->assertSame('', Strings::rcut('12345', 6));
    }

    public function test_clip()
    {
        $this->assertNull(Strings::clip(null, 10));
        $this->assertSame('', Strings::clip('', 10));
        $this->assertSame('12345', Strings::clip('12345', 10));
        $this->assertSame('1234567890', Strings::clip('1234567890', 10));
        $this->assertSame('1234567...', Strings::clip('1234567890+', 10));
        $this->assertSame('123456789*', Strings::clip('1234567890+', 10, '*'));
    }

    /**
     * @expectedException Rebet\Common\Exception\LogicException
     * @expectedExceptionMessage Invalid clip length and ellipsis. The length must be longer than ellipsis.
     */
    public function test_clip_exception()
    {
        $this->assertSame('', Strings::clip('1234567890', 2));
    }

    public function test_match()
    {
        $this->assertFalse(Strings::match(null, '/[0-9]{3}/'));
        $this->assertFalse(Strings::match('', '/[0-9]{3}/'));
        $this->assertTrue(Strings::match('', '/.*/'));
        $this->assertFalse(Strings::match('12', '/[0-9]{3}/'));
        $this->assertTrue(Strings::match('123', '/[0-9]{3}/'));
        $this->assertFalse(Strings::match('12ab', '/[0-9]{3}/'));
        $this->assertTrue(Strings::match('12ab', ['/[0-9]{3}/', '/ab/']));
    }

    public function test_wildmatch()
    {
        $this->assertFalse(Strings::wildmatch(null, '*'));
        $this->assertTrue(Strings::wildmatch('', '*'));
        $this->assertFalse(Strings::wildmatch('', '/user'));
        $this->assertFalse(Strings::wildmatch('/user/profile', '/user'));
        $this->assertTrue(Strings::wildmatch('/user/', '/user?'));
        $this->assertFalse(Strings::wildmatch('/user/profile', 'user/profile'));
        $this->assertTrue(Strings::wildmatch('/user/profile', '*/profile'));
        $this->assertTrue(Strings::wildmatch('/user/profile', '/user/profile'));
        $this->assertTrue(Strings::wildmatch('/user/profile', '/user*'));
        $this->assertTrue(Strings::wildmatch('/user/profile', '/user/*'));
        $this->assertFalse(Strings::wildmatch('/user/profile-confirm', '/user/profile'));
        $this->assertTrue(Strings::wildmatch('/user/profile-confirm', ['/user/profile', '/user/profile-*']));
    }

    public function test_split()
    {
        $this->assertSame([null, null], Strings::split(null, ',', 2));
        $this->assertSame(['', null], Strings::split('', ',', 2));
        $this->assertSame(['1', null], Strings::split('1', ',', 2));
        $this->assertSame(['1', '2'], Strings::split('1,2', ',', 2));
        $this->assertSame(['1', '2,3'], Strings::split('1,2,3', ',', 2));
        $this->assertSame(['1', '2', '3'], Strings::split('1,2,3', ',', 3));

        $this->assertSame(['1', '-'], Strings::split('1', ',', 2, '-'));
    }
}
