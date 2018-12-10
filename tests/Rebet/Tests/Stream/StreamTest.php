<?php
namespace Rebet\Tests\Stream;

use Rebet\DateTime\DateTime;
use Rebet\Stream\Stream;
use Rebet\Tests\Mock\Gender;
use Rebet\Tests\RebetTestCase;

class StreamTest extends RebetTestCase
{
    private $null;
    private $int;
    private $float;
    private $string;
    private $text;
    private $html;
    private $json;
    private $enum;
    private $enums;
    private $datetime_o;
    private $datetime_s;
    private $array;
    private $map;
    private $rs;
    private $callable;
    private $destructive;

    public function setUp()
    {
        parent::setUp();
        DateTime::setTestNow('2001/02/03 04:05:06');
        $this->null       = Stream::valueOf(null);
        $this->int        = Stream::valueOf(123);
        $this->float      = Stream::valueOf(1234.5678);
        $this->string     = Stream::valueOf("Hello Rebet");
        $this->text       = Stream::valueOf("Hello\nRebet");
        $this->html       = Stream::valueOf("<h1>Hello Rebet</h1>");
        $this->json       = Stream::valueOf("[1 ,2, 3]");
        $this->enum       = Stream::valueOf(Gender::MALE());
        $this->enums      = Stream::valueOf(Gender::lists());
        $this->datetime_o = Stream::valueOf(DateTime::now());
        $this->datetime_s = Stream::valueOf('2001/02/03 04:05:06');
        $this->array      = Stream::valueOf([1, 2, 3]);
        $this->map        = Stream::valueOf([
            'foo'    => 'FOO',
            'parent' => [
                'child' => [
                    'bar' => 'BAR',
                ],
            ],
            'number'  => 123,
            'gender'  => Gender::MALE(),
            'boolean' => true,
        ]);
        $this->rs          = Stream::valueOf([
            new StreamTest_User(1, 'Foo', 'First', 'foo@hoge.com', Gender::MALE(), new DateTime('1976-08-12')),
            new StreamTest_User(2, 'Bar', 'Second', 'bar@moge.net', Gender::FEMALE(), new DateTime('1993-11-27')),
            new StreamTest_User(3, 'Baz', 'Third', 'baz@piyo.co.jp', Gender::MALE(), new DateTime('2000-02-05')),
            new StreamTest_User(4, 'Qux', 'Fourth', 'qux@hoge.com', Gender::FEMALE(), new DateTime('1968-07-18')),
            new StreamTest_User(5, 'Quxx', 'Fifth', 'quxx@moge.net', Gender::FEMALE(), new DateTime('1983-04-21')),
        ]);
        $this->callable    = Stream::valueOf(function (string $value) { return "Hello {$value}"; });
        $this->destructive = Stream::valueOf(new StreamTest_DestructiveMock());
        $this->safty       = Stream::valueOf("Hello Rebet", true);
    }

    public function test_valueOf()
    {
        $this->assertInstanceOf(Stream::class, Stream::valueOf(123));
    }

    public function test_promise()
    {
        $source = null;
        $value  = Stream::promise(function () use (&$source) { return $source; });
        $this->assertInstanceOf(Stream::class, $value);

        $source = 1;
        $this->assertSame(1, $value->origin());

        $source = 2;
        $this->assertSame(1, $value->origin());
    }

    public function test_addFilter()
    {
        $this->assertSame("Hello Rebet", $this->string->wrap()->origin());
        $this->assertSame("Hello Rebet", $this->safty->wrap()->origin());
        Stream::addFilter('wrap', function ($value) { return "({$value})"; });
        $this->assertSame("(Hello Rebet)", $this->string->wrap()->origin());
        $this->assertSame("Hello Rebet", $this->safty->wrap()->origin());

        $this->assertSame("HELLO REBET", $this->string->upper()->origin());
        $this->assertSame("HELLO REBET", $this->safty->upper()->origin());
        Stream::addFilter('upper', function ($value) { return "Upper: $value"; });
        $this->assertSame("Upper: Hello Rebet", $this->string->upper()->origin());
        $this->assertSame("HELLO REBET", $this->safty->upper()->origin());
    }

