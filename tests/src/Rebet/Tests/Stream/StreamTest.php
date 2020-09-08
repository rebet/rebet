<?php
namespace Rebet\Tests\Stream;

use InvalidArgumentException;
use Rebet\Common\Exception\LogicException;
use Rebet\DateTime\DateTime;
use Rebet\Stream\Stream;
use Rebet\Tests\Mock\Enum\Gender;
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

    protected function setUp() : void
    {
        parent::setUp();
        DateTime::setTestNow('2001/02/03 04:05:06');
        $this->null       = Stream::of(null);
        $this->int        = Stream::of(123);
        $this->float      = Stream::of(1234.5678);
        $this->string     = Stream::of("Hello Rebet");
        $this->text       = Stream::of("Hello\nRebet");
        $this->html       = Stream::of("<h1>Hello Rebet</h1>");
        $this->json       = Stream::of("[1 ,2, 3]");
        $this->enum       = Stream::of(Gender::MALE());
        $this->enums      = Stream::of(Gender::lists());
        $this->datetime_o = Stream::of(DateTime::now());
        $this->datetime_s = Stream::of('2001/02/03 04:05:06');
        $this->array      = Stream::of([1, 2, 3]);
        $this->map        = Stream::of([
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
        $this->rs          = Stream::of([
            new StreamTest_User(1, 'Foo', 'First', 'foo@hoge.com', Gender::MALE(), new DateTime('1976-08-12')),
            new StreamTest_User(2, 'Bar', 'Second', 'bar@moge.net', Gender::FEMALE(), new DateTime('1993-11-27')),
            new StreamTest_User(3, 'Baz', 'Third', 'baz@piyo.co.jp', Gender::MALE(), new DateTime('2000-02-05')),
            new StreamTest_User(4, 'Qux', 'Fourth', 'qux@hoge.com', Gender::FEMALE(), new DateTime('1968-07-18')),
            new StreamTest_User(5, 'Quxx', 'Fifth', 'quxx@moge.net', Gender::FEMALE(), new DateTime('1983-04-21')),
        ]);
        $this->callable    = Stream::of(function (string $value) { return "Hello {$value}"; });
        $this->destructive = Stream::of(new StreamTest_DestructiveMock());
        $this->safty       = Stream::of("Hello Rebet", true);
    }

    public function test_of()
    {
        $this->assertInstanceOf(Stream::class, Stream::of(123));
    }

    public function test_promise()
    {
        $source = null;
        $value  = Stream::promise(function () use (&$source) { return $source; });
        $this->assertInstanceOf(Stream::class, $value);

        $source = 1;
        $this->assertSame(1, $value->return());

        $source = 2;
        $this->assertSame(1, $value->return());
    }

    public function test_addFilter()
    {
        $this->assertSame("Hello Rebet", $this->string->wrap()->return());
        $this->assertSame("Hello Rebet", $this->safty->wrap()->return());
        Stream::addFilter('wrap', function ($value) { return "({$value})"; });
        $this->assertSame("(Hello Rebet)", $this->string->wrap()->return());
        $this->assertSame("Hello Rebet", $this->safty->wrap()->return());

        $this->assertSame("HELLO REBET", $this->string->upper()->return());
        $this->assertSame("HELLO REBET", $this->safty->upper()->return());
        Stream::addFilter('upper', function ($value) { return "Upper: $value"; });
        $this->assertSame("Upper: Hello Rebet", $this->string->upper()->return());
        $this->assertSame("HELLO REBET", $this->safty->upper()->return());
    }

    public function test_return()
    {
        $this->assertSame(123, $this->int->return());
        $this->assertSame("Hello Rebet", $this->string->return());
        $this->assertSame(Gender::MALE(), $this->enum->return());
    }

    public function test___get()
    {
        $this->assertNull($this->null->nothing->return());
        $this->assertNull($this->int->nothing->return());

        $this->assertNull($this->enum->nothing->return());
        $this->assertSame(1, $this->enum->value->return());
        $this->assertSame('Male', $this->enum->label->return());

        $this->assertNull($this->map->nothing->return());
        $this->assertSame('FOO', $this->map->foo->return());
        $this->assertSame([
            'child' => [
                'bar' => 'BAR',
            ],
        ], $this->map->parent->return());
        $this->assertSame('BAR', $this->map->parent->child->bar->return());
        $this->assertNull($this->map->parent->nothing->bar->return());

        $this->assertTrue($this->map->boolean);
    }

    public function test___call()
    {
        $this->assertNull($this->null->nothing()->return());
        $this->assertSame(123, $this->int->nothing()->return());

        $this->assertSame(Gender::MALE(), $this->enum->nothing()->return());
        $this->assertEquals(
            DateTime::valueOf('2002/02/03 04:05:06'),
            $this->datetime_o->addYear(1)->return()
        );
        $this->assertEquals(
            '2001/02/03 04:05:06',
            $this->datetime_s->addYear(1)->return()
        );
        $this->assertEquals(
            DateTime::valueOf('2002/02/03 04:05:06'),
            $this->datetime_s->convert(DateTime::class)->addYear(1)->return()
        );

        $this->assertSame(0, $this->destructive->count->return());
        $this->assertSame(1, $this->destructive->add_void()->count->return());
        $this->assertSame(3, $this->destructive->add_void()->add_void()->count->return());
        $this->assertSame(4, $this->destructive->add_bool()->count->return());
        $this->assertSame(6, $this->destructive->add_bool()->add_bool()->count->return());
        $this->assertSame(7, $this->destructive->add_nohint_void()->count->return());
        $this->assertSame(9, $this->destructive->add_nohint_void()->add_nohint_void()->count->return());
        $this->assertSame(10, $this->destructive->add_nohint_bool()->count->return());
        $this->assertSame(12, $this->destructive->add_nohint_bool()->add_nohint_bool()->count->return());
        $this->assertSame(13, $this->destructive->add_nohint_self()->count->return());
        $this->assertSame(15, $this->destructive->add_nohint_self()->add_nohint_self()->count->return());
        $this->assertSame(15, $this->destructive->add_nohint_self(0)->count->return());
        $this->assertSame(15, $this->destructive->add_void(0)->count->return());
        $this->assertSame(null, $this->destructive->add_nohint_void(0)->count->return());
        $this->assertSame(true, $this->destructive->add_nohint_bool(0));
    }

    public function test___set()
    {
        $this->assertSame(0, $this->destructive->count->return());
        $this->destructive->count = 12;
        $this->assertSame(12, $this->destructive->count->return());

        $this->assertSame('FOO', $this->map->foo->return());
        $this->map->foo = 'foo';
        $this->assertSame('foo', $this->map->foo->return());
        $this->assertSame('BAR', $this->map->parent->child->bar->return());
        $this->map->parent->child->bar = 'bar'; // can not cahnge the map origin
        $this->assertSame('BAR', $this->map->parent->child->bar->return());
        $this->map->parent->qux->bar = 'qux'; // can not cahnge the map origin
        $this->assertSame(null, $this->map->parent->qux->bar->return());
        $this->map->qux = 'qux';
        $this->assertSame('qux', $this->map->qux->return());

        $this->assertSame(null, $this->map->nothing->return());
        $this->map->nothing = 12;
        $this->assertSame(12, $this->map->nothing->return());
    }

    public function test_offsetSet()
    {
        $this->assertSame(null, $this->null->return());
        $this->null[] = 4;
        $this->assertSame(null, $this->null->return());
        $this->null[0] = 'a';
        $this->assertSame(null, $this->null->return());

        $this->assertSame(123, $this->int->return());
        $this->int[] = 4;
        $this->assertSame(123, $this->int->return());
        $this->int[0] = 'a';
        $this->assertSame(123, $this->int->return());

        $this->assertSame([1, 2, 3], $this->array->return());
        $this->array[] = 4;
        $this->assertSame([1, 2, 3, 4], $this->array->return());
        $this->array[0] = 'a';
        $this->assertSame(['a', 2, 3, 4], $this->array->return());

        $this->assertSame('FOO', $this->map['foo']->return());
        $this->map['foo'] = 'foo';
        $this->assertSame('foo', $this->map['foo']->return());
        $this->assertSame('BAR', $this->map['parent']['child']['bar']->return());
        $this->assertSame('BAR', $this->map['parent.child.bar']->return());
        $this->map['parent.child.bar'] = 'Bar';
        $this->assertSame('Bar', $this->map['parent.child.bar']->return());
        $this->map['parent']['child']['bar'] = 'bar'; // can not change the map origin
        $this->assertSame('Bar', $this->map['parent']['child']['bar']->return());

        $this->assertSame(0, $this->destructive['count']->return());
        $this->destructive['count'] = 12;
        $this->assertSame(12, $this->destructive['count']->return());
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
        $this->assertSame(null, $this->null[0]->return());
        $this->assertSame(1, $this->array[0]->return());
        $this->assertSame('FOO', $this->map['foo']->return());
        $this->assertSame('BAR', $this->map['parent.child.bar']->return());
        $this->assertSame('BAR', $this->map['parent']['child']['bar']->return());
        $this->assertSame(null, $this->map['parent']['nothing']['bar']->return());
        $this->assertSame(null, $this->map['parent.nothing.bar']->return());
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
            $this->assertSame($expects[$key], $value->return());
            $count++;
        }
        $this->assertSame(count($expects), $count);

        $expects = ['Hello Rebet'];
        $count   = 0;
        foreach ($this->string as $key => $value) {
            $this->assertInstanceOf(Stream::class, $value);
            $this->assertSame($expects[$key], $value->return());
            $count++;
        }
        $this->assertSame(count($expects), $count);

        $expects = ['Hello', 'Rebet'];
        $count   = 0;
        foreach ($this->string->explode(' ') as $key => $value) {
            $this->assertInstanceOf(Stream::class, $value);
            $this->assertSame($expects[$key], $value->return());
            $count++;
        }
        $this->assertSame(count($expects), $count);

        $expects = [1, 2, 3];
        $count   = 0;
        foreach ($this->array as $key => $value) {
            $this->assertInstanceOf(Stream::class, $value);
            $this->assertSame($expects[$key], $value->return());
            $count++;
        }
        $this->assertSame(count($expects), $count);

        $expects = ['value' => 1, 'label' => 'Male', 'name' => 'MALE'];
        $count   = 0;
        foreach ($this->enum as $key => $value) {
            $this->assertInstanceOf(Stream::class, $value);
            $this->assertSame($expects[$key], $value->return());
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
        $this->assertNull($this->null->_('convert', 'string')->return());
        $this->assertSame('123', $this->int->_('convert', 'string')->return());

        $this->assertSame('123', $this->int->convert('string')->return());

        // format => number
        $this->assertNull($this->null->_('number')->return());
        $this->assertSame('123', $this->int->_('number')->return());
        $this->assertSame('1,235', $this->float->_('number')->return());
        $this->assertSame('1,235', $this->float->_('number', 0)->return());
        $this->assertSame('1,234.6', $this->float->_('number', 1)->return());
        $this->assertSame('1,234.57', $this->float->_('number', 2)->return());
        $this->assertSame('1,234.568', $this->float->_('number', 3)->return());
        $this->assertSame('1,234.5678', $this->float->_('number', 4)->return());
        $this->assertSame('1,234.56780', $this->float->_('number', 5)->return());
        $this->assertSame('1,234.5678', $this->float->_('number', 5, true)->return());
        $this->assertSame('1234.57', $this->float->_('number', 2, false, '.', '')->return());
        $this->assertSame('1 234,57', $this->float->_('number', 2, false, ',', ' ')->return());

        // floor
        $this->assertNull($this->null->_('floor')->value()->return());
        $this->assertSame('1234', $this->float->_('floor')->value()->return());
        $this->assertSame('1234.56', $this->float->_('floor', 2)->value()->return());
        $this->assertSame('1200', $this->float->_('floor', -2)->value()->return());

        $this->assertSame('1234', $this->float->floor()->value()->return());
        $this->assertSame('1234.56', $this->float->floor(2)->value()->return());

        // round
        $this->assertNull($this->null->_('round')->value()->return());
        $this->assertSame('1235', $this->float->_('round')->value()->return());
        $this->assertSame('1234.57', $this->float->_('round', 2)->value()->return());
        $this->assertSame('1200', $this->float->_('round', -2)->value()->return());

        $this->assertSame('1235', $this->float->round()->value()->return());
        $this->assertSame('1234.57', $this->float->round(2)->value()->return());

        // ceil
        $this->assertNull($this->null->_('ceil')->value()->return());
        $this->assertSame('1235', $this->float->_('ceil')->value()->return());
        $this->assertSame('1234.57', $this->float->_('ceil', 2)->value()->return());
        $this->assertSame('1300', $this->float->_('ceil', -2)->value()->return());

        $this->assertSame('1235', $this->float->ceil()->value()->return());
        $this->assertSame('1234.57', $this->float->ceil(2)->value()->return());

        // Utils::isBlank
        $this->assertTrue($this->null->isBlank());
        $this->assertFalse($this->int->isBlank());

        // Utils::bvl
        $this->assertSame('(blank)', $this->null->bvl('(blank)')->return());
        $this->assertSame(123, $this->int->bvl('(blank)')->return());

        // Utils::isEmpty
        $this->assertTrue($this->null->isEmpty());
        $this->assertFalse($this->int->isEmpty());

        // Utils::evl
        $this->assertSame('(empty)', $this->null->evl('(empty)')->return());
        $this->assertSame(123, $this->int->evl('(empty)')->return());

        // Strings::clip
        $this->assertNull($this->null->clip(10)->return());
        $this->assertSame('Hello R...', $this->string->clip(10)->return());

        // Strings::indent
        $this->assertNull($this->null->indent('> ')->return());
        $this->assertSame("> Hello\n> Rebet", $this->text->indent('> ')->return());

        // Arrays::implode
        $this->assertSame('1, 2, 3', $this->array->implode()->return());
        $this->assertSame('1／2／3', $this->array->implode('／')->return());
        $this->assertSame('[1, 2, 3]', $this->array->implode()->text('[%s]')->return());


        // default
        $this->assertSame('(null)', $this->null->_('default', '(null)')->return());
        $this->assertSame(123, $this->int->_('default', '(null)')->return());
        $this->assertSame('(null)', $this->int->nothing->_('default', '(null)')->return());

        $this->assertSame('(null)', $this->null->default('(null)')->return());

        // nvl
        $this->assertSame('(null)', $this->null->_('nvl', '(null)')->return());
        $this->assertSame(123, $this->int->_('nvl', '(null)')->return());
        $this->assertSame('(null)', $this->int->nothing->_('nvl', '(null)')->return());

        $this->assertSame('(null)', $this->null->nvl('(null)')->return());

        // escape:html
        $this->assertNull($this->null->_('escape')->return());
        $this->assertSame('123', $this->int->_('escape')->return());
        $this->assertSame('Hello Rebet', $this->string->_('escape')->return());
        $this->assertSame('&lt;h1&gt;Hello Rebet&lt;/h1&gt;', $this->html->_('escape')->return());
        $this->assertSame('男性', $this->enum->_('escape')->return());

        $this->assertSame('&lt;h1&gt;Hello Rebet&lt;/h1&gt;', $this->html->escape()->return());

        // escape:url
        $this->assertNull($this->null->_('escape', 'url')->return());
        $this->assertSame('Hello+Rebet', $this->string->_('escape', 'url')->return());
        $this->assertSame('%3Ch1%3EHello+Rebet%3C%2Fh1%3E', $this->html->_('escape', 'url')->return());

        $this->assertSame('%3Ch1%3EHello+Rebet%3C%2Fh1%3E', $this->html->escape('url')->return());

        // nl2br
        $this->assertNull($this->null->_('nl2br')->return());
        $this->assertSame('Hello Rebet', $this->string->_('nl2br')->return());
        $this->assertSame("Hello<br />\nRebet", $this->text->_('nl2br')->return());

        $this->assertSame("Hello<br />\nRebet", $this->text->nl2br()->return());

        // datetime
        $this->assertNull($this->null->_('datetime', 'Ymd')->return());
        $this->assertSame('20010203', $this->datetime_o->_('datetime', 'Ymd')->return());
        $this->assertSame('20010203', $this->datetime_s->_('datetime', 'Ymd')->return());

        $this->assertSame('20010203', $this->datetime_s->datetime('Ymd')->return());

        // text
        $this->assertNull($this->null->_('text', '%s')->return());
        $this->assertNull($this->null->_('text', '[%s]')->return());

        $this->assertSame('[123]', $this->int->_('text', '[%s]')->return());
        $this->assertSame('[000123]', $this->int->_('text', '[%06d]')->return());
        $this->assertSame('[123.00]', $this->int->_('text', '[%01.2f]')->return());
        $this->assertSame('[1234.57]', $this->float->_('text', '[%01.2f]')->return());

        $this->assertSame('[1234.57]', $this->float->text('[%01.2f]')->return());

        // explode
        $this->assertNull($this->null->_('explode', ' ')->return());
        $this->assertSame(['Hello', 'Rebet'], $this->string->_('explode', ' ')->return());

        $this->assertSame(['Hello', 'Rebet'], $this->string->explode(' ')->return());

        // replace
        $this->assertNull($this->null->_('replace', '/Hello/', 'Good by')->return());
        $this->assertSame('Good by Rebet', $this->string->_('replace', '/Hello/', 'Good by')->return());

        $this->assertSame('Good by Rebet', $this->string->replace('/Hello/', 'Good by')->return());

        // lcut
        $this->assertNull($this->null->_('lcut', 4)->return());
        $this->assertSame('ebet', $this->string->_('lcut', 7)->return());

        $this->assertSame('ebet', $this->string->lcut(7)->return());

        // rcut
        $this->assertNull($this->null->_('rcut', 4)->return());
        $this->assertSame('Hell', $this->string->_('rcut', 7)->return());

        $this->assertSame('Hell', $this->string->rcut(7)->return());

        // clip
        $this->assertNull($this->null->_('clip', 4)->return());
        $this->assertSame('Hell...', $this->string->_('clip', 7)->return());
        $this->assertSame('Hello R', $this->string->_('clip', 7, '')->return());
        $this->assertSame('Hell《略》', $this->string->_('clip', 7, '《略》')->return());

        $this->assertSame('Hell...', $this->string->clip(7)->return());

        // lower
        $this->assertNull($this->null->_('lower')->return());
        $this->assertSame('hello rebet', $this->string->_('lower')->return());

        $this->assertSame('hello rebet', $this->string->lower()->return());

        // upper
        $this->assertNull($this->null->_('upper')->return());
        $this->assertSame('HELLO REBET', $this->string->_('upper')->return());

        $this->assertSame('HELLO REBET', $this->string->upper()->return());

        // dump
        $this->assertSame('', $this->null->_('dump')->return());
        $this->assertSame(<<<EOS
Array
(
    [0] => 1
    [1] => 2
    [2] => 3
)

EOS
        , $this->array->_('dump')->return());

        $this->assertSame(<<<EOS
Array
(
    [0] => 1
    [1] => 2
    [2] => 3
)

EOS
        , $this->array->dump()->return());

        // invoke
        $this->assertNull($this->null->_('invoke', 'Test')->return());
        $this->assertSame('Hello Test', $this->callable->_('invoke', 'Test')->return());

        $this->assertSame('Hello Test', $this->callable->invoke('Test')->return());

        // nnvl
        $this->assertSame(null, $this->null->_('nnvl', 'A')->return());
        $this->assertSame('B', $this->null->_('nnvl', 'A', 'B')->return());
        $this->assertSame('A', $this->int->_('nnvl', 'A')->return());
        $this->assertSame('A', Stream::of('')->_('nnvl', 'A')->return());
        $this->assertSame('A', Stream::of([])->_('nnvl', 'A')->return());
        $this->assertSame('A', Stream::of(0)->_('nnvl', 'A')->return());

        $this->assertSame('A', $this->int->nnvl('A')->return());

        // nbvl
        $this->assertSame(null, $this->null->_('nbvl', 'A')->return());
        $this->assertSame('B', $this->null->_('nbvl', 'A', 'B')->return());
        $this->assertSame('A', $this->int->_('nbvl', 'A')->return());
        $this->assertSame(null, Stream::of('')->_('nbvl', 'A')->return());
        $this->assertSame(null, Stream::of([])->_('nbvl', 'A')->return());
        $this->assertSame('A', Stream::of(0)->_('nbvl', 'A')->return());

        $this->assertSame('A', $this->int->nbvl('A')->return());

        // nevl
        $this->assertSame(null, $this->null->_('nevl', 'A')->return());
        $this->assertSame('B', $this->null->_('nevl', 'A', 'B')->return());
        $this->assertSame('A', $this->int->_('nevl', 'A')->return());
        $this->assertSame(null, Stream::of('')->_('nevl', 'A')->return());
        $this->assertSame(null, Stream::of([])->_('nevl', 'A')->return());
        $this->assertSame(null, Stream::of(0)->_('nevl', 'A')->return());

        $this->assertSame('A', $this->int->nevl('A')->return());

        // when
        $this->assertSame('A', $this->null->_('when', null, 'A')->return());
        $this->assertSame(null, $this->null->_('when', 123, 'A')->return());
        $this->assertSame('A', $this->int->_('when', 123, 'A')->return());
        $this->assertSame('A', $this->int->_('when', Stream::of(123), 'A')->return());
        $this->assertSame(123, $this->int->_('when', 234, 'A')->return());
        $this->assertSame('B', $this->int->_('when', 234, 'A', 'B')->return());
        $this->assertSame('A', $this->int->_('when', function ($v) { return $v < 999; }, 'A', 'B')->return());
        $this->assertSame('A', $this->int->_('when', true, 'A', 'B')->return());
        $this->assertSame('B', $this->int->_('when', false, 'A', 'B')->return());

        $this->assertSame('A', $this->int->when(123, 'A')->return());

        // case
        $case = [123 => 'A', 'Hello Rebet' => 'B'];
        $this->assertNull($this->null->_('case', $case)->return());
        $this->assertSame('default', $this->null->_('case', $case, 'default')->return());
        $this->assertSame('A', $this->int->_('case', $case)->return());
        $this->assertSame('B', $this->string->_('case', $case)->return());
        $this->assertSame("Hello\nRebet", $this->text->_('case', $case)->return());
        $this->assertSame('C', $this->text->_('case', $case, 'C')->return());

        $this->assertSame('A', $this->int->case($case)->return());

        // length
        $this->assertSame(null, $this->null->_('length')->return());
        $this->assertSame(3, $this->int->_('length')->return());
        $this->assertSame(9, $this->float->_('length')->return());
        $this->assertSame(11, $this->string->_('length')->return());
        $this->assertSame(1, $this->enum->_('length')->return());
        $this->assertSame(2, $this->enums->_('length')->return());
        $this->assertSame(3, $this->array->_('length')->return());

        $this->assertSame(11, $this->string->length()->return());
        $this->assertSame(3, $this->array->length()->return());

        // values
        $this->assertSame(null, $this->null->_('values')->return());
        $this->assertSame([123], $this->int->_('values')->return());
        $this->assertSame([1, 2, 3], $this->array->_('values')->return());
        $this->assertSame([
            'FOO',
            [
                'child' => [
                    'bar' => 'BAR',
                ],
            ],
            123,
            Gender::MALE(),
            true,
        ], $this->map->_('values')->return());

        $this->assertSame(Gender::lists(), $this->enums->values()->return());

        // keys
        $this->assertSame(null, $this->null->_('keys')->return());
        $this->assertSame([0], $this->int->_('keys')->return());
        $this->assertSame([0, 1, 2], $this->array->_('keys')->return());
        $this->assertSame([
            'foo',
            'parent',
            'number',
            'gender',
            'boolean',
        ], $this->map->_('keys')->return());

        $this->assertSame([0, 1], $this->enums->keys()->return());
    }

    public function test_filters_php()
    {
        $this->assertTrue($this->null->isNull());
        $this->assertTrue($this->null->is_null());
        $this->assertFalse($this->int->isNull());
        $this->assertFalse($this->int->is_null());

        $this->assertSame('[1,2,3]', $this->array->jsonEncode()->return());
        $this->assertSame([1, 2, 3], $this->json->jsonDecode()->return());

        $this->assertSame([1, 2, 3, 0, 0], $this->array->arrayPad(5, 0)->return());
    }

    public function test_filters_escapeError()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid escape type [xml] given. The type must be html or url");

        $this->string->escape('xml');
    }

    public function test_filters_convertError()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Apply datetime filter failed. The origin type 'Closure' can not convert to Rebet\DateTime\DateTime.");

        $this->callable->datetime('Y/m/d');
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