    public function test_origin()
    {
        $this->assertSame(123, $this->int->origin());
        $this->assertSame("Hello Rebet", $this->string->origin());
        $this->assertSame(Gender::MALE(), $this->enum->origin());
    }

    public function test___get()
    {
        $this->assertNull($this->null->nothing->origin());
        $this->assertNull($this->int->nothing->origin());

        $this->assertNull($this->enum->nothing->origin());
        $this->assertSame(1, $this->enum->value->origin());
        $this->assertSame('Male', $this->enum->label->origin());

        $this->assertNull($this->map->nothing->origin());
        $this->assertSame('FOO', $this->map->foo->origin());
        $this->assertSame([
            'child' => [
                'bar' => 'BAR',
            ],
        ], $this->map->parent->origin());
        $this->assertSame('BAR', $this->map->parent->child->bar->origin());
        $this->assertNull($this->map->parent->nothing->bar->origin());

        $this->assertTrue($this->map->boolean);
    }

    public function test___call()
    {
        $this->assertNull($this->null->nothing()->origin());
        $this->assertSame(123, $this->int->nothing()->origin());

        $this->assertSame(Gender::MALE(), $this->enum->nothing()->origin());
        $this->assertEquals(
            DateTime::valueOf('2002/02/03 04:05:06'),
            $this->datetime_o->addYear(1)->origin()
        );
        $this->assertEquals(
            '2001/02/03 04:05:06',
            $this->datetime_s->addYear(1)->origin()
        );
        $this->assertEquals(
            DateTime::valueOf('2002/02/03 04:05:06'),
            $this->datetime_s->convert(DateTime::class)->addYear(1)->origin()
        );

        $this->assertSame(0, $this->destructive->count->origin());
        $this->assertSame(1, $this->destructive->add_void()->count->origin());
        $this->assertSame(3, $this->destructive->add_void()->add_void()->count->origin());
        $this->assertSame(4, $this->destructive->add_bool()->count->origin());
        $this->assertSame(6, $this->destructive->add_bool()->add_bool()->count->origin());
        $this->assertSame(7, $this->destructive->add_nohint_void()->count->origin());
        $this->assertSame(9, $this->destructive->add_nohint_void()->add_nohint_void()->count->origin());
        $this->assertSame(10, $this->destructive->add_nohint_bool()->count->origin());
        $this->assertSame(12, $this->destructive->add_nohint_bool()->add_nohint_bool()->count->origin());
        $this->assertSame(13, $this->destructive->add_nohint_self()->count->origin());
        $this->assertSame(15, $this->destructive->add_nohint_self()->add_nohint_self()->count->origin());
        $this->assertSame(15, $this->destructive->add_nohint_self(0)->count->origin());
        $this->assertSame(15, $this->destructive->add_void(0)->count->origin());
        $this->assertSame(null, $this->destructive->add_nohint_void(0)->count->origin());
        $this->assertSame(true, $this->destructive->add_nohint_bool(0));
    }

    public function test___set()
    {
        $this->assertSame(0, $this->destructive->count->origin());
        $this->destructive->count = 12;
        $this->assertSame(12, $this->destructive->count->origin());

        $this->assertSame('FOO', $this->map->foo->origin());
        $this->map->foo = 'foo';
        $this->assertSame('foo', $this->map->foo->origin());
        $this->assertSame('BAR', $this->map->parent->child->bar->origin());
        $this->map->parent->child->bar = 'bar'; // can not cahnge the map origin
        $this->assertSame('BAR', $this->map->parent->child->bar->origin());
        $this->map->parent->qux->bar = 'qux'; // can not cahnge the map origin
        $this->assertSame(null, $this->map->parent->qux->bar->origin());
        $this->map->qux = 'qux';
        $this->assertSame('qux', $this->map->qux->origin());

        $this->assertSame(null, $this->map->nothing->origin());
        $this->map->nothing = 12;
        $this->assertSame(12, $this->map->nothing->origin());
    }

    public function test_offsetSet()
    {
        $this->assertSame(null, $this->null->origin());
        $this->null[] = 4;
        $this->assertSame(null, $this->null->origin());
        $this->null[0] = 'a';
        $this->assertSame(null, $this->null->origin());

        $this->assertSame(123, $this->int->origin());
        $this->int[] = 4;
        $this->assertSame(123, $this->int->origin());
        $this->int[0] = 'a';
        $this->assertSame(123, $this->int->origin());

        $this->assertSame([1, 2, 3], $this->array->origin());
        $this->array[] = 4;
        $this->assertSame([1, 2, 3, 4], $this->array->origin());
        $this->array[0] = 'a';
        $this->assertSame(['a', 2, 3, 4], $this->array->origin());

        $this->assertSame('FOO', $this->map['foo']->origin());
        $this->map['foo'] = 'foo';
        $this->assertSame('foo', $this->map['foo']->origin());
        $this->assertSame('BAR', $this->map['parent']['child']['bar']->origin());
        $this->assertSame('BAR', $this->map['parent.child.bar']->origin());
        $this->map['parent.child.bar'] = 'Bar';
        $this->assertSame('Bar', $this->map['parent.child.bar']->origin());
        $this->map['parent']['child']['bar'] = 'bar'; // can not change the map origin
        $this->assertSame('Bar', $this->map['parent']['child']['bar']->origin());

        $this->assertSame(0, $this->destructive['count']->origin());
        $this->destructive['count'] = 12;
        $this->assertSame(12, $this->destructive['count']->origin());
    }

    public function test_offsetExists()
    {
        $this->assertFalse(isset($this->null[0]));
        $this->assertFalse(isset($this->int[0]));

        $this->assertTrue(isset($this->array[0]));
        $this->assertFalse(isset($this->array[4]));

        $this->assertTrue(isset($this->map['foo']));
        $this->assertTrue(isset($this->map['parent.child.bar']));
        $this->assertTrue(isset($this->map['parent']['child']['bar']));

        $this->assertFalse(isset($this->map['nothing']));
        $this->assertFalse(isset($this->map['parent.nothing.bar']));
        $this->assertFalse(isset($this->map['parent']['nothing']['bar']));
    }

    public function test_offsetUnset()
    {
        $this->assertFalse(isset($this->null[0]));
        unset($this->null[0]);
        $this->assertFalse(isset($this->null[0]));

        $this->assertFalse(isset($this->int[0]));
        unset($this->int[0]);
        $this->assertFalse(isset($this->int[0]));

        $this->assertTrue(isset($this->array[0]));
        unset($this->array[0]);
        $this->assertFalse(isset($this->array[0]));

        $this->assertTrue(isset($this->map['foo']));
        unset($this->map['foo']);
        $this->assertFalse(isset($this->map['foo']));

        $this->assertTrue(isset($this->map['parent']['child']['bar']));
        unset($this->map['parent']['child']['bar']); // can not unset map origin
        $this->assertTrue(isset($this->map['parent']['child']['bar']));

        $this->assertTrue(isset($this->map['parent.child.bar']));
        unset($this->map['parent.child.bar']);
        $this->assertFalse(isset($this->map['parent.child.bar']));
    }

    public function test_offsetGet()
    {
        $this->assertSame(null, $this->null[0]->origin());
        $this->assertSame(1, $this->array[0]->origin());
        $this->assertSame('FOO', $this->map['foo']->origin());
        $this->assertSame('BAR', $this->map['parent.child.bar']->origin());
        $this->assertSame('BAR', $this->map['parent']['child']['bar']->origin());
        $this->assertSame(null, $this->map['parent']['nothing']['bar']->origin());
        $this->assertSame(null, $this->map['parent.nothing.bar']->origin());
        $this->assertSame(true, $this->map['boolean']);
    }

    public function test_count()
    {
        $this->assertSame(0, count($this->null));
        $this->assertSame(1, count($this->int));
        $this->assertSame(3, count($this->array));
        $this->assertSame(1, count($this->enum));
    }

    public function test_getIterator()
    {
        foreach ($this->null as $key => $value) {
            fail('Never execute');
        }

        $expects = [123];
        $count   = 0;
        foreach ($this->int as $key => $value) {
            $this->assertInstanceOf(Stream::class, $value);
            $this->assertSame($expects[$key], $value->origin());
            $count++;
        }
        $this->assertSame(count($expects), $count);

        $expects = ['Hello Rebet'];
        $count   = 0;
        foreach ($this->string as $key => $value) {
            $this->assertInstanceOf(Stream::class, $value);
            $this->assertSame($expects[$key], $value->origin());
            $count++;
        }
        $this->assertSame(count($expects), $count);

        $expects = ['Hello', 'Rebet'];
        $count   = 0;
        foreach ($this->string->explode(' ') as $key => $value) {
            $this->assertInstanceOf(Stream::class, $value);
            $this->assertSame($expects[$key], $value->origin());
            $count++;
        }
        $this->assertSame(count($expects), $count);

        $expects = [1, 2, 3];
        $count   = 0;
        foreach ($this->array as $key => $value) {
            $this->assertInstanceOf(Stream::class, $value);
            $this->assertSame($expects[$key], $value->origin());
            $count++;
        }
        $this->assertSame(count($expects), $count);

        $expects = ['value' => 1, 'label' => 'Male', 'name' => 'MALE'];
        $count   = 0;
        foreach ($this->enum as $key => $value) {
            $this->assertInstanceOf(Stream::class, $value);
            $this->assertSame($expects[$key], $value->origin());
            $count++;
        }
        $this->assertSame(count($expects), $count);
    }

    public function test___toString()
    {
        $this->assertSame('', "{$this->null}");
        $this->assertSame('123', "{$this->int}");
        $this->assertSame('Hello Rebet', "{$this->string}");
        $this->assertSame('[1,2,3]', "{$this->array}");
        $this->assertSame('男性', "{$this->enum}");
        $this->assertSame('2001-02-03 04:05:06', "{$this->datetime_o}");
        $this->assertSame('{"foo":"FOO","parent":{"child":{"bar":"BAR"}},"number":123,"gender":1,"boolean":true}', "{$this->map}");
    }

    public function test___jsonSerialize()
    {
        $this->assertSame(null, $this->null->jsonSerialize());
        $this->assertSame(123, $this->int->jsonSerialize());
        $this->assertSame('Hello Rebet', $this->string->jsonSerialize());
        $this->assertSame([1, 2, 3], $this->array->jsonSerialize());
        $this->assertSame(1, $this->enum->jsonSerialize());
        $this->assertSame('2001-02-03 04:05:06', $this->datetime_o->jsonSerialize());
        $this->assertSame(['foo' => 'FOO', 'parent' => ['child' => ['bar' => 'BAR']], 'number' => 123, 'gender' => 1, 'boolean' => true], $this->map->jsonSerialize());
    }

    public function test_filters()
    {
        // Reflector::convert
        $this->assertNull($this->null->_('convert', 'string')->origin());
        $this->assertSame('123', $this->int->_('convert', 'string')->origin());

        $this->assertSame('123', $this->int->convert('string')->origin());

        // Math::format => number
        $this->assertNull($this->null->_('number')->origin());
        $this->assertSame('123', $this->int->_('number')->origin());
        $this->assertSame('1,235', $this->float->_('number')->origin());
        $this->assertSame('1,234.6', $this->float->_('number', 1)->origin());
        $this->assertSame('1,234.57', $this->float->_('number', 2)->origin());
        $this->assertSame('1,234.568', $this->float->_('number', 3)->origin());
        $this->assertSame('1,234.5678', $this->float->_('number', 4)->origin());
        $this->assertSame('1,234.56780', $this->float->_('number', 5)->origin());
        $this->assertSame('1234.57', $this->float->_('number', 2, '.', '')->origin());
        $this->assertSame('1 234,57', $this->float->_('number', 2, ',', ' ')->origin());

        // Math::floor
        $this->assertNull($this->null->_('floor')->origin());
        $this->assertSame('1234', $this->float->_('floor')->origin());
        $this->assertSame('1234.56', $this->float->_('floor', 2)->origin());
        $this->assertSame('1200', $this->float->_('floor', -2)->origin());

        $this->assertSame('1234', $this->float->floor()->origin());
        $this->assertSame('1234.56', $this->float->floor(2)->origin());

        // Math::round
        $this->assertNull($this->null->_('round')->origin());
        $this->assertSame('1235', $this->float->_('round')->origin());
        $this->assertSame('1234.57', $this->float->_('round', 2)->origin());
        $this->assertSame('1200', $this->float->_('round', -2)->origin());

        $this->assertSame('1235', $this->float->round()->origin());
        $this->assertSame('1234.57', $this->float->round(2)->origin());

        // Math::ceil
        $this->assertNull($this->null->_('ceil')->origin());
        $this->assertSame('1235', $this->float->_('ceil')->origin());
        $this->assertSame('1234.57', $this->float->_('ceil', 2)->origin());
        $this->assertSame('1300', $this->float->_('ceil', -2)->origin());

        $this->assertSame('1235', $this->float->ceil()->origin());
        $this->assertSame('1234.57', $this->float->ceil(2)->origin());

        // Utils::isBlank
        $this->assertTrue($this->null->isBlank());
        $this->assertFalse($this->int->isBlank());

        // Utils::bvl
        $this->assertSame('(blank)', $this->null->bvl('(blank)')->origin());
        $this->assertSame(123, $this->int->bvl('(blank)')->origin());

        // Utils::isEmpty
        $this->assertTrue($this->null->isEmpty());
        $this->assertFalse($this->int->isEmpty());

        // Utils::evl
        $this->assertSame('(empty)', $this->null->evl('(empty)')->origin());
        $this->assertSame(123, $this->int->evl('(empty)')->origin());

        // Strings::cut
        $this->assertNull($this->null->cut(10)->origin());
        $this->assertSame('Hello R...', $this->string->cut(10)->origin());

        // Strings::indent
        $this->assertNull($this->null->indent(1, '> ')->origin());
        $this->assertSame("> Hello\n> Rebet", $this->text->indent(1, '> ')->origin());

        // default
        $this->assertSame('(null)', $this->null->_('default', '(null)')->origin());
        $this->assertSame(123, $this->int->_('default', '(null)')->origin());
        $this->assertSame('(null)', $this->int->nothing->_('default', '(null)')->origin());

        $this->assertSame('(null)', $this->null->default('(null)')->origin());

        // nvl
        $this->assertSame('(null)', $this->null->_('nvl', '(null)')->origin());
        $this->assertSame(123, $this->int->_('nvl', '(null)')->origin());
        $this->assertSame('(null)', $this->int->nothing->_('nvl', '(null)')->origin());

        $this->assertSame('(null)', $this->null->nvl('(null)')->origin());

        // escape:html
        $this->assertNull($this->null->_('escape')->origin());
        $this->assertSame('123', $this->int->_('escape')->origin());
        $this->assertSame('Hello Rebet', $this->string->_('escape')->origin());
        $this->assertSame('&lt;h1&gt;Hello Rebet&lt;/h1&gt;', $this->html->_('escape')->origin());
        $this->assertSame('男性', $this->enum->_('escape')->origin());

        $this->assertSame('&lt;h1&gt;Hello Rebet&lt;/h1&gt;', $this->html->escape()->origin());

        // escape:url
        $this->assertNull($this->null->_('escape', 'url')->origin());
        $this->assertSame('Hello+Rebet', $this->string->_('escape', 'url')->origin());
        $this->assertSame('%3Ch1%3EHello+Rebet%3C%2Fh1%3E', $this->html->_('escape', 'url')->origin());

        $this->assertSame('%3Ch1%3EHello+Rebet%3C%2Fh1%3E', $this->html->escape('url')->origin());

        // nl2br
        $this->assertNull($this->null->_('nl2br')->origin());
        $this->assertSame('Hello Rebet', $this->string->_('nl2br')->origin());
        $this->assertSame("Hello<br />\nRebet", $this->text->_('nl2br')->origin());

        $this->assertSame("Hello<br />\nRebet", $this->text->nl2br()->origin());

        // datetime
        $this->assertNull($this->null->_('datetime', 'Ymd')->origin());
        $this->assertSame('20010203', $this->datetime_o->_('datetime', 'Ymd')->origin());
        $this->assertSame('20010203', $this->datetime_s->_('datetime', 'Ymd')->origin());

        $this->assertSame('20010203', $this->datetime_s->datetime('Ymd')->origin());

        // text
        $this->assertNull($this->null->_('text', '%s')->origin());
        $this->assertNull($this->null->_('text', '[%s]')->origin());

        $this->assertSame('[123]', $this->int->_('text', '[%s]')->origin());
        $this->assertSame('[000123]', $this->int->_('text', '[%06d]')->origin());
        $this->assertSame('[123.00]', $this->int->_('text', '[%01.2f]')->origin());
        $this->assertSame('[1234.57]', $this->float->_('text', '[%01.2f]')->origin());

        $this->assertSame('[1234.57]', $this->float->text('[%01.2f]')->origin());

        // explode
        $this->assertNull($this->null->_('explode', ' ')->origin());
        $this->assertSame(['Hello', 'Rebet'], $this->string->_('explode', ' ')->origin());

        $this->assertSame(['Hello', 'Rebet'], $this->string->explode(' ')->origin());

        // implode
        $this->assertNull($this->null->_('implode', ',')->origin());
        $this->assertSame('1, 2, 3', $this->array->_('implode', ', ')->origin());

        $this->assertSame('1, 2, 3', $this->array->implode(', ')->origin());
        $this->assertSame('[1, 2, 3]', $this->array->implode(', ')->text('[%s]')->origin());

        // replace
        $this->assertNull($this->null->_('replace', '/Hello/', 'Good by')->origin());
        $this->assertSame('Good by Rebet', $this->string->_('replace', '/Hello/', 'Good by')->origin());

        $this->assertSame('Good by Rebet', $this->string->replace('/Hello/', 'Good by')->origin());

        // cut
        $this->assertNull($this->null->_('cut', 4)->origin());
        $this->assertSame('Hell...', $this->string->_('cut', 7)->origin());
        $this->assertSame('Hello R', $this->string->_('cut', 7, '')->origin());
        $this->assertSame('Hell《略》', $this->string->_('cut', 7, '《略》')->origin());

        $this->assertSame('Hell...', $this->string->cut(7)->origin());

        // lower
        $this->assertNull($this->null->_('lower')->origin());
        $this->assertSame('hello rebet', $this->string->_('lower')->origin());

        $this->assertSame('hello rebet', $this->string->lower()->origin());

        // upper
        $this->assertNull($this->null->_('upper')->origin());
        $this->assertSame('HELLO REBET', $this->string->_('upper')->origin());

        $this->assertSame('HELLO REBET', $this->string->upper()->origin());

        // dump
        $this->assertSame('', $this->null->_('dump')->origin());
        $this->assertSame(<<<EOS
Array
(
    [0] => 1
    [1] => 2
    [2] => 3
)

EOS
        , $this->array->_('dump')->origin());

        $this->assertSame(<<<EOS
Array
(
    [0] => 1
    [1] => 2
    [2] => 3
)

EOS
        , $this->array->dump()->origin());

        // run
        $this->assertNull($this->null->_('invoke', 'Test')->origin());
        $this->assertSame('Hello Test', $this->callable->_('invoke', 'Test')->origin());

        $this->assertSame('Hello Test', $this->callable->invoke('Test')->origin());
    }

    public function test_filters_php()
    {
        $this->assertTrue($this->null->isNull());
        $this->assertTrue($this->null->is_null());
        $this->assertFalse($this->int->isNull());
        $this->assertFalse($this->int->is_null());

        $this->assertSame('[1,2,3]', $this->array->jsonEncode()->origin());
        $this->assertSame([1, 2, 3], $this->json->jsonDecode()->origin());

        $this->assertSame([1, 2, 3, 0, 0], $this->array->arrayPad(5, 0)->origin());
    }
}

class StreamTest_DestructiveMock
{
    public $count = 0;

    public function add_void(int $i = 1) : void
    {
        $this->count += $i;
    }

    public function add_bool(int $i = 1) : bool
    {
        $this->count += $i;
        return true;
    }

    public function add_nohint_void(int $i = 1)
    {
        $this->count += $i;
    }

    public function add_nohint_bool(int $i = 1)
    {
        $this->count += $i;
        return true;
    }

    public function add_nohint_self(int $i = 1)
    {
        $this->count += $i;
        return $this;
    }
}

class StreamTest_User
{
    public $user_id;
    public $first_name;
    public $last_name;
    public $email;
    public $gender;
    public $birthday;

    public function __construct($user_id, $first_name, $last_name, $email, $gender, $birthday)
    {
        $this->user_id    = $user_id;
        $this->first_name = $first_name;
        $this->last_name  = $last_name;
        $this->email      = $email;
        $this->gender     = $gender;
        $this->birthday   = $birthday;
    }

    public function fullName() : string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function age($at = 'today') : int
    {
        return $this->birthday->age($at);
    }
}
